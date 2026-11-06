<?php
$mod = 'admin';
include('../Common/Core_brain.php');
if (!$isLogin) {
    header('Location:./login.php');
    exit;
}
if ((int)$adminData['adminRank'] !== 1) {
    http_response_code(403);
    exit('无权管理内容');
}

if (empty($_SESSION['content_csrf'])) {
    $_SESSION['content_csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['content_csrf'];
$types = array('article' => '文章', 'moment' => '动态', 'notice' => '公告');
$statusLabels = array(0 => '草稿', 1 => '已发布');
$error = '';
$schemaReady = false;
try {
    $schemaReady = $DB->query('SELECT 1 FROM nteam_content LIMIT 0') !== false;
} catch (Throwable $e) {
    $schemaReady = false;
}
if (!$schemaReady) {
    $error = '内容表尚未创建，请先导入项目根目录的 content-system-migration.sql';
}

function content_db_failed($message = '内容模块暂时不可用，请稍后重试。') {
    http_response_code(503);
    exit(content_h($message));
}
function content_h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function content_redirect() {
    header('Location: content.php');
    exit;
}
function content_substr($value, $length) {
    return function_exists('mb_substr') ? mb_substr($value, 0, $length, 'UTF-8') : substr($value, 0, $length);
}
function content_log($action) {
    global $DB, $Gets;
    $ip = $Gets->ip();
    $label = function_exists('mb_substr') ? mb_substr($action, 0, 200, 'UTF-8') : substr($action, 0, 200);
    $DB->exec("INSERT INTO nteam_log (adminUser, type, data, ip, city) VALUES (?, ?, NOW(), ?, '')", array($_SESSION['adminUser'], $label, $ip));
}

if ($schemaReady && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf']) || !hash_equals($csrf, (string)$_POST['csrf'])) {
        http_response_code(400);
        exit('请求校验失败，请刷新页面重试');
    }
    $action = isset($_POST['action']) ? (string)$_POST['action'] : '';
    if ($action === 'delete') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id < 1) {
            $error = '内容编号无效';
        } else {
            $row = $DB->getRow('SELECT id, title FROM nteam_content WHERE id = ?', array($id));
            if (!$row) {
                $error = '内容不存在';
            } else {
                $DB->exec('DELETE FROM nteam_content WHERE id = ?', array($id));
                content_log('删除内容：' . content_substr($row['title'], 80));
                content_redirect();
            }
        }
    } elseif ($action === 'save') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $type = isset($_POST['type']) ? (string)$_POST['type'] : '';
        $title = trim(isset($_POST['title']) ? (string)$_POST['title'] : '');
        $summary = trim(isset($_POST['summary']) ? (string)$_POST['summary'] : '');
        $body = trim(isset($_POST['body']) ? (string)$_POST['body'] : '');
        $coverUrl = trim(isset($_POST['cover_url']) ? (string)$_POST['cover_url'] : '');
        $status = isset($_POST['status']) ? (int)$_POST['status'] : 0;
        $pinned = isset($_POST['is_pinned']) ? 1 : 0;
        $titleLength = function_exists('mb_strlen') ? mb_strlen($title, 'UTF-8') : strlen($title);
        $summaryLength = function_exists('mb_strlen') ? mb_strlen($summary, 'UTF-8') : strlen($summary);

        if (!isset($types[$type])) {
            $error = '内容类型无效';
        } elseif ($title === '' || $titleLength > 200) {
            $error = '标题不能为空，且最多 200 个字符';
        } elseif ($body === '' || strlen($body) > 2000000) {
            $error = '正文不能为空，且不能超过 2MB';
        } elseif ($summaryLength > 500) {
            $error = '摘要最多 500 个字符';
        } elseif (!in_array($status, array(0, 1), true)) {
            $error = '发布状态无效';
        } elseif ($coverUrl !== '' && (!filter_var($coverUrl, FILTER_VALIDATE_URL) || !in_array(strtolower((string)parse_url($coverUrl, PHP_URL_SCHEME)), array('http', 'https'), true))) {
            $error = '封面地址只接受完整的 HTTP/HTTPS URL';
        } else {
            $existing = $id > 0 ? $DB->getRow('SELECT id, status, published_at FROM nteam_content WHERE id = ?', array($id)) : false;
            if ($id > 0 && !$existing) {
                $error = '要编辑的内容不存在';
            } else {
                $publishedAt = $existing ? $existing['published_at'] : null;
                if ($status === 1 && (!$existing || (int)$existing['status'] !== 1 || empty($publishedAt))) {
                    $publishedAt = date('Y-m-d H:i:s');
                }
                if ($id > 0) {
                    $saved = $DB->exec('UPDATE nteam_content SET type = ?, title = ?, summary = ?, body = ?, cover_url = ?, is_pinned = ?, status = ?, published_at = ? WHERE id = ?', array($type, $title, $summary, $body, $coverUrl, $pinned, $status, $publishedAt, $id));
                } else {
                    $saved = $DB->exec('INSERT INTO nteam_content (type, title, summary, body, cover_url, is_pinned, status, created_by, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', array($type, $title, $summary, $body, $coverUrl, $pinned, $status, $_SESSION['adminUser'], $publishedAt));
                }
                if ($saved === false) {
                    $error = '保存失败：' . $DB->error() . '；SQLSTATE=' . $DB->error()[0];
                } else {
                    content_log(($id > 0 ? '编辑内容：' : '新增内容：') . $title);
                    content_redirect();
                }
            }
        }
    } else {
        $error = '不支持的操作';
    }
}

