<?php
/**
 * 后台 · 在线更新检测（只读）
 *
 * 对应 42.5 的 #6。后台「用户中心运营」开 Updater_Open 后菜单出现本页。
 * 只做版本检测与更新日志展示，绝不自动下载 / 覆盖线上文件：
 * 更新接口一旦能写文件，就等于给外部开了一个任意代码执行入口。
 */
$mod = 'admin';
include(__DIR__ . '/../Common/Core_brain.php');
require_once(__DIR__ . '/../Common/Schema.php');
require_once(__DIR__ . '/../Common/OtConfig.php');

site_schema_init($DB);

if ($adminData['adminRank'] != 1) {
    echo '您的账号没有权限使用此功能';
    exit;
}

$localVer = trim((string)ot_cfg('Updater_Version'));
$checkUrl = trim((string)ot_cfg('Updater_Check_Url'));
$result   = null;
$err      = '';

if (isset($_GET['act']) && $_GET['act'] === 'check') {
    if ($checkUrl === '') {
        $err = '还没填版本检测接口，去「用户中心运营 → 在线更新检测」配置。';
    } elseif (!preg_match('#^https?://#i', $checkUrl) || !filter_var($checkUrl, FILTER_VALIDATE_URL)) {
        $err = '检测接口只支持 http / https 完整 URL。';
    } else {
        $ch = curl_init($checkUrl);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT      => 'OneTeam-Updater/1.0',
        ));
        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($body === false || $code < 200 || $code >= 300) {
            $err = '检测请求失败（HTTP ' . $code . '），请检查接口地址与网络。';
        } else {
            $j = json_decode((string)$body, true);
            if (!is_array($j) || !isset($j['version'])) {
                $err = '返回内容不是合法 JSON（需要 version 字段）：' . htmlspecialchars(mb_substr((string)$body, 0, 160), ENT_QUOTES, 'UTF-8');
            } else {
                $remote = trim((string)$j['version']);
                $result = array(
                    'version' => $remote,
                    'log'     => isset($j['log']) ? (string)$j['log'] : '',
                    'url'     => isset($j['url']) ? (string)$j['url'] : '',
                    'newer'   => version_compare($remote, $localVer, '>'),
                );
                $DB->query(
                    "INSERT INTO `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) VALUES (?,?,NOW(),'','')",
                    array($_SESSION['adminUser'], '检测了在线更新')
                );
            }
        }
    }
}
?>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>在线更新检测 - <?php echo htmlspecialchars(conf('Name'), ENT_QUOTES, 'UTF-8'); ?> - 后台管理中心</title>
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

  <?php if (!ot_on('Updater_Open')) { ?>
  <div class="alert alert-warning d-flex align-items-center" role="alert">
    <i class="fa fa-triangle-exclamation me-2"></i>
    <div>在线更新检测当前<strong>未启用</strong>，菜单不会显示。要去 <a href="uconfig.php">用户中心运营</a> 打开开关。</div>
  </div>
  <?php } ?>

  <div class="block block-rounded">
    <div class="block-header block-header-default">
      <h3 class="block-title"><b>版本信息</b></h3>
      <div class="block-options">
        <a class="btn btn-sm btn-alt-primary" href="updater.php?act=check"><i class="fa fa-rotate me-1"></i>检查更新</a>
      </div>
    </div>
    <div class="block-content">
      <div class="row fs-sm">
        <div class="col-md-6">
          <dl class="row mb-0">
            <dt class="col-sm-4">当前版本</dt><dd class="col-sm-8"><span class="badge bg-primary"><?php echo htmlspecialchars($localVer, ENT_QUOTES, 'UTF-8'); ?></span></dd>
            <dt class="col-sm-4">检测接口</dt><dd class="col-sm-8 text-break"><?php echo $checkUrl !== '' ? htmlspecialchars($checkUrl, ENT_QUOTES, 'UTF-8') : '<span class="text-muted">未配置</span>'; ?></dd>
            <dt class="col-sm-4">PHP 版本</dt><dd class="col-sm-8"><?php echo PHP_VERSION; ?></dd>
            <dt class="col-sm-4">服务器时间</dt><dd class="col-sm-8"><?php echo date('Y-m-d H:i:s'); ?></dd>
          </dl>
        </div>
        <div class="col-md-6">
          <div class="alert alert-secondary mb-0">
            <i class="fa fa-lock me-1"></i>
            本页<strong>只读</strong>：只拉取并展示远端版本与更新日志，不下载、不解压、不覆盖任何线上文件。
            需要升级时请按日志里的地址人工核对后手动部署。
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php if ($err !== '') { ?>
  <div class="alert alert-danger d-flex align-items-center" role="alert">
    <i class="fa fa-circle-exclamation me-2"></i><div><?php echo $err; ?></div>
  </div>
  <?php } ?>

  <?php if ($result !== null) { ?>
  <div class="block block-rounded">
    <div class="block-header block-header-default">
      <h3 class="block-title"><b>检测结果</b></h3>
    </div>
    <div class="block-content">
      <?php if ($result['newer']) { ?>
      <div class="alert alert-success d-flex align-items-center" role="alert">
        <i class="fa fa-circle-arrow-up me-2"></i>
        <div>发现新版本 <b><?php echo htmlspecialchars($result['version'], ENT_QUOTES, 'UTF-8'); ?></b>（当前 <?php echo htmlspecialchars($localVer, ENT_QUOTES, 'UTF-8'); ?>）。请人工核对后部署。</div>
      </div>
      <?php } else { ?>
      <div class="alert alert-info d-flex align-items-center" role="alert">
        <i class="fa fa-circle-check me-2"></i>
        <div>已是最新版本 <b><?php echo htmlspecialchars($result['version'], ENT_QUOTES, 'UTF-8'); ?></b>。</div>
      </div>
      <?php } ?>
      <?php if ($result['log'] !== '') { ?>
      <div class="block block-bordered block-rounded mt-3">
        <div class="block-header"><h4 class="block-title">更新日志</h4></div>
        <div class="block-content fs-sm"><?php echo nl2br(htmlspecialchars($result['log'], ENT_QUOTES, 'UTF-8')); ?></div>
      </div>
      <?php } ?>
      <?php if ($result['url'] !== '' && preg_match('#^https?://#i', $result['url'])) { ?>
      <p class="fs-sm mt-3 mb-0">发布地址：<a href="<?php echo htmlspecialchars($result['url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($result['url'], ENT_QUOTES, 'UTF-8'); ?></a></p>
      <?php } ?>
    </div>
  </div>
  <?php } ?>

</div>
</main>
<?php require_once __DIR__ . '/foot.php'; ?>
</div>
<script src="../assets/admin/js/codebase.app.min-5.5.js"></script>
<script type="text/javascript" src="../assets/admin/js/jquery.min.js"></script>
<script src="../assets/layer/layer.js"></script>
</body>
</html>
