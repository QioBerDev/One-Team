<?php
include './Common/Core_brain.php';
function news_h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function news_type_label($type) {
    $labels = array('article' => '文章', 'moment' => '动态', 'notice' => '公告');
    return isset($labels[$type]) ? $labels[$type] : '内容';
}
function news_body($value) {
    $text = news_h($value);
    $linked = preg_replace_callback('~(?<![\\w"=])((?:https?://|t\\.me/)[^\\s<]+)~iu', function ($match) {
        $url = $match[1];
        $suffix = '';
        while ($url !== '' && preg_match('/[.,!?;:，。！？；：、)\]】》]$/u', $url)) {
            $suffix = substr($url, -1) . $suffix;
            $url = substr($url, 0, -1);
        }
        if ($url === '') {
            return $match[0];
        }
        $href = preg_match('~^https?://~i', $url) ? $url : 'https://' . $url;
        return '<a href="' . news_h($href) . '" target="_blank" rel="noopener noreferrer">' . $url . '</a>' . $suffix;
    }, $text);
    return nl2br($linked, false);
}
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$entry = false;
$items = array();
$contentUnavailable = false;
try {
    if ($DB->query('SELECT 1 FROM nteam_content LIMIT 0') === false) {
        $contentUnavailable = true;
        http_response_code(503);
    } elseif ($id > 0) {
        $entry = $DB->getRow("SELECT id, type, title, summary, body, cover_url, is_pinned, published_at FROM nteam_content WHERE id = ? AND status = 1 LIMIT 1", array($id));
        if ($entry === false) {
            http_response_code(404);
        }
    } else {
        $items = $DB->getAll("SELECT id, type, title, summary, cover_url, is_pinned, published_at FROM nteam_content WHERE status = 1 ORDER BY is_pinned DESC, published_at DESC, id DESC LIMIT 50");
        if ($items === false) {
            $contentUnavailable = true;
            http_response_code(503);
        }
    }
} catch (Throwable $e) {
    http_response_code(503);
    $contentUnavailable = true;
}
$pageTitle = $entry ? $entry['title'] : '公告与动态';
?>
<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title><?php echo news_h($pageTitle . ' - ' . conf('Name')); ?></title>
  <meta name="description" content="公告、文章与团队动态">
  <link rel="shortcut icon" href="assets/media/various/favicon.png">
  <link rel="stylesheet" id="css-main" href="assets/css/oneui.min-5.6.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/site-footer.v7.css?v=20261105q">
    <style>
        /* 团队动态卡片：水晶星角花 */
        .news-deco-card {
            position: relative;
            overflow: hidden;
        }
        .news-deco-card::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image:
                url("/assets/img/decor/category-tl.webp"),
                url("/assets/img/decor/category-br.webp");
            background-size: min(34%, 116px) auto, min(32%, 110px) auto;
            background-position: 0% 0%, 100% 100%;
            background-repeat: no-repeat, no-repeat;
            opacity: .22;
            z-index: 0;
            pointer-events: none;
        }
        .news-deco-card > * {
            position: relative;
            z-index: 1;
        }
        .news-deco-card > .block-content {
            padding-top: 2.6rem;
        }
        .dark-mode .news-deco-card::before {
            opacity: .20;
        }
    </style>
</head>
<body>
<div id="page-container" class="main-content-boxed">
<header id="page-header"><div class="content-header">
  <a class="fw-bold fs-lg tracking-wider text-dual" href="index.php"><img class="hd-brand-logo" src="<?php echo htmlspecialchars(site_logo_url(), ENT_QUOTES, 'UTF-8'); ?>" alt="Logo"><?php echo news_h(conf('Name')); ?> - <?php echo news_h(conf('SiteName')); ?></a>
  <div class="d-flex align-items-center">
    <ul class="nav-main nav-main-horizontal nav-main-hover d-none d-lg-block me-2">
      <li class="nav-main-item"><a class="nav-main-link" href="index.php"><i class="nav-main-link-icon fa fa-house"></i><span class="nav-main-link-name"><strong>首页</strong></span></a></li>
      <li class="nav-main-item"><a class="nav-main-link" href="works.php"><i class="nav-main-link-icon fa fa-layer-group"></i><span class="nav-main-link-name"><strong>作品项目</strong></span></a></li>
      <li class="nav-main-item"><a class="nav-main-link active" href="news.php"><i class="nav-main-link-icon fa fa-bullhorn"></i><span class="nav-main-link-name"><strong>公告与动态</strong></span></a></li>
      <li class="nav-main-item"><a class="nav-main-link" href="about.php"><i class="nav-main-link-icon fa fa-circle-info"></i><span class="nav-main-link-name"><strong>关于我们</strong></span></a></li>
    </ul>
    <a class="btn btn-alt-secondary d-lg-none" href="index.php" aria-label="返回首页"><i class="fa fa-house"></i></a>
  </div>
