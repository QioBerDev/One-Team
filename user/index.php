<?php
/**
 * 个人中心（OneTool console/index.html 版式）
 * 横幅 / 原版六宫格 / 本站常用功能 / 公告 / 用户卡 / 站点四统计 / 最近订单
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

$u   = user_current();
$uid = (int)$u['id'];

$rows = $DB->getAll(
    "SELECT * FROM `nteam_order` WHERE `user_id`=? ORDER BY `id` DESC LIMIT 100",
    array($uid)
);
if (!is_array($rows)) { $rows = array(); }

$wait = 0; $paid = 0; $paidMoney = 0.0;
foreach ($rows as $r) {
    if ((int)$r['status'] === 0) { $wait++; }
    elseif ((int)$r['status'] === 1) { $paid++; $paidMoney += (float)$r['money']; }
}

$vipLevel  = isset($u['vip_level']) ? (int)$u['vip_level'] : 0;
$vipExpire = isset($u['vip_expire']) ? (int)$u['vip_expire'] : 0;
$balance   = wallet_balance($DB, $uid);

$notices = $DB->getAll(
    "SELECT `id`, `title`, `summary`, `published_at` FROM `nteam_content`
      WHERE `type`='notice' AND `status`=1
      ORDER BY `is_pinned` DESC, `published_at` DESC, `id` DESC LIMIT 6"
);
if (!is_array($notices)) { $notices = array(); }

/* OneTool console/index.html 四个统计是站点级数据：用户数 / 托管账号 / 任务 / 运行次数。
   本站映射成：用户数量 / 上架项目 / 我的订单 / 账户余额。 */
$userCnt = (int)$DB->getColumn("SELECT COUNT(*) FROM `nteam_user`");
$workCnt = (int)$DB->getColumn("SELECT COUNT(*) FROM `nteam_project_list` WHERE `Audit_status`=1");
$nick = $u['nickname'] !== '' ? $u['nickname'] : $u['username'];

$kfUrl = function_exists('ot_kf_url') ? ot_kf_url() : '';

account_head('个人中心', $u, 'home');
?>

<!-- 横幅：OneTool 原版 bg-gd-dusk -->
<div class="block block-rounded bg-gd-dusk mb-4">
    <div class="block-content bg-white-5 py-4 text-center">
        <h1 class="h2 fw-bold text-white mb-2"><?php echo wk_h(conf('Name')); ?></h1>
        <h2 class="h5 fw-medium text-white-75 mb-0"><?php echo wk_h(conf('SiteName')); ?></h2>
    </div>
</div>

<!-- OneTool console/index.html 原版六宫格 -->
<div class="row g-3 mb-2 ot-quick">
    <?php
    $quick = array(
        array('/user/vip.php',     'fa fa-chess-queen',  '会员开通', 'ribbon ribbon-bookmark ribbon-danger ribbon-left',  'Hot !'),
        array('/user/agent.php',   'fa fa-graduation-cap','代理开通', 'ribbon ribbon-bookmark ribbon-primary ribbon-left', ''),
        array('/user/money.php',   'fa fa-wallet',       '充值余额', '', ''),
        array('/user/faq.php',     'fa fa-hands-helping','帮助中心', '', ''),
        array('/user/account.php', 'fa fa-id-card',      '个人资料', '', ''),
    );
    /* 后台开关：关闭的入口不渲染，联系客服按客服 QQ 是否可用决定 */
    if (!ot_on('Agent_Open'))  { unset($quick[1]); }
    if (!ot_on('Money_Open'))  { unset($quick[2]); }
    if (!ot_on('Faq_Open'))    { unset($quick[3]); }
    if ($kfUrl !== '') {
        $quick[] = array($kfUrl, 'fa fa-headset', '联系客服', '', '');
    }
    /* 开关关掉几项后仍补齐 6 个，保持原版六宫格整齐 */
    $fill = array(
        array('/user/order.php', 'fa fa-receipt',       '我的订单', '', ''),
        array('/works.php',      'si si-basket-loaded', '选购源码', '', ''),
        array('/news.php',       'fa fa-bullhorn',      '公告动态', '', ''),
    );
    foreach ($fill as $f) {
        if (count($quick) >= 6) { break; }
        $quick[] = $f;
    }
    foreach ($quick as $q) { ?>
    <div class="col-6 col-md-4 col-xl-2">
        <a class="block block-rounded block-link-shadow text-center h-100 mb-0"
           href="<?php echo wk_h($q[0]); ?>"<?php echo strpos($q[0], 'http') === 0 || strpos($q[0], 'tencent:') === 0 || strpos($q[0], 'mqq:') === 0 ? ' target="_blank" rel="noopener"' : ''; ?>>
            <div class="block-content py-4 <?php echo $q[3]; ?>">
                <?php if ($q[4] !== '') { ?><div class="ribbon-box opacity-75"><?php echo $q[4]; ?></div><?php } ?>
                <p class="my-3"><i class="<?php echo $q[1]; ?> fa-2x"></i></p>
                <p class="fw-semibold fs-sm mb-0"><?php echo $q[2]; ?></p>
            </div>
        </a>
    </div>
    <?php } ?>
