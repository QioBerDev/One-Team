<?php
/**
 * 余额 / 卡密 —— 公共库
 *
 * 设计要点：
 *  - 所有余额变动只走 wallet_change()，事务 + SELECT ... FOR UPDATE + 幂等 ref，
 *    支付回调重复、卡密并发兑换都不会重复入账，也扣不成负数。
 *  - 充值余额订单：remark = "money|金额"，epay_settle() 识别后自动入账。
 *  - 余额支付订单：wallet_pay_order() 本地扣款 + 复用 epay_settle() 的交付/发放逻辑。
 *  - 卡密：抢占式 UPDATE status=0→1 防重复兑换，发放失败自动回滚卡状态。
 *
 * 依赖：Common/Schema.php 的 pay_schema_init() 先跑（建表/补列）。
 */

if (!defined('IN_CRONLITE')) {
    exit('Access denied');
}

/** 卡密类型 */
function card_types()
{
    return array(
        'balance' => '余额卡',
        'vip'     => 'VIP 天数卡',
        'svip'    => 'SVIP 天数卡',
    );
}

function card_type_name($type)
{
    $t = card_types();
    return isset($t[$type]) ? $t[$type] : '未知';
}

function wallet_type_name($type)
{
    $t = array(
        'recharge' => '余额充值',
        'card'     => '卡密兑换',
        'consume'  => '余额消费',
        'refund'   => '退款返还',
        'admin'    => '后台调整',
    );
    return isset($t[$type]) ? $t[$type] : $type;
}

/** 读余额 */
function wallet_balance($DB, $uid)
{
    $row = $DB->getRow("SELECT `money` FROM `nteam_user` WHERE `id`=? LIMIT 1", array((int)$uid));
    return $row ? round((float)$row['money'], 2) : 0.0;
}

/**
 * 余额变动（唯一入口）
 *
 * @param float  $amount 正入负出，两位小数
 * @param string $type   recharge/card/consume/refund/admin
 * @param string $memo   说明
 * @param string $ref    幂等键，同 ref 只会成功一次（如 order:20261106xxx / card:12）
 * @param string $admin  后台操作人，前台留空
 * @return bool
 */
function wallet_change($DB, $uid, $amount, $type, $memo, $ref, $admin = '')
{
    $uid    = (int)$uid;
    $amount = round((float)$amount, 2);
    $ref    = substr((string)$ref, 0, 64);
    if ($uid <= 0 || abs($amount) < 0.001 || $ref === '') {
        return false;
    }

    $pdo = $DB->db; // PdoHelper::__get('db') 暴露底层 PDO
    if (!($pdo instanceof PDO)) {
        return false;
    }

    try {
        $pdo->beginTransaction();

        /* 幂等：同一 ref 已处理过直接返回 */
        $chk = $pdo->prepare("SELECT `id` FROM `nteam_money_log` WHERE `ref`=? LIMIT 1");
        $chk->execute(array($ref));
        if ($chk->fetch()) {
            $pdo->rollBack();
            return false;
        }

        /* 锁住用户行，串行化同一账号的余额变动 */
        $sel = $pdo->prepare("SELECT `money` FROM `nteam_user` WHERE `id`=? FOR UPDATE");
        $sel->execute(array($uid));
        $row = $sel->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            $pdo->rollBack();
            return false;
        }
        $balance = round((float)$row['money'] + $amount, 2);
        if ($balance < 0) {
            $pdo->rollBack();
            return false;
        }

        $upd = $pdo->prepare("UPDATE `nteam_user` SET `money`=? WHERE `id`=?");
        $upd->execute(array($balance, $uid));

        $ins = $pdo->prepare(
            "INSERT INTO `nteam_money_log`
                (`user_id`,`amount`,`balance`,`type`,`memo`,`ref`,`admin`,`create_time`)
             VALUES (?,?,?,?,?,?,?,?)"
        );
        $ins->execute(array(
            $uid, $amount, $balance, (string)$type,
            mb_substr((string)$memo, 0, 240, 'UTF-8'), $ref, (string)$admin, time()
        ));

        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return false;
    }
}

/** 余额流水 */
function wallet_logs($DB, $uid, $limit = 50)
{
    $limit = max(1, min(200, (int)$limit));
    $rows = $DB->getAll(
        "SELECT * FROM `nteam_money_log` WHERE `user_id`=? ORDER BY `id` DESC LIMIT {$limit}",
        array((int)$uid)
    );
    return is_array($rows) ? $rows : array();
}

/** 充值订单 remark 编解码：money|金额 */
function wallet_order_remark($amount)
{
    return 'money|' . number_format((float)$amount, 2, '.', '');
}

function wallet_parse_remark($remark)
{
    $remark = (string)$remark;
    if (strpos($remark, 'money|') !== 0) {
        return 0.0;
    }
    $amount = round((float)substr($remark, 6), 2);
    return $amount > 0 ? $amount : 0.0;
}

/**
 * 余额支付：扣款 + 结算（复用 epay_settle 的交付 / 会员发放）
 * @return array(bool ok, string msg)
 */
