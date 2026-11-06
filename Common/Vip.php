<?php
/**
 * 会员计划与发放
 *
 * 计划定义集中在 vip_plans()，价格改这一处即可。
 * 订单侧：project_id=0、remark="vip|等级|天数"，支付回调 epay_settle() 识别后发放。
 */

if (!defined('IN_CRONLITE')) {
    exit('Access denied');
}
require_once __DIR__ . '/OtConfig.php';

/**
 * 会员计划：价格 / 折扣 / 免费下载全部来自后台「用户中心运营」(Common/OtConfig.php)。
 * 列还没建时 ot_cfg() 会用内置默认值兜底。
 */
function vip_plans()
{
    return array(
        array('id' => 'vip_m',   'level' => 1, 'days' => 31,  'name' => 'VIP 包月',  'price' => (float)ot_cfg('Vip_P_Vip_M'),  'unit' => '每 1 月'),
        array('id' => 'vip_q',   'level' => 1, 'days' => 92,  'name' => 'VIP 包季',  'price' => (float)ot_cfg('Vip_P_Vip_Q'),  'unit' => '每 3 月'),
        array('id' => 'vip_y',   'level' => 1, 'days' => 366, 'name' => 'VIP 年费',  'price' => (float)ot_cfg('Vip_P_Vip_Y'),  'unit' => '每 12 月'),
        array('id' => 'svip_m',  'level' => 2, 'days' => 31,  'name' => 'SVIP 包月', 'price' => (float)ot_cfg('Vip_P_Svip_M'), 'unit' => '每 1 月'),
        array('id' => 'svip_q',  'level' => 2, 'days' => 92,  'name' => 'SVIP 包季', 'price' => (float)ot_cfg('Vip_P_Svip_Q'), 'unit' => '每 3 月'),
        array('id' => 'svip_y',  'level' => 2, 'days' => 366, 'name' => 'SVIP 年费', 'price' => (float)ot_cfg('Vip_P_Svip_Y'), 'unit' => '每 12 月'),
    );
}

/** 当前有效的会员等级：过期自动按普通用户算 */
function vip_active_level($user)
{
    if (!$user || !is_array($user)) { return 0; }
    $lv = isset($user['vip_level']) ? (int)$user['vip_level'] : 0;
    $exp = isset($user['vip_expire']) ? (int)$user['vip_expire'] : 0;
    return ($lv > 0 && $exp > time()) ? min(2, $lv) : 0;
}

/**
 * 按后台权益规则算源码实付价。
 * @return array {price, base, discount, free, level}
 */
function vip_effective_price($base, $user)
{
    $base  = max(0, round((float)$base, 2));
    $level = vip_active_level($user);
    $out   = array('price' => $base, 'base' => $base, 'discount' => false, 'free' => false, 'level' => $level);
    if ($base <= 0 || $level <= 0) { return $out; }

    if ($level >= 2 && ot_on('Svip_Free')) {
        $out['price'] = 0.00;
        $out['free']  = true;
        return $out;
    }
    $rate = ot_num('Vip_Discount', 0, 100);
    if ($level >= 1 && $rate < 100) {
        $out['price'] = round($base * $rate / 100, 2);
        $out['discount'] = true;
    }
    return $out;
}

function vip_plan($planId)
{
    foreach (vip_plans() as $p) {
        if ($p['id'] === (string)$planId) {
            return $p;
        }
    }
    return null;
}

function vip_level_name($level)
{
    $level = (int)$level;
    return $level >= 2 ? 'SVIP' : ($level === 1 ? 'VIP' : '普通用户');
}

/** 各等级权益（个人中心 / 会员中心共用） */
function vip_perks($level)
{
    $rate = ot_num('Vip_Discount', 0, 100);
    $free = ot_on('Svip_Free');
    $perks = array(
        0 => array(
            '原价购买全部源码项目',
            '付款后自动交付，订单随时可查',
            '支持找回密码，账号安全有保障',
        ),
        1 => array(
            $rate < 100
                ? ('全部源码项目 ' . rtrim(rtrim(number_format($rate / 10, 1, '.', ''), '0'), '.') . ' 折（实付 = 原价 × ' . $rate . '%）')
                : '全部源码项目按原价购买',
            '付款后自动交付，订单随时可查',
            '问题优先响应',
        ),
        2 => array(
            $free ? '全部源码项目免费下载（0 元下单自动交付）' : '全部源码项目按原价购买',
            '付款后自动交付，订单随时可查',
            '专属客服通道',
        ),
    );
    $level = (int)$level;
    return isset($perks[$level]) ? $perks[$level] : $perks[0];
}

/**
 * 发放会员：只升不降，时长顺延。
 * 返回新的到期时间戳。
 */
function vip_grant($DB, $userId, $level, $days)
{
    $userId = (int)$userId;
    $level  = max(1, min(2, (int)$level));
    $days   = max(1, (int)$days);
    $row = $DB->getRow(
        "SELECT `vip_level`, `vip_expire` FROM `nteam_user` WHERE `id`=? LIMIT 1",
        array($userId)
    );
    if (!$row) {
        return 0;
    }
    $now = time();
    $curLevel  = isset($row['vip_level']) ? (int)$row['vip_level'] : 0;
    $curExpire = isset($row['vip_expire']) ? (int)$row['vip_expire'] : 0;

    $newLevel  = max($curLevel, $level);
    $base      = ($curLevel === $level && $curExpire > $now) ? $curExpire : $now;
    /* 升级（低→高）时从当前时间重新起算，避免低级剩余时长拖累高级 */
    if ($level > $curLevel) {
        $base = $now;
    }
    $newExpire = $base + $days * 86400;

    $DB->query(
        "UPDATE `nteam_user` SET `vip_level`=?, `vip_expire`=? WHERE `id`=?",
        array($newLevel, $newExpire, $userId)
    );
    return $newExpire;
}

/** 订单 remark 编解码：vip|等级|天数 */
function vip_order_remark($level, $days)
{
    return 'vip|' . (int)$level . '|' . (int)$days;
}

function vip_parse_remark($remark)
{
    $remark = (string)$remark;
    if (strpos($remark, 'vip|') !== 0) {
        return null;
    }
    $parts = explode('|', $remark);
    if (count($parts) !== 3) {
        return null;
    }
    return array('level' => (int)$parts[1], 'days' => (int)$parts[2]);
}
