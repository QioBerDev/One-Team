<?php
/**
 * 用户中心 · 公共布局（OneTool / Codebase 5.0 原生版式）
 *
 * account_head()  — 控制台壳（侧栏 + 页头 + 主题切换），登录后的页面用
 * auth_head()     — OneTool 原版 login.html / reg.html 分栏：左大图 hero + 右窄栏表单
 * 两套共用 account_notice() / account_redirect_target()
 *
 * 资源全部走 /assets/onetool/，与官网首页的 OneUI 5.6 隔离，互不污染。
 * 默认主题与「允许访客切换」开关由后台 Admin/set.php 配置（User_Theme / User_Theme_Switch）。
 */

if (!defined('IN_CRONLITE')) {
    exit('Access denied');
}
require_once __DIR__ . '/../Common/works_helper.php';
require_once __DIR__ . '/../Common/AuthForms.php';
require_once __DIR__ . '/../Common/OtConfig.php';
require_once __DIR__ . '/../Common/ModeSwitch.php';

/** 当前用户是否为管理员：统一走 ModeSwitch，普通用户恒 false */
function ot_is_admin($u)
{
    return ms_is_admin($u);
}

/** OneTool 静态资源前缀，改一处即可 */
function ot_asset($path)
{
    return '/assets/onetool/' . ltrim($path, '/');
}

/** 页脚 / 品牌用到的站点信息 */
function ot_icp()
{
    $icp = trim((string)conf('ICP'));
    return $icp !== '' ? $icp : conf('SiteName');
}

