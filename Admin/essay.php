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
  <title><?php echo conf('Name');?> - 作者随笔</title>
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
  <div class="block block-rounded">
    <div class="block-header block-header-default">
      <h3 class="block-title"><b>作者随笔</b></h3>
    </div>
    <div class="block-content">
      <h4 class="fw-bold mb-3">关于作者</h4>
      <p class="text-muted">你好，我是寒阳，一个对技术充满热情的独立开发者。喜欢捣鼓各种有意思的小项目，从网站模板到自动化脚本，从工具开发到服务器运维，都愿意折腾。</p>
      <p class="text-muted">信奉"代码即艺术"，追求简洁优雅的解决方案。日常沉迷于 Web 开发、Linux 运维和各种开源工具，偶尔写点技术笔记。</p>
      <p class="text-muted mb-4">如果你有合作意向、技术交流，或者就是单纯想聊聊，欢迎通过下面的方式联系我。</p>
      <hr>
      <div class="text-center py-3">
        <a class="btn btn-primary py-2 px-4 m-1" href="https://qm.qq.com/cgi-bin/qm/qr?k=157777491" target="_blank">
          <i class="fa-brands fa-qq me-1"></i> 作者QQ
        </a>
        <a class="btn btn-info py-2 px-4 m-1" href="https://t.me/UI3I4s" target="_blank">
          <i class="fa-brands fa-telegram me-1"></i> 作者TG
        </a>
      </div>
    </div>
  </div>
</div>
</main>
</div>
<script src="../assets/admin/js/codebase.app.min-5.5.js"></script>
<script src="../assets/admin/js/jquery.min.js"></script>
</body>
</html>
