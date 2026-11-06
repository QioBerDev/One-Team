<?php
/**
 * 下单 / 继续支付 / 余额支付
 *
 * 只在 POST 里做，GET 一律回作品页 —— 防止用 <img src="/buy.php?project_id=12">
 * 这种第三方页面把用户的待支付订单刷爆。
 * channel=balance 时走 wallet_pay_order() 本地扣款结算，不跳收银台。
 */
require __DIR__ . '/Common/Core_brain.php';
require __DIR__ . '/Common/Pay.php';
require __DIR__ . '/Common/Schema.php';
require __DIR__ . '/Common/User.php';
require __DIR__ . '/Common/Vip.php';
require __DIR__ . '/Common/Wallet.php';
require __DIR__ . '/user/_layout.php';

pay_schema_init($DB);
user_require_login('/');

$u = user_current();

function buy_fail($u, $msg, $back = '/works.php')
{
    account_head('无法下单', $u, 'orders');
    ?>
    <div class="block block-rounded" style="max-width:620px;margin:0 auto">
        <div class="block-content block-content-full text-center py-5">
            <i class="fa fa-circle-exclamation fa-2x text-danger d-block mb-3"></i>
            <p class="fw-semibold mb-4"><?php echo wk_h($msg); ?></p>
            <div class="d-flex gap-2 justify-content-center flex-wrap">
                <a class="btn btn-alt-primary" href="<?php echo wk_h($back); ?>">返回</a>
                <a class="btn btn-alt-secondary" href="/user/index.php">个人中心</a>
            </div>
        </div>
    </div>
    <?php
    account_foot();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /works.php');
    exit;
}

if (!user_csrf_check()) {
    buy_fail($u, '页面已过期，请重新点一次购买。');
}

$act     = isset($_POST['act']) ? (string)$_POST['act'] : 'buy';
$order   = false;
$channel = isset($_POST['channel']) ? (string)$_POST['channel'] : '';

/* 支付渠道：在线两个 + 余额 + SVIP 免费 */
if (!in_array($channel, array('wxpay', 'alipay', 'balance', 'free'), true)) {
    buy_fail($u, '请选择支付方式。');
}
$isBalance = $channel === 'balance';
$isFree    = $channel === 'free';
if (!$isBalance && !$isFree && !epay_channel_on($channel)) {
    buy_fail($u, epay_type_name($channel) . '暂未开通，请换一种支付方式。');
}

