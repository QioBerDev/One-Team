<?php
include("./Common/Core_brain.php");

/* 无 id 走列表页：作品从首页搬进导航后，/works.php 必须能单独立住。
   带 id 的详情逻辑原样留在下面，老链接 works.php?id=N 一行没改。 */
$id = isset($_GET['id']) ? trim((string)$_GET['id']) : '';
if ($id === '' || !ctype_digit($id)) {
    require __DIR__ . '/works-list.php';
    exit;
}

require_once __DIR__ . '/Common/works_helper.php';
require_once __DIR__ . '/Common/Pay.php';
require_once __DIR__ . '/Common/Schema.php';
require_once __DIR__ . '/Common/User.php';
require_once __DIR__ . '/Common/Wallet.php';
require_once __DIR__ . '/Common/Vip.php';
require_once __DIR__ . '/Common/OtConfig.php';

pay_schema_init($DB);

$sql = "SELECT * FROM nteam_project_list WHERE id='$id' LIMIT 1";
$rows = $DB->getRow($sql);
if (!$rows) { exit("该项目不存在！"); }

$projects = $DB->query($sql);
while ($project = $projects->fetch()) {
    $pId    = (int)$project['id'];
    $pName  = trim((string)$project['name']);
    $pType  = (isset($project['type']) && $project['type'] === 'web') ? '网页' : '程序';
    $pSketch = trim((string)$project['sketch']);
    $pDesc  = isset($project['descriptison']) ? (string)$project['descriptison'] : '';
    $pMoney = trim((string)$project['money']);
    $pVer   = trim((string)$project['version']);
    $pDate  = wk_date($project['intime'], false);
    $pUrl   = wk_url(isset($project['url']) ? $project['url'] : '');
    $pCover = wk_img($project['img']);
    $pLive  = ((string)$project['status'] === '1');
    $pInitial = wk_first($pName);

    $pShots = array();
    foreach (array('img2', 'img3') as $k) {
        $s = wk_img(isset($project[$k]) ? $project[$k] : '');
        if ($s !== '') { $pShots[] = $s; }
    }

    $rel = array();
    try {
        $rq = $DB->query("SELECT id, name, img, sketch, money, version, type, intime FROM nteam_project_list WHERE status=1 AND Audit_status=1 AND id<>" . $pId . " ORDER BY id DESC LIMIT 3");
        while ($r = $rq->fetch()) { $rel[] = $r; }
    } catch (Throwable $e) {
        $rel = array();
    }
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?php echo wk_h($pName);?> - <?php echo wk_h(conf('Name'));?> - <?php echo wk_h(conf('SiteName'));?></title>
    <meta name="keywords" content="<?php echo wk_h(conf('Keywords'));?>">
    <meta name="description" content="<?php echo wk_h($pSketch !== '' ? $pSketch : conf('Descriptison'));?>">
    <link rel="shortcut icon" href="/assets/media/various/favicon.png">
    <link rel="stylesheet" id="css-main" href="/assets/css/oneui.min-5.6.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/site-footer.v7.css?v=20261105q">
    <link rel="stylesheet" href="/assets/css/works-apple.css?v=20261105n">
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-9HQDQJJYW7"></script>
    <script>window.dataLayer = window.dataLayer || [];function gtag(){dataLayer.push(arguments);}gtag('js', new Date());gtag('config', 'G-9HQDQJJYW7');</script>
</head>
<body class="wk-page">
<div id="page-container" class="page-header-fixed main-content-boxed">
        <header id="page-header">
        <div class="content-header">
            <div class="d-flex align-items-center">
                <a class="btn btn-alt-secondary d-lg-none me-2" href="index.php" aria-label="返回首页"><i class="fa fa-house"></i></a>
                <a class="fw-bold fs-lg tracking-wider text-dual me-2" href="index.php">
                    <img class="hd-brand-logo" src="<?php echo htmlspecialchars(site_logo_url(), ENT_QUOTES, 'UTF-8'); ?>" alt="Logo"><?php echo wk_h(conf('Name'));?> - <span class="fw-semibold"><?php echo wk_h(conf('SiteName'));?></span>
                </a>
            </div>
            <div class="d-flex align-items-center">
                <ul class="nav-main nav-main-horizontal nav-main-hover d-none d-lg-block me-2">
                    <li class="nav-main-item">
                        <a class="nav-main-link" href="index.php">
                            <i class="nav-main-link-icon fa fa-house"></i>
                            <span class="nav-main-link-name"><strong>首页</strong></span>
                        </a>
                    </li>
                    <li class="nav-main-item">
                        <a class="nav-main-link active" href="works.php">
                            <i class="nav-main-link-icon fa fa-layer-group"></i>
                            <span class="nav-main-link-name"><strong>作品项目</strong></span>
                        </a>
                    </li>
                    <li class="nav-main-item">
                        <a class="nav-main-link" href="news.php">
                            <i class="nav-main-link-icon fa fa-bullhorn"></i>
                            <span class="nav-main-link-name"><strong>公告与动态</strong></span>
                        </a>
                    </li>
                    <li class="nav-main-item">
                        <a class="nav-main-link" href="about.php">
                            <i class="nav-main-link-icon fa fa-circle-info"></i>
                            <span class="nav-main-link-name"><strong>关于我们</strong></span>
                        </a>
                    </li>
                </ul>
                <button type="button" class="btn btn-alt-secondary me-1" data-toggle="layout" data-action="dark_mode_toggle">
                    <i class="far fa-moon"></i>
                </button>
            </div>
        </div>
    </header>

    <main id="main-container">
        <section class="wd-hero">
            <div class="wk-wrap">
                <a class="wd-back" href="works.php">
                    <svg viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M10 3 5 8l5 5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    作品项目
                </a>
                <div class="wd-kicker">
                    <span class="wd-chip"><?php echo $pType;?></span>
                    <span class="wd-live<?php echo $pLive ? '' : ' is-off';?>"><i></i><?php echo $pLive ? '正常运营' : '暂停运营';?></span>
                </div>
                <h1><?php echo wk_h($pName);?></h1>
                <?php if ($pSketch !== '') { ?>
                <p class="wd-lede"><?php echo wk_h($pSketch);?></p>
                <?php } ?>
                <?php if ($pMoney !== '' || $pVer !== '' || $pDate !== '') { ?>
                <p class="wd-facts">
                    <?php if ($pMoney !== '') { ?><span><b>¥<?php echo wk_h($pMoney);?></b></span><?php } ?>
                    <?php if ($pVer !== '') { ?><span>v<?php echo wk_h($pVer);?></span><?php } ?>
                    <?php if ($pDate !== '') { ?><span><?php echo wk_h($pDate);?></span><?php } ?>
                </p>
                <?php } ?>
            </div>
        </section>

        <?php if ($pCover !== '') { ?>
        <div class="wk-wrap">
            <div class="wd-cover">
                <span class="wk-mono" aria-hidden="true"><?php echo wk_h($pInitial);?></span>
                <img src="<?php echo wk_h($pCover);?>" alt="<?php echo wk_h($pName);?>" onerror="var p=this.parentNode;p.classList.add('is-empty');this.remove();">
            </div>
        </div>
        <?php } ?>

        <?php if (trim($pDesc) !== '') { ?>
        <div class="wd-body">
            <div class="wk-wrap">
                <div class="wd-read">
                    <h2 class="wd-h2">作品介绍</h2>
                    <?php echo $pDesc;?>
                </div>
            </div>
        </div>
        <?php } ?>

        <?php if ($pShots) { ?>
        <section class="wd-gallery">
            <div class="wk-wrap">
                <h2 class="wd-h2">截图</h2>
                <div class="wd-shots">
                    <?php foreach ($pShots as $i => $shot) { ?>
                    <a class="wd-shot<?php echo $i === 0 ? ' wd-shot--wide' : '';?>" href="<?php echo wk_h($shot);?>" target="_blank" rel="noopener noreferrer">
                        <img src="<?php echo wk_h($shot);?>" alt="<?php echo wk_h($pName);?> 截图 <?php echo $i + 1;?>" loading="lazy" onerror="this.closest('.wd-shot').remove()">
                    </a>
                    <?php } ?>
                </div>
            </div>
        </section>
        <?php } ?>

        <section class="wd-buy-wrap">
            <div class="wk-wrap">
                <div class="wd-buy">
                    <?php
                    $pPrice     = epay_parse_money($pMoney);
                    $wxOn       = epay_channel_on('wxpay');
                    $aliOn      = epay_channel_on('alipay');
                    $buyUser    = user_current();
                    $buyBalance = $buyUser ? wallet_balance($DB, (int)$buyUser['id']) : 0.0;
                    /* 后台会员权益：VIP 打折 / SVIP 免费，价格实时读配置 */
                    $eff        = vip_effective_price($pPrice, $buyUser);
                    $payPrice   = $eff['price'];
                    $freeBuy    = $eff['free'] && $payPrice <= 0 && $buyUser;
                    $onlineBuy  = ($payPrice > 0) && epay_ready() && ($wxOn || $aliOn);
                    /* 登录用户即便没开在线支付，也能用余额直接买 */
                    $canBuy     = $freeBuy || $onlineBuy || ($payPrice > 0 && $buyUser);
                    ?>
                    <p class="wd-price">
                        <span>价格</span>
                        <b><?php echo $pMoney !== '' ? '¥' . wk_h($pMoney) : '面议';?></b>
                        <?php if ($eff['discount']) { ?><span style="margin-left:10px;color:#0f766e;font-weight:600">会员价 ¥<?php echo number_format($payPrice, 2); ?></span><?php } ?>
                        <?php if ($eff['free']) { ?><span style="margin-left:10px;color:#0f766e;font-weight:600">SVIP 免费</span><?php } ?>
                        <?php if ($canBuy) { ?><i class="wd-price-note">付款后自动交付</i><?php } ?>
                    </p>
                    <div class="wd-actions">
                        <?php if ($freeBuy) { ?>
                        <form method="post" action="/buy.php" style="margin:0;display:flex;gap:10px;flex-wrap:wrap">
                            <?php echo user_csrf_field(); ?>
                            <input type="hidden" name="act" value="buy">
                            <input type="hidden" name="project_id" value="<?php echo (int)$pId; ?>">
                            <input type="hidden" name="channel" value="free">
                            <button class="btn-wk btn-wk--primary" type="submit">
                                <i class="fa fa-gift"></i> SVIP 免费领取
                            </button>
                        </form>
                        <?php } elseif ($canBuy && $buyUser) { ?>
                        <form method="post" action="/buy.php" style="margin:0;display:flex;gap:10px;flex-wrap:wrap">
                            <?php echo user_csrf_field(); ?>
                            <input type="hidden" name="act" value="buy">
                            <input type="hidden" name="project_id" value="<?php echo (int)$pId; ?>">
                            <?php if ($onlineBuy && $wxOn) { ?>
                            <button class="btn-wk btn-wk--primary" type="submit" name="channel" value="wxpay">
                                <i class="fa-brands fa-weixin"></i> 微信支付 ¥<?php echo number_format($payPrice, 2); ?>
                            </button>
                            <?php } ?>
                            <?php if ($onlineBuy && $aliOn) { ?>
                            <button class="btn-wk btn-wk--primary" type="submit" name="channel" value="alipay">
                                <i class="fa-brands fa-alipay"></i> 支付宝 ¥<?php echo number_format($payPrice, 2); ?>
                            </button>
                            <?php } ?>
                            <button class="btn-wk <?php echo $onlineBuy ? 'btn-wk--ghost' : 'btn-wk--primary'; ?>" type="submit"
                                    name="channel" value="balance" title="使用账户余额直接购买">
                                <i class="fa fa-wallet"></i> 余额支付 ¥<?php echo number_format($payPrice, 2); ?>
                                <small style="opacity:.75">（余额 ¥<?php echo number_format($buyBalance, 2); ?>）</small>
                            </button>
                            <a class="btn-wk btn-wk--ghost" href="/user/money.php">充值余额</a>
                        </form>
                        <?php } elseif ($onlineBuy) { ?>
                        <a class="btn-wk btn-wk--primary" href="/user/login.php?redirect=<?php echo urlencode('/works.php?id=' . $pId); ?>"><i class="fa fa-bag-shopping"></i> 登录后购买</a>
                        <?php } ?>
                        <?php if ($pUrl !== '') { ?>
                        <a class="btn-wk <?php echo $canBuy ? 'btn-wk--ghost' : 'btn-wk--primary'; ?>" href="<?php echo wk_h($pUrl);?>" target="_blank" rel="noopener noreferrer">前往体验</a>
                        <?php } ?>
                        <?php if (trim((string)(isset($project['paytg']) ? $project['paytg'] : '')) !== '') { ?>
                        <a class="btn-wk btn-wk--ghost" href="https://t.me/<?php echo wk_h($project['paytg']);?>" target="_blank" rel="noopener noreferrer"><i class="fa-brands fa-telegram"></i>Telegram</a>
                        <?php } ?>
                        <?php if (trim((string)(isset($project['paycontact']) ? $project['paycontact'] : '')) !== '') { ?>
                        <a class="btn-wk btn-wk--ghost" href="https://qm.qq.com/cgi-bin/qm/qr?k=<?php echo wk_h($project['paycontact']);?>" target="_blank" rel="noopener noreferrer"><i class="fa-brands fa-qq"></i>QQ</a>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </section>

        <?php if ($rel) { ?>
        <section class="wd-related">
            <div class="wk-wrap">
                <div class="wd-rel-head">
                    <h2 class="wd-h2">更多作品</h2>
                    <a href="works.php">全部作品 →</a>
                </div>
                <div class="wk-grid">
                    <?php foreach ($rel as $r) {
                        $rWeb = isset($r['type']) && $r['type'] === 'web';
                        $rImg = wk_img($r['img']);
                        $rName = trim((string)$r['name']);
                        $rInitial = wk_first($rName);
                        $rMeta = array();
                        if ((string)$r['money'] !== '') { $rMeta[] = array('wk-price', '¥' . $r['money']); }
                        $rd = wk_date($r['intime']);
                        if ($rd !== '') { $rMeta[] = array('', $rd); }
                    ?>
                    <a class="wk-card" href="works.php?id=<?php echo (int)$r['id'];?>">
                        <div class="wk-thumb">
                            <span class="wk-mono" aria-hidden="true"><?php echo wk_h($rInitial);?></span>
                            <?php if ($rImg !== '') { ?>
                            <img src="<?php echo wk_h($rImg);?>" alt="<?php echo wk_h($rName);?>" loading="lazy" onerror="this.remove()">
                            <?php } ?>
                            <span class="wk-type"><?php echo $rWeb ? '网页' : '程序';?></span>
                        </div>
                        <div class="wk-body">
                            <h3 class="wk-name"><?php echo wk_h($rName);?></h3>
                            <?php if (trim((string)$r['sketch']) !== '') { ?>
                            <p class="wk-desc"><?php echo wk_h($r['sketch']);?></p>
                            <?php } ?>
                            <?php if ($rMeta) { ?>
                            <div class="wk-meta">
                                <?php foreach ($rMeta as $m) { ?>
                                <span<?php echo $m[0] !== '' ? ' class="' . $m[0] . '"' : '';?>><?php echo wk_h($m[1]);?></span>
                                <?php } ?>
                            </div>
                            <?php } ?>
                        </div>
                    </a>
                    <?php } ?>
                </div>
            </div>
        </section>
        <?php } ?>

        <?php require __DIR__ . '/Common/site_footer.php'; ?>
    </main>
</div>

<script src="/assets/js/oneui.app.min-5.6.js"></script>
<script src="/assets/js/jquery.min.js"></script>
<?php if (conf('Index_Fang') == 1) {?>
<script src="/assets/js/fang.js"></script>
<?php }?>
<script src="https://myhkw.cn/api/player/<?php echo conf_index('Index_Music') ?>" id="myhk" key="<?php echo conf_index('Index_Music') ?>" m="1"></script>
</body>
</html>
<?php
}
?>
