<?php
/**
 * 后台 · 卡密管理
 * 生成（余额卡 / VIP 卡 / SVIP 卡）、列表、停用启用、按批次导出 CSV
 */
$mod = 'admin';
include(__DIR__ . '/../Common/Core_brain.php');
require_once(__DIR__ . '/../Common/Schema.php');
require_once(__DIR__ . '/../Common/User.php');
require_once(__DIR__ . '/../Common/Vip.php');
require_once(__DIR__ . '/../Common/Wallet.php');

pay_schema_init($DB);

if ($adminData['adminRank'] != 1 && $adminData['adminRank'] != 2) {
    echo '您的账号没有权限使用此功能';
    exit;
}

$flash     = '';
$generated = array();

/* ---------- 导出 CSV（放在任何输出之前） ---------- */
if (isset($_GET['export'])) {
    $batch = isset($_GET['batch']) ? trim((string)$_GET['batch']) : '';
    $w = $batch !== '' ? " WHERE `batch`=?" : '';
    $args = $batch !== '' ? array($batch) : array();
    $rows = $DB->getAll("SELECT * FROM `nteam_card` {$w} ORDER BY `id` DESC LIMIT 5000", $args);
    if (!is_array($rows)) { $rows = array(); }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="cards_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM，Excel 不乱码
    fputcsv($out, array('ID', '批次', '卡密', '类型', '面额', '天数', '状态', '使用人UID', '使用时间', '备注'));
    foreach ($rows as $r) {
        $st = array(0 => '未使用', 1 => '已使用', 2 => '已停用');
        fputcsv($out, array(
            $r['id'], $r['batch'], $r['code'], card_type_name($r['type']),
            $r['value'], $r['days'], isset($st[(int)$r['status']]) ? $st[(int)$r['status']] : '未知',
            $r['user_id'], $r['used_time'] > 0 ? date('Y-m-d H:i', (int)$r['used_time']) : '', $r['remark']
        ));
    }
    fclose($out);
    exit;
}

/* ---------- 写操作 ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = isset($_POST['act']) ? (string)$_POST['act'] : '';

    if ($act === 'gen') {
        $type   = isset($_POST['type']) ? (string)$_POST['type'] : 'balance';
        $value  = round((float)(isset($_POST['value']) ? $_POST['value'] : 0), 2);
        $days   = (int)(isset($_POST['days']) ? $_POST['days'] : 0);
        $count  = (int)(isset($_POST['count']) ? $_POST['count'] : 0);
        $prefix = isset($_POST['prefix']) ? (string)$_POST['prefix'] : '';
        $remark = isset($_POST['remark']) ? (string)$_POST['remark'] : '';

        $generated = card_generate($DB, $type, $value, $days, $count, $prefix, $remark);
        if ($generated) {
            $flash = '已生成 ' . count($generated) . ' 张卡密，请复制保存（只显示这一次）';
            $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('"
                . addslashes($_SESSION['adminUser']) . "','生成卡密 " . count($generated) . " 张', NOW(), '', '')");
        } else {
            $flash = '生成失败：类型、面额/天数或数量不对（单次 1-500 张）';
        }
    } elseif ($act === 'disable' || $act === 'enable') {
        $id = (int)(isset($_POST['id']) ? $_POST['id'] : 0);
        if ($id > 0) {
            $newStatus = $act === 'disable' ? 2 : 0;
            $DB->query("UPDATE `nteam_card` SET `status`=? WHERE `id`=? AND `status`<>1", array($newStatus, $id));
            $flash = 'UID#' . $id . ' 卡密状态已更新';
        }
        header('Location: card.php');
        exit;
    } elseif ($act === 'del') {
        $id = (int)(isset($_POST['id']) ? $_POST['id'] : 0);
        if ($id > 0) {
            $DB->query("DELETE FROM `nteam_card` WHERE `id`=? AND `status`<>1", array($id));
            $flash = '未使用的卡密已删除';
        }
        header('Location: card.php');
        exit;
    }
}

/* ---------- 列表 ---------- */
$status = isset($_GET['status']) && $_GET['status'] !== '' ? (int)$_GET['status'] : null;
$batch  = isset($_GET['batch']) ? trim((string)$_GET['batch']) : '';
$kw     = isset($_GET['kw']) ? trim((string)$_GET['kw']) : '';
$page   = max(1, (int)(isset($_GET['page']) ? $_GET['page'] : 1));
$per    = 20;

