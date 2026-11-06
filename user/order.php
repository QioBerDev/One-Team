<?php
/**
 * 我的订单：无参数=列表，?no= =详情
 * 只有下单人自己能看。待支付可以继续付（在线 / 余额）；已支付显示交付内容。
 */
require dirname(__DIR__) . '/Common/Core_brain.php';
require dirname(__DIR__) . '/Common/Pay.php';
require dirname(__DIR__) . '/Common/Schema.php';
require dirname(__DIR__) . '/Common/User.php';
require dirname(__DIR__) . '/Common/Vip.php';
require dirname(__DIR__) . '/Common/Wallet.php';
require __DIR__ . '/_layout.php';

pay_schema_init($DB);
user_require_login('/');

$u  = user_current();
$no = isset($_GET['no']) ? preg_replace('/[^A-Za-z0-9]/', '', (string)$_GET['no']) : '';

/* ================= 列表模式 ================= */
if ($no === '') {
    $rows = $DB->getAll(
        "SELECT * FROM `nteam_order` WHERE `user_id`=? ORDER BY `id` DESC LIMIT 200",
        array((int)$u['id'])
    );
    if (!is_array($rows)) { $rows = array(); }
    $wait = 0; $paid = 0; $paidMoney = 0.0;
    foreach ($rows as $r) {
        if ((int)$r['status'] === 0) { $wait++; }
        elseif ((int)$r['status'] === 1) { $paid++; $paidMoney += (float)$r['money']; }
    }

    account_head('我的订单', $u, 'orders');
    ?>
    <div class="row g-sm mb-3">
        <?php
        $stats = array(
            array(count($rows), '全部订单', 'text-primary'),
            array($wait, '待支付', 'text-warning'),
            array($paid, '已支付', 'text-success'),
            array('¥' . number_format($paidMoney, 2), '累计消费', 'text-elegance'),
        );
        foreach ($stats as $s) { ?>
        <div class="col-6 col-xl-3">
            <div class="block block-rounded mb-0">
                <div class="block-content block-content-full text-end">
                    <div class="fs-3 fw-semibold <?php echo $s[2]; ?>"><?php echo $s[0]; ?></div>
                    <div class="fs-sm fw-semibold text-uppercase text-muted"><?php echo $s[1]; ?></div>
                </div>
            </div>
        </div>
        <?php } ?>
    </div>

    <div class="block block-rounded">
        <div class="block-header block-header-default">
            <h3 class="block-title"><i class="fa fa-receipt me-1 text-muted"></i> 订单列表</h3>
            <div class="block-options"><a class="btn btn-sm btn-alt-secondary" href="/works.php">去选购源码</a></div>
        </div>
        <div class="block-content block-content-full" style="padding:0">
            <?php if (!$rows) { ?>
            <div class="text-center text-muted fs-sm py-4">
                <i class="fa fa-receipt fa-2x d-block mb-2 opacity-50"></i>还没有订单。去 <a href="/works.php">作品项目</a> 看看。
            </div>
            <?php } else { ?>
            <div class="table-responsive">
                <table class="table table-striped table-vcenter mb-0">
                    <thead><tr><th>项目</th><th>订单号</th><th>金额</th><th>支付方式</th><th>状态</th><th>时间</th></tr></thead>
                    <tbody>
                        <?php foreach ($rows as $r) {
                            $st = (int)$r['status'];
                            $bcls = array(0 => 'warning', 1 => 'success', 2 => 'secondary', 3 => 'secondary');
                            $b = isset($bcls[$st]) ? $bcls[$st] : 'secondary';
                        ?>
                        <tr>
                            <td><a class="fw-semibold text-dual" href="/user/order.php?no=<?php echo urlencode($r['out_trade_no']); ?>"><?php echo wk_h($r['project_name']); ?></a></td>
                            <td class="text-muted fs-xs"><?php echo wk_h($r['out_trade_no']); ?></td>
                            <td class="fw-semibold">¥<?php echo number_format((float)$r['money'], 2); ?></td>
                            <td class="text-muted fs-xs"><?php echo $r['pay_type'] !== '' ? wk_h(epay_type_name($r['pay_type'])) : '—'; ?></td>
                            <td><span class="badge bg-<?php echo $b; ?>"><?php echo epay_status_name($st); ?></span></td>
                            <td class="text-muted fs-xs"><?php echo date('Y-m-d H:i', (int)$r['create_time']); ?></td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <?php } ?>
        </div>
    </div>
    <?php
    account_foot();
    exit;
}

/* ================= 详情模式 ================= */
$order = $DB->getRow("SELECT * FROM `nteam_order` WHERE `out_trade_no`=? LIMIT 1", array($no));

/* 待支付页轮询用：只回状态，不渲染整页 */
if (isset($_GET['probe'])) {
    header('Content-Type: application/json; charset=utf-8');
    $ok = $order && (int)$order['user_id'] === (int)$u['id'];
    echo json_encode(array('ok' => $ok, 'status' => $ok ? (int)$order['status'] : -1));
    exit;
}

if (!$order || (int)$order['user_id'] !== (int)$u['id']) {
    account_head('订单不存在', $u, 'orders');
    ?>
    <div class="block block-rounded">
        <div class="block-content block-content-full text-center py-5">
            <i class="fa fa-circle-question fa-2x d-block mb-3 opacity-50"></i>
            <p class="mb-3">这张订单不存在，或者不属于当前账号。</p>
            <a class="btn btn-alt-primary btn-sm" href="/user/order.php">返回订单列表</a>
        </div>
    </div>
    <?php
    account_foot();
    exit;
}

$st = (int)$order['status'];
$moneyOk = (float)$order['money'] > 0;
$onlinePay = $st === 0 && $moneyOk && epay_ready() && epay_any_channel();
$balanceEligible = (int)$order['project_id'] > 0 || strpos((string)$order['remark'], 'vip|') === 0;
$balancePay = $st === 0 && $moneyOk && $balanceEligible;
$canPay = $onlinePay || $balancePay;
$balance = wallet_balance($DB, (int)$u['id']);

account_head('订单详情', $u, 'orders');
?>

<?php if (isset($_GET['paid'])) { account_notice('支付成功，交付内容已更新', 'ok'); } ?>

<div class="block block-rounded">
    <div class="block-header block-header-default">
        <h3 class="block-title"><i class="fa fa-receipt me-1 text-muted"></i> <?php echo wk_h($order['project_name']); ?></h3>
        <?php
        $bcls = array(0 => 'warning', 1 => 'success', 2 => 'secondary', 3 => 'secondary');
        $b = isset($bcls[$st]) ? $bcls[$st] : 'secondary';
        ?>
        <span class="badge bg-<?php echo $b; ?>"><?php echo epay_status_name($st); ?></span>
    </div>
    <div class="block-content">
        <dl class="row mb-0">
            <dt class="col-sm-3 text-muted fs-sm fw-normal py-1">订单号</dt>
            <dd class="col-sm-9 py-1"><?php echo wk_h($order['out_trade_no']); ?></dd>
            <dt class="col-sm-3 text-muted fs-sm fw-normal py-1">金额</dt>
            <dd class="col-sm-9 py-1 fw-semibold">¥<?php echo number_format((float)$order['money'], 2); ?></dd>
            <dt class="col-sm-3 text-muted fs-sm fw-normal py-1">下单时间</dt>
            <dd class="col-sm-9 py-1"><?php echo date('Y-m-d H:i:s', (int)$order['create_time']); ?></dd>
            <?php if ((int)$order['pay_time'] > 0) { ?>
            <dt class="col-sm-3 text-muted fs-sm fw-normal py-1">支付时间</dt>
            <dd class="col-sm-9 py-1"><?php echo date('Y-m-d H:i:s', (int)$order['pay_time']); ?></dd>
            <dt class="col-sm-3 text-muted fs-sm fw-normal py-1">支付方式</dt>
            <dd class="col-sm-9 py-1"><?php echo wk_h(epay_type_name($order['pay_type'])); ?></dd>
            <?php if ($order['trade_no'] !== '') { ?>
            <dt class="col-sm-3 text-muted fs-sm fw-normal py-1">平台单号</dt>
            <dd class="col-sm-9 py-1"><?php echo wk_h($order['trade_no']); ?></dd>
            <?php } ?>
            <?php } ?>
        </dl>

        <?php if ($st === 1) { ?>
            <?php if (trim((string)$order['deliver']) !== '') { ?>
            <div class="block block-rounded mt-4 mb-0">
                <div class="block-header block-header-default">
                    <h3 class="block-title"><i class="fa fa-box-open me-1 text-muted"></i> 交付内容</h3>
                </div>
                <div class="block-content">
                    <pre id="otDeliver" style="margin:0 0 12px;white-space:pre-wrap;word-break:break-all"><?php echo wk_h($order['deliver']); ?></pre>
                    <button class="btn btn-alt-primary btn-sm" type="button" onclick="otCopy()"><i class="fa fa-copy me-1"></i>复制</button>
                </div>
            </div>
            <?php } else { ?>
            <div class="alert alert-warning mt-4 mb-0" role="alert">
                <i class="fa fa-clock me-1"></i> 款项已到账。交付内容需要人工确认，请把订单号
                <b><?php echo wk_h($order['out_trade_no']); ?></b> 发给客服，会在 24 小时内处理。
            </div>
            <?php } ?>
        <?php } elseif ($st === 2) { ?>
        <div class="alert alert-secondary mt-4 mb-0" role="alert"><i class="fa fa-rotate-left me-1"></i> 这笔订单已经退款，款项按原路退回。</div>
        <?php } elseif ($st === 3) { ?>
        <div class="alert alert-secondary mt-4 mb-0" role="alert"><i class="fa fa-ban me-1"></i> 订单已关闭，如需购买请重新下单。</div>
        <?php } ?>

        <?php if ($canPay) { ?>
        <div class="d-flex gap-2 flex-wrap mt-4">
            <?php if ($onlinePay) { ?>
            <form method="post" action="/buy.php" style="margin:0;display:flex;gap:10px;flex-wrap:wrap">
                <?php echo user_csrf_field(); ?>
                <input type="hidden" name="act" value="repay">
                <input type="hidden" name="no" value="<?php echo wk_h($order['out_trade_no']); ?>">
                <?php if (epay_channel_on('wxpay')) { ?>
                <button class="btn btn-alt-primary" type="submit" name="channel" value="wxpay">
                    <i class="fa-brands fa-weixin me-1"></i>微信支付 ¥<?php echo number_format((float)$order['money'], 2); ?>
                </button>
                <?php } ?>
                <?php if (epay_channel_on('alipay')) { ?>
                <button class="btn btn-alt-primary" type="submit" name="channel" value="alipay">
                    <i class="fa-brands fa-alipay me-1"></i>支付宝 ¥<?php echo number_format((float)$order['money'], 2); ?>
                </button>
                <?php } ?>
            </form>
            <?php } ?>
            <?php if ($balancePay) { ?>
            <form method="post" action="/buy.php" style="margin:0">
                <?php echo user_csrf_field(); ?>
                <input type="hidden" name="act" value="repay">
                <input type="hidden" name="no" value="<?php echo wk_h($order['out_trade_no']); ?>">
                <button class="btn btn-alt-success" type="submit" name="channel" value="balance"
                        <?php echo $balance < (float)$order['money'] ? 'disabled title="余额不足"' : ''; ?>>
                    <i class="fa fa-wallet me-1"></i>余额支付 ¥<?php echo number_format((float)$order['money'], 2); ?>
                    <small>（可用 ¥<?php echo number_format($balance, 2); ?>）</small>
                </button>
            </form>
            <a class="btn btn-alt-secondary" href="/user/money.php">充值余额</a>
            <?php } ?>
            <?php if ((int)$order['project_id'] > 0) { ?>
            <a class="btn btn-alt-secondary" href="/works.php?id=<?php echo (int)$order['project_id']; ?>">回到项目</a>
            <?php } ?>
            <a class="btn btn-alt-secondary" href="/user/order.php">返回列表</a>
        </div>
        <p class="text-muted fs-xs mt-3 mb-0">支付完成后本页会自动更新。如果已经付过但状态没变，等 1 分钟刷新。</p>
        <?php } else { ?>
        <div class="mt-4"><a class="btn btn-alt-secondary" href="/user/order.php">返回列表</a></div>
        <?php } ?>
    </div>
</div>

<script>
function otCopy() {
    var el = document.getElementById('otDeliver');
    if (!el) return;
    var txt = el.textContent;
    function done() {
        x.notify('已复制到剪贴板', 'success');
    }
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(txt).then(done);
    } else {
        var t = document.createElement('textarea');
        t.value = txt; document.body.appendChild(t); t.select();
        try { document.execCommand('copy'); } catch (e) {}
        document.body.removeChild(t); done();
    }
}
<?php if ($canPay) { ?>
/* 待支付时轮询，付完自动刷新出交付内容 */
(function () {
    var n = 0;
    var t = setInterval(function () {
        if (++n > 40) { clearInterval(t); return; }
        fetch('/user/order.php?no=<?php echo urlencode($order['out_trade_no']); ?>&probe=1', {credentials: 'same-origin'})
            .then(function (r) { return r.json(); })
            .then(function (d) { if (d && d.status !== 0) { location.reload(); } })
            .catch(function () {});
    }, 5000);
})();
<?php } ?>
</script>
<?php account_foot(); ?>
