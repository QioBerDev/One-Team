<?php
$mod = 'admin';
include('../Common/Core_brain.php');
if($adminData['adminRank']== 2) {
	echo "您的账号没有权限使用此功能";
	exit;
}
?>
<!DOCTYPE html>
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
  <script type="text/javascript" src="../assets/admin/js/jquery.min.js"></script>
  <script src="../assets/layer/layer.js"></script>
  <script async src="https://www.googletagmanager.com/gtag/js?id=G-9HQDQJJYW7"></script><script>window.dataLayer = window.dataLayer || [];function gtag(){dataLayer.push(arguments);}gtag('js', new Date());gtag('config', 'G-9HQDQJJYW7');</script>
    <link rel="stylesheet" href="../assets/admin/css/decor.css">
</head>
<body>
<div id="page-container" class="sidebar-o enable-page-overlay side-scroll page-header-modern main-content-boxed">
<?php require_once 'head.php'; ?>
<?php
$my=isset($_GET['my'])?$_GET['my']:null;
if($my=='add'){
?>
<main id="main-container">
<div class="content">
  <div class="block block-rounded">
    <div class="block-header block-header-default">
      <h3 class="block-title"><b>添加管理员</b></h3>
    </div>
    <div class="block-content">
      <form onsubmit="return addAdmin(this)" method="POST">
        <div class="row">
          <div class="col-md-12">
            <div class="row mb-4">
              <div class="col-6">
                <label class="form-label" for="name"><b>名称</b></label>
                <input type="text" class="form-control form-control-lg" name="adminUser" value="" placeholder="请输入管理员登录账号">
              </div>
              <div class="col-6">
                <label class="form-label" for="name"><b>密码</b></label>
                <input type="text" class="form-control form-control-lg" name="adminPwd" value="" placeholder="请输入管理员登录密码">
              </div>
              </div>
              <div class="row mb-4">
                <div class="col-6">
                  <label class="form-label" for="qq"><b>QQ</b></label>
                  <input type="text" class="form-control form-control-lg" name="adminQq" value="" placeholder="请输入管理员QQ号">
                </div>
              </div>
              <div class="col-lg-8 col-xl-5">
                <div class="mb-4">
                  <label class="form-label" for="adminRank"><b>管理员等级</b></label>
                  <select class="form-select" name="adminRank">
                    <option value="1">系统管理员</option>
                    <option value="2">普通管理员</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
          <div class="mb-4">
            <button type="submit" class="btn btn-primary">
              <i class="fa fa-check opacity-50 me-1"></i> <b>保存设置</b>
            </button>
            <button type="button" class="btn btn-primary" onclick="javascript:history.back(-1);return false;">
              <i class="fa fa-arrow-right-arrow-left opacity-50 me-1"></i> <b>返回列表</b>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</main>
<?php
}elseif($my=='edit'){
$id=intval($_GET['id']);
if($id==1){echo "<script>layer.ready(function(){layer.msg('你还想修改你老大？？？', {icon: 2, time: 1500}, function(){window.location.href='javascript:history.go(-1)'});});</script>";exit();}
$row=$DB->getRow("select * from nteam_admin where id='$id' limit 1");
if(!$row){echo "<script>layer.ready(function(){layer.msg('该管理员不存在', {icon: 2, time: 1500}, function(){window.location.href='javascript:history.go(-1)'});});</script>";exit();}
?>
<main id="main-container">
<div class="content">
  <div class="block block-rounded">
    <div class="block-header block-header-default">
      <h3 class="block-title"><b>修改管理员信息</b></h3>
    </div>
    <div class="block-content">
      <form onsubmit="return editAdmin(this)" method="POST">
        <div class="row">
          <div class="col-md-12">
            <div class="row mb-4">
              <div class="col-6">
                <label class="form-label" for="name"><b>名称</b></label>
                <input type="text" class="form-control form-control-lg" name="adminUser" value="<?php echo $row['adminUser'];?>" placeholder="请输入管理员登录账号">
              </div>
              <div class="col-6">
                <label class="form-label" for="qq"><b>密码</b></label>
                <input type="text" class="form-control form-control-lg" name="adminPwd" value="" placeholder="请输入管理员密码(不修改请留空)">
              </div>
              </div>
              <div class="row mb-4">
              <div class="col-6">
                <label class="form-label" for="describe"><b>QQ</b></label>
                <input type="text" class="form-control form-control-lg" name="adminQq" value="<?php echo $row['adminQq'];?>" placeholder="请输入QQ号">
              </div>
              <div class="col-lg-8 col-xl-5">
                <div class="mb-4">
                  <label class="form-label" for="adminRank"><b>管理员等级</b></label>
                  <select class="form-select" name="adminRank">
                    <?php if($row['adminRank'] == 1){echo '<option value="1" selected>系统管理员</option><option value="2">普通管理员</option>'; }else{ echo '<option value="1">系统管理员</option><option value="2" selected>普通管理员</option>';}?>
                  </select>
                </div>
              </div>
            </div>
          </div>
          <div class="mb-4">
            <button type="submit" class="btn btn-primary">
              <i class="fa fa-check opacity-50 me-1"></i> <b>保存设置</b>
            </button>
            <button type="button" class="btn btn-primary" onclick="javascript:history.back(-1);return false;">
              <i class="fa fa-arrow-right-arrow-left opacity-50 me-1"></i> <b>返回列表</b>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</main>
<?php
  }
  ?>
<?php require_once 'foot.php'; ?>
</div>
<script src="../assets/admin/js/codebase.app.min-5.5.js"></script>
<script src="../assets/admin/js/time.js"></script>
<script>
	function addAdmin(obj){
	  var ii = layer.load(2, {shade:[0.1,'#fff']});
	  $.ajax({
	    type : 'POST',
	    url : 'ajax.php?act=AddAdmin',
	    data : $(obj).serialize(),
	    dataType : 'json',
	    success : function(data) {
	      layer.close(ii);
	      if(data.code == 0){
	        layer.alert(data.msg, {icon: 1,closeBtn: false}, function(){window.location.reload()});
	      }else{
	        layer.alert(data.msg, {icon: 2})
	      }
	    },
	    error:function(data){
	      layer.msg('服务器错误');
	      return false;
	    }
	  });
	  return false;
	}
	function editAdmin(obj){
	  var ii = layer.load(2, {shade:[0.1,'#fff']});

	  $.ajax({
	    type : 'POST',
	    url : 'ajax.php?act=EditAdmin',
	    data : $(obj).serialize(),
	    dataType : 'json',
	    success : function(data) {
	      layer.close(ii);
	      if(data.code == 0){
	        layer.alert(data.msg, {icon: 1,closeBtn: false}, function(){window.location.reload()});
	      }else{
	        layer.alert(data.msg, {icon: 2})
	      }
	    },
	    error:function(data){
	      layer.msg('服务器错误');
	      return false;
	    }
	  });
	  return false;
	}
</script>
</body>
</html>