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
      <h3 class="block-title"><b>拓展设置</b></h3>
    </div>
    <div class="block-content">
      <form onsubmit="return saveSettings(this)" method="post" name="edit-form" role="form">
          <div class="row">
          <div class="col-md-12">
            <div class="row mb-4">
              <div class="col-6">
                <label class="form-label" for="Statistics_Id"><b>51.LA网站统计ID &nbsp; <small><a href="//51.la/" target="_blank">前往51.LA获取</a></small></b></label>
                <input type="text" class="form-control form-control-lg" name="Statistics_Id" value="<?php echo conf_index('Statistics_Id');?>" placeholder="请输入网站统计ID">
              </div>
              <div class="col-6">
                <label class="form-label" for="Statistics_Dm"><b>51.LA网站统计数据挂件地址 &nbsp; <small><a href="//51.la/" target="_blank">前往51.LA获取</a></small></b></label>
                <input type="text" class="form-control form-control-lg" name="Statistics_Dm" value="<?php echo conf_index('Statistics_Dm');?>" placeholder="只需填写script中的src地址即可">
              </div>
              </div>
              <div class="row mb-4">
              <div class="col-6">
                <label class="form-label" for="Index_Music"><b>明月浩空音乐播放器ID &nbsp; <small><a href="https://myhkw.cn/" target="_blank">前往获取</a></small></b></label>
                <input type="text" class="form-control form-control-lg" name="Index_Music" value="<?php echo conf_index('Index_Music');?>" placeholder="请输入播放器代码">
              </div>
              <div class="col-6">
                <label class="form-label" for="Index_Tc"><b>首页自定义弹窗内容</b></label>
                <input type="text" class="form-control form-control-lg" name="Index_Tc" value="<?php echo conf_index('Index_Tc');?>" placeholder="请输入自定义弹窗内容">
              </div>
              <div class="col-6">
                <label class="form-label" for="Index_Tc_Time"><b>弹窗间隔（小时）<small>，0=每次刷新都弹</small></b></label>
                <input type="number" class="form-control form-control-lg" name="Index_Tc_Time" value="<?php echo conf_index('Index_Tc_Time');?>" placeholder="例如 24 表示24小时内只弹一次" min="0">
              </div>
              </div>
              <div class="col-md-15">
                  <div class="mb-4 text-center">
                      <label class="form-label" for="Notice"><b>站点公告</b></label>
                      <textarea class="form-control form-control-lg" rows="4" name="Notice" placeholder="请输入站点后台公告(换行代码<br>)"><?php echo conf_index('Notice');?></textarea>
                  </div>
              </dvi>
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