<?php
/**
 * 卡密激活（OneTool shop/card.html 版式）
 *
 * 卡密类型：balance 余额卡 / vip VIP 天数卡 / svip SVIP 天数卡
 * 兑换走 Common/Wallet.php 的 card_redeem()，抢占式 UPDATE + 幂等，防重复兑换。
 */
require dirname(__DIR__) . '/Common/Core_brain.php';
require dirname(__DIR__) . '/Common/Pay.php';
require dirname(__DIR__) . '/Common/Schema.php';
require dirname(__DIR__) . '/Common/User.php';
require dirname(__DIR__) . '/Common/Vip.php';
require dirname(__DIR__) . '/Common/Wallet.php';
require __DIR__ . '/_layout.php';

pay_schema_init($DB);
user_require_login('/user/card.php');

$u   = user_current();
$uid = (int)$u['id'];
if (!ot_on('Card_Open')) { ot_feature_closed($u, '卡密激活', '管理员暂时关闭了卡密激活功能。', 'card'); }
$err = '';
$ok  = '';

$balance   = wallet_balance($DB, $uid);
$vipLevel  = isset($u['vip_level']) ? (int)$u['vip_level'] : 0;
$vipExpire = isset($u['vip_expire']) ? (int)$u['vip_expire'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['act']) && $_POST['act'] === 'redeem')) {
    $km = isset($_POST['km']) ? (string)$_POST['km'] : '';
    $isAjax = isset($_POST['ajax']);

    if (!user_csrf_check()) {
        $err = '页面已过期，请刷新后重试';
    } elseif (!user_ip_throttle($DB, 'card', 10, 600)) {
        $err = '尝试次数过多，请 10 分钟后再试';
    } else {
        $res = card_redeem($DB, $uid, $km);
        if ($res[0]) {
            $ok = $res[1];
            $balance = wallet_balance($DB, $uid);
            $u = $DB->getRow("SELECT * FROM `nteam_user` WHERE `id`=? LIMIT 1", array($uid));
            if ($u) {
                $vipLevel  = (int)$u['vip_level'];
                $vipExpire = (int)$u['vip_expire'];
            }
        } else {
            $err = $res[1];
        }
    }

    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array(
            'ok'      => $err === '' && $ok !== '',
            'msg'     => $err !== '' ? $err : $ok,
            'balance' => number_format($balance, 2, '.', ''),
        ));
        exit;
    }
}

/* 最近的兑换记录 */
$redeemLogs = $DB->getAll(
    "SELECT * FROM `nteam_money_log` WHERE `user_id`=? AND `type`='card' ORDER BY `id` DESC LIMIT 10",
    array($uid)
);
if (!is_array($redeemLogs)) {
    $redeemLogs = array();
}

account_head('卡密激活', $u, 'card');
?>

<div class="block block-rounded">
    <div class="block-content block-content-full text-center py-4">
        <h2 class="fw-bold mb-2">卡密激活</h2>
        <h3 class="fs-base fw-medium text-muted mb-0">
            系统 24 小时自动处理订单，兑换后自动为您的账户充值余额或发放会员，无需等待。
        </h3>
    </div>
</div>

<?php account_notice($err, 'err'); account_notice($ok, 'ok'); ?>