$where = '1';
$args  = array();
if ($status !== null) { $where .= ' AND `status`=?'; $args[] = $status; }
if ($batch !== '')    { $where .= ' AND `batch`=?';  $args[] = $batch; }
if ($kw !== '')       { $where .= ' AND (`code` LIKE ? OR `remark` LIKE ?)'; $args[] = '%' . $kw . '%'; $args[] = '%' . $kw . '%'; }

$total = (int)$DB->getColumn("SELECT COUNT(*) FROM `nteam_card` WHERE {$where}", $args);
$pages = max(1, (int)ceil($total / $per));
if ($page > $pages) { $page = $pages; }
$offset = ($page - 1) * $per;
$rows = $DB->getAll("SELECT * FROM `nteam_card` WHERE {$where} ORDER BY `id` DESC LIMIT {$offset},{$per}", $args);
if (!is_array($rows)) { $rows = array(); }

$cntAll  = (int)$DB->getColumn("SELECT COUNT(*) FROM `nteam_card`");
$cntUn   = (int)$DB->getColumn("SELECT COUNT(*) FROM `nteam_card` WHERE `status`=0");
$cntUsed = (int)$DB->getColumn("SELECT COUNT(*) FROM `nteam_card` WHERE `status`=1");
$sumVal  = (float)$DB->getColumn("SELECT IFNULL(SUM(`value`),0) FROM `nteam_card` WHERE `type`='balance' AND `status`=0");

