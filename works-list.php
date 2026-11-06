<?php
/* 作品项目列表页
   —— 原来是「首页作品网格 + works.php?id= 详情」，首页撤掉网格后列表搬到这里。
   works.php 顶部路由：无 id 就 require 本文件，有 id 走原详情逻辑。
   允许直接访问，所以自己做一次引导。 */
if (!defined('IN_CRONLITE')) {
    require __DIR__ . '/Common/Core_brain.php';
}

$wkRows = array();
try {
    $wkQuery = $DB->query("SELECT id, name, url, img, sketch, money, version, type, intime, status FROM nteam_project_list WHERE Audit_status=1 AND status=1 ORDER BY id DESC");
    while ($wkRow = $wkQuery->fetch()) {
        $wkRows[] = $wkRow;
    }
} catch (Throwable $e) {
    $wkRows = array();
}

$wkWeb = 0;
$wkApp = 0;
foreach ($wkRows as $wkRow) {
    if (isset($wkRow['type']) && $wkRow['type'] === 'web') {
        $wkWeb++;
    } else {
        $wkApp++;
    }
}
$wkTotal = count($wkRows);

require_once __DIR__ . '/Common/works_helper.php';
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>作品项目 - <?php echo wk_h(conf('Name'));?> - <?php echo wk_h(conf('SiteName'));?></title>
    <meta name="keywords" content="<?php echo wk_h(conf('Keywords'));?>">
    <meta name="description" content="<?php echo wk_h(conf('Descriptison'));?>">
    <link rel="shortcut icon" href="assets/media/various/favicon.png">
    <link rel="stylesheet" id="css-main" href="assets/css/oneui.min-5.6.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/site-footer.v7.css?v=20261105q">
    <link rel="stylesheet" href="/assets/css/works-apple.css?v=20261105n">
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
        <section class="wk-hero">
            <div class="wk-wrap">
                <p class="wk-eyebrow">Portfolio</p>
                <h1>作品项目</h1>
                <p class="wk-lede">独立开发、长期维护、真机在跑的项目。<br class="wk-br-sm">点开看介绍、截图和报价。</p>
            </div>
        </section>

        <div class="wk-bar" id="wkBar">
            <div class="wk-wrap wk-bar-in">
                <div class="wk-seg" id="wkFilters" role="group" aria-label="按类型筛选">
                    <button type="button" class="is-on" data-filter="all">全部 <b><?php echo $wkTotal;?></b></button>
                    <button type="button" data-filter="app">程序 <b><?php echo $wkApp;?></b></button>
                    <button type="button" data-filter="web">网页 <b><?php echo $wkWeb;?></b></button>
                </div>
                <p class="wk-hint" id="wkHint"><?php echo $wkTotal;?> 个作品</p>
            </div>
        </div>

        <section class="wk-section">
            <div class="wk-wrap">
                <?php if (!$wkRows) { ?>
                <div class="wk-empty">
                    <p>还没有上架的作品，稍后再来看看。</p>
                </div>
                <?php } else { ?>
                <div class="wk-grid" id="wkGrid">
                    <?php foreach ($wkRows as $row) {
                        $isWeb = isset($row['type']) && $row['type'] === 'web';
                        $img = wk_img($row['img']);
                        $initial = wk_first($row['name']);
                        $name = trim((string)$row['name']);
                        $meta = array();
                        if ((string)$row['money'] !== '') {
                            $meta[] = array('wk-price', '¥' . $row['money']);
                        }
                        if ((string)$row['version'] !== '') {
                            $meta[] = array('', 'v' . $row['version']);
                        }
                        $d = wk_date($row['intime']);
                        if ($d !== '') {
                            $meta[] = array('', $d);
                        }
                    ?>
                    <a class="wk-card" href="works.php?id=<?php echo (int)$row['id'];?>" data-type="<?php echo $isWeb ? 'web' : 'app';?>">
                        <div class="wk-thumb">
                            <span class="wk-mono" aria-hidden="true"><?php echo wk_h($initial);?></span>
                            <?php if ($img !== '') { ?>
                            <img src="<?php echo wk_h($img);?>" alt="<?php echo wk_h($name);?>" loading="lazy" decoding="async" onerror="this.remove()">
                            <?php } ?>
                            <span class="wk-type"><?php echo $isWeb ? '网页' : '程序';?></span>
                        </div>
                        <div class="wk-body">
                            <h3 class="wk-name"><?php echo wk_h($name);?></h3>
                            <?php if (trim((string)$row['sketch']) !== '') { ?>
                            <p class="wk-desc"><?php echo wk_h($row['sketch']);?></p>
                            <?php } ?>
                            <?php if ($meta) { ?>
                            <div class="wk-meta">
                                <?php foreach ($meta as $m) { ?>
                                <span<?php echo $m[0] !== '' ? ' class="' . $m[0] . '"' : '';?>><?php echo wk_h($m[1]);?></span>
                                <?php } ?>
                            </div>
                            <?php } ?>
                        </div>
                    </a>
                    <?php } ?>
                </div>
                <div class="wk-empty d-none" id="wkEmpty">
                    <p>这一分类下暂时没有作品。</p>
                </div>
                <?php } ?>
            </div>
        </section>

        <?php require __DIR__ . '/Common/site_footer.php'; ?>
    </main>
</div>

<script src="assets/js/oneui.app.min-5.6.js" defer></script>
<script src="assets/js/jquery.min.js" defer></script>
<script>
(function () {
    /* 筛选条：滚起来才出线（滚动边缘，不是常驻分割线） */
    var bar = document.getElementById('wkBar');
    function onScroll() {
        if (bar) {
            var top = bar.getBoundingClientRect().top;
            bar.classList.toggle('is-stuck', top <= 65);
        }
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    /* 分类筛选：纯前端，不重新请求。空态跟着切。 */
    var seg = document.getElementById('wkFilters');
    var grid = document.getElementById('wkGrid');
    if (!seg || !grid) return;
    var cards = Array.prototype.slice.call(grid.querySelectorAll('.wk-card'));
    var hint = document.getElementById('wkHint');
    var empty = document.getElementById('wkEmpty');

    seg.addEventListener('click', function (e) {
        var btn = e.target.closest('button[data-filter]');
        if (!btn) return;
        var key = btn.getAttribute('data-filter');
        Array.prototype.forEach.call(seg.querySelectorAll('button'), function (b) {
            b.classList.toggle('is-on', b === btn);
        });
        var shown = 0;
        cards.forEach(function (card) {
            var hit = key === 'all' || card.getAttribute('data-type') === key;
            card.classList.toggle('is-hidden', !hit);
            if (hit) shown++;
        });
        if (hint) hint.textContent = shown + ' 个作品';
        if (empty) empty.classList.toggle('d-none', shown !== 0);
    });
})();
</script>
</body>
</html>
