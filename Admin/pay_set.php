<?php
$mod = 'admin';
include('../Common/Core_brain.php');
require_once('../Common/Schema.php');
require_once('../Common/Pay.php');

pay_schema_init($DB);

if ($adminData['adminRank'] != 1) {
    echo "您的账号没有权限使用此功能";
    exit;
}

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $api    = trim(isset($_POST['Epay_Api']) ? (string)$_POST['Epay_Api'] : '');
    $pid    = trim(isset($_POST['Epay_Pid']) ? (string)$_POST['Epay_Pid'] : '');
    $key    = trim(isset($_POST['Epay_Key']) ? (string)$_POST['Epay_Key'] : '');
    $mchpri = trim(isset($_POST['Epay_MchPri']) ? (string)$_POST['Epay_MchPri'] : '');
    $pubkey = trim(isset($_POST['Epay_PubKey']) ? (string)$_POST['Epay_PubKey'] : '');
    $wx     = isset($_POST['Epay_Wx']) && $_POST['Epay_Wx'] == '1' ? 1 : 0;
    $ali    = isset($_POST['Epay_Ali']) && $_POST['Epay_Ali'] == '1' ? 1 : 0;
    $open   = isset($_POST['Epay_Open']) && $_POST['Epay_Open'] == '1' ? 1 : 0;

    if ($api === '' || !preg_match('#^https?://#i', $api)) {
        $err = '接口地址要以 http:// 或 https:// 开头';
    } elseif ($pid === '') {
        $err = '商户 ID 不能空着';
    } elseif ($key === '' && ($mchpri === '' || $pubkey === '')) {
        $err = 'V1 密钥和 V2 密钥对至少要配一组（V2 需要商户私钥 + 平台公钥）';
    } else {
        if (substr($api, -1) !== '/') {
            $api .= '/';
        }
        $DB->query(
            "UPDATE `nteam_config`
                SET `Epay_Api`=?, `Epay_Pid`=?, `Epay_Key`=?, `Epay_MchPri`=?, `Epay_PubKey`=?,
                    `Epay_Wx`=?, `Epay_Ali`=?, `Epay_Open`=?
              WHERE `id`=1",
            array($api, $pid, $key, $mchpri, $pubkey, $wx, $ali, $open)
        );
        $msg = '保存成功';
        $ip = isset($ip) ? $ip : '';
        $DB->query("insert into `nteam_log` (`adminUser`,`type`,`data`,`ip`,`city`) values ('"
            . addslashes($_SESSION['adminUser']) . "','修改了易支付配置', NOW(), '" . addslashes($ip) . "', '')");
    }
}

$ready   = epay_ready();
$notify  = epay_base_url() . 'pay/notify.php';
$return  = epay_base_url() . 'pay/return.php';
?>
<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title><?php echo conf('Name');?> - 后台管理中心</title>
  <link rel="shortcut icon" href="../assets/img/favicon.png">
  <link rel="stylesheet" id="css-main" href="../assets/admin/css/codebase.min-5.5.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="../assets/admin/css/decor.css">
