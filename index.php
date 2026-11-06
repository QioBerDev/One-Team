<?php
include("./Common/Core_brain.php");
require_once __DIR__ . '/Common/Pay.php';
require_once __DIR__ . '/Common/Schema.php';
require_once __DIR__ . '/Common/User.php';
require_once __DIR__ . '/Common/OtConfig.php';
pay_schema_init($DB);
$hp_user = user_current();
?>
<?php if (conf('Jump') == 1) {?>
<?php
$conf['qqjump']=1;
if(strpos($_SERVER['HTTP_USER_AGENT'], 'QQ/')||strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')!==false && $conf['qqjump']==1){
echo '<!DOCTYPE html>
<html><head>
<meta name="viewport" content="width=device-width, initial-scale=1.0,minimum-scale=1.0,maximum-scale=1.0, user-scalable=no">
<title>Hello World</title>
<frameset><frame name="main" src="//cdn.oss.uaovo.com/jump/index.php" scrolling="auto" noresize></frameset></head>';
exit; }
?>
<?php }?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, viewport-fit=cover, user-scalable=no minimal-ui">
    <title><?php echo conf('Name');?> - <?php echo conf('SiteName');?></title>
    <meta name="keywords" content="<?php echo conf('Keywords');?>">
    <meta name="description" content="<?php echo conf('Descriptison');?>">
    <link rel="shortcut icon" href="assets/media/various/favicon.png">
    <link rel="stylesheet" id="css-main" href="assets/css/oneui.min-5.6.css">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/site-footer.v7.css?v=20261105q">
    <link rel="preload" as="image" href="<?php echo htmlspecialchars(conf('Index_Image'), ENT_QUOTES, 'UTF-8'); ?>" media="(min-width: 992px)">
    <link rel="preload" as="image" href="<?php echo htmlspecialchars(conf('Mobile_Index_Image') ?: 'assets/media/various/mobile-hero.webp', ENT_QUOTES, 'UTF-8'); ?>" media="(max-width: 991.98px)">
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        gtag('js', new Date());
        gtag('config', 'G-9HQDQJJYW7');
        window.loadOptionalHomeAssets = function () {
            if (window.__optionalHomeAssetsLoaded) return;
            window.__optionalHomeAssetsLoaded = true;
            var statsId = <?php echo json_encode((string)conf_index('Statistics_Id')); ?>;
            var scripts = [
                ['https://www.googletagmanager.com/gtag/js?id=G-9HQDQJJYW7', 'analytics'],
                ['https://sdk.jinrishici.com/v2/browser/jinrishici.js', 'sentence'],
                ['https://cdn.bootcss.com/sweetalert/2.1.0/sweetalert.min.js', 'alert'],
                ['https://myhkw.cn/api/player/<?php echo rawurlencode((string)conf_index('Index_Music')); ?>', 'music']
            ];
            if (statsId) scripts.push(['//sdk.51.la/js-sdk-pro.min.js', 'stats']);
            scripts.forEach(function (item) {
                var script = document.createElement('script');
                script.src = item[0];
                script.async = true;
                script.onload = function () {
                    if (item[1] === 'analytics' && window.gtag) window.gtag('js', new Date());
                    if (item[1] === 'stats' && window.LA) window.LA.init({id: statsId, ck: statsId});
                    if (item[1] === 'stats') {
                        var widgetUrl = <?php echo json_encode((string)conf_index('Statistics_Dm')); ?>;
                        if (widgetUrl) {
                            var widget = document.createElement('script');
                            widget.id = 'LA-DATA-WIDGET';
                            widget.src = widgetUrl;
                            widget.async = true;
                            document.getElementById('statistics-widget').appendChild(widget);
                        }
                    }
                    if (item[1] === 'alert' && window.showHomeNotice) window.showHomeNotice();
                };
                if (item[1] === 'music') {
                    script.id = 'myhk';
                    script.setAttribute('key', <?php echo json_encode((string)conf_index('Index_Music')); ?>);
                    script.setAttribute('m', '1');
                }
                document.head.appendChild(script);
            });
        };
        window.addEventListener('load', function () {
            var start = function () { window.loadOptionalHomeAssets(); };
            if ('requestIdleCallback' in window) requestIdleCallback(start, {timeout: 2500});
            else setTimeout(start, 1500);
        }, {once: true});
    </script>
    <style>
        #one-hero {
            background-image: url('<?php echo conf('Index_Image');?>');
        }
        @media (max-width: 991.98px) {
            #one-hero {
                background-image: url('<?php echo conf('Mobile_Index_Image') ?: 'assets/media/various/mobile-hero.webp'; ?>');
            }
        }
        #mobile-drawer {
            position: fixed;
            top: 0; left: 0;
            width: 260px; height: 100%;
            background: #fff;
            z-index: 1040;
            transform: translateX(-100%);
            transition: transform 0.28s ease;
            box-shadow: 2px 0 12px rgba(0,0,0,0.12);
        }
        #mobile-drawer.open { transform: translateX(0); }
        #mobile-drawer-overlay {
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.45);
            z-index: 1039;
            display: none;
        }
        #mobile-drawer-overlay.open { display: block; }
        .drawer-header {
            padding: 1rem;
            border-bottom: 1px solid #e8e8e8;
            font-weight: 700;
        }
        .drawer-body a {
            display: block;
            padding: 0.75rem 1rem;
            color: #333;
            text-decoration: none;
        }
        .drawer-body a:hover { background: #f5f5f5; }
    </style>
    <style>
        /* 成员区背景：柔和渐变 + 顶部光晕（与 about / disclaimer hero 同语言） */
        .member-section {
            background:
                radial-gradient(900px 340px at 50% -12%, rgba(76, 120, 221, .12), transparent 72%),
                linear-gradient(180deg, #fbfcfe 0%, #f0f4fa 100%);
        }
        .dark-mode .member-section {
            background:
                radial-gradient(900px 340px at 50% -12%, rgba(76, 120, 221, .20), transparent 72%),
                linear-gradient(180deg, #1e2836 0%, #182130 100%);
        }
    </style>
    <style>
        /* 成员区：整块通栏大卡片 + 珍珠贝壳角花（参考 n7 的 .ng-my-card） */
        #one-features.member-section {
            position: relative;
            overflow: hidden;
            background-color: #fffdfd;
            border-top: 1px solid #e1d4ea;
            border-bottom: 1px solid #e1d4ea;
            box-shadow:
                inset 0 2px 0 rgba(245, 241, 246, .95),
                inset 0 -2px 0 rgba(245, 241, 246, .95);
        }
        #one-features.member-section::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image:
                url("/assets/img/decor/member-tl.webp"),
                url("/assets/img/decor/member-br.webp");
            background-size: min(46%, 185px) auto, min(43%, 175px) auto;
            background-position: 0% 0%, 100% 100%;
            background-repeat: no-repeat, no-repeat;
            opacity: .42;
            z-index: 0;
            pointer-events: none;
        }
        #one-features.member-section > .content {
            position: relative;
            z-index: 1;
        }

        /* 暗色 */
        .dark-mode #one-features.member-section {
            background-color: #1f2937;
            border-top-color: #3a2b49;
            border-bottom-color: #3a2b49;
            box-shadow:
                inset 0 2px 0 rgba(255, 255, 255, .03),
                inset 0 -2px 0 rgba(255, 255, 255, .03);
        }
        .dark-mode #one-features.member-section::before {
            opacity: .26;
        }
        /* Hitokoto 一言区：橄榄枝角花 */
        #moyi {
            position: relative;
            overflow: hidden;
        }
        #moyi::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image:
                url("/assets/img/decor/olive-berry-tl.webp"),
                url("/assets/img/decor/olive-berry-br.webp");
            background-size: min(42%, 200px) auto, min(40%, 190px) auto;
            background-position: 0% 0%, 100% 100%;
            background-repeat: no-repeat, no-repeat;
            opacity: .38;
            z-index: 0;
            pointer-events: none;
        }
        #moyi > .content {
            position: relative;
            z-index: 1;
        }
        .dark-mode #moyi::before {
            opacity: .2;
        }
        /* 首页「团队动态」区块：通栏底板 + 水晶星角花 */
        .news-section {
            position: relative;
            overflow: hidden;
            background-color: #fffdfd;
            border-top: 1px solid #e1d4ea;
            border-bottom: 1px solid #e1d4ea;
        }
        .news-section::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image:
                url("/assets/img/decor/category-tl.webp"),
                url("/assets/img/decor/category-br.webp");
            background-size: min(26%, 168px) auto, min(24%, 158px) auto;
            background-position: 0% 0%, 100% 100%;
            background-repeat: no-repeat, no-repeat;
            opacity: .34;
            z-index: 0;
            pointer-events: none;
        }
        .news-section > .content {
            position: relative;
            z-index: 1;
        }
        .dark-mode .news-section {
            /* 和上下区块（服务 #1b2431 / CTA #1b2431）对齐，
               原来写 #1f2937 比邻居亮一档，暗色下会裂出一条横带 */
            background-color: #1b2431;
            border-top-color: #3a2b49;
            border-bottom-color: #3a2b49;
        }
        .dark-mode body { background-color: #19222d; }
        .dark-mode .news-section::before {
            opacity: .22;
        }
        /* 水晶按钮（参考 n7 登录/注册按钮） */
        .crystal-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 46px;
            min-width: 186px;
            padding: 10px 32px;
            border: 1px solid transparent;
            border-radius: 999px;
            background: #6a4693 url("/assets/img/decor/button.webp") no-repeat 50% 50% / 100% 100%;
            color: #fff9ed;
            -webkit-text-fill-color: #fff9ed;
            font-size: 14px;
            font-weight: 750;
            line-height: 1.4;
            text-decoration: none;
            text-shadow: 0 1px 2px rgba(0, 0, 0, .6);
            overflow: hidden;
            box-shadow: 0 4px 14px rgba(106, 70, 147, .35);
            transition: transform .175s cubic-bezier(.2, .8, .2, 1), box-shadow .175s, filter .175s;
            cursor: pointer;
        }
        .crystal-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 22px rgba(106, 70, 147, .45);
            filter: brightness(1.06);
            color: #fff9ed;
            -webkit-text-fill-color: #fff9ed;
        }
        .crystal-btn:active {
            transform: translateY(0);
            filter: brightness(.97);
        }
        .crystal-btn i {
            font-size: 15px;
            opacity: .95;
        }
    </style>
    <style>
        /* ===================== 首页 v2 =====================
           结构：英雄区 → 公告 → 服务能力 → 作品入口 → 动态 → 一言 → 成员 → 加入
           作品列表整块搬进 works.php，首页只留一张通往作品页的门卡。
           配色沿用 about.php 的 #4c78dd / #253041 / #e6eaf1，不引新色。 */
        :root {
            --hp-accent: #4c78dd;
            --hp-ink: #1d2533;
            --hp-muted: #6b7a90;
            --hp-line: #e6eaf1;
        }

        /* ---------- 英雄区 ---------- */
        #one-hero {
            position: relative;
            min-height: 88vh;
            background-size: cover;
            background-position: center center;
            background-repeat: no-repeat;
            overflow: hidden;
        }
        #one-hero::after {
            content: "";
            position: absolute;
            inset: 0;
            /* 只压底部一条，图片本身按原亮度显示；
               原版英雄区就没有遮罩，靠文字自身阴影保可读 */
            background: linear-gradient(180deg, rgba(9, 12, 20, 0) 0%, rgba(9, 12, 20, 0) 64%, rgba(9, 12, 20, .38) 100%);
            pointer-events: none;
        }
        #one-hero > .content { position: relative; z-index: 2; }
        .hp-badge {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            max-width: min(92vw, 720px);
            padding: .4rem 1rem;
            margin-bottom: 1.4rem;
            font-size: .8125rem;
            color: rgba(255, 255, 255, .92);
            background: rgba(9, 12, 20, .28);
            border: 1px solid rgba(255, 255, 255, .24);
            border-radius: 50rem;
            -webkit-backdrop-filter: blur(8px);
            backdrop-filter: blur(8px);
        }
        .hp-badge span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .hp-title {
            margin: 0 0 1.1rem;
            font-size: clamp(2rem, 4.4vw, 3.5rem);
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: .01em;
            color: #fff;
            text-shadow: 0 6px 30px rgba(0, 0, 0, .35);
        }
        .hp-title-dot { margin: 0 .35em; font-weight: 400; opacity: .45; }
        .hp-title-sub { font-weight: 600; letter-spacing: .06em; }
        .hp-sub {
            max-width: 660px;
            margin: 0 auto;
            font-size: 1.0625rem;
            line-height: 1.9;
            color: rgba(255, 255, 255, .9);
            text-shadow: 0 2px 14px rgba(0, 0, 0, .62), 0 1px 3px rgba(0, 0, 0, .5);
        }
        /* hero 下方居中的登录/注册水晶按钮 */
        .hp-auth-btns {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: .85rem;
            margin-top: 2rem;
        }
        /* 登录/注册展开区：hero 与公告条之间 */
        .hp-auth {
            background: #f6f8fc;
            border-bottom: 1px solid var(--hp-line);
            overflow: hidden;
        }
        .hp-auth > .content { padding-top: clamp(28px, 4vw, 46px); padding-bottom: clamp(34px, 5vw, 58px); }
        .dark-mode .hp-auth { background: #1b2431; border-bottom-color: #2c384a; }

        /* ---------- 公告条 ---------- */
        .hp-notice { background: #fff7e6; border-bottom: 1px solid #f3e2bd; }
        .dark-mode .hp-notice { background: #2a2320; border-bottom-color: #3d332a; color: #e8e2d8; }

        /* ---------- 节标题 ---------- */
        .hp-section { background: #fff; }
        .hp-section > .content { padding-top: 4rem; padding-bottom: 4rem; }
        .hp-head { max-width: 620px; margin: 0 auto 2.6rem; text-align: center; }
        .hp-eyebrow {
            display: inline-block;
            margin-bottom: .7rem;
            font-size: .7rem;
            font-weight: 800;
            letter-spacing: .22em;
            color: var(--hp-accent);
        }
        .hp-h2 {
            margin: 0 0 .7rem;
            font-size: clamp(1.6rem, 2.4vw, 2.1rem);
            font-weight: 800;
            letter-spacing: .01em;
            color: var(--hp-ink);
        }
        .hp-h2-light { font-weight: 300; opacity: .72; }
        .hp-desc { margin: 0; font-size: .9375rem; line-height: 1.8; color: var(--hp-muted); }
        .dark-mode .hp-section { background: #1b2431; }
        .dark-mode .hp-h2 { color: #e9eef7; }
        .dark-mode .hp-desc { color: #9aa7ba; }

        /* ---------- 服务能力：标题行 + 横排四卡 ---------- */
        .hp-services {
            background-color: #fff;
            /* 右上角一层极淡紫光，纯白整块会发平 */
            background-image: radial-gradient(90% 86% at 100% 0%, rgba(87, 81, 216, .045) 0%, rgba(87, 81, 216, 0) 60%);
        }
        /* 标题行：左标题、右副标题，一条细线压住 */
        .hp-svc-head {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 2.4rem;
            padding-bottom: 1.25rem;
            border-bottom: 1px solid var(--hp-line);
        }
        .hp-svc-head .hp-h2 { margin-bottom: 0; }
        .hp-svc-head-desc {
            max-width: 340px;
            margin: 0 0 .25rem;
            font-size: .9375rem;
            line-height: 1.8;
            color: var(--hp-muted);
            text-align: right;
        }
        .hp-svc-cards {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }
        .hp-svc {
            position: relative;
            padding: 1.5rem 1.35rem 1.4rem;
            background: #fff;
            border: 1px solid var(--hp-line);
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 1px 2px rgba(29, 37, 51, .035);
            transition: transform .24s cubic-bezier(.2, .8, .2, 1), box-shadow .24s, border-color .24s;
        }
        /* 顶边一条渐变线，hover 从左侧拉开 */
        .hp-svc::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, rgba(87, 81, 216, .9) 0%, rgba(143, 123, 255, .75) 58%, rgba(143, 123, 255, 0) 100%);
            transform: scaleX(0);
            transform-origin: left center;
            transition: transform .3s cubic-bezier(.2, .8, .2, 1);
        }
        .hp-svc:hover::before { transform: scaleX(1); }
        .hp-svc:hover {
            transform: translateY(-5px);
            border-color: rgba(87, 81, 216, .26);
            box-shadow: 0 24px 46px -26px rgba(46, 38, 120, .45);
        }
        /* 序号做水印：默认几乎看不出，hover 才亮 */
        .hp-svc-no {
            position: absolute;
            top: .55rem;
            right: .9rem;
            font-size: 2.35rem;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -.05em;
            color: rgba(87, 81, 216, .085);
            pointer-events: none;
            transition: color .24s;
        }
        .hp-svc:hover .hp-svc-no { color: rgba(87, 81, 216, .17); }
        .hp-svc-ico {
            position: relative;
            display: grid;
            place-items: center;
            width: 48px;
            height: 48px;
            margin-bottom: 1.05rem;
            border-radius: 14px;
            font-size: 19px;
            transition: transform .24s;
        }
        .hp-svc:hover .hp-svc-ico { transform: translateY(-2px) scale(1.04); }
        .tint-amethyst { color: #8268b8; background: rgba(130, 104, 184, .12); }
        .tint-flat { color: #3d83c9; background: rgba(61, 131, 201, .12); }
        .tint-smooth { color: #3aa08a; background: rgba(58, 160, 138, .12); }
        .tint-city { color: #d3813c; background: rgba(211, 129, 60, .13); }
        .hp-svc-title { position: relative; margin: 0 0 .45rem; font-size: 1.0625rem; font-weight: 700; color: var(--hp-ink); }
        .hp-svc-desc { position: relative; margin: 0; font-size: .8125rem; line-height: 1.8; color: var(--hp-muted); }
        .dark-mode .hp-services {
            background-color: #1b2431;
            background-image: radial-gradient(90% 86% at 100% 0%, rgba(120, 110, 255, .07) 0%, rgba(120, 110, 255, 0) 60%);
        }
        .dark-mode .hp-svc-head { border-bottom-color: #2f3c50; }
        .dark-mode .hp-svc { background: #202b3a; border-color: #2f3c50; box-shadow: 0 1px 2px rgba(0, 0, 0, .18); }
        .dark-mode .hp-svc:hover { border-color: rgba(140, 130, 255, .35); }
        .dark-mode .hp-svc-no { color: rgba(255, 255, 255, .06); }
        .dark-mode .hp-svc:hover .hp-svc-no { color: rgba(160, 150, 255, .22); }
        .dark-mode .hp-svc-title { color: #e9eef7; }
        .dark-mode .hp-svc-desc { color: #9aa7ba; }
        /* 平板两列，手机两列 */
        @media (max-width: 991.98px) {
            .hp-svc-head { flex-direction: column; align-items: flex-start; gap: .95rem; }
            .hp-svc-head-desc { text-align: left; max-width: none; margin-bottom: 0; }
            .hp-svc-cards { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 575.98px) {
            .hp-svc-cards { gap: .8rem; margin-top: 1.15rem; }
            .hp-svc { padding: 1.15rem 1rem 1.1rem; border-radius: .85rem; }
            .hp-svc-ico { width: 40px; height: 40px; margin-bottom: .75rem; font-size: 16px; border-radius: 12px; }
            .hp-svc-title { font-size: .9375rem; }
            .hp-svc-desc { font-size: .8125rem; line-height: 1.7; }
            .hp-svc-no { top: .4rem; right: .6rem; font-size: 1.7rem; }
        }
        /* ---------- 一言 ---------- */
        .hp-quote {
            position: relative;
            overflow: hidden;
            background: #fbfcfe;
            border-top: 1px solid var(--hp-line);
            border-bottom: 1px solid var(--hp-line);
        }
        .hp-quote::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image: url("/assets/img/decor/olive-berry-tl.webp"), url("/assets/img/decor/olive-berry-br.webp");
            background-repeat: no-repeat, no-repeat;
            background-position: 0 0, 100% 100%;
            background-size: min(24%, 170px) auto, min(22%, 155px) auto;
            opacity: .3;
            pointer-events: none;
        }
        .hp-quote-inner { position: relative; z-index: 1; padding: 3.2rem 0; text-align: center; }
        .hp-quote-mark { display: inline-block; margin-bottom: .9rem; font-size: 1.4rem; color: rgba(76, 120, 221, .35); }
        .hp-quote-text {
            margin: 0 0 .8rem;
            font-size: clamp(1.05rem, 1.7vw, 1.35rem);
            font-weight: 500;
            line-height: 1.9;
            color: #33415a;
        }
        .hp-quote-from { margin: 0; font-size: .8125rem; letter-spacing: .06em; color: #8a94a6; }
        .dark-mode .hp-quote { background: #182130; border-color: #2b3648; }
        .dark-mode .hp-quote-text { color: #dbe3ef; }

        /* ---------- 加入 ---------- */
        .hp-cta { background: #fff; }
        .hp-cta-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 2rem;
            padding: 3rem 0;
        }
        .hp-cta-title { margin: 0 0 .6rem; font-size: clamp(1.35rem, 2.1vw, 1.75rem); font-weight: 800; color: var(--hp-ink); }
        .hp-cta-desc { font-size: .9rem; line-height: 1.8; color: var(--hp-muted); }
        .hp-cta-actions { display: flex; flex-wrap: wrap; gap: .7rem; }
        .dark-mode .hp-cta { background: #1b2431; }
        .dark-mode .hp-cta-title { color: #e9eef7; }
        .dark-mode .hp-cta-desc { color: #9aa7ba; }

        /* ---------- 成员卡：底部信息条压成渐变，和封面融在一起 ---------- */
        .hp-member { overflow: hidden; border-radius: 8px; }
        .hp-member .bg-primary-dark-op {
            padding: 1.05rem 1rem 1.15rem;
            background: linear-gradient(180deg, rgba(17, 24, 39, .18) 0%, rgba(17, 24, 39, .84) 100%) !important;
            border-radius: 0 0 8px 8px;
        }
        .hp-member .bg-primary-dark-op p.fw-semibold { font-size: .9375rem; letter-spacing: .02em; }

        /* ---------- 进入视口淡入 ---------- */
        [data-reveal] {
            opacity: 0;
            transform: translateY(18px);
            transition: opacity .6s cubic-bezier(.2, .8, .2, 1), transform .6s cubic-bezier(.2, .8, .2, 1);
        }
        [data-reveal].is-in { opacity: 1; transform: none; }
        @media (prefers-reduced-motion: reduce) {
            [data-reveal] { opacity: 1; transform: none; transition: none; }
        }

        /* ---------- 小屏 ---------- */
        @media (max-width: 767.98px) {
            #one-hero { min-height: 82vh; }
            .hp-sub { font-size: .9375rem; }
            .hp-cta-inner { flex-direction: column; align-items: flex-start; }
            .hp-section > .content { padding-top: 2.8rem; padding-bottom: 2.8rem; }
        }
    </style>
</head>
<body>
    <div id="page-container" class="page-header-fixed main-content-boxed">
        <header id="page-header">
            <div class="content-header">
                <div class="d-flex align-items-center">
                    <button type="button" class="btn btn-alt-secondary d-lg-none me-2" onclick="toggleMobileDrawer()" aria-label="菜单"><i class="fa fa-bars"></i></button>
                    <a class="fw-bold fs-lg tracking-wider text-dual me-2" href="index.php">
                        <img class="hd-brand-logo" src="<?php echo htmlspecialchars(site_logo_url(), ENT_QUOTES, 'UTF-8'); ?>" alt="Logo"><?php echo conf('Name');?> - <span class="fw-semibold"><?php echo conf('SiteName');?></span>
                    </a>
                </div>
                <div class="d-flex align-items-center">
                    <ul class="nav-main nav-main-horizontal nav-main-hover d-none d-lg-block me-2">
                        <li class="nav-main-item">
                            <a class="nav-main-link active" href="index.php">
                                <i class="nav-main-link-icon fa fa-house"></i>
                                <span class="nav-main-link-name"><strong>首页</strong></span>
                            </a>
                        </li>
                        <li class="nav-main-item">
                            <a class="nav-main-link" href="works.php">
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
                    <div class="dropdown">
                        <button type="button" class="btn btn-alt-secondary me-2" id="sidebar-themes-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fa fa-brush"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end fs-sm smini-hide border-0" aria-labelledby="sidebar-themes-dropdown">
                            <a class="dropdown-item d-flex align-items-center justify-content-between fw-medium" data-toggle="theme" data-theme="default" href="#">
                                <span>Default</span>
                                <i class="fa fa-circle text-default"></i>
                            </a>
                            <a class="dropdown-item d-flex align-items-center justify-content-between fw-medium" data-toggle="theme" data-theme="assets/css/themes/amethyst.min-5.6.css" href="#">
                                <span>Amethyst</span>
                                <i class="fa fa-circle text-amethyst"></i>
                            </a>
                            <a class="dropdown-item d-flex align-items-center justify-content-between fw-medium" data-toggle="theme" data-theme="assets/css/themes/city.min-5.6.css" href="#">
                                <span>City</span>
                                <i class="fa fa-circle text-city"></i>
                            </a>
                            <a class="dropdown-item d-flex align-items-center justify-content-between fw-medium" data-toggle="theme" data-theme="assets/css/themes/flat.min-5.6.css" href="#">
                                <span>Flat</span>
                                <i class="fa fa-circle text-flat"></i>
                            </a>
                            <a class="dropdown-item d-flex align-items-center justify-content-between fw-medium" data-toggle="theme" data-theme="assets/css/themes/modern.min-5.6.css" href="#">
                                <span>Modern</span>
                                <i class="fa fa-circle text-modern"></i>
                            </a>
                            <a class="dropdown-item d-flex align-items-center justify-content-between fw-medium" data-toggle="theme" data-theme="assets/css/themes/smooth.min-5.6.css" href="#">
                                <span>Smooth</span>
                                <i class="fa fa-circle text-smooth"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div id="page-header-loader" class="overlay-header bg-primary-lighter">
                <div class="content-header">
                    <div class="w-100 text-center">
                        <i class="fa fa-fw fa-circle-notch fa-spin text-primary"></i>
                    </div>
                </div>
            </div>
        </header>
        <main id="main-container">
<?php
/* 首页只需要三个数：作品、成员、动态。取不到就显 0，绝不让首页因为一条 SQL 挂掉。 */
function hp_count($DB, $sql) {
    try {
        $row = $DB->getRow($sql);
        if (!$row) return 0;
        $v = array_values($row);
        return (int)$v[0];
    } catch (Throwable $e) {
        return 0;
    }
}
?>
            <!-- 首屏 -->
            <div id="one-hero" class="bg-body-extra-light bg-image d-flex align-items-center">
                <div class="content content-full w-100">
                    <div class="row g-0 justify-content-center text-center">
                        <div class="col-lg-9 py-5">
                            <div class="hp-badge">
                                <i class="fa-solid fa-quote-left me-1 opacity-50"></i>
                                <span id="jinrishici-sentence">保持心脏震动 有人与你共鸣</span>
                            </div>
                            <h1 class="hp-title"><?php echo conf('Name');?><span class="hp-title-dot">·</span><span class="hp-title-sub"><?php echo conf('SiteName');?></span></h1>
                            <p class="hp-sub"><?php echo conf_index('Index_About');?></p>
                            <div class="hp-auth-btns">
                                <?php if ($hp_user) { ?>
                                <a class="crystal-btn" href="/user/index.php">
                                    <i class="fa fa-user"></i> 个人中心
                                </a>
                                <?php } else { ?>
                                <a class="crystal-btn" href="/user/login.php">
                                    <i class="fa fa-right-to-bracket"></i> 登录 / 注册
                                </a>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php
            try {
                $latestNotice = $DB->getRow("SELECT id, title, summary, published_at FROM nteam_content WHERE type='notice' AND status=1 ORDER BY is_pinned DESC, published_at DESC, id DESC LIMIT 1");
            } catch (Throwable $e) {
                $latestNotice = false;
            }
            if ($latestNotice) {
                $noticeText = $latestNotice['summary'] !== '' ? $latestNotice['summary'] : $latestNotice['title'];
            ?>
            <div class="hp-notice">
                <div class="content py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div><span class="badge bg-warning me-2"><i class="fa fa-bullhorn me-1"></i>公告</span><strong><?php echo htmlspecialchars($noticeText, ENT_QUOTES, 'UTF-8'); ?></strong></div>
                    <a class="btn btn-sm btn-alt-warning" href="news.php?id=<?php echo (int)$latestNotice['id']; ?>">查看详情</a>
                </div>
            </div>
            <?php } ?>

            <!-- 服务能力 -->
            <!-- 服务能力：标题行 + 2×2 编号卡，文案仍走后台配置 -->
            <section id="one-services" class="hp-section hp-services">
                <div class="content content-full">
                    <div class="hp-svc-head" data-reveal>
                        <div>
                            <span class="hp-eyebrow">WHAT WE DO</span>
                            <h2 class="hp-h2">服务<span class="hp-h2-light">能力</span></h2>
                        </div>
                        <p class="hp-svc-head-desc">从需求到上线，一条链路自己走完。</p>
                    </div>
                    <div class="hp-svc-cards">
                        <article class="hp-svc" data-reveal>
                            <span class="hp-svc-no">01</span>
                            <div class="hp-svc-ico tint-amethyst"><i class="fa fa-fw fa-boxes"></i></div>
                            <h3 class="hp-svc-title"><?php echo conf_index('Index_Services_t1') ?></h3>
                            <p class="hp-svc-desc"><?php echo conf_index('Index_Services_d1') ?></p>
                        </article>
                        <article class="hp-svc" data-reveal>
                            <span class="hp-svc-no">02</span>
                            <div class="hp-svc-ico tint-flat"><i class="fa fa-fw fa-laptop-code"></i></div>
                            <h3 class="hp-svc-title"><?php echo conf_index('Index_Services_t2') ?></h3>
                            <p class="hp-svc-desc"><?php echo conf_index('Index_Services_d2') ?></p>
                        </article>
                        <article class="hp-svc" data-reveal>
                            <span class="hp-svc-no">03</span>
                            <div class="hp-svc-ico tint-smooth"><i class="fa fa-fw fa-cloud"></i></div>
                            <h3 class="hp-svc-title"><?php echo conf_index('Index_Services_t3') ?></h3>
                            <p class="hp-svc-desc"><?php echo conf_index('Index_Services_d3') ?></p>
                        </article>
                        <article class="hp-svc" data-reveal>
                            <span class="hp-svc-no">04</span>
                            <div class="hp-svc-ico tint-city"><i class="fa fa-fw fa-user-lock"></i></div>
                            <h3 class="hp-svc-title"><?php echo conf_index('Index_Services_t4') ?></h3>
                            <p class="hp-svc-desc"><?php echo conf_index('Index_Services_d4') ?></p>
                        </article>
                    </div>
                </div>
            </section>

            <?php
            try {
                $recentUpdates = $DB->getAll("SELECT id, type, title, summary, LEFT(body, 400) AS body, published_at FROM nteam_content WHERE status=1 AND type IN ('article','moment') ORDER BY is_pinned DESC, published_at DESC, id DESC LIMIT 3");
            } catch (Throwable $e) {
                $recentUpdates = array();
            }
            if ($recentUpdates) {
            ?>
            <section class="news-section">
                <div class="content content-full py-5">
                    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2" data-reveal>
                        <div>
                            <span class="hp-eyebrow">UPDATES</span>
                            <h2 class="hp-h2 mb-1">团队<span class="hp-h2-light">动态</span></h2>
                            <p class="hp-desc mb-0">最近发布的文章与日常更新</p>
                        </div>
                        <a class="btn btn-alt-primary" href="news.php">查看全部 <i class="fa fa-arrow-right ms-1"></i></a>
                    </div>
                    <div class="row items-push">
                        <?php foreach ($recentUpdates as $update) { ?>
                        <div class="col-md-4" data-reveal>
                            <article class="block block-rounded block-link-shadow h-100">
                                <div class="block-content">
                                    <span class="badge bg-primary"><?php echo $update['type'] === 'moment' ? '动态' : '文章'; ?></span>
                                    <small class="text-muted ms-2"><?php echo htmlspecialchars($update['published_at'], ENT_QUOTES, 'UTF-8'); ?></small>
                                    <h3 class="h5 mt-3"><a href="news.php?id=<?php echo (int)$update['id']; ?>"><?php echo htmlspecialchars($update['title'], ENT_QUOTES, 'UTF-8'); ?></a></h3>
                                    <p class="text-muted mb-0"><?php $preview = $update['summary'] !== '' ? $update['summary'] : (function_exists('mb_substr') ? mb_substr($update['body'], 0, 100, 'UTF-8') : substr($update['body'], 0, 100)); echo htmlspecialchars($preview, ENT_QUOTES, 'UTF-8'); ?></p>
                                </div>
                            </article>
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </section>
            <?php } ?>

            <!-- 一言 -->
            <div id="moyi" class="hp-quote">
                <div class="content content-full">
                    <div class="hp-quote-inner">
                        <i class="fa-solid fa-quote-left hp-quote-mark"></i>
                        <p class="hp-quote-text" id="hitokoto_text">保持心脏震动，有人与你共鸣。</p>
                        <p class="hp-quote-from" id="hitokoto_from">—— 佚名「寒阳」</p>
                    </div>
                </div>
            </div>

            <!-- 团队成员 -->
            <section id="one-features" class="member-section">
                <div class="content content-full">
                    <div class="py-5">
                        <div class="hp-head" data-reveal>
                            <span class="hp-eyebrow">TEAM</span>
                            <h2 class="hp-h2">团队<span class="hp-h2-light">成员</span></h2>
                            <p class="hp-desc">一群充满活力的开发者。</p>
                        </div>
                        <div class="row items-push">
                            <?php
                            $teams=$DB->query("SELECT id, teamimg, qq, name, `describe`, tg FROM nteam_team_member WHERE Audit_status=1 AND is_show=1 ORDER BY id");
                            while($team = $teams->fetch()){
                            ?>
                            <div class="col-md-6 col-xl-3" data-reveal>
                                <div class="block block-rounded text-center bg-image hp-member" style="background-image: url('<?php echo $team['teamimg'];?>');">
                                    <div class="block-content block-content-full">
                                        <img class="img-avatar img-avatar-thumb" src="https://q1.qlogo.cn/g?b=qq&amp;nk=<?php echo rawurlencode($team['qq']); ?>&amp;s=100" alt="<?php echo htmlspecialchars($team['name'], ENT_QUOTES, 'UTF-8');?>" loading="lazy" decoding="async">
                                    </div>
                                    <div class="block-content block-content-full bg-primary-dark-op">
                                        <p class="fw-semibold text-white mb-0"><?php echo $team['name'];?></p>
                                        <p class="fs-sm text-white-75 mb-2"><?php echo $team['describe'];?></p>
                                        <a class="btn btn-sm btn-alt-primary me-1 js-bs-tooltip-enabled" href="https://qm.qq.com/cgi-bin/qm/qr?k=<?php echo $team['qq'];?>" target="_blank" rel="noopener">
                                            <i class="fa-brands fa-qq"></i>
                                        </a>
                                        <a class="btn btn-sm btn-alt-secondary me-1 js-bs-tooltip-enabled" href="mailto:<?php echo $team['qq'];?>@qq.com">
                                            <i class="fa-solid fa-envelope"></i>
                                        </a>
                                        <?php if(!empty($team['tg'])){?>
                                        <a class="btn btn-sm btn-alt-info me-1 js-bs-tooltip-enabled" href="https://t.me/<?php echo $team['tg'];?>" target="_blank" rel="noopener">
                                            <i class="fa-brands fa-telegram"></i>
                                        </a>
                                        <?php }?>
                                    </div>
                                </div>
                            </div>
                            <?php
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 加入 -->
            <div id="one-call-to-action" class="hp-cta">
                <div class="content content-full">
                    <div class="hp-cta-inner" data-reveal>
                        <div>
                            <h2 class="hp-cta-title">成员查询 <i class="fa fa-fw fa-heart text-city"></i> 加入我们</h2>
                            <p class="hp-cta-desc mb-0">谨防假冒成员，点击下方按钮即可查询，同时我们也期待您的加入！</p>
                        </div>
                        <div class="hp-cta-actions">
                            <a class="crystal-btn" href="javascript:;" id="Query">
                                <i class="nav-main-link-icon fa fa-box"></i> 成员查询
                            </a>
                            <a class="crystal-btn" href="javascript:;" id="Join">
                                <i class="nav-main-link-icon fa fa-rocket"></i> 加入我们
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <?php require __DIR__ . '/Common/site_footer.php'; ?>
        </main>
    </div>
    <script src="assets/js/oneui.app.min-5.6.js" defer></script>
    <script src="assets/js/jump.js" defer></script>
    <script src="assets/js/jquery.min.js" defer></script>
    <script src="assets/layer/layer.js" defer></script>
    <?php if (conf('Index_Fang') == 1) {?>
    <script src="assets/js/fang.js" defer></script>
    <?php }?>
    <script>
        window.showHomeNotice = function () {
            var tcContent = <?php echo json_encode((string)conf_index('Index_Tc')); ?>;
            var tcInterval = parseInt(<?php echo json_encode((string)(conf_index('Index_Tc_Time') ?: 0)); ?>, 10) * 3600 * 1000;
            if (!tcContent || !window.swal) return;
            var lastTime = localStorage.getItem('tcLastTime') || 0;
            var now = Date.now();
            if (now - lastTime >= tcInterval) {
                swal('通知', tcContent, 'success');
                localStorage.setItem('tcLastTime', now);
            }
        };
        window.addEventListener('DOMContentLoaded', function () {
            if (window.swal) window.showHomeNotice();
            var queryButton = document.getElementById('Query');
            var joinButton = document.getElementById('Join');
            if (queryButton && window.jQuery && window.layer) {
                queryButton.addEventListener('click', function () {
                    layer.open({type: 2, title: '成员查询', shadeClose: true, scrollbar: false, shade: false, area: ['380px', '560px'], content: '/indexs.php?my=Query'});
                });
            }
            if (joinButton && window.jQuery && window.layer) {
                joinButton.addEventListener('click', function () {
                    layer.open({type: 2, title: '申请加入', shadeClose: true, scrollbar: false, shade: false, maxmin: true, area: ['315px', '538px'], content: '/indexs.php?my=Join'});
                });
            }
            var sentenceText = document.getElementById('hitokoto_text');
            var sentenceFrom = document.getElementById('hitokoto_from');
            if (sentenceText && sentenceFrom) {
                var loadSentence = function () {
                    fetch('https://v1.hitokoto.cn', {credentials: 'omit'})
                        .then(function (response) { return response.ok ? response.json() : null; })
                        .then(function (data) {
                            if (!data) return;
                            sentenceText.textContent = data.hitokoto || '';
                            sentenceFrom.textContent = '—— ' + (data.from_who || '') + '「' + (data.from || '无名氏') + '」';
                        })
                        .catch(function () {});
                };
                if ('requestIdleCallback' in window) requestIdleCallback(loadSentence, {timeout: 3000});
                else setTimeout(loadSentence, 1800);
            }
        }, {once: true});

        /* 进入视口淡入上移：只做一次，走完就摘掉 observer，滚动不掉帧 */
        (function () {
            var items = Array.prototype.slice.call(document.querySelectorAll('[data-reveal]'));
            if (!items.length) return;
            if (!('IntersectionObserver' in window)) {
                items.forEach(function (el) { el.classList.add('is-in'); });
                return;
            }
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) return;
                    entry.target.classList.add('is-in');
                    io.unobserve(entry.target);
                });
            }, {rootMargin: '0px 0px -8% 0px', threshold: 0.08});
            items.forEach(function (el) { io.observe(el); });
        })();
    </script>
    <div id="mobile-drawer-overlay" onclick="toggleMobileDrawer()"></div>
    <div id="mobile-drawer">
        <div class="drawer-header"><?php echo htmlspecialchars(conf('Name'), ENT_QUOTES, 'UTF-8'); ?></div>
        <div class="drawer-body">
            <a href="index.php"><i class="fa fa-house me-2"></i>首页</a>
            <a href="works.php"><i class="fa fa-layer-group me-2"></i>作品项目</a>
            <a href="news.php"><i class="fa fa-bullhorn me-2"></i>公告与动态</a>
            <a href="about.php"><i class="fa fa-circle-info me-2"></i>关于我们</a>
            <a href="#one-services"><i class="fa fa-boxes me-2"></i>服务能力</a>
            <a href="#one-features"><i class="fa fa-users me-2"></i>团队成员</a>
            <a href="#one-call-to-action"><i class="fa fa-rocket me-2"></i>成员查询 / 加入</a>
        </div>
    </div>
    <script>
        function toggleMobileDrawer() {
            document.getElementById('mobile-drawer').classList.toggle('open');
            document.getElementById('mobile-drawer-overlay').classList.toggle('open');
        }
    </script>
</body>
</html>
