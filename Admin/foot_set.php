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
    <link rel="stylesheet" href="../assets/admin/css/decor.css">
</head>
<body>
<div id="page-container" class="sidebar-o enable-page-overlay side-scroll page-header-modern main-content-boxed">
<?php require_once 'head.php'; ?>
<main id="main-container">
<div class="content">

  <!-- 品牌信息 -->
  <div class="block block-rounded">
    <div class="block-header block-header-default">
      <h3 class="block-title"><b>页脚 · 品牌信息</b></h3>
    </div>
    <div class="block-content">
      <form onsubmit="return saveSetting(this)" method="post" name="edit-form">
        <div class="row mb-4">
          <div class="col-md-6">
            <label class="form-label" for="Footer_Title"><b>品牌标题</b></label>
            <input type="text" class="form-control form-control-lg" id="Footer_Title" name="Footer_Title" value="<?php echo htmlspecialchars(conf('Footer_Title'), ENT_QUOTES, 'UTF-8');?>" placeholder="例如：寒阳网络科技工作团队">
            <div class="form-text">显示在页脚 Logo 下方，加粗大字。</div>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="Footer_Desc"><b>品牌描述</b></label>
            <input type="text" class="form-control form-control-lg" id="Footer_Desc" name="Footer_Desc" value="<?php echo htmlspecialchars(conf('Footer_Desc'), ENT_QUOTES, 'UTF-8');?>" placeholder="例如：寒阳科技 专注优质网络资源分享的技术团队">
            <div class="form-text">标题下方的一行灰色说明文字。</div>
          </div>
        </div>
        <div class="mb-2">
          <button type="submit" class="btn btn-primary">
            <i class="fa fa-check opacity-50 me-1"></i> <b>保存品牌信息</b>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- 联系方式 -->
  <div class="block block-rounded">
    <div class="block-header block-header-default">
      <h3 class="block-title"><b>页脚 · 联系方式</b></h3>
    </div>
    <div class="block-content">
      <form onsubmit="return saveSetting(this)" method="post" name="edit-form">
        <div class="row mb-4">
          <div class="col-md-6">
            <label class="form-label" for="Footer_Email1"><b>支持服务邮箱</b></label>
            <input type="email" class="form-control form-control-lg" id="Footer_Email1" name="Footer_Email1" value="<?php echo htmlspecialchars(conf('Footer_Email1'), ENT_QUOTES, 'UTF-8');?>" placeholder="support@example.com">
          </div>
          <div class="col-md-6">
            <label class="form-label" for="Footer_Email2"><b>违规投诉邮箱</b></label>
            <input type="email" class="form-control form-control-lg" id="Footer_Email2" name="Footer_Email2" value="<?php echo htmlspecialchars(conf('Footer_Email2'), ENT_QUOTES, 'UTF-8');?>" placeholder="abuse@example.com">
          </div>
        </div>
        <div class="mb-2">
          <button type="submit" class="btn btn-primary">
            <i class="fa fa-check opacity-50 me-1"></i> <b>保存联系方式</b>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- 社交链接 -->
  <div class="block block-rounded">
    <div class="block-header block-header-default">
      <h3 class="block-title"><b>页脚 · 社交链接</b></h3>
    </div>
    <div class="block-content">
      <form onsubmit="return saveSetting(this)" method="post" name="edit-form">
        <div class="row mb-4">
          <div class="col-md-4">
            <label class="form-label" for="Footer_QQ"><b><i class="fa-brands fa-qq me-1"></i>QQ 链接</b></label>
            <input type="text" class="form-control form-control-lg" id="Footer_QQ" name="Footer_QQ" value="<?php echo htmlspecialchars(conf('Footer_QQ'), ENT_QUOTES, 'UTF-8');?>" placeholder="https://qm.qq.com/cgi-bin/qm/qr?k=...">
            <div class="form-text">留空则不显示 QQ 图标。</div>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="Footer_TG"><b><i class="fa-brands fa-telegram me-1"></i>Telegram 链接</b></label>
            <input type="text" class="form-control form-control-lg" id="Footer_TG" name="Footer_TG" value="<?php echo htmlspecialchars(conf('Footer_TG'), ENT_QUOTES, 'UTF-8');?>" placeholder="https://t.me/yourname">
            <div class="form-text">留空则不显示 TG 图标。</div>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="Footer_X"><b><i class="fa-brands fa-x-twitter me-1"></i>X (Twitter) 链接</b></label>
            <input type="text" class="form-control form-control-lg" id="Footer_X" name="Footer_X" value="<?php echo htmlspecialchars(conf('Footer_X'), ENT_QUOTES, 'UTF-8');?>" placeholder="https://x.com/yourname">
            <div class="form-text">留空则不显示 X 图标。</div>
          </div>
        </div>
        <div class="mb-2">
          <button type="submit" class="btn btn-primary">
            <i class="fa fa-check opacity-50 me-1"></i> <b>保存社交链接</b>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- 预览说明 -->
  <div class="block block-rounded">
    <div class="block-header block-header-default">
      <h3 class="block-title"><b>说明</b></h3>
    </div>
    <div class="block-content fs-sm text-muted">
      <p class="mb-2">本页配置会实时应用到全站页脚（首页 / 公告与动态 / 作品案例 / 关于我们 / 免责声明）。</p>
      <p class="mb-0">网站导航与资源链接为固定栏目，无需配置；备案号在「网站配置」中维护。</p>
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
function saveSetting(obj){
  return otSave(obj, 'ajax.php?act=set');
}
</script>
</body>
</html>