</div>

<!-- 本站常用功能：与上面六宫格分开成整齐的一行 4 列，避免挤成一团 -->
<div class="d-flex align-items-center justify-content-between mb-2 mt-1">
    <span class="fs-sm fw-semibold text-muted text-uppercase"><i class="fa fa-grip me-1"></i>常用功能</span>
    <a class="fs-xs text-muted" href="/works.php">去选购源码 <i class="fa fa-angle-right ms-1"></i></a>
</div>
<div class="row g-3 mb-4 ot-quick">
    <?php
    $quick2 = array(
        array('/user/card.php',  'fa fa-ticket',   '卡密激活'),
        array('/user/order.php', 'fa fa-receipt',  '我的订单'),
        array('/news.php',       'fa fa-bullhorn', '公告动态'),
        array('/works.php',      'si si-basket-loaded', '选购源码'),
    );
    if (!ot_on('Card_Open')) { unset($quick2[0]); }
    foreach ($quick2 as $q) { ?>
    <div class="col-6 col-md-3 col-xl-3">
        <a class="block block-rounded block-link-shadow text-center h-100 mb-0" href="<?php echo $q[0]; ?>">
            <div class="block-content py-3">
                <p class="mb-2"><i class="<?php echo $q[1]; ?> fa-2x opacity-75"></i></p>
                <p class="fw-semibold fs-sm mb-0"><?php echo $q[2]; ?></p>
            </div>
        </a>
    </div>
    <?php } ?>
</div>

<div class="row g-3 mb-4">
    <!-- 公告通知 -->
    <div class="col-lg-8">
        <div class="block block-rounded h-100 mb-0">
            <div class="block-header block-header-default">
                <h3 class="block-title"><i class="fa fa-bullhorn me-1 text-muted"></i> 公告通知</h3>
            </div>
            <div class="block-content block-content-full" data-toggle="slimscroll" data-height="259px">
                <?php if (!$notices) { ?>
                <div class="text-center text-muted fs-sm py-4">
                    <i class="si si-disc fa-2x d-block mb-2 opacity-50"></i>暂时还没有公告哦
                </div>
                <?php } else { foreach ($notices as $n) { ?>
                <div class="border-start border-4 rounded-2 border-primary mb-3">
                    <div class="rounded p-3 bg-body-light">
                        <a class="fw-semibold text-dual" href="/news.php?id=<?php echo (int)$n['id']; ?>" style="text-decoration:none">
                            <?php echo wk_h($n['title']); ?>
                        </a>
                        <p class="mt-2 mb-1 fs-sm text-muted"><?php echo wk_h($n['summary']); ?></p>
                        <p class="mb-0 fs-xs text-muted"><?php echo date('Y-m-d', (int)$n['published_at']); ?></p>
                    </div>
                </div>
                <?php } } ?>
            </div>
        </div>
    </div>

    <!-- 用户卡：OneTool 原版 bg-gd-dusk + bg-black-25 -->
    <div class="col-lg-4">
        <a class="block block-rounded block-link-shadow text-center h-100 mb-0" href="/user/account.php">
            <div class="block-content bg-gd-dusk">
                <div class="push">
                    <?php echo user_avatar_html($u, 'img-avatar img-avatar-thumb', 'bg-white-25 text-white'); ?>
                </div>
                <div class="pull-x pull-b py-3 bg-black-25">
                    <div class="fw-semibold mb-1 text-white">
                        <i class="fa fa-star text-white-75 ms-1"></i> <?php echo wk_h($nick); ?>
                        <span class="fs-xs">UID: <?php echo $uid; ?></span>
                    </div>
                    <?php if ($vipLevel > 0 && $vipExpire > time()) { ?>
                    <div class="fs-sm text-white-75"><?php echo vip_level_name($vipLevel); ?>会员 - <?php echo date('Y-m-d', $vipExpire); ?></div>
                    <?php } else { ?>
                    <div class="fs-sm text-white-75">普通用户</div>
                    <?php } ?>
                    <div class="fs-sm text-white-75">共有 <?php echo count($rows); ?> 个订单 | 已支付 <?php echo $paid; ?> 个</div>
                </div>
            </div>
            <div class="block-content">
                <div class="row items-push text-center mb-0">
                    <div class="col-6">
                        <div class="mb-1"><i class="si si-user fa-2x opacity-50"></i></div>
                        <div class="fs-sm fw-medium text-muted"><?php echo vip_level_name($vipLevel); ?></div>
                    </div>
                    <div class="col-6">
                        <div class="mb-1"><i class="si si-wallet fa-2x opacity-50"></i></div>
                        <div class="fs-sm fw-medium text-muted">¥<?php echo number_format($balance, 2); ?> 元</div>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- 四个统计：OneTool 原版站点级数据 -->
