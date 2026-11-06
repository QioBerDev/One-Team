<?php
/**
 * 充值账户余额（OneTool shop/money.html 版式）
 *
 * 下单 → 易支付收银台 → notify/return 落账（remark=money|金额，epay_settle 自动入账）
 * 余额可用于购买源码、开通会员（channel=balance，见 buy.php / vip.php）。
 */
require dirname(__DIR__) . '/Common/Core_brain.php';
require dirname(__DIR__) . '/Common/Pay.php';
require dirname(__DIR__) . '/Common/Schema.php';
require dirname(__DIR__) . '/Common/User.php';
require dirname(__DIR__) . '/Common/Vip.php';
require dirname(__DIR__) . '/Common/Wallet.php';
require __DIR__ . '/_layout.php';

pay_schema_init($DB);
user_require_login('/user/money.php');

$u       = user_current();
$uid     = (int)$u['id'];
if (!ot_on('Money_Open')) { ot_feature_closed($u, '充值余额', '管理员暂时关闭了余额充值功能。', 'money'); }
$err     = '';
$amount  = 0.0;
$balance = wallet_balance($DB, $uid);
$logs    = wallet_logs($DB, $uid, 30);

$wxOn  = epay_channel_on('wxpay');
$aliOn = epay_channel_on('alipay');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = round((float)(isset($_POST['money']) ? $_POST['money'] : 0), 2);
    $channel = isset($_POST['channel']) ? (string)$_POST['channel'] : '';

    if (!user_csrf_check()) {
        $err = '页面已过期，请重新提交';
    } elseif ($amount < 1 || $amount > 10000) {
        $err = '充值金额要在 1 - 10000 元之间';
    } elseif (!epay_ready()) {
        $err = '在线支付还没开通，请联系客服';
    } elseif (!in_array($channel, array('wxpay', 'alipay'), true)) {
        $err = '请选择支付方式';
    } elseif (!epay_channel_on($channel)) {
        $err = epay_type_name($channel) . '暂未开通，请换一种支付方式';
    } else {
        $remark = wallet_order_remark($amount);
        /* 同金额待支付单直接复用，避免刷新一次挂一张废单 */
        $order = $DB->getRow(
            "SELECT * FROM `nteam_order`
              WHERE `user_id`=? AND `project_id`=0 AND `status`=0 AND `money`=? AND `remark`=?
              ORDER BY `id` DESC LIMIT 1",
            array($uid, $amount, $remark)
        );
        if (!$order) {
            $no = epay_new_no();
            $stmt = $DB->query(
                "INSERT INTO `nteam_order`
                    (`out_trade_no`,`user_id`,`project_id`,`project_name`,`money`,`status`,`client_ip`,`create_time`,`remark`)
                 VALUES (?,?,0,?,?,0,?,?,?)",
                array($no, $uid, '余额充值 ¥' . number_format($amount, 2), $amount, user_ip(), time(), $remark)
            );
            if ($stmt === false) {
                $err = '下单失败，稍后再试';
            } else {
                $order = $DB->getRow("SELECT * FROM `nteam_order` WHERE `out_trade_no`=? LIMIT 1", array($no));
                if ($order) {
                    epay_log($DB, 'submit', array('shop' => 'money', 'money' => $amount), 1, $no);
                }
            }
        }
        if ($order && $err === '') {
            account_head('正在跳转支付', $u, 'money');
            ?>
            <div class="block block-rounded text-center">
                <div class="block-content block-content-full py-5">
                    <div class="mb-3"><i class="far fa-sun fa-spin fa-2x text-primary"></i></div>
                    <h4 class="fw-bold mb-1">正在跳转到收银台…</h4>
                    <p class="text-muted fs-sm mb-4">
                        余额充值 ¥<?php echo number_format($amount, 2); ?> ·
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
}

account_head('充值账户余额', $u, 'money');
?>

<div class="block block-rounded">
    <div class="block-content block-content-full text-center py-4">
        <h2 class="fw-bold mb-2">充值账户余额</h2>
        <h3 class="fs-base fw-medium text-muted mb-0">
            系统 24 小时自动处理订单，支付后自动为您的账户充值余额，无需等待。
        </h3>
    </div>
</div>

<?php account_notice($err, 'err'); ?>

<div class="row">
    <div class="col-lg-6 col-xl-4">
        <div class="block block-rounded text-center overflow-hidden">
            <div class="d-block bg-gd-dusk fw-semibold text-uppercase py-2">
                <span class="text-white fs-sm">余额明细</span>
            </div>
            <div class="block-content block-content-full">
                <p class="fs-sm fw-semibold text-uppercase text-muted">当前账户余额：</p>
                <div class="pb-3"><div class="fs-3 fw-bold">¥<?php echo number_format($balance, 2); ?></div></div>
                <a href="/user/card.php" class="btn btn-alt-secondary btn-sm w-100">
                    <i class="fa fa-ticket me-1"></i>使用卡密兑换
                </a>
            </div>
        </div>
    </div>
    <div class="col-lg-6 col-xl-4">
        <div class="block block-rounded text-center overflow-hidden">
            <div class="d-block bg-gd-sea fw-semibold text-uppercase py-2">
                <span class="text-white fs-sm">余额充值</span>
            </div>
            <div class="block-content">
                <form id="otMoneyForm" method="post" onsubmit="return false;">
                    <?php echo user_csrf_field(); ?>
                    <input type="hidden" name="channel" id="otChannel" value="">
                    <div class="mb-4">
                        <div class="input-group input-group-lg">
                            <input type="number" class="form-control" id="money" name="money" step="0.01" min="1" max="10000"
                                   value="<?php echo $amount > 0 ? wk_h(number_format($amount, 2, '.', '')) : ''; ?>"
                                   placeholder="0.00">
                            <span class="input-group-text fw-semibold">元</span>
                        </div>
                    </div>
                    <div class="mb-4">
                        <button type="button" class="btn btn-lg w-100 btn-alt-primary" onclick="otSelectPayment();">确定充值</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-12 col-xl-4">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title"><i class="fa fa-circle-info me-1 text-muted"></i> 余额能做什么</h3>
            </div>
            <div class="block-content fs-sm">
                <p class="mb-2"><i class="fa fa-check text-success me-1"></i> 购买源码项目时选择「余额支付」，无需跳转收银台。</p>
                <p class="mb-2"><i class="fa fa-check text-success me-1"></i> 开通 VIP / SVIP 会员时可用余额直接抵扣。</p>
                <p class="mb-0 text-muted">余额只在本站消费，不支持提现；充值记录永久保留。</p>
            </div>
        </div>
    </div>
</div>

<!-- 余额流水 -->
<div class="block block-rounded">
    <div class="block-header block-header-default">
        <h3 class="block-title"><i class="si si-list me-1 text-muted"></i> 余额明细</h3>
    </div>
    <div class="block-content block-content-full" style="padding:0">
        <?php if (!$logs) { ?>
        <div class="text-center text-muted fs-sm py-4">
            <i class="si si-wallet fa-2x d-block mb-2 opacity-50"></i>暂时还没有余额变动记录
        </div>
        <?php } else { ?>
        <div class="table-responsive">
            <table class="table table-striped table-vcenter mb-0">
                <thead>
                    <tr>
                        <th style="width:130px">时间</th>
                        <th>说明</th>
                        <th style="width:90px">类型</th>
                        <th class="text-end" style="width:110px">变动</th>
                        <th class="text-end" style="width:110px">余额</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $l) {
                        $amt = (float)$l['amount'];
                        $cls = $amt >= 0 ? 'text-success' : 'text-danger';
                    ?>
                    <tr>
                        <td class="fs-xs text-muted"><?php echo date('Y-m-d H:i', (int)$l['create_time']); ?></td>
                        <td class="fs-sm"><?php echo wk_h($l['memo']); ?></td>
                        <td><span class="fs-xs badge bg-body-light"><?php echo wk_h(wallet_type_name($l['type'])); ?></span></td>
                        <td class="text-end fw-semibold <?php echo $cls; ?>"><?php echo $amt >= 0 ? '+' : ''; ?><?php echo number_format($amt, 2); ?></td>
                        <td class="text-end fs-sm text-muted"><?php echo number_format((float)$l['balance'], 2); ?></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <?php } ?>
    </div>
</div>

<!-- 支付方式选择层（原版 layer 弹层） -->
<div class="p-2" id="payment-selector" style="display:none">
    <div class="text-center fw-semibold fs-sm mb-2">请选择支付方式</div>
    <div class="btn-group btn-group-sm my-2" role="group">
        <?php if ($wxOn) { ?>
        <input type="radio" class="btn-check" name="otpay" id="otWx" onclick="otSubmit('wxpay');">
        <label class="btn btn-alt-info" for="otWx"><i class="fa-brands fa-weixin me-1"></i>微信支付</label>
        <?php } ?>
        <?php if ($aliOn) { ?>
        <input type="radio" class="btn-check" name="otpay" id="otAli" onclick="otSubmit('alipay');">
        <label class="btn btn-alt-info" for="otAli"><i class="fa-brands fa-alipay me-1"></i>支付宝</label>
        <?php } ?>
    </div>
</div>

<script>
function otSelectPayment() {
    var v = parseFloat(document.getElementById('money').value);
    if (!v || v < 1 || v > 10000) {
        x.notify('请输入 1 - 10000 之间的充值金额', 'warning');
        document.getElementById('money').focus();
        return;
    }
    <?php if (!$wxOn && !$aliOn) { ?>
    x.notify('暂无支付渠道，请联系站长', 'danger');
    return;
    <?php } ?>
    layer.open({
        type: 1, shade: .25, shadeClose: false, title: false,
        content: $('#payment-selector'),
        cancel: function () { x.notify('交易取消', 'warning'); }
    });
}
function otSubmit(channel) {
    layer.closeAll();
    document.getElementById('otChannel').value = channel;
    document.getElementById('otMoneyForm').submit();
}
</script>
<?php account_foot(); ?>