function cd_pagelink($page, $kw, $status, $batch)
{
    $qs = array_filter(array('page' => $page, 'kw' => $kw, 'status' => $status, 'batch' => $batch),
        function ($v) { return $v !== '' && $v !== null; });
    return 'card.php' . ($qs ? '?' . http_build_query($qs) : '');
}
?>
<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title><?php echo conf('Name');?> - 卡密管理</title>
  <link rel="shortcut icon" href="../assets/img/favicon.png">
  <link rel="stylesheet" id="css-main" href="../assets/admin/css/codebase.min-5.5.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="../assets/admin/css/decor.css">
  <style>
    .cd-code { font-family: ui-monospace, Consolas, monospace; font-size: 12.5px; letter-spacing: .02em; }
    .cd-new { background: #f3f0ff; border: 1px dashed #8f55f2; border-radius: 8px; padding: 14px; }
    .cd-new code { display: block; padding: 3px 0; color: #5b2ca8; font-weight: 600; }
  </style>
</head>
<body>
<div id="page-container" class="sidebar-o enable-page-overlay side-scroll page-header-modern main-content-boxed">
<?php require_once __DIR__ . '/head.php'; ?>
<main id="main-container">
  <div class="content">
  <?php require_once __DIR__ . '/_tabs.php'; admin_tabs('pay', 'card.php', $adminData['adminRank']); ?>

    <div class="row g-3 mb-3">
      <?php
      $stats = array(
          array('全部卡密', $cntAll, 'fa-ticket', 'text-primary'),
          array('未使用', $cntUn, 'fa-circle-check', 'text-success'),
          array('已使用', $cntUsed, 'fa-user-check', 'text-muted'),
          array('未兑余额面额', '¥' . number_format($sumVal, 2), 'fa-wallet', 'text-elegance'),
      );
      foreach ($stats as $s) { ?>
      <div class="col-6 col-md-3">
        <div class="block block-rounded block-link-shadow mb-0">
          <div class="block-content block-content-full">
            <div class="d-flex justify-content-between align-items-center">
              <div><div class="fs-sm text-muted mb-1"><?php echo $s[0]; ?></div><div class="h4 fw-bold mb-0 <?php echo $s[3]; ?>"><?php echo $s[1]; ?></div></div>
              <i class="fa <?php echo $s[2]; ?> fa-2x opacity-25"></i>
            </div>
          </div>
        </div>
      </div>
      <?php } ?>
    </div>

    <?php if ($flash !== '') { ?>
    <div class="alert alert-info d-flex align-items-center" role="alert">
      <i class="fa fa-circle-info me-2"></i><div><?php echo $flash; ?></div>
    </div>
    <?php } ?>

    <?php if ($generated) { ?>
    <div class="block block-rounded mb-3">
      <div class="block-header block-header-default"><h3 class="block-title">本次生成的卡密</h3></div>
      <div class="block-content">
        <div class="cd-new"><?php foreach ($generated as $c) { ?><code><?php echo htmlspecialchars($c, ENT_QUOTES, 'UTF-8'); ?></code><?php } ?></div>
        <p class="fs-sm text-muted mt-2 mb-0">复制后请妥善保管，关闭本页不会再次展示。可到下方列表按批次导出 CSV。</p>
      </div>
    </div>
    <?php } ?>

    <!-- 生成卡密 -->
    <div class="block block-rounded mb-3">
      <div class="block-header block-header-default">
        <h3 class="block-title"><i class="fa fa-plus me-1 text-muted"></i> 生成卡密</h3>
      </div>
      <div class="block-content">
        <form method="post" class="row g-3 align-items-end">
          <input type="hidden" name="act" value="gen">
          <div class="col-6 col-md-2">
            <label class="form-label">类型</label>
            <select class="form-select" name="type" id="cdType" onchange="cdToggle()">
              <?php foreach (card_types() as $k => $n) { ?>
              <option value="<?php echo $k; ?>"><?php echo $n; ?></option>
              <?php } ?>
            </select>
          </div>
          <div class="col-6 col-md-2" id="cdValueBox">
            <label class="form-label">面额（元）</label>
            <input class="form-control" type="number" name="value" value="10.00" step="0.01" min="0.01">
          </div>
          <div class="col-6 col-md-2" id="cdDaysBox" style="display:none">
            <label class="form-label">会员天数</label>
            <input class="form-control" type="number" name="days" value="31" min="1">
          </div>
          <div class="col-6 col-md-1">
            <label class="form-label">数量</label>
            <input class="form-control" type="number" name="count" value="10" min="1" max="500">
          </div>
          <div class="col-6 col-md-2">
            <label class="form-label">前缀（选填）</label>
            <input class="form-control" type="text" name="prefix" maxlength="4" placeholder="如 ONE">
          </div>
          <div class="col-12 col-md-3">
            <label class="form-label">备注（选填）</label>
            <input class="form-control" type="text" name="remark" maxlength="100" placeholder="批次用途">
          </div>
          <div class="col-12 col-md-auto">
            <button class="btn btn-alt-primary" type="submit"><i class="fa fa-wand-magic-sparkles me-1"></i>生成</button>
          </div>
        </form>
      </div>
    </div>

    <!-- 列表 -->
    <div class="block block-rounded">
      <div class="block-header block-header-default">
        <h3 class="block-title"><i class="fa fa-list me-1 text-muted"></i> 卡密列表</h3>
        <div class="block-options">
          <a class="btn btn-sm btn-alt-secondary" href="card.php?export=1<?php echo $batch !== '' ? '&batch=' . urlencode($batch) : ''; ?>">
            <i class="fa fa-file-csv me-1"></i>导出 CSV
          </a>
        </div>
      </div>
      <div class="block-content">
        <form method="get" class="row g-2 mb-3">
          <div class="col-6 col-md-3"><input class="form-control" type="text" name="kw" value="<?php echo htmlspecialchars($kw, ENT_QUOTES, 'UTF-8'); ?>" placeholder="卡密 / 备注"></div>
          <div class="col-6 col-md-3"><input class="form-control" type="text" name="batch" value="<?php echo htmlspecialchars($batch, ENT_QUOTES, 'UTF-8'); ?>" placeholder="批次号"></div>
          <div class="col-6 col-md-2">
            <select class="form-select" name="status">
              <option value="">全部状态</option>
              <option value="0" <?php echo $status === 0 ? 'selected' : ''; ?>>未使用</option>
              <option value="1" <?php echo $status === 1 ? 'selected' : ''; ?>>已使用</option>
              <option value="2" <?php echo $status === 2 ? 'selected' : ''; ?>>已停用</option>
            </select>
          </div>
          <div class="col-6 col-md-2"><button class="btn btn-alt-primary" type="submit">筛选</button></div>
        </form>

        <?php if (!$rows) { ?>
        <div class="text-center text-muted fs-sm py-4"><i class="fa fa-ticket fa-2x d-block mb-2 opacity-50"></i>还没有卡密，用上方表单生成一批。</div>
        <?php } else { ?>
        <div class="table-responsive">
          <table class="table table-striped table-vcenter">
            <thead><tr><th>卡密</th><th>类型 / 内容</th><th>批次</th><th>状态</th><th>使用人</th><th>备注</th><th class="text-end">操作</th></tr></thead>
            <tbody>
              <?php foreach ($rows as $r) {
                  $st = (int)$r['status'];
                  $stName = array(0 => '未使用', 1 => '已使用', 2 => '已停用');
                  $stCls  = array(0 => 'success', 1 => 'secondary', 2 => 'danger');
                  $content = $r['type'] === 'balance'
                      ? '¥' . number_format((float)$r['value'], 2)
                      : card_type_name($r['type']) . ' · ' . (int)$r['days'] . ' 天';
              ?>
              <tr>
                <td class="cd-code"><?php echo htmlspecialchars($r['code'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($content, ENT_QUOTES, 'UTF-8'); ?></td>
                <td class="fs-xs text-muted"><?php echo htmlspecialchars($r['batch'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><span class="badge bg-<?php echo $stCls[$st]; ?>"><?php echo $stName[$st]; ?></span></td>
                <td class="fs-xs"><?php echo $st === 1 ? 'UID ' . (int)$r['user_id'] . '<br>' . date('m-d H:i', (int)$r['used_time']) : '—'; ?></td>
                <td class="fs-xs text-muted"><?php echo htmlspecialchars($r['remark'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td class="text-end">
                  <?php if ($st !== 1) { ?>
                  <form method="post" style="display:inline">
                    <input type="hidden" name="act" value="<?php echo $st === 0 ? 'disable' : 'enable'; ?>">
                    <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                    <button class="btn btn-sm btn-alt-secondary" type="submit"><?php echo $st === 0 ? '停用' : '启用'; ?></button>
                  </form>
                  <form method="post" style="display:inline" onsubmit="return confirm('确定删除这张未使用的卡密？');">
                    <input type="hidden" name="act" value="del">
                    <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                    <button class="btn btn-sm btn-alt-danger" type="submit">删除</button>
                  </form>
                  <?php } else { echo '—'; } ?>
                </td>
              </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-3">
          <span class="text-muted fs-sm">共 <?php echo $total; ?> 张 · 第 <?php echo $page; ?> / <?php echo $pages; ?> 页</span>
          <div class="btn-group">
            <a class="btn btn-sm btn-alt-secondary <?php echo $page <= 1 ? 'disabled' : ''; ?>" href="<?php echo cd_pagelink(max(1, $page - 1), $kw, $status, $batch); ?>">上一页</a>
            <a class="btn btn-sm btn-alt-secondary <?php echo $page >= $pages ? 'disabled' : ''; ?>" href="<?php echo cd_pagelink(min($pages, $page + 1), $kw, $status, $batch); ?>">下一页</a>
          </div>
        </div>
        <?php } ?>
      </div>
    </div>

  </div>
</main>
<?php include __DIR__ . '/foot.php'; ?>
</div>
<script src="../assets/admin/js/codebase.app.min-5.5.js"></script>
<script>
function cdToggle() {
    var t = document.getElementById('cdType').value;
    document.getElementById('cdValueBox').style.display = (t === 'balance') ? '' : 'none';
    document.getElementById('cdDaysBox').style.display  = (t === 'balance') ? 'none' : '';
}
cdToggle();
</script>
</body>
</html>