</head>
<body>
<div id="page-container" class="sidebar-o enable-page-overlay side-scroll page-header-modern main-content-boxed">
<?php require_once 'head.php'; ?>
<main id="main-container">
  <div class="content">
  <?php require_once __DIR__ . '/_tabs.php'; admin_tabs('pay', 'pay_set.php', $adminData['adminRank']); ?>
    <?php if ($msg !== '') { ?>
    <div class="alert alert-success"><i class="fa fa-check me-1"></i><?php echo htmlspecialchars($msg); ?></div>
    <?php } ?>
    <?php if ($err !== '') { ?>
    <div class="alert alert-danger"><i class="fa fa-circle-exclamation me-1"></i><?php echo htmlspecialchars($err); ?></div>
    <?php } ?>

    <div class="block block-rounded">
      <div class="block-header block-header-default">
        <h3 class="block-title"><b>彩虹易支付</b></h3>
        <div class="block-options">
          <?php if ($ready) { ?>
          <span class="badge bg-success">已启用</span>
          <?php } else { ?>
          <span class="badge bg-secondary">未启用</span>
          <?php } ?>
        </div>
      </div>
      <div class="block-content">
        <form method="post">
          <div class="row mb-4">
            <div class="col-md-8">
              <label class="form-label" for="Epay_Api"><b>接口地址</b></label>
              <input type="text" class="form-control form-control-lg" id="Epay_Api" name="Epay_Api"
                     value="<?php echo htmlspecialchars(epay_conf('Api', 'https://pays.org.cn/')); ?>"
                     placeholder="https://pays.org.cn/">
              <div class="form-text">结尾带不带 / 都行，末尾会自动补上。换平台只改这一行。</div>
            </div>
            <div class="col-md-4">
              <label class="form-label" for="Epay_Pid"><b>商户 ID（PID）</b></label>
              <input type="text" class="form-control form-control-lg" id="Epay_Pid" name="Epay_Pid"
                     value="<?php echo htmlspecialchars(epay_conf('Pid')); ?>" placeholder="1001">
            </div>
          </div>

          <div class="row mb-4">
            <div class="col-md-6">
              <label class="form-label" for="Epay_Key"><b>V1 商户密钥（MD5 KEY）</b></label>
              <input type="text" class="form-control" id="Epay_Key" name="Epay_Key"
                     value="<?php echo htmlspecialchars(epay_conf('Key')); ?>" placeholder="32 位密钥">
              <div class="form-text">旧版 V1（MD5）用。只填这一组就走 V1 接口。</div>
            </div>
            <div class="col-md-6">
              <label class="form-label"><b>接口版本</b></label>
              <p class="form-control-plaintext"><span class="badge bg-info"><?php echo htmlspecialchars(epay_version_name()); ?></span></p>
              <div class="form-text">自动识别：配了下方 V2 密钥对就走 V2（RSA），只配 V1 密钥就走 V1。不用手选。</div>
            </div>
          </div>

          <div class="row mb-4">
            <div class="col-md-6">
              <label class="form-label" for="Epay_MchPri"><b>V2 商户私钥（RSA）</b></label>
              <textarea class="form-control" id="Epay_MchPri" name="Epay_MchPri" rows="5"
                        placeholder="-----BEGIN PRIVATE KEY----- ... -----END PRIVATE KEY-----"><?php echo htmlspecialchars(epay_conf('MchPri')); ?></textarea>
              <div class="form-text">商户后台「API 信息」→ 生成商户RSA密钥对 → 商户私钥。带不带 PEM 头都收。</div>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="Epay_PubKey"><b>V2 平台公钥（RSA）</b></label>
              <textarea class="form-control" id="Epay_PubKey" name="Epay_PubKey" rows="5"
                        placeholder="-----BEGIN PUBLIC KEY----- ... -----END PUBLIC KEY-----"><?php echo htmlspecialchars(epay_conf('PubKey')); ?></textarea>
              <div class="form-text">同一页面里的「平台公钥」，回调验签用。</div>
            </div>
          </div>

          <div class="row mb-4">
            <div class="col-md-4">
              <label class="form-label d-block"><b>支付渠道</b></label>
              <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" id="Epay_Wx" name="Epay_Wx" value="1"
                       <?php echo conf('Epay_Wx') == 1 ? 'checked' : ''; ?>>
                <label class="form-check-label" for="Epay_Wx"><i class="fa-brands fa-weixin"></i> 微信支付</label>
              </div>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="Epay_Ali" name="Epay_Ali" value="1"
                       <?php echo conf('Epay_Ali') == 1 ? 'checked' : ''; ?>>
                <label class="form-check-label" for="Epay_Ali"><i class="fa-brands fa-alipay"></i> 支付宝</label>
              </div>
              <div class="form-text">关掉的渠道前台按钮置灰不可点。</div>
            </div>
            <div class="col-md-4">
              <label class="form-label d-block"><b>总开关</b></label>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="Epay_Open" name="Epay_Open" value="1"
                       <?php echo conf('Epay_Open') == 1 ? 'checked' : ''; ?>>
                <label class="form-check-label" for="Epay_Open">启用在线支付</label>
              </div>
              <div class="form-text">关掉后作品页只显示「联系作者」。</div>
            </div>
          </div>

          <button type="submit" class="btn btn-primary"><i class="fa fa-check opacity-50 me-1"></i> <b>保存设置</b></button>
        </form>
      </div>
    </div>

    <div class="block block-rounded">
      <div class="block-header block-header-default">
        <h3 class="block-title"><b>回调地址</b></h3>
      </div>
      <div class="block-content">
        <p class="text-muted">商户后台如果能填回调地址，填下面两条；不能填也没关系，本系统每次下单都会带上。</p>
        <table class="table table-bordered mb-0">
          <tbody>
            <tr>
              <th style="width:140px">异步通知</th>
              <td><code><?php echo htmlspecialchars($notify); ?></code></td>
            </tr>
            <tr>
              <th>同步跳转</th>
              <td><code><?php echo htmlspecialchars($return); ?></code></td>
            </tr>
          </tbody>
        </table>
        <p class="text-muted mt-3 mb-0" style="font-size:13px">
          检查点：<code>/pay/notify.php</code> 必须能公网访问、返回纯 <code>success</code>；
          如果服务器开了 CDN / WAF，记得把这个路径加白，否则平台回调会被拦。
        </p>
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
