<?php
/**
 * 会员中心（OneTool shop/vip.html 版式）
 *
 * 订单 remark=vip|等级|天数，支付回调 epay_settle() 自动发放。
 * 支持微信 / 支付宝 / 余额支付；余额支付本地结算，不跳收银台。
 */
require dirname(__DIR__) . '/Common/Core_brain.php';
require dirname(__DIR__) . '/Common/Pay.php';
require dirname(__DIR__) . '/Common/Schema.php';
require dirname(__DIR__) . '/Common/User.php';
require dirname(__DIR__) . '/Common/Vip.php';
require dirname(__DIR__) . '/Common/Wallet.php';
require __DIR__ . '/_layout.php';

pay_schema_init($DB);
user_require_login('/user/vip.php');

$u   = user_current();
$uid = (int)$u['id'];
$err = '';

$vipLevel  = isset($u['vip_level']) ? (int)$u['vip_level'] : 0;
$vipExpire = isset($u['vip_expire']) ? (int)$u['vip_expire'] : 0;
$plans     = vip_plans();
$balance   = wallet_balance($DB, $uid);
$wxOn      = epay_channel_on('wxpay');
$aliOn     = epay_channel_on('alipay');

/* ---- 下单 ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!user_csrf_check()) {
        $err = '页面已过期，请重新提交';
    } else {
        $channel = isset($_POST['channel']) ? (string)$_POST['channel'] : '';
        $planId  = isset($_POST['plan']) ? (string)$_POST['plan'] : '';
        $plan    = vip_plan($planId);
        $isBalance = $channel === 'balance';

        if (!$plan) {
            $err = '请选择要开通的会员套餐。';
        } elseif (!in_array($channel, array('wxpay', 'alipay', 'balance'), true)) {
            $err = '请选择支付方式。';
        } elseif (!$isBalance && !epay_ready()) {
            $err = '在线支付还没开通，请充值余额后使用余额支付，或联系客服购买。';
        } elseif (!$isBalance && !epay_channel_on($channel)) {
            $err = epay_type_name($channel) . '暂未开通，请换一种支付方式。';
        } else {
            /* 同套餐同金额的待支付单 30 分钟内复用 */
            $order = $DB->getRow(
                "SELECT * FROM `nteam_order`
                  WHERE `user_id`=? AND `project_id`=0 AND `status`=0 AND `money`=? AND `remark`=? AND `create_time`>?
                  ORDER BY `id` DESC LIMIT 1",
                array($uid, $plan['price'], vip_order_remark($plan['level'], $plan['days']), time() - 1800)
            );
            if (!$order) {
                $no = epay_new_no();
                $stmt = $DB->query(
                    "INSERT INTO `nteam_order`
                        (`out_trade_no`,`user_id`,`project_id`,`project_name`,`money`,`status`,`client_ip`,`create_time`,`remark`)
                     VALUES (?,?,0,?,?,0,?,?,?)",
                    array($no, $uid, $plan['name'], $plan['price'], user_ip(), time(),
                          vip_order_remark($plan['level'], $plan['days']))
                );
                if ($stmt === false) {
                    $err = '下单失败，稍后再试。';
                } else {
                    $order = $DB->getRow("SELECT * FROM `nteam_order` WHERE `out_trade_no`=? LIMIT 1", array($no));
                    if ($order) {
                        epay_log($DB, 'submit', array('plan' => $plan['id'], 'money' => $plan['price'], 'channel' => $channel), 1, $no);
                    }
                }
            }

            if ($order && $err === '') {
                /* 余额支付：本地结算 */
                if ($isBalance) {
                    $res = wallet_pay_order($DB, $uid, $order);
                    if (!$res[0]) {
                        $err = $res[1];
                    } else {
                        header('Location: /user/order.php?no=' . urlencode($order['out_trade_no']) . '&paid=1');
                        exit;
                    }
                } else {
                    /* 在线支付：跳收银台 */
                    account_head('正在跳转支付', $u, 'vip');
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
                    <script>(function () { var f = document.getElementById('epayForm'); if (f) f.submit(); })();</script>
                    <?php
                    account_foot();
                    exit;
                }
            }
            if ($err === '' && !$order) {
                $err = '下单失败，稍后再试。';
            }
        }
    }
}

account_head('会员中心', $u, 'vip');
?>

<?php account_notice($err, 'err'); ?>

<!-- 当前会员状态 -->
<div class="block block-rounded bg-gd-dusk mb-4">
    <div class="block-content bg-white-5 text-center py-4">
        <h1 class="h3 fw-bold text-white mb-2">
            你好，<?php echo wk_h($u['nickname'] !== '' ? $u['nickname'] : $u['username']); ?>
            <i class="fa fa-crown ms-1"></i>
        </h1>
        <h2 class="h6 fw-medium text-white-75 mb-3">
            当前身份：<?php echo vip_level_name($vipLevel); ?>
            <?php if ($vipLevel > 0 && $vipExpire > time()) { ?>
             · 有效期至 <?php echo date('Y-m-d', $vipExpire); ?>
            <?php } elseif ($vipLevel > 0) { ?>
             · 长期有效
            <?php } else { ?>
             · 尚未开通
            <?php } ?>
        </h2>
        <div class="d-flex justify-content-center gap-2 flex-wrap fs-sm text-white-75">
            <?php foreach (vip_perks($vipLevel) as $pk) { ?>
            <span class="badge bg-white-10"><i class="fa fa-check me-1"></i><?php echo wk_h($pk); ?></span>
            <?php } ?>
        </div>
    </div>
</div>

