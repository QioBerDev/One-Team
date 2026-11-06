<?php
$mod = 'admin';
include('../Common/Core_brain.php');
require_once('../Common/Schema.php');
require_once('../Common/Pay.php');

pay_schema_init($DB);

if ($adminData['adminRank'] != 1 && $adminData['adminRank'] != 2) {
    echo "您的账号没有权限使用此功能";
    exit;
}

/* ---------- 写操作 ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = isset($_POST['act']) ? (string)$_POST['act'] : '';
    $id  = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if ($id > 0 && $act === 'deliver') {
        $d = trim(isset($_POST['deliver']) ? (string)$_POST['deliver'] : '');
        $DB->query("UPDATE `nteam_order` SET `deliver`=?, `deliver_state`=? WHERE `id`=?",
            array($d, $d === '' ? 0 : 1, $id));
        $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('"
            . addslashes($_SESSION['adminUser']) . "','订单发货 #" . $id . "', NOW(), '', '')");
    } elseif ($id > 0 && $act === 'paid') {
        /* 线下转账 / 补单：只对未支付单生效，不能覆盖已支付 */
        $DB->query("UPDATE `nteam_order` SET `status`=1, `pay_time`=?, `remark`='后台手工确认' WHERE `id`=? AND `status`=0",
            array(time(), $id));
    } elseif ($id > 0 && $act === 'close') {
        $DB->query("UPDATE `nteam_order` SET `status`=3 WHERE `id`=? AND `status`=0", array($id));
    } elseif ($id > 0 && $act === 'refund') {
        $DB->query("UPDATE `nteam_order` SET `status`=2 WHERE `id`=? AND `status`=1", array($id));
        $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('"
            . addslashes($_SESSION['adminUser']) . "','订单标记退款 #" . $id . "（需自行在易支付后台退款）', NOW(), '', '')");
    }

    $qs = http_build_query(array_filter(array(
        'status' => isset($_GET['status']) ? $_GET['status'] : '',
        'kw'     => isset($_GET['kw']) ? $_GET['kw'] : '',
        'page'   => isset($_GET['page']) ? $_GET['page'] : '',
    ), function ($v) { return $v !== '' && $v !== null; }));
    header('Location: order.php' . ($qs ? '?' . $qs : ''));
    exit;
}

/* ---------- 列表 ---------- */
$status = isset($_GET['status']) && $_GET['status'] !== '' ? (int)$_GET['status'] : null;
$kw     = isset($_GET['kw']) ? trim((string)$_GET['kw']) : '';
$uid    = isset($_GET['uid']) ? (int)$_GET['uid'] : 0;
$page   = max(1, (int)(isset($_GET['page']) ? $_GET['page'] : 1));
$per    = 20;

$where = '1';
$args  = array();
if ($status !== null) {
    $where .= ' AND `status`=?';
    $args[] = $status;
}
if ($uid > 0) {
    $where .= ' AND `user_id`=?';
    $args[] = $uid;
}
if ($kw !== '') {
    $where .= ' AND (`out_trade_no` LIKE ? OR `project_name` LIKE ?)';
    $args[] = '%' . $kw . '%';
    $args[] = '%' . $kw . '%';
}

$total = (int)$DB->getColumn("SELECT COUNT(*) FROM `nteam_order` WHERE {$where}", $args);
$pages = max(1, (int)ceil($total / $per));
if ($page > $pages) {
    $page = $pages;
}
$offset = ($page - 1) * $per;
$rows = $DB->getAll(
    "SELECT * FROM `nteam_order` WHERE {$where} ORDER BY `id` DESC LIMIT {$offset},{$per}",
    $args
);
if (!is_array($rows)) {
    $rows = array();
}

$todayStart = strtotime('today');
$todayCount = (int)$DB->getColumn("SELECT COUNT(*) FROM `nteam_order` WHERE `status`=1 AND `pay_time`>=?", array($todayStart));
$todayMoney = (float)$DB->getColumn("SELECT IFNULL(SUM(`money`),0) FROM `nteam_order` WHERE `status`=1 AND `pay_time`>=?", array($todayStart));
$allMoney   = (float)$DB->getColumn("SELECT IFNULL(SUM(`money`),0) FROM `nteam_order` WHERE `status`=1");