/* ---------------- 控制台壳（侧栏 + 页头） ---------------- */
function account_head($title, $user = null, $active = '')
{
    $siteName = conf('Name');
    $siteSub  = conf('SiteName');
    $nick = $user ? ($user['nickname'] !== '' ? $user['nickname'] : $user['username']) : '';
    $uid  = $user ? (int)$user['id'] : 0;
    $balance = ($user && isset($user['money'])) ? (float)$user['money'] : 0.0;
    $vipLevel = ($user && isset($user['vip_level'])) ? (int)$user['vip_level'] : 0;
    $kfUrl = function_exists('ot_kf_url') ? ot_kf_url() : '';
    $cacheOpen = function_exists('ot_on') && ot_on('Cache_Open');

    /* 侧栏：一级 + 自助商店子菜单，照 OneTool console/head.html 的结构 */
    $mainNav = array(
        'home'   => array('/user/index.php',  'fa fa-house-user',    '用户中心'),
        'vip'    => array('/user/vip.php',    'fa fa-chess-queen',   '会员开通'),
        'money'  => array('/user/money.php',  'si si-wallet',        '充值余额'),
        'card'   => array('/user/card.php',   'fa fa-ticket',        '卡密激活'),
        'shop'   => array('/works.php',       'si si-basket-loaded', '选购源码'),
        'orders' => array('/user/order.php',  'fa fa-receipt',       '我的订单'),
        'account'=> array('/user/account.php','si si-user',          '账户设置'),
    );
    /* 后台控制器：卡密 / 余额 / 代理 / 帮助中心 入口开关 */
    if (!ot_on('Money_Open')) { unset($mainNav['money']); }
    if (!ot_on('Card_Open'))  { unset($mainNav['card']); }
    if (ot_on('Agent_Open'))  { $mainNav['agent'] = array('/user/agent.php', 'si si-users',    '代理中心'); }
    if (ot_on('Faq_Open'))    { $mainNav['faq']   = array('/user/faq.php',   'si si-question', '帮助中心'); }
    $shopKeys = array_values(array_intersect(array('vip', 'money', 'card', 'shop'), array_keys($mainNav)));
    ?><!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="referrer" content="no-referrer">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?php echo wk_h($title); ?> - <?php echo wk_h($siteName); ?></title>
    <meta name="robots" content="noindex,nofollow">
    <link rel="shortcut icon" href="/assets/media/various/favicon.png">
    <link rel="stylesheet" href="<?php echo ot_asset('css/nprogress.min.css'); ?>">
    <link rel="stylesheet" id="css-main" href="<?php echo ot_asset('css/codebase.min-5.0.css'); ?>">
<?php $__otTheme = ot_theme_href(ot_user_theme()); if ($__otTheme !== 'default') { ?>
    <link rel="stylesheet" id="css-theme" href="<?php echo wk_h($__otTheme); ?>">
<?php } ?>
    <link rel="stylesheet" href="/assets/css/user-onetool.css?v=20261106i">
</head>
<body>
<div id="page-container" class="sidebar-o enable-page-overlay side-scroll page-header-fixed main-content-boxed<?php echo ot_user_theme_switch() ? ' remember-theme' : ''; ?>">

    <!-- ============ 侧栏 ============ -->
    <nav id="sidebar">
        <div class="sidebar-content">
            <div class="content-header justify-content-lg-center">
                <div>
                    <a class="link-fx fw-bold tracking-wide mx-auto" href="/index.php">
                        <span class="smini-hidden">
                            <i class="fa fa-fire text-primary"></i>
                            <span class="fs-5 text-dual"><?php echo wk_h($siteName); ?></span>
                        </span>
                        <span class="smini-visible fw-bold fs-lg text-dual"><?php echo wk_h(mb_substr($siteName, 0, 2)); ?></span>
                    </a>
                </div>
                <div>
                    <button class="btn btn-sm btn-alt-danger d-lg-none" data-action="sidebar_close" data-toggle="layout" type="button">
                        <i class="fa fa-fw fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="js-sidebar-scroll">
            <div class="content-side content-side-user px-0 py-0">
                <div class="smini-visible-block animated fadeIn px-2">
                    <?php echo user_avatar_html($user, 'img-avatar img-avatar32', 'bg-primary text-white'); ?>
                </div>
                <div class="smini-hidden text-center mx-auto">
                    <a class="img-link" href="/user/account.php">
                        <?php echo user_avatar_html($user, 'img-avatar', 'bg-primary text-white'); ?>
                    </a>
                    <ul class="list-inline mt-3 mb-0">
                        <li class="list-inline-item">
                            <a class="link-fx text-dual fs-sm fw-semibold text-uppercase" href="/user/account.php"><?php echo wk_h($nick); ?></a>
                        </li>
                        <li class="list-inline-item">
                            <a class="link-fx text-dual" data-action="dark_mode_toggle" data-toggle="layout" href="javascript:void(0)" title="深色模式">
                                <i class="fa fa-burn"></i>
                            </a>
                        </li>
                        <li class="list-inline-item">
                            <a href="javascript:;" onclick="otLogout();" class="link-fx text-dual" title="退出登录">
                                <i class="fa fa-sign-out-alt"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="content-side content-side-full" id="nav-main">
                <ul class="nav-main">
                    <li class="nav-main-heading">控制台</li>
                    <li class="nav-main-item">
                        <a class="nav-main-link<?php echo $active === 'home' ? ' active' : ''; ?>" href="<?php echo $mainNav['home'][0]; ?>">
                            <i class="nav-main-link-icon <?php echo $mainNav['home'][1]; ?>"></i>
                            <span class="nav-main-link-name">用户中心</span>
                        </a>
                    </li>

                    <!-- 自助商店：OneTool 原生二级菜单 -->
                    <li class="nav-main-item<?php echo in_array($active, array('vip', 'money', 'card', 'shop'), true) ? ' open' : ''; ?>">
                        <a aria-expanded="true" aria-haspopup="true" class="nav-main-link nav-main-link-submenu" data-toggle="submenu" href="#">
                            <i class="nav-main-link-icon si si-basket-loaded"></i>
                            <span class="nav-main-link-name">自助商店</span>
                        </a>
                        <ul class="nav-main-submenu">
                            <?php foreach ($shopKeys as $k) { ?>
                            <li class="nav-main-item">
                                <a class="nav-main-link<?php echo $active === $k ? ' active' : ''; ?>" href="<?php echo $mainNav[$k][0]; ?>">
                                    <i class="nav-main-link-icon <?php echo $mainNav[$k][1]; ?>"></i>
                                    <span class="nav-main-link-name"><?php echo $mainNav[$k][2]; ?></span>
                                </a>
                            </li>
                            <?php } ?>
                        </ul>
                    </li>

                    <li class="nav-main-item">
                        <a class="nav-main-link<?php echo $active === 'orders' ? ' active' : ''; ?>" href="<?php echo $mainNav['orders'][0]; ?>">
                            <i class="nav-main-link-icon <?php echo $mainNav['orders'][1]; ?>"></i>
                            <span class="nav-main-link-name">我的订单</span>
                        </a>
                    </li>
                    <li class="nav-main-item">
                        <a class="nav-main-link<?php echo $active === 'account' ? ' active' : ''; ?>" href="<?php echo $mainNav['account'][0]; ?>">
                            <i class="nav-main-link-icon <?php echo $mainNav['account'][1]; ?>"></i>
                            <span class="nav-main-link-name">账户设置</span>
                        </a>
                    </li>

                    <li class="nav-main-heading text-center">- 更多功能敬请期待 -</li>
                    <li class="nav-main-heading">其他</li>
                    <?php if ($kfUrl !== '') { ?>
                    <li class="nav-main-item">
                        <a class="nav-main-link" href="<?php echo wk_h($kfUrl); ?>" target="_blank" rel="noopener">
                            <i class="nav-main-link-icon fa fa-headset"></i>
                            <span class="nav-main-link-name">联系客服</span>
                        </a>
                    </li>
                    <?php } ?>
                    <?php if ($cacheOpen) { ?>
                    <li class="nav-main-item">
                        <a class="nav-main-link" href="/user/cache.php">
                            <i class="nav-main-link-icon si si-refresh"></i>
                            <span class="nav-main-link-name">清理缓存</span>
                        </a>
                    </li>
                    <?php } ?>
                    <?php /* 参考 OneTool：只有管理员账号登录后显示「进入管理员操作台」 */ ?>
                    <?php if (ot_is_admin($user)) { ?>
                    <li class="nav-main-item">
                        <a class="nav-main-link ot-admin-switch" href="<?php echo htmlspecialchars(ms_ticket_url('admin'), ENT_QUOTES, 'UTF-8'); ?>">
                            <i class="nav-main-link-icon fa fa-gears"></i>
                            <span class="nav-main-link-name">进入管理员操作台</span>
                        </a>
                    </li>
                    <?php } ?>
                    <li class="nav-main-item">
                        <a class="nav-main-link" href="/index.php">
                            <i class="nav-main-link-icon si si-globe"></i>
                            <span class="nav-main-link-name">返回官网</span>
                        </a>
                    </li>
                    <li class="nav-main-item">
                        <a class="nav-main-link" href="/news.php">
                            <i class="nav-main-link-icon si si-speech"></i>
                            <span class="nav-main-link-name">公告动态</span>
                        </a>
                    </li>
                    <?php if (isset($mainNav['agent'])) { ?>
                    <li class="nav-main-item">
                        <a class="nav-main-link<?php echo $active === 'agent' ? ' active' : ''; ?>" href="<?php echo $mainNav['agent'][0]; ?>">
                            <i class="nav-main-link-icon <?php echo $mainNav['agent'][1]; ?>"></i>
                            <span class="nav-main-link-name"><?php echo $mainNav['agent'][2]; ?></span>
                        </a>
                    </li>
                    <?php } ?>
                    <?php if (isset($mainNav['faq'])) { ?>
                    <li class="nav-main-item">
                        <a class="nav-main-link<?php echo $active === 'faq' ? ' active' : ''; ?>" href="<?php echo $mainNav['faq'][0]; ?>">
                            <i class="nav-main-link-icon <?php echo $mainNav['faq'][1]; ?>"></i>
                            <span class="nav-main-link-name"><?php echo $mainNav['faq'][2]; ?></span>
                        </a>
                    </li>
                    <?php } else { ?>
                    <li class="nav-main-item">
                        <a class="nav-main-link" href="/about.php">
                            <i class="nav-main-link-icon si si-info"></i>
                            <span class="nav-main-link-name">关于我们</span>
                        </a>
                    </li>
                    <?php } ?>
                    <li class="nav-main-item">
                        <a class="nav-main-link" href="javascript:;" onclick="otLogout();">
                            <i class="nav-main-link-icon si si-logout"></i>
                            <span class="nav-main-link-name">退出登录</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- ============ 页头 ============ -->
    <header id="page-header">
        <div class="content-header">
            <div class="space-x-1">
                <button type="button" class="btn btn-sm btn-alt-secondary" data-toggle="layout" data-action="sidebar_toggle">
                    <i class="fa fa-fw fa-bars"></i>
                </button>
                <a class="btn btn-sm btn-alt-secondary" href="/index.php" title="返回官网"><i class="fa fa-fw fa-house"></i></a>
                <?php ot_theme_picker('page-header-themes-dropdown'); ?>
            </div>
            <div class="space-x-1">
                <?php if (ot_is_admin($user)) { ?>
                <a class="ot-admin-switch-btn" href="<?php echo htmlspecialchars(ms_ticket_url('admin'), ENT_QUOTES, 'UTF-8'); ?>" title="进入管理员操作台">
                    <i class="fa fa-fw fa-gears me-1"></i>进入管理员操作台
                </a>
                <?php } ?>
                <?php if (ot_on('Money_Open')) { ?>
                <a class="btn btn-sm btn-alt-secondary d-none d-sm-inline-block" href="/user/money.php" title="账户余额">
                    <i class="fa fa-wallet me-1"></i><span class="fw-semibold">¥<?php echo number_format($balance, 2); ?></span>
                </a>
                <?php } ?>
                <div class="dropdown d-inline-block">
                    <button type="button" class="btn btn-sm btn-alt-secondary" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fa fa-user d-sm-none"></i>
                        <span class="d-none d-sm-inline-block fw-semibold"><?php echo wk_h($nick); ?></span>
                        <i class="fa fa-angle-down opacity-50 ms-1"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-md dropdown-menu-end p-0" aria-labelledby="page-header-user-dropdown">
                        <div class="px-2 py-3 bg-body-light rounded-top">
                            <h5 class="h6 text-center mb-0"><?php echo wk_h($nick); ?></h5>
                            <p class="text-center fs-sm text-muted mb-0">
                                UID: <?php echo $uid; ?> · <?php echo $vipLevel >= 2 ? 'SVIP' : ($vipLevel === 1 ? 'VIP' : '普通用户'); ?>
                            </p>
                        </div>
                        <div class="p-2">
                            <a href="/user/account.php" class="dropdown-item d-flex align-items-center justify-content-between space-x-1">
                                <span>账户设置</span><i class="fa fa-fw fa-user-cog opacity-25"></i>
                            </a>
                            <?php if (ot_on('Money_Open')) { ?>
                            <a href="/user/money.php" class="dropdown-item d-flex align-items-center justify-content-between space-x-1">
                                <span>我的余额</span><i class="fa fa-fw fa-wallet opacity-25"></i>
                            </a>
                            <?php } ?>
                            <?php if ($kfUrl !== '') { ?>
                            <a href="<?php echo wk_h($kfUrl); ?>" target="_blank" rel="noopener" class="dropdown-item d-flex align-items-center justify-content-between space-x-1">
                                <span>联系客服</span><i class="fa fa-fw fa-headset opacity-25"></i>
                            </a>
                            <?php } ?>
                            <?php if ($cacheOpen) { ?>
                            <a href="/user/cache.php" class="dropdown-item d-flex align-items-center justify-content-between space-x-1">
                                <span>清理缓存</span><i class="fa fa-fw fa-rotate opacity-25"></i>
                            </a>
                            <?php } ?>
                            <div class="dropdown-divider"></div>
                            <a href="javascript:;" onclick="otLogout();" class="dropdown-item d-flex align-items-center justify-content-between space-x-1">
                                <span>注销登录</span><i class="fa fa-fw fa-sign-out-alt opacity-25"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="page-header-loader" class="overlay-header bg-primary">
            <div class="content-header">
                <div class="w-100 text-center"><i class="far fa-sun fa-spin text-white"></i></div>
            </div>
        </div>
    </header>

    <main id="main-container">
        <div class="content ot-console" id="pjax-container">
<?php
}