$editId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$editing = ($schemaReady && $editId > 0) ? $DB->getRow('SELECT * FROM nteam_content WHERE id = ?', array($editId)) : false;
if ($editId > 0 && !$editing) {
    $error = '要编辑的内容不存在';
}
if ($schemaReady && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save' && $error !== '') {
    $editing = array_merge($editing ?: array(), $_POST);
}
$items = $schemaReady ? $DB->getAll('SELECT id, type, title, status, is_pinned, updated_at, published_at FROM nteam_content ORDER BY status DESC, is_pinned DESC, COALESCE(published_at, updated_at) DESC, id DESC') : array();
?>
<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>内容与公告管理 - <?php echo content_h(conf('Name')); ?></title>
  <link rel="stylesheet" id="css-main" href="../assets/admin/css/codebase.min-5.5.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/admin/css/decor.css">
</head>
<body>
<div id="page-container" class="sidebar-o enable-page-overlay side-scroll page-header-modern main-content-boxed">
<?php require_once 'head.php'; ?>
<main id="main-container">
<div class="content">
  <h2 class="content-heading"><a href="index.php">后台首页</a> &nbsp; <i class="fa fa-angle-right"></i> &nbsp; 内容与公告</h2>
  <?php if ($error !== '') { ?><div class="alert alert-danger"><?php echo content_h($error); ?></div><?php } ?>
  <div class="block block-rounded">
    <div class="block-header block-header-default"><h3 class="block-title"><b><?php echo $editing ? '编辑内容' : '发布文章 / 动态 / 公告'; ?></b></h3></div>
    <div class="block-content">
      <form method="post" action="content.php<?php echo $editing && isset($editing['id']) ? '?id=' . (int)$editing['id'] : ''; ?>">
        <input type="hidden" name="csrf" value="<?php echo content_h($csrf); ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?php echo $editing && isset($editing['id']) ? (int)$editing['id'] : 0; ?>">
        <div class="row mb-3">
          <div class="col-md-4"><label class="form-label" for="type">类型</label><select class="form-select" id="type" name="type" required><?php foreach ($types as $key => $label) { $selected = isset($editing['type']) && $editing['type'] === $key ? ' selected' : ''; ?><option value="<?php echo content_h($key); ?>"<?php echo $selected; ?>><?php echo content_h($label); ?></option><?php } ?></select></div>
          <div class="col-md-8"><label class="form-label" for="title">标题</label><input class="form-control" id="title" name="title" maxlength="200" required value="<?php echo content_h(isset($editing['title']) ? $editing['title'] : ''); ?>"></div>
        </div>
        <div class="mb-3"><label class="form-label" for="summary">摘要（可选，最多 500 字）</label><input class="form-control" id="summary" name="summary" maxlength="500" value="<?php echo content_h(isset($editing['summary']) ? $editing['summary'] : ''); ?>"></div>
        <div class="mb-3"><label class="form-label" for="cover_url">封面图片 URL（可选，仅 HTTP/HTTPS）</label><input class="form-control" type="url" id="cover_url" name="cover_url" maxlength="500" value="<?php echo content_h(isset($editing['cover_url']) ? $editing['cover_url'] : ''); ?>"></div>
        <div class="mb-3"><label class="form-label" for="body">正文（纯文本，前台会安全转义并保留换行）</label><textarea class="form-control" id="body" name="body" rows="10" required><?php echo content_h(isset($editing['body']) ? $editing['body'] : ''); ?></textarea></div>
        <div class="row align-items-center mb-4">
          <div class="col-md-4"><label class="form-label" for="status">发布状态</label><select class="form-select" id="status" name="status"><option value="0"<?php echo isset($editing['status']) && (int)$editing['status'] === 0 ? ' selected' : ''; ?>>草稿</option><option value="1"<?php echo isset($editing['status']) && (int)$editing['status'] === 1 ? ' selected' : ''; ?>>立即发布</option></select></div>
          <div class="col-md-4 pt-md-4"><label><input type="checkbox" name="is_pinned" value="1"<?php echo !empty($editing['is_pinned']) ? ' checked' : ''; ?>> 置顶（公告和动态列表优先显示）</label></div>
          <div class="col-md-4 text-md-end pt-3"><button class="btn btn-primary" type="submit"><i class="fa fa-check me-1"></i>保存</button> <a class="btn btn-alt-secondary" href="content.php">重置</a></div>
        </div>
      </form>
    </div>
  </div>
  <div class="block block-rounded">
    <div class="block-header block-header-default"><h3 class="block-title"><b>内容列表</b></h3></div>
    <div class="block-content block-content-full table-responsive">
      <table class="table table-vcenter">
        <thead><tr><th>类型</th><th>标题</th><th>状态</th><th>置顶</th><th>更新时间</th><th class="text-end">操作</th></tr></thead>
        <tbody>
        <?php if (!$items) { ?><tr><td colspan="6" class="text-center text-muted">还没有内容</td></tr><?php } else { foreach ($items as $item) { ?>
          <tr>
            <td><?php echo content_h(isset($types[$item['type']]) ? $types[$item['type']] : '未知'); ?></td>
            <td><?php echo content_h($item['title']); ?></td>
            <td><?php echo content_h(isset($statusLabels[(int)$item['status']]) ? $statusLabels[(int)$item['status']] : '未知'); ?></td>
            <td><?php echo (int)$item['is_pinned'] === 1 ? '是' : '否'; ?></td>
            <td><?php echo content_h($item['updated_at']); ?></td>
            <td class="text-end"><a class="btn btn-sm btn-alt-primary" href="content.php?id=<?php echo (int)$item['id']; ?>">编辑</a>
              <form method="post" action="content.php" class="d-inline" onsubmit="return confirm('确定删除这条内容？');"><input type="hidden" name="csrf" value="<?php echo content_h($csrf); ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo (int)$item['id']; ?>"><button class="btn btn-sm btn-alt-danger" type="submit">删除</button></form>
            </td>
          </tr>
        <?php } } ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</main>
<?php require_once 'foot.php'; ?>
</div>
<script src="../assets/admin/js/codebase.app.min-5.5.js"></script>
</body>
</html>