<div class="row">
    <div class="col-lg-6 col-xl-4">
        <div class="block block-rounded text-center overflow-hidden">
            <div class="d-block bg-pulse fw-semibold text-uppercase py-2">
                <span class="text-white fs-sm">账户明细</span>
            </div>
            <div class="block-content block-content-full">
                <div class="fw-semibold mb-2" id="otBalance">余额：¥<?php echo number_format($balance, 2); ?> 元</div>
                <div class="fw-semibold mb-2">
                    <?php
                    if ($vipLevel > 0 && $vipExpire > time()) {
                        echo wk_h(vip_level_name($vipLevel)) . '：' . date('Y-m-d', $vipExpire);
                    } else {
                        echo '普通用户';
                    }
                    ?>
                </div>
                <div class="fw-semibold mb-3">UID：<?php echo $uid; ?></div>
                <a href="/user/money.php" class="btn btn-alt-secondary btn-sm w-100">
                    <i class="fa fa-wallet me-1"></i>查看余额明细
                </a>
            </div>
        </div>
    </div>
    <div class="col-lg-6 col-xl-4">
        <div class="block block-rounded text-center overflow-hidden">
            <div class="d-block bg-elegance fw-semibold text-uppercase py-2">
                <span class="text-white fs-sm">输入卡密</span>
            </div>
            <div class="block-content">
                <form id="otCardForm" method="post" onsubmit="return false;">
                    <?php echo user_csrf_field(); ?>
                    <input type="hidden" name="act" value="redeem">
                    <div class="mb-4">
                        <div class="input-group input-group-lg">
                            <input type="text" class="form-control" id="km" name="km" placeholder="请输入你的卡密"
                                   autocomplete="off" spellcheck="false">
                        </div>
                    </div>
                    <div class="mb-4">
                        <button type="button" class="btn btn-lg w-100 btn-alt-primary" onclick="otRedeem();">确定兑换</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-12 col-xl-4">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title"><i class="fa fa-circle-question me-1 text-muted"></i> 使用说明</h3>
            </div>
            <div class="block-content fs-sm">
                <p class="mb-2"><i class="fa fa-check text-success me-1"></i> 余额卡：兑换后金额立即到账，可直接消费。</p>
                <p class="mb-2"><i class="fa fa-check text-success me-1"></i> 会员卡：自动发放 VIP / SVIP，同级时长顺延。</p>
                <p class="mb-0 text-muted">每张卡密只能使用一次，请勿泄露。兑换失败会原样退回卡密状态。</p>
            </div>
        </div>
    </div>
</div>

<div class="block block-rounded">
    <div class="block-header block-header-default">
        <h3 class="block-title"><i class="si si-list me-1 text-muted"></i> 最近兑换记录</h3>
    </div>
    <div class="block-content block-content-full" style="padding:0">
        <?php if (!$redeemLogs) { ?>
        <div class="text-center text-muted fs-sm py-4">
            <i class="fa fa-ticket fa-2x d-block mb-2 opacity-50"></i>还没有兑换过卡密
        </div>
        <?php } else { ?>
        <div class="table-responsive">
            <table class="table table-striped table-vcenter mb-0">
                <thead>
                    <tr>
                        <th style="width:130px">时间</th>
                        <th>说明</th>
                        <th class="text-end" style="width:110px">入账</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($redeemLogs as $l) { ?>
                    <tr>
                        <td class="fs-xs text-muted"><?php echo date('Y-m-d H:i', (int)$l['create_time']); ?></td>
                        <td class="fs-sm"><?php echo wk_h($l['memo']); ?></td>
                        <td class="text-end fw-semibold <?php echo (float)$l['amount'] > 0 ? 'text-success' : 'text-muted'; ?>">
                            <?php echo (float)$l['amount'] > 0 ? '+' . number_format((float)$l['amount'], 2) : '会员'; ?>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <?php } ?>
    </div>
</div>

<script>
function otRedeem() {
    var km = x.getval('#km', '请先输入卡密');
    if (!km) return;
    var btn = document.querySelector('#otCardForm .btn');
    if (btn) btn.disabled = true;
    $.post('/user/card.php', {
        act: 'redeem', km: km, ajax: 1, _csrf: '<?php echo user_csrf_token(); ?>'
    }, function (j) {
        if (btn) btn.disabled = false;
        if (j && j.ok) {
            x.notify(j.msg, 'success');
            document.getElementById('km').value = '';
            var b = document.getElementById('otBalance');
            if (b && j.balance) b.textContent = '余额：¥' + j.balance + ' 元';
            setTimeout(function () { window.location.reload(); }, 1200);
        } else {
            x.notify((j && j.msg) || '兑换失败', 'warning');
        }
    }, 'json').fail(function () {
        if (btn) btn.disabled = false;
        x.notify('网络异常，请重试', 'danger');
    });
}
</script>
<?php account_foot(); ?>