function account_foot()
{
    ?>
        </div>
    </main>
    <footer id="page-footer">
        <div class="content py-3">
            <div class="row fs-sm">
                <div class="col-sm-6 order-sm-2 py-1 text-center text-sm-end">
                    <span class="text-muted fw-semibold"><?php echo wk_h(ot_icp()); ?></span>
                </div>
                <div class="col-sm-6 order-sm-1 py-1 text-center text-sm-start">
                    &copy; <span data-toggle="year-copy"></span>
                    <a class="fw-semibold" href="/index.php"><?php echo wk_h(conf('Name')); ?></a>
                </div>
            </div>
        </div>
    </footer>
</div>
<script src="<?php echo ot_asset('js/codebase.app.min-5.0.js'); ?>"></script>
<script src="<?php echo ot_asset('js/lib/jquery.min.js'); ?>"></script>
<script src="<?php echo ot_asset('js/plugins/bootstrap-notify/bootstrap-notify.min.js'); ?>"></script>
<script src="<?php echo ot_asset('js/jquery.slimscroll.min.js'); ?>"></script>
<script src="/assets/layer/layer.js"></script>
<script src="<?php echo ot_asset('js/app-one.js'); ?>"></script>
<?php
    /* 公告强提醒：layer.alert + 10 秒倒计时 + 「不再显示」cookie（照 console/index.html） */
    if (ot_on('Notice_Open') && trim((string)ot_cfg('Notice_Content')) !== '') {
        $nTitle = (string)ot_cfg('Notice_Title');
        $nBody  = (string)ot_cfg('Notice_Content');
        $nKey   = 'ot_notice_' . substr(md5($nTitle . '|' . $nBody), 0, 12);
        $nOnce  = ot_on('Notice_Once') ? 1 : 0;
?>
<script>
(function () {
    var KEY = <?php echo json_encode($nKey, JSON_UNESCAPED_UNICODE); ?>;
    var ONCE = <?php echo (int)$nOnce; ?>;
    function ck(n) { var m = document.cookie.match(new RegExp('(^| )' + n + '=([^;]*)(;|$)')); return m ? m[2] : ''; }
    function setCk(n, v, d) { document.cookie = n + '=' + v + ';expires=' + new Date(Date.now() + d * 86400000).toUTCString() + ';path=/'; }
    if (ONCE && ck(KEY) === '0') return;
    if (typeof layer === 'undefined') return;
    layer.alert(<?php echo json_encode($nBody, JSON_UNESCAPED_UNICODE); ?>, {
        title: <?php echo json_encode($nTitle, JSON_UNESCAPED_UNICODE); ?>,
        time: 10000,
        area: ['420px', 'auto'],
        btn: ONCE ? ['确定', '不再显示'] : ['确定'],
        success: function (layero, index) {
            var t = 10, timer = setInterval(function () {
                if (--t > 0) layer.title(t + ' 秒后关闭', index); else clearInterval(timer);
            }, 1000);
            layero.data('otTimer', timer);
            layer.title(t + ' 秒后关闭', index);
        },
        end: function () { },
        btn2: function () { if (ONCE) setCk(KEY, '0', 7); }
    });
})();
</script>
<?php } ?>
</body>
</html>
<?php
}

