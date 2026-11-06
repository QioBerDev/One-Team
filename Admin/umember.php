<?php
$mod = 'admin';
include(__DIR__ . '/../Common/Core_brain.php');
require_once(__DIR__ . '/../Common/Schema.php');
require_once(__DIR__ . '/../Common/User.php');

site_schema_init($DB);

if ($adminData['adminRank'] != 1 && $adminData['adminRank'] != 2) {
    echo "您的账号没有权限使用此功能";
    exit;
}

/* ---------- 写操作 ---------- */
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = isset($_POST['act']) ? (string)$_POST['act'] : '';
    $id  = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if ($id > 0 && $act === 'ban') {
        $DB->query("UPDATE `nteam_user` SET `status`=0 WHERE `id`=?", array($id));
        $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('"
            . addslashes($_SESSION['adminUser']) . "','封禁用户 UID#{$id}', NOW(), '', '')");
        $flash = 'UID ' . $id . ' 已封禁，该用户无法登录';
    } elseif ($id > 0 && $act === 'unban') {
        $DB->query("UPDATE `nteam_user` SET `status`=1, `fail_count`=0, `lock_until`=0 WHERE `id`=?", array($id));
        $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('"
            . addslashes($_SESSION['adminUser']) . "','解封用户 UID#{$id}', NOW(), '', '')");
        $flash = 'UID ' . $id . ' 已解封';
    } elseif ($id > 0 && $act === 'resetpwd') {
        /* 管理员代重置：随机生成一个，展示一次 */
        $new = '';
        for ($i = 0; $i < 12; $i++) {
            $new .= substr('ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789', random_int(0, 55), 1);
        }
        $DB->query("UPDATE `nteam_user` SET `pass_hash`=?, `fail_count`=0, `lock_until`=0 WHERE `id`=?",
            array(password_hash($new, PASSWORD_DEFAULT), $id));
        $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('"
            . addslashes($_SESSION['adminUser']) . "','重置用户 UID#{$id} 密码', NOW(), '', '')");
        $flash = 'UID ' . $id . ' 新密码：<b style="font-family:monospace">' . $new . '</b>（仅显示这一次，请立即发给用户）';
    } elseif ($id > 0 && ($act === 'admin_on' || $act === 'admin_off')) {
        /* 管理员前台化：管理员身份就是前端用户表里的一个标记，不再单独维护管理员表。
           被标记的用户登录用户中心后会看到「进入管理员操作台」，其余账号看不到。
           后台 nteam_admin 镜像出来的账号不允许取消，否则前后台切换会断掉。 */
        $to = $act === 'admin_on' ? 1 : 0;
        $row = $DB->getRow("SELECT `username` FROM `nteam_user` WHERE `id`=? LIMIT 1", array($id));
        $isMirror = $row && $DB->getRow(
            "SELECT `id` FROM `nteam_admin` WHERE `adminUser`=? LIMIT 1", array($row['username'])
        );
        if ($to === 0 && $isMirror) {
            $flash = 'UID ' . $id . ' 是后台管理员账号的镜像，不能取消管理员身份（取消后前后台切换会失效）';
        } else {
            $DB->query("UPDATE `nteam_user` SET `is_admin`=? WHERE `id`=?", array($to, $id));
            $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('"
                . addslashes($_SESSION['adminUser']) . "','" . ($to ? '设为管理员' : '取消管理员') . " UID#{$id}', NOW(), '', '')");
            $flash = 'UID ' . $id . ($to ? ' 已设为管理员：该账号登录用户中心后会显示「进入管理员操作台」' : ' 已取消管理员身份');
        }
    }

    $qs = http_build_query(array_filter(array(
        'status' => isset($_GET['status']) ? $_GET['status'] : '',
        'kw'     => isset($_GET['kw']) ? $_GET['kw'] : '',
        'page'   => isset($_GET['page']) ? $_GET['page'] : '',
    ), function ($v) { return $v !== '' && $v !== null; }));
    if ($act === 'resetpwd' || $act === 'admin_on' || $act === 'admin_off') {
        /* 重置密码 / 改管理员身份要展示结果，不跳转，原地渲染 */
    } else {
        header('Location: umember.php' . ($qs ? '?' . $qs : ''));
        exit;
    }
}

