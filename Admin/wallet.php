<?php
/**
 * 后台 · 余额管理
 * 全站余额流水、手工调整（正入负出，全部留痕 + 幂等 ref）、按条件筛选导出
 */
$mod = 'admin';
include(__DIR__ . '/../Common/Core_brain.php');
require_once(__DIR__ . '/../Common/Schema.php');
require_once(__DIR__ . '/../Common/User.php');
require_once(__DIR__ . '/../Common/Wallet.php');

pay_schema_init($DB);

if ($adminData['adminRank'] != 1 && $adminData['adminRank'] != 2) {
    echo '您的账号没有权限使用此功能';
    exit;
}

$flash = '';

/* ---------- 导出 CSV ---------- */
if (isset($_GET['export'])) {
    $rows = $DB->getAll("SELECT l.*, u.`username` FROM `nteam_money_log` l
                         LEFT JOIN `nteam_user` u ON u.`id`=l.`user_id`
                         ORDER BY l.`id` DESC LIMIT 10000");
    if (!is_array($rows)) { $rows = array(); }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="money_log_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, array('ID', '时间', '用户', 'UID', '类型', '变动', '变动后余额', '说明', '操作人', '幂等键'));
    foreach ($rows as $r) {
        fputcsv($out, array(
            $r['id'], date('Y-m-d H:i:s', (int)$r['create_time']), $r['username'], $r['user_id'],
            wallet_type_name($r['type']), $r['amount'], $r['balance'], $r['memo'], $r['admin'], $r['ref']
        ));
    }
    fclose($out);
    exit;
}

/* ---------- 手工调整 ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act    = isset($_POST['act']) ? (string)$_POST['act'] : '';
    $uid    = (int)(isset($_POST['uid']) ? $_POST['uid'] : 0);
    $amount = round((float)(isset($_POST['amount']) ? $_POST['amount'] : 0), 2);
    $memo   = trim(isset($_POST['memo']) ? (string)$_POST['memo'] : '');

    if ($act === 'adjust') {
        if ($uid <= 0 || abs($amount) < 0.01) {
            $flash = '请填写正确的 UID 和金额（不能为 0）';
        } elseif (abs($amount) > 100000) {
            $flash = '单次调整不能超过 100000 元';
        } elseif (!$DB->getRow("SELECT `id` FROM `nteam_user` WHERE `id`=? LIMIT 1", array($uid))) {
            $flash = 'UID ' . $uid . ' 不存在';
        } else {
            $ref = 'admin:' . time() . ':' . bin2hex(random_bytes(4));
            $ok = wallet_change($DB, $uid, $amount, 'admin',
                ($memo !== '' ? $memo : '后台调整'), $ref, (string)$_SESSION['adminUser']);
            if ($ok) {
                $flash = 'UID ' . $uid . ' 余额已' . ($amount > 0 ? '增加' : '扣减') . ' ¥' . number_format(abs($amount), 2);
                $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('"
                    . addslashes($_SESSION['adminUser']) . "','调整 UID#{$uid} 余额 {$amount}', NOW(), '', '')");
            } else {
                $flash = '调整失败：用户不存在或扣减后余额为负';
            }
        }
    }
}

/* ---------- 列表 ---------- */
$type = isset($_GET['type']) ? trim((string)$_GET['type']) : '';
$uidF = (int)(isset($_GET['uid']) ? $_GET['uid'] : 0);
$kw   = isset($_GET['kw']) ? trim((string)$_GET['kw']) : '';
$page = max(1, (int)(isset($_GET['page']) ? $_GET['page'] : 1));
$per  = 25;

$where = '1';
$args  = array();
if ($type !== '') { $where .= ' AND l.`type`=?'; $args[] = $type; }
if ($uidF > 0)   { $where .= ' AND l.`user_id`=?'; $args[] = $uidF; }
if ($kw !== '')  { $where .= ' AND (l.`memo` LIKE ? OR l.`ref` LIKE ? OR u.`username` LIKE ?)';
                   $args[] = '%' . $kw . '%'; $args[] = '%' . $kw . '%'; $args[] = '%' . $kw . '%'; }

$total = (int)$DB->getColumn(
    "SELECT COUNT(*) FROM `nteam_money_log` l LEFT JOIN `nteam_user` u ON u.`id`=l.`user_id` WHERE {$where}", $args);
$pages = max(1, (int)ceil($total / $per));
if ($page > $pages) { $page = $pages; }
$offset = ($page - 1) * $per;
$rows = $DB->getAll(
    "SELECT l.*, u.`username` FROM `nteam_money_log` l
      LEFT JOIN `nteam_user` u ON u.`id`=l.`user_id`
      WHERE {$where} ORDER BY l.`id` DESC LIMIT {$offset},{$per}", $args);
if (!is_array($rows)) { $rows = array(); }

$sumBalance = (float)$DB->getColumn("SELECT IFNULL(SUM(`money`),0) FROM `nteam_user`");
$todayIn    = (float)$DB->getColumn("SELECT IFNULL(SUM(`amount`),0) FROM `nteam_money_log`
                WHERE `amount`>0 AND `create_time`>=? AND `type` IN ('recharge','card','admin')", array(strtotime('today')));
$todayOut   = (float)$DB->getColumn("SELECT IFNULL(SUM(`amount`),0) FROM `nteam_money_log`
                WHERE `amount`<0 AND `create_time`>=?", array(strtotime('today')));
$logCnt     = (int)$DB->getColumn("SELECT COUNT(*) FROM `nteam_money_log`");

function wl_pagelink($page, $kw, $type, $uid)
{
    $qs = array_filter(array('page' => $page, 'kw' => $kw, 'type' => $type, 'uid' => $uid),
        function ($v) { return $v !== '' && $v !== null && $v !== 0; });
    return 'wallet.php' . ($qs ? '?' . http_build_query($qs) : '');
}
?>
<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title><?php echo conf('Name');?> - 余额管理</title>
  <link rel="shortcut icon" href="../assets/img/favicon.png">
  <link rel="stylesheet" id="css-main" href="../assets/admin/css/codebase.min-5.5.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="../assets/admin/css/decor.css">
</head>
<body>
<div id="page-container" class="sidebar-o enable-page-overlay side-scroll page-header-modern main-content-boxed">
<?php require_once __DIR__ . '/head.php'; ?>
<main id="main-container">
  <div class="content">
  <?php require_once __DIR__ . '/_tabs.php'; admin_tabs('pay', 'wallet.php', $adminData['adminRank']); ?>

    <div class="row g-3 mb-3">
      <?php
      $stats = array(
          array('全站用户余额', '¥' . number_format($sumBalance, 2), 'fa-wallet', 'text-primary'),
          array('今日入账', '¥' . number_format($todayIn, 2), 'fa-arrow-down', 'text-success'),
          array('今日支出', '¥' . number_format(abs($todayOut), 2), 'fa-arrow-up', 'text-danger'),
          array('流水总数', $logCnt, 'fa-list', 'text-elegance'),
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
      <i class="fa fa-circle-info me-2"></i><div><?php echo htmlspecialchars($flash, ENT_QUOTES, 'UTF-8'); ?></div>
    </div>
    <?php } ?>

    <!-- 手工调整 -->
    <div class="block block-rounded mb-3">
      <div class="block-header block-header-default">
        <h3 class="block-title"><i class="fa fa-sliders me-1 text-muted"></i> 手工调整余额</h3>
      </div>
      <div class="block-content">
        <form method="post" class="row g-3 align-items-end">
          <input type="hidden" name="act" value="adjust">
          <div class="col-6 col-md-2">
            <label class="form-label">用户 UID</label>
            <input class="form-control" type="number" name="uid" min="1" required placeholder="如 1">
          </div>
          <div class="col-6 col-md-2">
            <label class="form-label">金额（负数扣减）</label>
            <input class="form-control" type="number" name="amount" step="0.01" required placeholder="如 50 或 -20">
          </div>
          <div class="col-12 col-md-5">
            <label class="form-label">说明</label>
            <input class="form-control" type="text" name="memo" maxlength="100" placeholder="会写进流水，用户可见">
          </div>
          <div class="col-12 col-md-auto">
            <button class="btn btn-alt-primary" type="submit"><i class="fa fa-check me-1"></i>确认调整</button>
          </div>
        </form>
      </div>
    </div>

    <!-- 流水 -->
    <div class="block block-rounded">
      <div class="block-header block-header-default">
        <h3 class="block-title"><i class="fa fa-list me-1 text-muted"></i> 余额流水</h3>
        <div class="block-options">
          <a class="btn btn-sm btn-alt-secondary" href="wallet.php?export=1"><i class="fa fa-file-csv me-1"></i>导出 CSV</a>
        </div>
      </div>
      <div class="block-content">
        <form method="get" class="row g-2 mb-3">
          <div class="col-6 col-md-3"><input class="form-control" type="text" name="kw" value="<?php echo htmlspecialchars($kw, ENT_QUOTES, 'UTF-8'); ?>" placeholder="说明 / 单号 / 用户名"></div>
          <div class="col-6 col-md-2"><input class="form-control" type="number" name="uid" value="<?php echo $uidF > 0 ? $uidF : ''; ?>" placeholder="UID"></div>
          <div class="col-6 col-md-3">
            <select class="form-select" name="type">
              <option value="">全部类型</option>
              <?php foreach (array('recharge','card','consume','refund','admin') as $t) { ?>
              <option value="<?php echo $t; ?>" <?php echo $type === $t ? 'selected' : ''; ?>><?php echo wallet_type_name($t); ?></option>
              <?php } ?>
            </select>
          </div>
          <div class="col-6 col-md-2"><button class="btn btn-alt-primary" type="submit">筛选</button></div>
        </form>

        <?php if (!$rows) { ?>
        <div class="text-center text-muted fs-sm py-4"><i class="fa fa-wallet fa-2x d-block mb-2 opacity-50"></i>还没有余额流水</div>
        <?php } else { ?>
        <div class="table-responsive">
          <table class="table table-striped table-vcenter">
            <thead><tr><th>时间</th><th>用户</th><th>类型</th><th>说明</th><th class="text-end">变动</th><th class="text-end">余额</th><th>操作人</th></tr></thead>
            <tbody>
              <?php foreach ($rows as $r) {
                  $amt = (float)$r['amount'];
                  $cls = $amt >= 0 ? 'text-success' : 'text-danger';
              ?>
              <tr>
                <td class="fs-xs text-muted"><?php echo date('Y-m-d H:i', (int)$r['create_time']); ?></td>
                <td class="fs-sm"><?php echo htmlspecialchars(($r['username'] ?: ('UID ' . $r['user_id'])), ENT_QUOTES, 'UTF-8'); ?><br><span class="fs-xs text-muted">UID <?php echo (int)$r['user_id']; ?></span></td>
                <td><span class="badge bg-body-light"><?php echo wallet_type_name($r['type']); ?></span></td>
                <td class="fs-sm"><?php echo htmlspecialchars($r['memo'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td class="text-end fw-semibold <?php echo $cls; ?>"><?php echo $amt >= 0 ? '+' : ''; ?><?php echo number_format($amt, 2); ?></td>
                <td class="text-end fs-sm text-muted"><?php echo number_format((float)$r['balance'], 2); ?></td>
                <td class="fs-xs text-muted"><?php echo htmlspecialchars($r['admin'], ENT_QUOTES, 'UTF-8'); ?></td>
              </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-3">
          <span class="text-muted fs-sm">共 <?php echo $total; ?> 条 · 第 <?php echo $page; ?> / <?php echo $pages; ?> 页</span>
          <div class="btn-group">
            <a class="btn btn-sm btn-alt-secondary <?php echo $page <= 1 ? 'disabled' : ''; ?>" href="<?php echo wl_pagelink(max(1, $page - 1), $kw, $type, $uidF); ?>">上一页</a>
            <a class="btn btn-sm btn-alt-secondary <?php echo $page >= $pages ? 'disabled' : ''; ?>" href="<?php echo wl_pagelink(min($pages, $page + 1), $kw, $type, $uidF); ?>">下一页</a>
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
</body>
</html>
