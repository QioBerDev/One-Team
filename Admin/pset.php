<?php
$mod = 'admin';
include('../Common/Core_brain.php');
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
      <h3 class="block-title"><b>添加项目</b></h3>
    </div>
    <div class="block-content">
      <form onsubmit="return addProject(this)" method="POST">
        <div class="row">
          <div class="col-md-12">
            <div class="row mb-4">
              <div class="col-6">
                <label class="form-label" for="title"><b>项目名称</b></label>
                <input type="text" class="form-control form-control-lg" name="name" value="" placeholder="请输入项目名称">
              </div>
              <div class="col-6">
                <label class="form-label" for="url"><b>项目网址</b></label>
                <input type="text" class="form-control form-control-lg" name="url" value="" placeholder="请输入项目网址，不加http://和/">
              </div>
              </div>
              <div class="row mb-4">
              <div class="col-6">
                <label class="form-label" for="img"><b>项目图片</b></label>
                <div class="input-group">
                  <input type="text" class="form-control form-control-lg" name="img" value="" placeholder="请输入项目的图片Url">
                  <button class="btn btn-secondary" type="button" onclick="document.getElementById('up_img').click()">上传</button>
                </div>
                <input type="file" id="up_img" style="display:none" accept="image/*" onchange="uploadImg(this,'img')">
              </div>
              <div class="col-6">
                <label class="form-label" for="img2"><b>项目图片2</b></label>
                <div class="input-group">
                  <input type="text" class="form-control form-control-lg" name="img2" value="" placeholder="请输入项目的图片Url">
                  <button class="btn btn-secondary" type="button" onclick="document.getElementById('up_img2').click()">上传</button>
                </div>
                <input type="file" id="up_img2" style="display:none" accept="image/*" onchange="uploadImg(this,'img2')">
              </div>
              </div>
              <div class="row mb-4">
              <div class="col-6">
                <label class="form-label" for="img3"><b>项目图片3</b></label>
                <div class="input-group">
                  <input type="text" class="form-control form-control-lg" name="img3" value="" placeholder="请输入项目的图片Url">
                  <button class="btn btn-secondary" type="button" onclick="document.getElementById('up_img3').click()">上传</button>
                </div>
                <input type="file" id="up_img3" style="display:none" accept="image/*" onchange="uploadImg(this,'img3')">
              </div>
              <div class="col-6">
                <label class="form-label" for="sketch"><b>项目简述</b></label>
                <input type="text" class="form-control form-control-lg" name="sketch" value="" placeholder="请输入项目的简单介绍（用于首页显示）">
              </div>
              </div>
              <div class="row mb-4">
                <div class="col-6">
                <label class="form-label" for="money"><b>项目售价</b></label>
                <input type="text" class="form-control form-control-lg" name="money" value="" placeholder="请输入项目的售价">
              </div>
              <div class="col-6">
                <label class="form-label" for="version"><b>项目版本号</b></label>
                <input type="text" class="form-control form-control-lg" name="version" value="" placeholder="请输入项目的当前版本号">
              </div>
              </div>
              <div class="mb-4">
              <label class="form-label" for="descriptison"><b>项目描述 <small class="text-muted">（支持 HTML 代码，直接渲染样式）</small></b></label>
              <textarea class="form-control" name="descriptison" rows="15" value="" placeholder="请输入项目的具体描述，支持 HTML 标签"></textarea>
              </div>
              <div class="mb-4">
              <label class="form-label" for="deliver"><b>付款后自动交付内容 <small class="text-muted">（选填：下载地址 / 卡密 / 说明，用户付款后自动发放；留空则后台手动发货）</small></b></label>
              <textarea class="form-control" name="deliver" rows="6" placeholder="例如：&#10;下载地址：https://example.com/file.zip&#10;卡密：XXXX-XXXX-XXXX&#10;使用说明：……"></textarea>
              </div>
            </div>
            <div class="row mb-4">
              <div class="col-6">
                <label class="form-label" for="paycontact"><b>作者QQ（选填）</b></label>
                <input type="text" class="form-control form-control-lg" name="paycontact" value="" placeholder="QQ账号，不填则前端不显示">
              </div>
              <div class="col-6">
                <label class="form-label" for="paytg"><b>作者TG（选填）</b></label>
                <input type="text" class="form-control form-control-lg" name="paytg" value="" placeholder="TG账号，不填则前端不显示">
              </div>
            </div>
           <div class="mb-4">
              <label class="form-label" for="type"><b>项目类型</b></label>
              <select class="form-select" name="type" size="1">
                <option value="web">单页</option>
                <option value="app">程序</option>
              </select>
            </div>
            <div class="mb-4">
              <label class="form-label" for="status"><b>显示首页</b></label>
              <select class="form-select" name="is_show" size="1">
                <option value="1">正常</option>
                <option value="0">暂停</option>
              </select>
            </div>
            <?php if($adminData['adminRank'] == 1) {?>
            <div class="mb-4">
              <label class="form-label" for="status"><b>审核状态</b></label>
              <select class="form-select" name="Audit_status" size="1">
                <option value="1">通过</option>
                <option value="0">未通过</option>
              </select>
            </div>
            <?php }?>
            <div class="mb-4">
              <label class="form-label" for="status"><b>项目状态</b></label>
              <select class="form-select" name="status" size="1">
                <option value="1">正常运营</option>
                <option value="0">暂停运营</option>
              </select>
            </div>
          </div>
          <div class="mb-4">
            <button type="submit" class="btn btn-primary">
              <i class="fa fa-check opacity-50 me-1"></i> <b>保存设置</b>
            </button>
            <button type="submit" class="btn btn-primary" onclick="javascript:history.back(-1);return false;">
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
$row=$DB->getRow("select * from nteam_project_list where id='$id' limit 1");
if(!$row){echo "<script>layer.ready(function(){layer.msg('该项目不存在', {icon: 2, time: 1500}, function(){window.location.href='javascript:history.go(-1)'});});</script>";exit();}
?>
<main id="main-container">
<div class="content">
  <div class="block block-rounded">
    <div class="block-header block-header-default">
      <h3 class="block-title"><b>修改 <?php echo $row['name'];?> 项目信息</b></h3>
    </div>
    <div class="block-content">
      <?php echo '<form onsubmit="return editProject(this,'.$id.')" method="POST" class="row">';?>
        <div class="row">
          <div class="col-md-12">
            <div class="row mb-4">
              <div class="col-6">
                <label class="form-label" for="title"><b>项目名称</b></label>
                <input type="text" class="form-control form-control-lg" name="name" value="<?php echo $row['name'];?>" placeholder="请输入项目名称">
              </div>
              <div class="col-6">
                <label class="form-label" for="url"><b>项目网址</b></label>
                <input type="text" class="form-control form-control-lg" name="url" value="<?php echo $row['url'];?>" placeholder="请输入项目网址，不加http://和/">
              </div>
              </div>
              <div class="row mb-4">
              <div class="col-6">
                <label class="form-label" for="img"><b>项目图片</b></label>
                <div class="input-group">
                  <input type="text" class="form-control form-control-lg" name="img" value="<?php echo $row['img'];?>" placeholder="请输入项目的图片Url">
                  <button class="btn btn-secondary" type="button" onclick="document.getElementById('up_img').click()">上传</button>
                </div>
                <input type="file" id="up_img" style="display:none" accept="image/*" onchange="uploadImg(this,'img')">
              </div>
              <div class="col-6">
                <label class="form-label" for="img2"><b>项目图片2</b></label>
                <div class="input-group">
                  <input type="text" class="form-control form-control-lg" name="img2" value="<?php echo $row['img2'];?>" placeholder="请输入项目的图片Url">
                  <button class="btn btn-secondary" type="button" onclick="document.getElementById('up_img2').click()">上传</button>
                </div>
                <input type="file" id="up_img2" style="display:none" accept="image/*" onchange="uploadImg(this,'img2')">
              </div>
              </div>
              <div class="row mb-4">
              <div class="col-6">
                <label class="form-label" for="img3"><b>项目图片3</b></label>
                <div class="input-group">
                  <input type="text" class="form-control form-control-lg" name="img3" value="<?php echo $row['img3'];?>" placeholder="请输入项目的图片Url">
                  <button class="btn btn-secondary" type="button" onclick="document.getElementById('up_img3').click()">上传</button>
                </div>
                <input type="file" id="up_img3" style="display:none" accept="image/*" onchange="uploadImg(this,'img3')">
              </div>
              <div class="col-6">
                <label class="form-label" for="sketch"><b>项目简述</b></label>
                <input type="text" class="form-control form-control-lg" name="sketch" value="<?php echo $row['sketch'];?>" placeholder="请输入项目的简单介绍（用于首页显示）">
              </div>
              </div>
              <div class="row mb-4">
              <div class="col-6">
                <label class="form-label" for="money"><b>项目售价</b></label>
                <input type="text" class="form-control form-control-lg" name="money" value="<?php echo $row['money'];?>" placeholder="请输入项目的售价">
              </div>
              <div class="col-6">
                <label class="form-label" for="version"><b>项目版本号</b></label>
                <input type="text" class="form-control form-control-lg" name="version" value="<?php echo $row['version'];?>" placeholder="请输入项目的当前版本号">
              </div>
              </div>
              <div class="mb-4">
              <label class="form-label" for="descriptison"><b>项目描述 <small class="text-muted">（支持 HTML 代码，直接渲染样式）</small></b></label>
              <textarea class="form-control" name="descriptison" rows="15" placeholder="请输入项目的具体描述，支持 HTML 标签"><?php echo $row['descriptison'];?></textarea>
              </div>
              <div class="mb-4">
              <label class="form-label" for="deliver"><b>付款后自动交付内容 <small class="text-muted">（选填：下载地址 / 卡密 / 说明，用户付款后自动发放；留空则后台手动发货）</small></b></label>
              <textarea class="form-control" name="deliver" rows="6" placeholder="例如：&#10;下载地址：https://example.com/file.zip&#10;卡密：XXXX-XXXX-XXXX&#10;使用说明：……"><?php echo isset($row['deliver']) ? $row['deliver'] : '';?></textarea>
              </div>
            </div>
            <div class="row mb-4">
              <div class="col-6">
                <label class="form-label" for="paycontact"><b>作者QQ（选填）</b></label>
                <input type="text" class="form-control form-control-lg" name="paycontact" value="<?php echo $row['paycontact'];?>" placeholder="QQ账号，不填则前端不显示">
              </div>
              <div class="col-6">
                <label class="form-label" for="paytg"><b>作者TG（选填）</b></label>
                <input type="text" class="form-control form-control-lg" name="paytg" value="<?php echo isset($row['paytg']) ? $row['paytg'] : '';?>" placeholder="TG账号，不填则前端不显示">
              </div>
            </div>
           <div class="mb-4">
              <label class="form-label" for="type"><b>项目类型</b></label>
              <select class="form-select" name="type" size="1">
                <?php if($row['type'] == 'web'){echo '<option value="web" selected>单页</option><option value="app">程序</option>'; }else{ echo '<option value="web">单页</option><option value="app" selected>程序</option>';}?>
              </select>
            </div>
            <div class="mb-4">
              <label class="form-label" for="status"><b>显示首页</b></label>
              <select class="form-select" name="is_show" size="1">
                <?php if($row['is_show'] == 1){echo '<option value="1" selected>正常</option><option value="0">暂停</option>'; }else{ echo '<option value="1">正常</option><option value="0" selected>暂停</option>';}?>
              </select>
            </div>
            <?php if($adminData['adminRank'] == 1) {?>
            <div class="mb-4">
              <label class="form-label" for="status"><b>审核状态</b></label>
              <select class="form-select" name="Audit_status" size="1">
                <?php if($row['Audit_status'] == 1){echo '<option value="1" selected>通过</option><option value="0">未通过</option>'; }else{ echo '<option value="1">通过</option><option value="0" selected>未通过</option>';}?>
              </select>
            </div>
            <?php }?>
            <div class="mb-4">
              <label class="form-label" for="status"><b>项目状态</b></label>
              <select class="form-select" name="status" size="1">
                <?php if($row['status'] == 1){echo '<option value="1" selected>正常运营</option><option value="0">暂停运营</option>'; }else{ echo '<option value="1">正常运营</option><option value="0" selected>暂停运营</option>';}?>
              </select>
            </div>
          </div>
          <div class="mb-4">
            <button type="submit" class="btn btn-primary">
              <i class="fa fa-check opacity-50 me-1"></i> <b>保存设置</b>
            </button>
            <button type="submit" class="btn btn-primary" onclick="javascript:history.back(-1);return false;">
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
	function addProject(obj){
  return otSave(obj, 'ajax.php?act=setProject&type=Add');
}
	function editProject(obj,id){
  return otSave(obj, 'ajax.php?act=setProject&type=Edit&id='+id);
}
</script>
</body>
</html>