<?php
$mod = 'admin';
include('../Common/Core_brain.php');
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
      <h3 class="block-title"><b>管理密码修改</b></h3>
    </div>
    <div class="block-content">
      <form method="post" action="./edit_pwd.php">
        <div class="row">
          <div class="col-md-12">
            <div class="row mb-4">
              <div class="col-6">
                <label class="form-label" for="id"><b>管理员ID</b></label>
                <input type="text" class="form-control form-control-lg" value="<?=$adminData['id']?>" name="id" class="form-control text-primary font-size-sm" disabled>
              </div>
              <div class="col-6">
                <label class="form-label" for="adminUser"><b>账号</b></label>
                <input type="text" class="form-control form-control-lg" value="<?=$adminData['adminUser']?>" name="adminUser" placeholder="请输入管理员账号" disabled>
                <div style="margin-top: 10px;"><b>如需修改账号请联系最高管理员</b></div>
              </div>
              </div>
              <div class="row mb-4">
              <div class="col-6">
                <label class="form-label" for="new-password"><b>新密码</b></label>
                <input type="text" class="form-control form-control-lg" name="adminPwd" placeholder="请输入管理员密码(不修改请留空)" autocomplete="off">
              </div>
              <div class="col-6">
                <label class="form-label" for="confirm-password"><b>管理员ＱＱ</b></label>
                <input type="text" class="form-control form-control-lg" name="adminQq" value="<?=$adminData['adminQq']?>" placeholder="请输入管理员ＱＱ" autocomplete="off">
              </div>
            </div>
          </div>
          <div class="mb-4">
            <button type="submit" id="submit" class="btn btn-primary">
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
var vaptcha_open = 0;
$(document).ready(function(){
  if($("#vaptchaContainer").length>0) vaptcha_open=1;
  $("#submit").click(function(){
    var adminUser = $("input[name='adminUser']").val();
    var adminPwd = $("input[name='adminPwd']").val();
    var adminQq = $("input[name='adminQq']").val();
    var id = $("input[name='id']").val();
    var data = {adminUser:adminUser,adminPwd:adminPwd,adminQq:adminQq,id:id};
    var edit = $("button[type='submit']");
    if(adminUser.length < 1 || adminQq.length < 1){
        layer.alert('账号或者QQ号为空，请补充完整！',{icon:2,shade:0.8});
        return false;
    }
    if(vaptcha_open==1){
      var token = obj.getToken();
      var adddata = {token:token};
    }
    edit.attr('disabled', 'true');
    layer.msg('正在修改中，请稍后...');
    $.ajax({
      type: "POST",
      url: "ajax.php?act=admininfo",
      data: Object.assign(data, adddata),
      dataType: "json",
      success: function (data) {
        if(data.code == 1){
          edit.removeAttr('disabled');
          layer.alert(data.msg,{icon:1,shade:0.8});
          obj.reset();
        }else if(data.code == 2){
          setTimeout(function (){
              parent.location.href = './login.php'
          },1000);
          layer.alert(data.msg,{icon:1,shade:0.8});
        }else{
          edit.removeAttr('disabled');
          layer.alert(data.msg,{icon:2,shade:0.8});
          obj.reset();
        }
      },
    });
    return false;
  });
});
</script>
</body>
</html>