/* ---------- 列表 ---------- */
$status = isset($_GET['status']) && $_GET['status'] !== '' ? (int)$_GET['status'] : null;
$kw     = isset($_GET['kw']) ? trim((string)$_GET['kw']) : '';
$page   = max(1, (int)(isset($_GET['page']) ? $_GET['page'] : 1));
$per    = 20;

$where = '1';
$args  = array();
if ($status !== null) {
    $where .= ' AND u.`status`=?';
    $args[] = $status;
}
if ($kw !== '') {
    $where .= ' AND (u.`username` LIKE ? OR u.`email` LIKE ? OR u.`nickname` LIKE ? OR u.`id`=?)';
    $args[] = '%' . $kw . '%';
    $args[] = '%' . $kw . '%';
    $args[] = '%' . $kw . '%';
    $args[] = (int)$kw;
}

$total = (int)$DB->getColumn("SELECT COUNT(*) FROM `nteam_user` u WHERE {$where}", $args);
$pages = max(1, (int)ceil($total / $per));
if ($page > $pages) {
    $page = $pages;
}
$offset = ($page - 1) * $per;
$rows = $DB->getAll(
    "SELECT u.*, (SELECT COUNT(*) FROM `nteam_order` o WHERE o.`user_id`=u.`id`) AS `order_cnt`,
            (SELECT IFNULL(SUM(o.`money`),0) FROM `nteam_order` o WHERE o.`user_id`=u.`id` AND o.`status`=1) AS `paid_money`
     FROM `nteam_user` u WHERE {$where} ORDER BY u.`id` DESC LIMIT {$offset},{$per}",
    $args
);
if (!is_array($rows)) {
    $rows = array();
}

$allCnt    = (int)$DB->getColumn("SELECT COUNT(*) FROM `nteam_user`");
$todayCnt  = (int)$DB->getColumn("SELECT COUNT(*) FROM `nteam_user` WHERE `reg_time`>=?", array(strtotime('today')));
$activeCnt = (int)$DB->getColumn("SELECT COUNT(*) FROM `nteam_user` WHERE `status`=1");
$banCnt    = (int)$DB->getColumn("SELECT COUNT(*) FROM `nteam_user` WHERE `status`=0");

