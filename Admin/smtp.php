<?php
$mod = 'admin';
include('../Common/Core_brain.php');
if($adminData['adminRank']== 2) {
  echo "您的账号没有权限使用此功能";
  exit;
}
?>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title><?php echo conf('Name');?> - 后台管理中心</title>
  <meta content="<?php echo conf('Descriptison');?>" name="descriptison">
  <meta content="<?php echo conf('Keywords');?>" name="keywords">
  <link rel="shortcut icon" href="../assets/img/favicon.png">
  <link rel="apple-touch-icon" sizes="180x180" href="../assets/media/favicons/apple-touch-icon-180x180.png">
  <link rel="stylesheet" id="css-main" href="../assets/admin/css/codebase.min-5.5.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <script async src="https://www.googletagmanager.com/gtag/js?id=G-9HQDQJJYW7"></script><script>window.dataLayer = window.dataLayer || [];function gtag(){dataLayer.push(arguments);}gtag('js', new Date());gtag('config', 'G-9HQDQJJYW7');</script>
    <link rel="stylesheet" href="../assets/admin/css/decor.css">
</head>
<body>
<div id="page-container" class="sidebar-o enable-page-overlay side-scroll page-header-modern main-content-boxed">
<?php require_once 'head.php'; ?>
   <main id="main-container">
<div class="content">
  <div class="block block-rounded">
    <div class="block-header block-header-default">
      <h3 class="block-title"><b>发信邮箱配置</b></h3>
    </div>
    <div class="block-content">
      <form onsubmit="return saveSetting(this)" method="post" name="edit-form">
        <div class="row">
          <div class="col-md-12">
            <div class="row mb-4">
              <div class="col-6">
                <label class="form-label" for="Mail_Smtp"><b>SMTP地址</b></label>
                <input type="text" class="form-control form-control-lg" name="Mail_Smtp" value="<?php echo conf('Mail_Smtp');?>" placeholder="请输入SMTP地址">
              </div>
              <div class="col-6">
                <label class="form-label" for="Mail_Port"><b>SMTP端口</b></label>
                <input type="text" class="form-control form-control-lg" name="Mail_Port" value="<?php echo conf('Mail_Port');?>" placeholder="请输入SMTP端口">
              </div>
              </div>
              <div class="row mb-4">
              <div class="col-6">
                <label class="form-label" for="Mail_Name"><b>邮箱账号</b></label>
                <input type="text" class="form-control form-control-lg" name="Mail_Name" value="<?php echo conf('Mail_Name');?>" placeholder="请输入邮箱账号">
              </div>
              <div class="col-6">
                <label class="form-label" for="Mail_Pwd"><b>邮箱密码（授权码）</b></label>
                <input type="text" class="form-control form-control-lg" name="Mail_Pwd" value="<?php echo conf('Mail_Pwd');?>" placeholder="请输入邮箱密码（授权码）">
                <small>QQ邮箱请填写获取的授权码</small>
              </div>
            </div>
          </div>
          <div class="mb-4">
            <button type="submit" class="btn btn-primary">
              <i class="fa fa-check opacity-50 me-1"></i> <b>保存设置</b>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</main>
<?php require_once 'foot.php'; ?>
</div>
<script src="../assets/admin/js/codebase.app.min-5.5.js"></script>
<script type="text/javascript" src="../assets/admin/js/jquery.min.js"></script>
<script src="../assets/admin/js/time.js"></script>
<script src="../assets/layer/layer.js"></script>
<script>
function checkURL(obj)
{
  var url = $(obj).val();

  if (url.indexOf(" ")>=0){
    url = url.replace(/ /g,"");
  }
  if (url.toLowerCase().indexOf("http://")<0 && url.toLowerCase().indexOf("https://")<0){
    url = "http://"+url;
  }
  if (url.slice(url.length-1)!="/"){
    url = url+"/";
  }
  $(obj).val(url);
}
function saveSetting(obj){
  return otSave(obj, 'ajax.php?act=set');
}
function saveSettings(obj){
  return otSave(obj, 'ajax.php?act=sets');
}
</script>
</body>
</html>