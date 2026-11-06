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
      <h3 class="block-title"><b>添加成员</b></h3>
    </div>
    <div class="block-content">
      <form onsubmit="return addMember(this)" method="POST">
        <div class="row">
          <div class="col-md-12">
            <div class="row mb-4">
              <div class="col-6">
                <label class="form-label" for="name"><b>名称</b></label>
                <input type="text" class="form-control form-control-lg" name="name" value="" placeholder="请输入成员名称">
              </div>
              <div class="col-6">
                <label class="form-label" for="qq"><b>QQ</b></label>
                <input type="text" class="form-control form-control-lg" name="qq" value="" placeholder="请输入QQ号">
              </div>
              <div class="col-6">
                <label class="form-label" for="tg"><b>TG（选填）</b></label>
                <input type="text" class="form-control form-control-lg" name="tg" value="" placeholder="TG账号，不填则前端不显示">
              </div>
              </div>
            <div class="mb-4">
              <label class="form-label" for="describe"><b>成员描述</b></label>
              <textarea class="form-control" name="describe" rows="5" placeholder="请输入描述，换行用<br>"></textarea>
            </div>
            <div class="col-md-12">
                <div class="mb-4">
                  <label class="form-label" for="teamimg"><b>成员背景图</b></label>
                  <div class="input-group">
                    <input type="text" class="form-control form-control-lg" name="teamimg" value="" placeholder="请输入成员背景图片">
                    <button class="btn btn-secondary" type="button" onclick="document.getElementById('up_teamimg').click()">上传</button>
                  </div>
                  <input type="file" id="up_teamimg" style="display:none" accept="image/*" onchange="uploadImg(this,'teamimg')">
                </div>
              <div class="col-md-12">
                <div class="mb-4">
                  <label class="form-label" for="status"><b>显示首页</b></label>
                  <select class="form-select" name="is_show">
                    <option value="1">正常</option>
                    <option value="0">暂停</option>
                  </select>
                </div>
                <div class="mb-4">
                  <label class="form-label" for="status"><b>审核状态</b></label>
                  <select class="form-select" name="Audit_status">
                    <option value="1">通过</option>
                    <option value="0">未通过</option>
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
$row=$DB->getRow("select * from nteam_team_member where id='$id' limit 1");
if(!$row){echo "<script>layer.ready(function(){layer.msg('该成员不存在', {icon: 2, time: 1500}, function(){window.location.href='javascript:history.go(-1)'});});</script>";exit();}
?>
<main id="main-container">
<div class="content">
  <div class="block block-rounded">
    <div class="block-header block-header-default">
      <h3 class="block-title"><b>修改成员信息</b></h3>
    </div>
    <div class="block-content">
      <?php echo '<form onsubmit="return editMember(this,'.$id.')" method="POST" class="row">';?>
        <div class="row">
          <div class="col-md-12">
            <div class="row mb-4">
              <div class="col-6">
                <label class="form-label" for="name"><b>名称</b></label>
                <input type="text" class="form-control form-control-lg" name="name" value="<?php echo $row['name'];?>" placeholder="请输入成员名称">
              </div>
              <div class="col-6">
                <label class="form-label" for="qq"><b>QQ</b></label>
                <input type="text" class="form-control form-control-lg" name="qq" value="<?php echo $row['qq'];?>" placeholder="请输入QQ号">
              </div>
              <div class="col-6">
                <label class="form-label" for="tg"><b>TG（选填）</b></label>
                <input type="text" class="form-control form-control-lg" name="tg" value="<?php echo isset($row['tg']) ? $row['tg'] : '';?>" placeholder="TG账号，不填则前端不显示">
              </div>
              </div>
            <div class="mb-4">
              <label class="form-label" for="describe"><b>成员描述</b></label>
              <textarea class="form-control" name="describe" rows="5" placeholder="请输入描述，换行用<br>"><?php echo $row['describe'];?></textarea>
            </div>
            <div class="col-md-12">
                <div class="mb-4">
                  <label class="form-label" for="teamimg"><b>成员背景图</b></label>
                  <div class="input-group">
                    <input type="text" class="form-control form-control-lg" name="teamimg" value="<?php echo $row['teamimg'];?>" placeholder="请输入成员背景图片">
                    <button class="btn btn-secondary" type="button" onclick="document.getElementById('up_teamimg').click()">上传</button>
                  </div>
                  <input type="file" id="up_teamimg" style="display:none" accept="image/*" onchange="uploadImg(this,'teamimg')">
                </div>
              <div class="col-md-12">
                <div class="mb-4">
                  <label class="form-label" for="status"><b>显示首页</b></label>
                  <select class="form-select" name="is_show">
                    <?php if($row['is_show'] == 1){echo '<option value="1" selected>正常</option><option value="0">暂停</option>'; }else{ echo '<option value="1">正常</option><option value="0" selected>暂停</option>';}?>
                  </select>
                </div>
                <div class="mb-4">
                  <label class="form-label" for="status"><b>审核状态</b></label>
                  <select class="form-select" name="Audit_status">
                    <?php if($row['Audit_status'] == 1){echo '<option value="1" selected>通过</option><option value="0">未通过</option>'; }else{ echo '<option value="1">通过</option><option value="0" selected>未通过</option>';}?>
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
	function addMember(obj){
  return otSave(obj, 'ajax.php?act=setMember&type=Add');
}
	function editMember(obj,id){
  return otSave(obj, 'ajax.php?act=setMember&type=Edit&id='+id);
}
</script>
</body>
</html>