function um_pagelink($page, $kw, $status)
{
    $qs = array_filter(array('page' => $page, 'kw' => $kw, 'status' => $status), function ($v) { return $v !== '' && $v !== null; });
    return 'umember.php' . ($qs ? '?' . http_build_query($qs) : '');
}
?>
<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title><?php echo conf('Name');?> - 用户管理</title>
  <link rel="shortcut icon" href="../assets/img/favicon.png">
  <link rel="stylesheet" id="css-main" href="../assets/admin/css/codebase.min-5.5.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="../assets/admin/css/decor.css">
  <style>
    .um-kv { font-size: 12.5px; color: #6c757d; }
    .um-kv b { color: #343a40; font-weight: 600; }
    .um-stat { font-size: 22px; font-weight: 600; }
    .um-stat-label { font-size: 12px; color: #6c757d; text-transform: uppercase; letter-spacing: .04em; }
    .um-pwd { font-family: monospace; background: #fff3cd; padding: 2px 8px; border-radius: 4px; }
  </style>
</head>
<body>
<div id="page-container" class="sidebar-o enable-page-overlay side-scroll page-header-modern main-content-boxed">
<?php require_once __DIR__ . '/head.php'; ?>
<main id="main-container">
  <div class="content">
  <?php require_once __DIR__ . '/_tabs.php'; admin_tabs('users', 'umember.php', $adminData['adminRank']); ?>

    <div class="row g-3 mb-3">
      <div class="col-6 col-md-3">
        <div class="block block-rounded block-link-shadow mb-0">
          <div class="block-content block-content-full">
            <div class="um-stat"><?php echo $allCnt; ?></div>
            <div class="um-stat-label">用户总数</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="block block-rounded block-link-shadow mb-0">
          <div class="block-content block-content-full">
            <div class="um-stat"><?php echo $todayCnt; ?></div>
            <div class="um-stat-label">今日注册</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="block block-rounded block-link-shadow mb-0">
          <div class="block-content block-content-full">
            <div class="um-stat"><?php echo $activeCnt; ?></div>
            <div class="um-stat-label">正常用户</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="block block-rounded block-link-shadow mb-0">
          <div class="block-content block-content-full">
            <div class="um-stat"><?php echo $banCnt; ?></div>
            <div class="um-stat-label">已封禁</div>
          </div>
        </div>
      </div>
    </div>

    <?php if ($flash !== '') { ?>
    <div class="alert alert-warning d-flex align-items-center justify-content-between">
      <span><?php echo $flash; ?></span>
      <a class="btn btn-sm btn-alt-secondary" href="umember.php">知道了</a>
    </div>
    <?php } ?>

    <div class="block block-rounded">
      <div class="block-header block-header-default">
        <h3 class="block-title"><b>用户管理</b></h3>
        <div class="block-options">
          <form method="get" class="d-flex align-items-center gap-2">
            <select name="status" class="form-select form-select-sm" style="width:120px" onchange="this.form.submit()">
              <option value="">全部状态</option>
              <option value="1" <?php echo $status === 1 ? 'selected' : ''; ?>>正常</option>
              <option value="0" <?php echo $status === 0 ? 'selected' : ''; ?>>已封禁</option>
            </select>
            <input type="text" name="kw" class="form-control form-control-sm" style="width:200px"
                   value="<?php echo htmlspecialchars($kw); ?>" placeholder="用户名 / 邮箱 / 昵称">
            <button class="btn btn-sm btn-alt-primary" type="submit"><i class="fa fa-search"></i></button>
          </form>
        </div>
      </div>
      <div class="block-content">
        <?php if (!$rows) { ?>
        <p class="text-muted mb-0">没有符合条件的用户。</p>
        <?php } else { ?>
        <div class="table-responsive">
          <table class="table table-bordered table-hover table-vcenter mb-0">
            <thead>
              <tr>
                <th style="width:70px">UID</th>
                <th>用户</th>
                <th style="width:210px">注册 / 最后登录</th>
                <th style="width:120px">订单</th>
                <th style="width:80px">状态</th>
                <th style="width:230px">操作</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $r) {
                  $banned = (int)$r['status'] !== 1;
                  $locked = (int)$r['lock_until'] > time();
              ?>
              <tr>
                <td class="fw-semibold"><?php echo (int)$r['id']; ?></td>
                <td>
                  <div class="fw-semibold"><?php echo htmlspecialchars($r['username']); ?>
                    <?php if (trim((string)$r['nickname']) !== '' && $r['nickname'] !== $r['username']) { ?>
                    <span class="um-kv">(<?php echo htmlspecialchars($r['nickname']); ?>)</span>
                    <?php } ?>
                    <?php if (isset($r['is_admin']) && (int)$r['is_admin'] === 1) {
                        $mirror = $DB->getRow("SELECT `id` FROM `nteam_admin` WHERE `adminUser`=? LIMIT 1", array($r['username']));
                    ?>
                    <span class="badge bg-primary ms-1"><?php echo $mirror ? '后台管理员' : '管理员'; ?></span>
                    <?php } ?>
                  </div>
                  <div class="um-kv"><?php echo $r['email'] !== '' && $r['email'] !== null ? htmlspecialchars($r['email']) : '未绑邮箱'; ?></div>
                  <div class="um-kv">注册 IP <?php echo htmlspecialchars($r['reg_ip']); ?></div>
                </td>
                <td class="um-kv">
                  <?php echo date('Y-m-d H:i', (int)$r['reg_time']); ?><br>
                  <?php echo (int)$r['last_time'] > 0 ? date('m-d H:i', (int)$r['last_time']) . ' · ' . htmlspecialchars($r['last_ip']) : '从未登录'; ?>
                  <?php if ($locked) { ?><br><span class="text-danger">锁定至 <?php echo date('H:i', (int)$r['lock_until']); ?></span><?php } ?>
                </td>
                <td class="um-kv">
                  <b><?php echo (int)$r['order_cnt']; ?></b> 单 · 已付 <b>¥<?php echo number_format((float)$r['paid_money'], 2); ?></b><br>
                  余额 <b>¥<?php echo number_format(isset($r['money']) ? (float)$r['money'] : 0, 2); ?></b><br>
                  <a href="wallet.php?uid=<?php echo (int)$r['id']; ?>" style="font-size:12px">余额流水 →</a>
                  <a href="order.php?uid=<?php echo (int)$r['id']; ?>" style="font-size:12px">查看订单 →</a>
                </td>
                <td>
                  <?php if ($banned) { ?>
                  <span class="badge bg-danger">已封禁</span>
                  <?php } else { ?>
                  <span class="badge bg-success">正常</span>
                  <?php } ?>
                </td>
                <td>
                  <div class="d-flex gap-1 flex-wrap">
                    <form method="post" style="margin:0">
                      <input type="hidden" name="act" value="resetpwd">
                      <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                      <button class="btn btn-sm btn-alt-warning" type="submit"
                              onclick="return confirm('重置 UID <?php echo (int)$r['id']; ?> 的密码？旧密码将失效');">
                        <i class="fa fa-key"></i> 重置密码
                      </button>
                    </form>
                    <form method="post" style="margin:0">
                      <input type="hidden" name="act" value="<?php echo $banned ? 'unban' : 'ban'; ?>">
                      <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                      <?php if ($banned) { ?>
                      <button class="btn btn-sm btn-alt-success" type="submit"><i class="fa fa-unlock"></i> 解封</button>
                      <?php } else { ?>
                      <button class="btn btn-sm btn-alt-danger" type="submit"
                              onclick="return confirm('封禁 UID <?php echo (int)$r['id']; ?>？封后无法登录，已有订单不受影响');">
                        <i class="fa fa-ban"></i> 封禁
                      </button>
                      <?php } ?>
                    </form>
                    <?php
                    $isAd = isset($r['is_admin']) && (int)$r['is_admin'] === 1;
                    $isMirror = $isAd && $DB->getRow(
                        "SELECT `id` FROM `nteam_admin` WHERE `adminUser`=? LIMIT 1", array($r['username'])
                    );
                    ?>
                    <?php if ($isMirror) { ?>
                    <span class="btn btn-sm btn-alt-secondary disabled opacity-75" title="后台管理员镜像账号，不能取消">
                      <i class="fa fa-lock"></i> 后台管理员
                    </span>
                    <?php } else { ?>
                    <form method="post" style="margin:0">
                      <input type="hidden" name="act" value="<?php echo $isAd ? 'admin_off' : 'admin_on'; ?>">
                      <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                      <?php if ($isAd) { ?>
                      <button class="btn btn-sm btn-alt-secondary" type="submit"
                              onclick="return confirm('取消 UID <?php echo (int)$r['id']; ?> 的管理员身份？该账号将不再显示「进入管理员操作台」');">
                        <i class="fa fa-user-minus"></i> 取消管理员
                      </button>
                      <?php } else { ?>
                      <button class="btn btn-sm btn-alt-primary" type="submit"
                              onclick="return confirm('把 UID <?php echo (int)$r['id']; ?> 设为管理员？该账号登录后可直接进入管理员操作台');">
                        <i class="fa fa-user-gear"></i> 设为管理员
                      </button>
                      <?php } ?>
                    </form>
                    <?php } ?>
                  </div>
                </td>
              </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3">
          <span class="text-muted fs-sm">共 <?php echo $total; ?> 人 · 第 <?php echo $page; ?> / <?php echo $pages; ?> 页</span>
          <div class="btn-group">
            <a class="btn btn-sm btn-alt-secondary <?php echo $page <= 1 ? 'disabled' : ''; ?>"
               href="<?php echo um_pagelink(max(1, $page - 1), $kw, $status); ?>">上一页</a>
            <a class="btn btn-sm btn-alt-secondary <?php echo $page >= $pages ? 'disabled' : ''; ?>"
               href="<?php echo um_pagelink(min($pages, $page + 1), $kw, $status); ?>">下一页</a>
          </div>
        </div>
        <?php } ?>
      </div>
    </div>

  </div>
</main>
<?php include __DIR__ . '/foot.php'; ?>
</div>
<script src="../assets/admin/js/codebase.min-5.5.js"></script>
</body>
</html>