/* ---------------- OneTool 原版 login.html / reg.html 分栏 ----------------
 * .bg-image(photo34@2x.jpg) > .row.bg-black-50（登录）/ bg-earth-op（注册）
 *   左：.hero-static.col-md-6.col-xl-8  品牌标语 + Copyright
 *   右：.hero-static.col-md-6.col-xl-4.bg-body-extra-light  品牌头 + 表单
 * 1:1 对齐原版类名，主题切换画笔由后台开关控制是否出现。
 */
function auth_head($title, $sub, $page = 'login')
{
    $siteName = conf('Name');
    $theme    = ot_theme_href(ot_user_theme());
    $boxed    = 'main-content-boxed' . (ot_user_theme_switch() ? ' remember-theme' : '');
    $bg       = ot_hero_overlay($page);
    $hero     = ot_hero_url();
    $fit      = ot_hero_fit();
    $pos      = ot_hero_position();
    $isReg    = $page === 'register';
    ?><!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="referrer" content="no-referrer">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?php echo wk_h($title); ?> - <?php echo wk_h($siteName); ?></title>
    <meta name="robots" content="noindex,nofollow">
    <link rel="shortcut icon" href="/assets/media/various/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo ot_asset('css/nprogress.min.css'); ?>">
    <link rel="stylesheet" id="css-main" href="<?php echo ot_asset('css/codebase.min-5.0.css'); ?>">
<?php if ($theme !== 'default') { ?>
    <link rel="stylesheet" id="css-theme" href="<?php echo wk_h($theme); ?>">
<?php } ?>
    <link rel="stylesheet" href="/assets/css/auth-panel.css?v=20261106f">
</head>
<body>
<div class="<?php echo $boxed; ?>" id="page-container">
    <main id="main-container">
        <?php /* 背景只铺在左栏：以前铺在整行，右侧 bg-body-extra-light 白表单会把图的
                 右半（人物）盖掉，看起来就是「大图显示不全」。覆盖色改成独立叠层。 */ ?>
        <div class="bg-image ot-auth-bg">
            <div class="row mx-0 ot-auth-row">
                <div class="hero-static col-md-6 col-xl-8 d-none d-md-flex align-items-md-end ot-auth-hero"
                     style="background-image: url('<?php echo wk_h($hero); ?>'); background-size: <?php echo $fit; ?>; background-position: <?php echo $pos; ?>; background-repeat: no-repeat;">
                    <div class="ot-auth-hero-shade <?php echo $bg; ?>"></div>
                    <div class="p-4 ot-auth-hero-copy">
                        <?php if ($isReg) { ?>
                        <p class="fs-3 fw-semibold text-white mb-1 ot-auth-slogan"><?php echo wk_h(ot_cfg('Auth_Slogan_Reg')); ?></p>
                        <p class="fs-5 text-white fw-medium"><i class="fa fa-angle-right opacity-50"></i> Create your account today !</p>
                        <?php } else { ?>
                        <p class="fs-3 fw-semibold text-white ot-auth-slogan"><?php echo wk_h(ot_cfg('Auth_Slogan_Login')); ?></p>
                        <?php } ?>
                        <p class="text-white-75 fw-medium">Copyright &copy; <span data-toggle="year-copy"></span> <?php echo wk_h(ot_icp()); ?></p>
                    </div>
                </div>
                <div class="hero-static col-md-6 col-xl-4 d-flex align-items-center bg-body-extra-light ot-auth-col">
                    <div class="content content-full">
                        <?php /* 右上角醒目关闭按钮：带文字 + 红色实心，普通用户一眼能认出 */ ?>
                        <a class="ot-auth-close" href="/index.php" title="关闭并返回官网" aria-label="关闭并返回官网">
                            <i class="fa fa-fw fa-xmark"></i><span>关闭</span>
                        </a>
                        <div class="px-4 py-2 mb-4 ot-auth-brand">
                            <a class="link-fx fw-bold" href="/index.php">
                                <i class="fa fa-fire"></i>
                                <span class="fs-4 text-body-color"><?php echo wk_h($siteName); ?></span>
                            </a>
                            <h1 class="h3 fw-bold mt-4 mb-2"><?php echo wk_h($title); ?></h1>
                            <h2 class="h5 fw-medium text-muted mb-0"><?php echo wk_h($sub); ?></h2>
                        </div>
                        <div class="ot-auth-form px-4">
<?php
}