</div></header>
<main id="main-container">
  <div class="bg-body-extra-light"><div class="content content-full content-boxed">
    <div class="py-5">
      <?php if ($id > 0 && $entry) { ?>
        <article class="block block-rounded news-deco-card">
          <div class="block-header block-header-default"><span class="badge bg-primary"><?php echo news_h(news_type_label($entry['type'])); ?></span><?php if ((int)$entry['is_pinned'] === 1) { ?> <span class="badge bg-warning">置顶</span><?php } ?></div>
          <div class="block-content">
            <h1 class="h2 fw-bold mb-2"><?php echo news_h($entry['title']); ?></h1>
            <p class="text-muted fs-sm"><?php echo news_h($entry['published_at']); ?></p>
            <?php if ($entry['summary'] !== '') { ?><p class="fs-lg text-muted"><?php echo news_h($entry['summary']); ?></p><?php } ?>
            <?php if ($entry['cover_url'] !== '') { ?><img class="img-fluid rounded mb-4" src="<?php echo news_h($entry['cover_url']); ?>" alt="<?php echo news_h($entry['title']); ?>" loading="lazy"><?php } ?>
            <div class="fs-md" style="white-space:normal;line-height:1.9"><?php echo news_body($entry['body']); ?></div>
          </div>
        </article>
        <a class="btn btn-alt-secondary" href="news.php"><i class="fa fa-arrow-left me-1"></i>返回列表</a>
      <?php } elseif (isset($contentUnavailable) && $contentUnavailable) { ?>
        <div class="alert alert-warning">内容系统尚未安装，请稍后访问。</div>
      <?php } elseif ($id > 0) { ?>
        <div class="alert alert-warning">内容不存在或尚未发布。</div>
        <a class="btn btn-alt-secondary" href="news.php">返回列表</a>
      <?php } else { ?>
        <div class="mb-4"><h1 class="h2 fw-bold mb-1">公告与动态</h1><p class="text-muted">团队公告、文章和日常更新</p></div>
        <?php if (!$items) { ?><div class="block block-rounded"><div class="block-content text-center text-muted py-5">暂时还没有已发布内容。</div></div><?php } ?>
        <?php foreach ($items as $item) { ?>
          <article class="block block-rounded block-link-shadow news-deco-card">
            <div class="block-content">
              <p class="mb-2"><span class="badge bg-primary"><?php echo news_h(news_type_label($item['type'])); ?></span><?php if ((int)$item['is_pinned'] === 1) { ?> <span class="badge bg-warning">置顶</span><?php } ?><span class="text-muted fs-sm ms-2"><?php echo news_h($item['published_at']); ?></span></p>
              <h2 class="h4"><a href="news.php?id=<?php echo (int)$item['id']; ?>"><?php echo news_h($item['title']); ?></a></h2>
              <?php if ($item['cover_url'] !== '') { ?><a href="news.php?id=<?php echo (int)$item['id']; ?>"><img class="img-fluid rounded mb-3" src="<?php echo news_h($item['cover_url']); ?>" alt="<?php echo news_h($item['title']); ?>" loading="lazy" style="max-height:260px;object-fit:cover"></a><?php } ?>
              <p class="text-muted mb-0"><?php echo news_h($item['summary'] !== '' ? $item['summary'] : (function_exists('mb_substr') ? mb_substr($item['body'], 0, 160, 'UTF-8') : substr($item['body'], 0, 160))); ?></p>
              <a class="btn btn-sm btn-alt-primary mt-3" href="news.php?id=<?php echo (int)$item['id']; ?>">阅读全文</a>
            </div>
          </article>
        <?php } ?>
      <?php } ?>
    </div>
  </div></div>
</main>
<?php require __DIR__ . '/Common/site_footer.php'; ?>
</div>
<script src="assets/js/oneui.app.min-5.6.js"></script>
</body>
</html>