<!-- 特权说明：OneTool shop/vip.html 的整宽 col-md-12 说明卡 -->
<div class="row items-push">
    <div class="col-md-12">
        <div class="block block-rounded h-100 mb-0">
            <div class="block-header block-header-default">
                <h3 class="block-title"><i class="si si-handbag fa-fw me-1 text-muted"></i> 平台付费会员专享特权</h3>
            </div>
            <div class="block-content block-content-full">
                <p class="mb-2 fs-sm"><strong>1、折扣购入</strong>　VIP 会员购买全部源码项目享受会员价，SVIP 全部免费下载。</p>
                <p class="mb-2 fs-sm"><strong>2、自动交付</strong>　系统 24 小时自动处理订单，支付后自动发放会员与交付内容，无需等待。</p>
                <p class="mb-2 fs-sm"><strong>3、余额通用</strong>　账户余额可直接用于购买源码、开通会员，也可通过卡密兑换获得。</p>
                <p class="mb-2 fs-sm"><strong>4、优先响应</strong>　问题优先处理，SVIP 享专属客服通道。</p>
                <p class="mb-0 fs-sm text-muted">注：到期自动回落为普通用户；续费时长自动顺延，升级套餐立即生效。</p>
            </div>
        </div>
    </div>
</div>

<!-- 定价卡：原版 col-md-6 col-xl-3 四列，本站 6 档改成 3 列 × 2 行，卡片之间留足间距 -->
<div class="row items-push g-3">
    <?php foreach ($plans as $i => $p) {
        $best = ($p['id'] === 'svip_y');
        $hot  = ($p['id'] === 'vip_y');
    ?>
    <div class="col-12 col-md-6 col-xl-4">
        <a class="block block-link-pop block-rounded block-bordered text-center h-100 mb-0 ot-plan-card"
           href="javascript:otVipSelect(<?php echo $i; ?>)">
            <div class="block-header">
                <h3 class="block-title">
                    <?php if ($best) { ?><i class="fa fa-check text-primary me-1"></i><?php } ?>
                    <?php echo wk_h($p['name']); ?>
                </h3>
            </div>
            <div class="block-content bg-body-light py-4">
                <div class="h1 fw-bold mb-2 <?php echo $best ? 'text-primary' : ''; ?>">¥<?php echo number_format($p['price'], 2); ?></div>
                <div class="fw-medium text-muted mb-0"><?php echo wk_h($p['unit']); ?></div>
            </div>
            <div class="block-content">
                <p class="mb-2"><i class="fa fa-crown fa-2x opacity-25"></i></p>
                <p class="mb-1 fs-sm"><i class="fa fa-check text-success me-1"></i><?php echo vip_level_name($p['level']); ?> 全部特权</p>
                <p class="mb-1 fs-sm"><i class="fa fa-clock me-1 opacity-50"></i>有效期 <?php echo (int)$p['days']; ?> 天</p>
                <p class="mb-0 fs-sm"><i class="fa fa-bolt me-1 opacity-50"></i>支付后自动开通</p>
            </div>
            <div class="block-content block-content-full">
                <span class="btn btn-primary w-100">
                    <i class="fa fa-arrow-up opacity-50 me-1"></i> 购买
                </span>
            </div>
        </a>
    </div>
    <?php } ?>
</div>

<form method="post" id="otVipForm">
    <?php echo user_csrf_field(); ?>
    <input type="hidden" name="plan" id="otPlanId" value="">
    <input type="hidden" name="channel" id="otChannel" value="">
</form>

<!-- 支付方式选择层 -->
<div class="p-2" id="payment-selector" style="display:none">
    <div class="text-center fw-semibold fs-sm mb-2" id="otPayTitle">请选择支付方式</div>
    <div class="btn-group btn-group-sm my-2" role="group">
        <?php if ($wxOn && epay_ready()) { ?>
        <input type="radio" class="btn-check" name="otvippay" id="otVipWx" onclick="otVipBuy('wxpay');">
        <label class="btn btn-alt-info" for="otVipWx"><i class="fa-brands fa-weixin me-1"></i>微信支付</label>
        <?php } ?>
        <?php if ($aliOn && epay_ready()) { ?>
        <input type="radio" class="btn-check" name="otvippay" id="otVipAli" onclick="otVipBuy('alipay');">
        <label class="btn btn-alt-info" for="otVipAli"><i class="fa-brands fa-alipay me-1"></i>支付宝</label>
        <?php } ?>
        <input type="radio" class="btn-check" name="otvippay" id="otVipBal" onclick="otVipBuy('balance');">
        <label class="btn btn-alt-info" for="otVipBal"><i class="fa fa-wallet me-1"></i>余额支付</label>
    </div>
    <div class="fs-xs text-muted text-center mt-1">当前余额 ¥<?php echo number_format($balance, 2); ?> · <a href="/user/money.php">去充值</a></div>
</div>

<script>
var otVipPlans = <?php echo json_encode(array_map(function ($p) {
    return array('id' => $p['id'], 'name' => $p['name'], 'price' => $p['price']);
}, $plans)); ?>;
var otVipIdx = 0;
function otVipSelect(idx) {
    var p = otVipPlans[idx];
    if (!p) return;
    otVipIdx = idx;
    document.getElementById('otPayTitle').textContent = p.name + ' · ¥' + p.price.toFixed(2);
    layer.open({type: 1, shade: .25, shadeClose: false, title: false, content: $('#payment-selector')});
}
function otVipBuy(channel) {
    var p = otVipPlans[otVipIdx];
    if (!p) return;
    layer.closeAll();
    document.getElementById('otPlanId').value = p.id;
    document.getElementById('otChannel').value = channel;
    document.getElementById('otVipForm').submit();
}
</script>
<?php account_foot(); ?>