function auth_foot()
{
    ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
<script src="<?php echo ot_asset('js/codebase.app.min-5.0.js'); ?>"></script>
<script src="<?php echo ot_asset('js/lib/jquery.min.js'); ?>"></script>
<script src="<?php echo ot_asset('js/plugins/bootstrap-notify/bootstrap-notify.min.js'); ?>"></script>
<script src="<?php echo ot_asset('js/nprogress.min.js'); ?>"></script>
<script src="/assets/layer/layer.js"></script>
<script src="<?php echo ot_asset('js/app-one.js'); ?>"></script>
<script src="/assets/js/auth-panel.js?v=20261106e"></script>
</body>
</html>
<?php
}

/** 后台开关关闭某个入口时，用它渲染统一的「未开放」页，而不是 404 或裸跳 */
function ot_feature_closed($u, $title, $msg, $active = '')
{
    account_head($title, $u, $active);
    echo '<div class="block block-rounded" style="max-width:620px;margin:0 auto">'
       . '<div class="block-content block-content-full text-center py-5">'
       . '<i class="fa fa-lock fa-2x text-muted d-block mb-3"></i>'
       . '<p class="fw-semibold mb-4">' . wk_h($msg) . '</p>'
       . '<a class="btn btn-alt-primary" href="/user/index.php">返回用户中心</a>'
       . '</div></div>';
    account_foot();
    exit;
}

/** 顶部提示条：Codebase 原生 alert，两种壳通用 */
function account_notice($msg, $type = 'ok')
{
    if ($msg === '') {
        return;
    }
    $ok = $type === 'ok';
    echo '<div class="alert ' . ($ok ? 'alert-success' : 'alert-danger')
        . ' d-flex align-items-center" role="alert"><i class="fa '
        . ($ok ? 'fa-circle-check' : 'fa-circle-exclamation') . ' me-2"></i><div>'
        . wk_h($msg) . '</div></div>';
}

/** 未登录时把当前地址记住，登录后原地回来 */
function account_redirect_target($fallback = '/user/index.php')
{
    $r = isset($_REQUEST['redirect']) ? (string)$_REQUEST['redirect'] : '';
    if ($r === '' || strpos($r, '//') !== false || strpos($r, '\\') !== false) {
        return $fallback;
    }
    if ($r[0] !== '/') {
        return $fallback;
    }
    return $r;
}