<div class="row g-3 mb-4 ot-stat">
    <?php
    $stats = array(
        array($userCnt, '用户数量', 'si si-users',            'text-primary-light',   'text-primary'),
        array($workCnt, '上架项目', 'si si-trophy',           'text-earth-light',     'text-earth'),
        array(count($rows), '我的订单', 'si si-bar-chart',    'text-elegance-light',  'text-elegance'),
        array('¥' . number_format($balance, 2), '账户余额', 'si si-wallet',        'text-corporate-light', 'text-pulse'),
    );
    foreach ($stats as $s) { ?>
    <div class="col-6 col-xl-3">
        <div class="block block-rounded block-link-pop text-end h-100 mb-0">
            <div class="block-content block-content-full d-sm-flex justify-content-between align-items-center border-black-op-b border-3">
                <div class="d-none d-sm-block"><i class="<?php echo $s[2]; ?> fa-2x <?php echo $s[3]; ?>"></i></div>
                <div class="text-end">
                    <div class="fs-3 fw-semibold <?php echo $s[4]; ?>"><?php echo $s[0]; ?></div>
                    <div class="fs-sm fw-semibold text-uppercase text-muted"><?php echo $s[1]; ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php } ?>
</div>

<!-- 最近订单 -->
<div class="block block-rounded">
    <div class="block-header block-header-default">
        <h3 class="block-title"><i class="fa fa-clock-rotate-left me-1 text-muted"></i> 最近订单</h3>
        <div class="block-options"><a class="btn btn-sm btn-alt-secondary" href="/user/order.php">查看全部</a></div>
    </div>
    <div class="block-content block-content-full" style="padding:0">
        <?php if (!$rows) { ?>
        <div class="text-center text-muted fs-sm py-4">
            <i class="fa fa-receipt fa-2x d-block mb-2 opacity-50"></i>
            还没有订单。去 <a href="/works.php">作品项目</a> 看看。
        </div>
        <?php } else { ?>
        <div class="table-responsive">
            <table class="table table-striped table-vcenter mb-0">
                <thead><tr><th>项目</th><th>订单号</th><th>金额</th><th>状态</th><th>时间</th></tr></thead>
                <tbody>
                    <?php foreach (array_slice($rows, 0, 8) as $r) {
                        $st = (int)$r['status'];
                        $bcls = array(0 => 'warning', 1 => 'success', 2 => 'secondary', 3 => 'secondary');
                        $b = isset($bcls[$st]) ? $bcls[$st] : 'secondary';
                    ?>
                    <tr>
                        <td><a class="fw-semibold text-dual" href="/user/order.php?no=<?php echo urlencode($r['out_trade_no']); ?>"><?php echo wk_h($r['project_name']); ?></a></td>
                        <td class="text-muted fs-xs"><?php echo wk_h($r['out_trade_no']); ?></td>
                        <td class="fw-semibold">¥<?php echo number_format((float)$r['money'], 2); ?></td>
                        <td><span class="badge bg-<?php echo $b; ?>"><?php echo epay_status_name($st); ?></span></td>
                        <td class="text-muted fs-xs"><?php echo date('m-d H:i', (int)$r['create_time']); ?></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <?php } ?>
    </div>
</div>

<script>
$(function () { if (window.Codebase) { Codebase.helpers('jq-slimscroll'); } });
</script>
<?php account_foot(); ?>
