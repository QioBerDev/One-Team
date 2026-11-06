<?php
/**
 * 代理中心 / 邀请返佣（OneTool console/agent/index.html 的 tab 版式）
 *
 * 后台「用户中心运营 → 代理中心」开 Agent_Open 后侧栏出现本页。
 * 邀请链接 /user/register.php?ref=UID → 好友注册时写 invite_by
 * → 好友在线充值入账时 epay_settle() 调 agent_reward() 按比例返佣。
 */
require dirname(__DIR__) . '/Common/Core_brain.php';
require dirname(__DIR__) . '/Common/Pay.php';
require dirname(__DIR__) . '/Common/Schema.php';
require dirname(__DIR__) . '/Common/User.php';
require dirname(__DIR__) . '/Common/Vip.php';
require dirname(__DIR__) . '/Common/Wallet.php';
require dirname(__DIR__) . '/Common/Agent.php';
require __DIR__ . '/_layout.php';

pay_schema_init($DB);
user_require_login('/user/agent.php');
$u = user_current();

if (!ot_on('Agent_Open')) {
    ot_feature_closed($u, '代理中心', '管理员暂时关闭了代理中心。', 'agent');
}

$uid   = (int)$u['id'];
$rate  = ot_num('Agent_Rate', 0, 50);
$invRow  = $DB->getRow("SELECT COUNT(*) AS c FROM `nteam_user` WHERE `invite_by`=?", array($uid));
$invited = $invRow ? (int)$invRow['c'] : 0;
$sumRow  = $DB->getRow("SELECT COALESCE(SUM(`amount`),0) AS s FROM `nteam_money_log` WHERE `user_id`=? AND `type`='agent'", array($uid));
$total   = (float)($sumRow ? $sumRow['s'] : 0);
$logs    = $DB->getAll("SELECT `amount`,`balance`,`memo`,`create_time` FROM `nteam_money_log`
                        WHERE `user_id`=? AND `type`='agent' ORDER BY `id` DESC LIMIT 30", array($uid));
if (!$logs) { $logs = array(); }

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : conf('Url');
$link   = $scheme . '://' . $host . '/user/register.php?ref=' . $uid;

account_head('代理中心', $u, 'agent');
?>

<!-- OneTool console/agent/index.html 原版渐变横幅 -->
<div class="block block-rounded bg-gd-dusk mb-4">
    <div class="block-content bg-white-5 text-center py-4">
        <h1 class="h2 fw-bold text-white mb-2">代理中心</h1>
        <h2 class="h5 fw-medium text-white-75 mb-0">邀请好友，充值返佣 · 当前比例 <?php echo (int)$rate; ?>%</h2>
    </div>
</div>

<!-- 原版 nav-tabs-block nav-justified 双 tab -->
<div class="block block-rounded overflow-hidden">
    <ul class="nav nav-tabs nav-tabs-block nav-justified" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" type="button" data-bs-toggle="tab" data-bs-target="#ot-agent-data" role="tab">
                <i class="fa fa-fw fa-list-alt opacity-50 me-1 d-none d-sm-inline-block"></i> 邀请数据
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" type="button" data-bs-toggle="tab" data-bs-target="#ot-agent-logs" role="tab">
                <i class="fa fa-fw fa-receipt opacity-50 me-1 d-none d-sm-inline-block"></i> 返佣明细
            </button>
        </li>
    </ul>
    <div class="tab-content p-3">
        <!-- 邀请数据 -->
        <div class="tab-pane active" id="ot-agent-data" role="tabpanel">
            <div class="row g-3">
                <div class="col-lg-6">
                    <div class="block block-rounded h-100 mb-0">
                        <div class="block-header block-header-default"><h3 class="block-title">专属邀请链接</h3></div>
                        <div class="block-content">
                            <p class="fs-sm text-muted">好友通过你的链接注册后即绑定邀请关系，此后每次在线充值都按 <b><?php echo (int)$rate; ?>%</b> 返到你的余额。</p>
                            <div class="input-group mb-3">
                                <input type="text" class="form-control" id="otInviteLink" value="<?php echo wk_h($link); ?>" readonly>
                                <button class="btn btn-alt-primary" type="button" onclick="otCopyInvite()"><i class="fa fa-copy me-1"></i>复制</button>
                            </div>
                            <div id="otCopyTip" class="fs-sm text-success" style="display:none">已复制到剪贴板</div>
                            <p class="fs-xs text-muted mb-0">邀请关系以注册时的 ref 参数为准，返佣走 <code>ref</code> 幂等，同一笔充值不会重复返。</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <a class="block block-rounded block-link-rotate overflow-hidden h-100 mb-0" href="javascript:void(0)">
                                <div class="block-content block-content-full">
                                    <div class="row g-4 py-3 text-center">
                                        <div class="col-12 fs-3 fw-bold text-primary"><?php echo $invited; ?></div>
                                        <div class="col-12 fs-sm fw-semibold text-uppercase text-muted">已邀请人数</div>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a class="block block-rounded block-link-rotate overflow-hidden h-100 mb-0" href="/user/money.php">
                                <div class="block-content block-content-full">
                                    <div class="row g-4 py-3 text-center">
                                        <div class="col-12 fs-3 fw-bold text-success">¥<?php echo number_format($total, 2); ?></div>
                                        <div class="col-12 fs-sm fw-semibold text-uppercase text-muted">累计返佣</div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- 返佣明细 -->
        <div class="tab-pane" id="ot-agent-logs" role="tabpanel">
            <div class="block block-rounded mb-0">
                <div class="block-header block-header-default">
                    <h3 class="block-title">返佣明细</h3>
                    <div class="block-options"><small class="text-muted">最近 30 条</small></div>
                </div>
                <div class="block-content block-content-full">
                    <?php if (!$logs) { ?>
                    <div class="alert alert-info mb-0">还没有返佣记录，把邀请链接发出去吧。</div>
                    <?php } else { ?>
                    <div class="table-responsive">
                        <table class="table table-vcenter table-sm">
                            <thead><tr><th>说明</th><th class="text-end">返佣</th><th class="text-end">返后余额</th><th>时间</th></tr></thead>
                            <tbody>
                            <?php foreach ($logs as $r) { ?>
                            <tr>
                                <td class="fs-sm"><?php echo wk_h($r['memo']); ?></td>
                                <td class="text-end fw-semibold text-success">+¥<?php echo number_format((float)$r['amount'], 2); ?></td>
                                <td class="text-end text-muted fs-sm">¥<?php echo number_format((float)$r['balance'], 2); ?></td>
                                <td class="text-muted fs-xs"><?php echo date('Y-m-d H:i', (int)$r['create_time']); ?></td>
                            </tr>
                            <?php } ?>
                            </tbody>
                        </table>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function otCopyInvite(){
    var el = document.getElementById('otInviteLink');
    var tip = document.getElementById('otCopyTip');
    el.select(); el.setSelectionRange(0, 99999);
    var ok = false;
    try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
    if (!ok && navigator.clipboard) {
        navigator.clipboard.writeText(el.value).then(function(){ tip.style.display='block'; });
        return;
    }
    tip.style.display = ok ? 'block' : 'none';
    setTimeout(function(){ tip.style.display='none'; }, 2000);
}
</script>
<?php account_foot(); ?>