if ($act === 'repay') {
    $no = isset($_POST['no']) ? preg_replace('/[^A-Za-z0-9]/', '', (string)$_POST['no']) : '';
    if ($no === '') {
        buy_fail($u, '订单号不对。');
    }
    $order = $DB->getRow("SELECT * FROM `nteam_order` WHERE `out_trade_no`=? LIMIT 1", array($no));
    if (!$order || (int)$order['user_id'] !== (int)$u['id']) {
        buy_fail($u, '这张订单不存在，或者不属于当前账号。');
    }
    if ((int)$order['status'] !== 0) {
        header('Location: /user/order.php?no=' . urlencode($order['out_trade_no']));
        exit;
    }
    if ((float)$order['money'] <= 0) {
        buy_fail($u, '这张订单金额为 0，不能支付。');
    }
} else {
    $pid = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
    if ($pid <= 0) {
        buy_fail($u, '没有选中项目。');
    }
    $proj = $DB->getRow(
        "SELECT * FROM `nteam_project_list` WHERE `id`=? AND `Audit_status`=1 LIMIT 1",
        array($pid)
    );
    if (!$proj) {
        buy_fail($u, '这个项目不存在，或者已经下架了。');
    }

    $base  = epay_parse_money($proj['money']);
    if ($base <= 0) {
        buy_fail($u, '这个项目是「' . $proj['money'] . '」，需要联系作者报价，不能直接在线支付。', '/works.php?id=' . $pid);
    }

    /* 会员权益：VIP 折扣 / SVIP 免费，服务端重算，不信任前端传的价格 */
    $eff   = vip_effective_price($base, $u);
    $money = $eff['price'];

    if ($money <= 0) {
        if (!$eff['free'] || !$isFree) {
            buy_fail($u, '这个订单不能直接结算，请选择支付方式。', '/works.php?id=' . $pid);
        }
        /* SVIP 免费领取：0 元单 + 立即结算 + 自动交付，不跳收银台 */
        $no  = epay_new_no();
        $ins = $DB->query(
            "INSERT INTO `nteam_order`
                (`out_trade_no`,`user_id`,`project_id`,`project_name`,`money`,`status`,`client_ip`,`create_time`)
             VALUES (?,?,?,?,0.00,0,?,?)",
            array($no, (int)$u['id'], $pid, (function_exists('mb_substr')
                ? mb_substr((string)$proj['name'], 0, 60, 'UTF-8')
                : substr((string)$proj['name'], 0, 180)), user_ip(), time())
        );
        if ($ins === false) {
            buy_fail($u, '下单失败，稍后再试。');
        }
        $fresh = $DB->getRow("SELECT * FROM `nteam_order` WHERE `out_trade_no`=? LIMIT 1", array($no));
        if (!$fresh) {
            buy_fail($u, '下单失败，订单没写进去。');
        }
        epay_log($DB, 'submit', array('project_id' => $pid, 'money' => 0, 'channel' => 'free'), 1, $no);
        epay_settle($DB, $fresh, 'FREE-' . $no, 'free');
        header('Location: /user/order.php?no=' . urlencode($no) . '&paid=1');
        exit;
    }
    if ($money < 0.01) {
        buy_fail($u, '金额太小，无法发起支付。');
    }
    if (!$isBalance && !epay_ready()) {
        buy_fail($u, '在线支付还没开通，请充值余额后使用余额支付，或联系作者购买。', '/works.php?id=' . $pid);
    }

    /* 30 分钟内同项目同金额的待支付单直接复用，避免一人挂一屏废单 */
    $order = $DB->getRow(
        "SELECT * FROM `nteam_order`
          WHERE `user_id`=? AND `project_id`=? AND `status`=0 AND `money`=? AND `create_time`>?
          ORDER BY `id` DESC LIMIT 1",
        array((int)$u['id'], $pid, $money, time() - 1800)
    );

    if (!$order) {
        $no = epay_new_no();
        $stmt = $DB->query(
            "INSERT INTO `nteam_order`
                (`out_trade_no`,`user_id`,`project_id`,`project_name`,`money`,`status`,`client_ip`,`create_time`)
             VALUES (?,?,?,?,?,0,?,?)",
            array($no, (int)$u['id'], $pid, (function_exists('mb_substr')
                ? mb_substr((string)$proj['name'], 0, 60, 'UTF-8')
                : substr((string)$proj['name'], 0, 180)), $money, user_ip(), time())
        );
        if ($stmt === false) {
            buy_fail($u, '下单失败，稍后再试。');
        }
        $order = $DB->getRow("SELECT * FROM `nteam_order` WHERE `out_trade_no`=? LIMIT 1", array($no));
        if (!$order) {
            buy_fail($u, '下单失败，订单没写进去。');
        }
        epay_log($DB, 'submit', array('project_id' => $pid, 'money' => $money, 'channel' => $channel), 1, $no);
    }
}

if (!$order) {
    buy_fail($u, '订单不存在。');
}

/* ================= 余额支付：本地结算，不跳收银台 ================= */
if ($isBalance) {
    $res = wallet_pay_order($DB, (int)$u['id'], $order);
    if (!$res[0]) {
        buy_fail($u, $res[1], '/user/money.php');
    }
    header('Location: /user/order.php?no=' . urlencode($order['out_trade_no']) . '&paid=1');
    exit;
}

/* ================= 在线支付：跳收银台 ================= */
if (!epay_ready()) {
    buy_fail($u, '在线支付还没开通，请联系作者。');
}

account_head('正在跳转支付', $u, 'orders');
?>
<div class="block block-rounded text-center">
    <div class="block-content block-content-full py-5">
        <div class="mb-3"><i class="far fa-sun fa-spin fa-2x text-primary"></i></div>
        <h4 class="fw-bold mb-1">正在跳转到收银台…</h4>
        <p class="text-muted fs-sm mb-4">
            <?php echo wk_h($order['project_name']); ?> · ¥<?php echo number_format((float)$order['money'], 2); ?> ·
            <?php echo wk_h(epay_type_name($channel)); ?> ·
            <?php echo wk_h($order['out_trade_no']); ?>
        </p>
        <?php echo epay_pay_form($order, $channel); ?>
        <button class="btn btn-alt-secondary btn-sm" type="button" onclick="document.getElementById('epayForm').submit()">
            没有反应？手动继续
        </button>
    </div>
</div>
<script>(function () { var f = document.getElementById('epayForm'); if (f) { f.submit(); } })();</script>
<?php account_foot(); ?>