function wallet_pay_order($DB, $uid, array $order)
{
    $uid = (int)$uid;
    if ($uid <= 0 || (int)$order['user_id'] !== $uid) {
        return array(false, '订单不属于当前账号');
    }
    if ((int)$order['status'] !== 0) {
        return array(false, '这张订单已处理过了');
    }
    $money = round((float)$order['money'], 2);
    if ($money <= 0) {
        return array(false, '订单金额不正确');
    }
    $out = preg_replace('/[^A-Za-z0-9]/', '', (string)$order['out_trade_no']);
    $ref = 'order:' . $out;

    if (!wallet_change($DB, $uid, -$money, 'consume', '购买：' . $order['project_name'], $ref)) {
        return array(false, '余额不足，或这张订单已经付过了');
    }

    /* 复用支付结算：自动交付 / 发放会员 / 订单状态 */
    if (!function_exists('epay_settle')) {
        require_once __DIR__ . '/Pay.php';
    }
    $ok = epay_settle($DB, $order, 'BALANCE-' . $out, 'balance');
    if (!$ok) {
        /* 订单已被别处结算，把钱退回去，两边都不亏 */
        wallet_change($DB, $uid, $money, 'refund', '订单结算失败返还：' . $out, 'refund:' . $ref);
        return array(false, '订单状态已变化，余额已退回');
    }
    return array(true, '支付成功，余额已扣除');
}

/**
 * 生成卡密
 * @return array 成功返回卡密字符串数组，失败返回空数组
 */
function card_generate($DB, $type, $value, $days, $count, $prefix = '', $remark = '')
{
    $types = card_types();
    if (!isset($types[$type]) || $count < 1 || $count > 500) {
        return array();
    }
    $value = round((float)$value, 2);
    $days  = max(0, (int)$days);
    if ($type === 'balance' && $value <= 0) {
        return array();
    }
    if ($type !== 'balance' && $days <= 0) {
        return array();
    }

    $batch = 'B' . date('YmdHis') . strtoupper(substr(bin2hex(random_bytes(2)), 0, 3));
    $prefix = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string)$prefix));
    if ($prefix !== '') {
        $prefix = substr($prefix, 0, 4) . '-';
    }
    /* 去掉 0/O/1/I，避免用户抄错 */
    $alphabet = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
    $codes = array();
    $now = time();

    for ($i = 0; $i < $count; $i++) {
        $raw = '';
        for ($j = 0; $j < 16; $j++) {
            $raw .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            if ($j % 4 === 3 && $j !== 15) {
                $raw .= '-';
            }
        }
        $code = $prefix . $raw;
        $stmt = $DB->query(
            "INSERT INTO `nteam_card`
                (`batch`,`code`,`type`,`value`,`days`,`status`,`create_time`,`remark`)
             VALUES (?,?,?,?,?,0,?,?)",
            array($batch, $code, $type, $value, $days, $now, mb_substr((string)$remark, 0, 240, 'UTF-8'))
        );
        if ($stmt && $stmt->rowCount() === 1) {
            $codes[] = $code;
        }
    }
    return $codes;
}

/**
 * 兑换卡密
 * @return array(bool ok, string msg, array data)
 */
function card_redeem($DB, $uid, $code)
{
    $uid  = (int)$uid;
    $code = strtoupper(preg_replace('/[^A-Za-z0-9\-]/', '', (string)$code));
    if ($uid <= 0 || strlen($code) < 8) {
        return array(false, '卡密格式不对', array());
    }

    /* 抢占：只有 status=0 的那一次能改成 1，并发下也只有一个人成功 */
    $stmt = $DB->query(
        "UPDATE `nteam_card` SET `status`=1, `user_id`=?, `used_time`=?
          WHERE `code`=? AND `status`=0",
        array($uid, time(), $code)
    );
    if (!$stmt || $stmt->rowCount() !== 1) {
        return array(false, '卡密不存在、已被使用或已停用', array());
    }

    $card = $DB->getRow("SELECT * FROM `nteam_card` WHERE `code`=? LIMIT 1", array($code));
    if (!$card) {
        return array(false, '卡密读取失败，请联系客服', array());
    }

    $ref  = 'card:' . (int)$card['id'];
    $type = (string)$card['type'];
    $done = false;
    $msg  = '';

    if ($type === 'balance') {
        $amount = round((float)$card['value'], 2);
        $done = wallet_change($DB, $uid, $amount, 'card', '卡密兑换：' . $code, $ref);
        $msg = $done ? ('兑换成功，¥' . number_format($amount, 2) . ' 已存入余额') : '入账失败，请联系客服';
    } elseif ($type === 'vip' || $type === 'svip') {
        if (!function_exists('vip_grant')) {
            require_once __DIR__ . '/Vip.php';
        }
        $level = $type === 'svip' ? 2 : 1;
        $days  = max(1, (int)$card['days']);
        $expire = vip_grant($DB, $uid, $level, $days);
        if ($expire > 0) {
            /* 会员卡也记一条 0 元流水，方便后台审计兑换记录 */
            $DB->query(
                "INSERT INTO `nteam_money_log`
                    (`user_id`,`amount`,`balance`,`type`,`memo`,`ref`,`create_time`)
                 SELECT ?,0,`money`,'card',?,?,? FROM `nteam_user` WHERE `id`=? LIMIT 1",
                array($uid, '卡密兑换：' . vip_level_name($level) . ' ' . $days . ' 天', $ref, time(), $uid)
            );
            $done = true;
            $msg = '兑换成功，' . vip_level_name($level) . ' 已发放，有效期至 ' . date('Y-m-d', $expire);
        } else {
            $msg = '会员发放失败，请联系客服';
        }
    } else {
        $msg = '未知卡密类型，请联系客服';
    }

    if (!$done) {
        /* 发放失败把卡还原成未使用，用户可以重试 */
        $DB->query(
            "UPDATE `nteam_card` SET `status`=0, `user_id`=0, `used_time`=0 WHERE `id`=? AND `status`=1",
            array((int)$card['id'])
        );
        return array(false, $msg, array());
    }

    return array(true, $msg, array(
        'type'    => $type,
        'value'   => (float)$card['value'],
        'days'    => (int)$card['days'],
        'balance' => wallet_balance($DB, $uid),
    ));
}