if (!function_exists('ord_cut')) {
    function ord_cut($s, $n)
    {
        $s = (string)$s;
        return function_exists('mb_substr') ? mb_substr($s, 0, $n, 'UTF-8') : substr($s, 0, $n * 3);
    }
}

$stName = array(0 => '待支付', 1 => '已支付', 2 => '已退款', 3 => '已关闭');
$stBadge = array(0 => 'warning', 1 => 'success', 2 => 'secondary', 3 => 'secondary');
?>
<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title><?php echo conf('Name');?> - 订单管理</title>
  <link rel="shortcut icon" href="../assets/img/favicon.png">
  <link rel="stylesheet" id="css-main" href="../assets/admin/css/codebase.min-5.5.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="../assets/admin/css/decor.css">
  <style>
    .ord-kv { font-size: 12.5px; color: #6c757d; }
    .ord-kv b { color: #343a40; font-weight: 600; }
    .ord-deliver { max-width: 260px; white-space: pre-wrap; word-break: break-all; font-size: 12.5px; }
    .ord-details summary { cursor: pointer; font-size: 12.5px; color: #0d6efd; list-style: none; }
    .ord-details summary::-webkit-details-marker { display: none; }
    .ord-form textarea { font-size: 13px; }
    .ord-stat { font-size: 22px; font-weight: 600; }
    .ord-stat-label { font-size: 12px; color: #6c757d; text-transform: uppercase; letter-spacing: .04em; }
  </style>
</head>
<body>
<div id="page-container" class="sidebar-o enable-page-overlay side-scroll page-header-modern main-content-boxed">
<?php require_once 'head.php'; ?>
<main id="main-container">
  <div class="content">
  <?php require_once __DIR__ . '/_tabs.php'; admin_tabs('pay', 'order.php', $adminData['adminRank']); ?>

    <div class="row g-3 mb-3">
      <div class="col-6 col-md-3">
        <div class="block block-rounded block-link-shadow mb-0">
          <div class="block-content block-content-full">
            <div class="ord-stat"><?php echo $total; ?></div>
            <div class="ord-stat-label">当前筛选单数</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="block block-rounded block-link-shadow mb-0">
          <div class="block-content block-content-full">
            <div class="ord-stat"><?php echo $todayCount; ?></div>
            <div class="ord-stat-label">今日成交</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="block block-rounded block-link-shadow mb-0">
          <div class="block-content block-content-full">
            <div class="ord-stat">¥<?php echo number_format($todayMoney, 2); ?></div>
            <div class="ord-stat-label">今日金额</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="block block-rounded block-link-shadow mb-0">
          <div class="block-content block-content-full">
            <div class="ord-stat">¥<?php echo number_format($allMoney, 2); ?></div>
            <div class="ord-stat-label">累计已支付</div>
          </div>
        </div>
      </div>
    </div>

    <div class="block block-rounded">
      <div class="block-header block-header-default">
        <h3 class="block-title"><b>订单管理</b></h3>
        <div class="block-options">
          <form method="get" class="d-flex align-items-center gap-2">
            <select name="status" class="form-select form-select-sm" style="width:130px" onchange="this.form.submit()">
              <option value="">全部状态</option>
              <?php foreach ($stName as $k => $v) { ?>
              <option value="<?php echo $k; ?>" <?php echo $status === $k ? 'selected' : ''; ?>><?php echo $v; ?></option>
              <?php } ?>
            </select>
            <input type="text" name="kw" class="form-control form-control-sm" style="width:200px"
                   value="<?php echo htmlspecialchars($kw); ?>" placeholder="单号 / 项目名">
            <button class="btn btn-sm btn-alt-primary" type="submit"><i class="fa fa-search"></i></button>
          </form>
        </div>
      </div>
      <div class="block-content">
        <?php if (!$rows) { ?>
        <p class="text-muted mb-0">没有符合条件的订单。</p>
        <?php } else { ?>
        <div class="table-responsive">
          <table class="table table-bordered table-hover table-vcenter mb-0">
            <thead>
              <tr>
                <th style="width:110px">订单号</th>
                <th>项目</th>
                <th style="width:100px">金额</th>
                <th style="width:90px">状态</th>
                <th style="width:150px">下单 / 支付时间</th>
                <th>交付</th>
                <th style="width:190px">操作</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $r) {
                  $st = (int)$r['status'];
              ?>
              <tr>
                <td>
                  <div class="fw-semibold" style="font-size:12.5px"><?php echo htmlspecialchars($r['out_trade_no']); ?></div>
                  <div class="ord-kv">UID <?php echo (int)$r['user_id']; ?> ·
                    <a href="umember.php?kw=<?php echo (int)$r['user_id']; ?>" style="font-size:12px" title="去用户管理">用户</a>
                  </div>
                </td>
                <td>
                  <div class="fw-semibold"><?php echo htmlspecialchars($r['project_name']); ?></div>
                  <div class="ord-kv">
                    <b><?php echo htmlspecialchars(epay_type_name($r['pay_type'])); ?></b>
                    <?php if ($r['trade_no'] !== '') { ?> · <?php echo htmlspecialchars($r['trade_no']); ?><?php } ?>
                    <?php if ($r['remark'] !== '') { ?> · <?php echo htmlspecialchars($r['remark']); ?><?php } ?>
                  </div>
                </td>
                <td class="fw-semibold">¥<?php echo number_format((float)$r['money'], 2); ?></td>
                <td><span class="badge bg-<?php echo $stBadge[$st]; ?>"><?php echo $stName[$st]; ?></span></td>
                <td class="ord-kv">
                  <?php echo date('m-d H:i', (int)$r['create_time']); ?><br>
                  <?php echo (int)$r['pay_time'] > 0 ? date('m-d H:i', (int)$r['pay_time']) : '—'; ?>
                </td>
                <td>
                  <?php if (trim((string)$r['deliver']) !== '') { ?>
                  <div class="ord-deliver"><?php echo htmlspecialchars(ord_cut($r['deliver'], 80)); ?></div>
                  <?php } else { ?>
                  <span class="text-muted" style="font-size:12.5px">未填</span>
                  <?php } ?>
                </td>
                <td>
                  <details class="ord-details mb-1">
                    <summary>发货 / 改交付</summary>
                    <form method="post" class="ord-form mt-2">
                      <input type="hidden" name="act" value="deliver">
                      <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                      <textarea name="deliver" class="form-control mb-2" rows="3"
                                placeholder="下载地址 / 网盘链接 / 卡密 / 说明"><?php echo htmlspecialchars((string)$r['deliver']); ?></textarea>
                      <button class="btn btn-sm btn-primary" type="submit">保存</button>
                    </form>
                  </details>
                  <?php if ($st === 0) { ?>
                  <form method="post" style="display:inline" onsubmit="return confirm('确认这笔订单已经收到款？只用于线下转账补单。')">
                    <input type="hidden" name="act" value="paid">
                    <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                    <button class="btn btn-sm btn-alt-success" type="submit">标记已付</button>
                  </form>
                  <form method="post" style="display:inline" onsubmit="return confirm('关闭这笔订单？')">
                    <input type="hidden" name="act" value="close">
                    <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                    <button class="btn btn-sm btn-alt-secondary" type="submit">关闭</button>
                  </form>
                  <?php } elseif ($st === 1) { ?>
                  <form method="post" style="display:inline" onsubmit="return confirm('只是把本地订单标成已退款，钱要在易支付后台退。继续？')">
                    <input type="hidden" name="act" value="refund">
                    <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                    <button class="btn btn-sm btn-alt-warning" type="submit">标记退款</button>
                  </form>
                  <?php } ?>
                </td>
              </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>

        <?php if ($pages > 1) { ?>
        <nav class="mt-3">
          <ul class="pagination pagination-sm mb-0">
            <?php
            $base = 'order.php?';
            $qsArr = array();
            if ($status !== null) { $qsArr['status'] = $status; }
            if ($kw !== '') { $qsArr['kw'] = $kw; }
            for ($i = 1; $i <= $pages; $i++) {
                if ($pages > 12 && abs($i - $page) > 3 && $i !== 1 && $i !== $pages) {
                    continue;
                }
                $qsArr['page'] = $i;
                $on = $i === $page ? ' active' : '';
                echo '<li class="page-item' . $on . '"><a class="page-link" href="' . $base . http_build_query($qsArr) . '">' . $i . '</a></li>';
            }
            ?>
          </ul>
        </nav>
        <?php } ?>
        <?php } ?>
      </div>
    </div>

  </div>
</main>
<?php require_once 'foot.php'; ?>
</div>
<script src="../assets/admin/js/codebase.app.min-5.5.js"></script>
<script src="../assets/admin/js/jquery.min.js"></script>
</body>
</html>
