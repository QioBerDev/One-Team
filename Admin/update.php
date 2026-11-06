<?php
/* 作者：CoCO —— 已去除远程更新检查（授权服务器已停止服务），仅显示当前版本 */
$mod = 'admin';
include('../Common/Core_brain.php');
if ($adminData['adminRank'] == 2) {
    echo '您的账号没有权限使用此功能';
    exit;
}
$config = (include '../Common/Auth.php');
$app_version = isset($config['app_version']) ? $config['app_version'] : '';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo conf('Name');?> - 后台管理中心</title>
    <link rel="stylesheet" id="css-main" href="../assets/admin/css/codebase.min-5.5.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/admin/css/decor.css">
</head>
<body>
<div id="page-container" class="sidebar-o enable-page-overlay side-scroll page-header-modern main-content-boxed">
    <?php require_once 'head.php';?>
    <main id="main-container">
        <div id="page-overlay"></div>
        <div class="content" id="pjax-container">
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title"><b>系统更新</b></h3>
                </div>
                <div class="block-content">
                    <div class="alert alert-success py-2 mb-0 small">
                        <i class="fa fa-circle-check opacity-50 me-1"></i> <b>当前已是最新版本</b>
                    </div>
                    <h1 class="fw-bold mt-5 mb-2">当前版本：v<?php echo $app_version;?></h1>
                    <hr>
                    <p class="text-muted">在线更新服务已停止，无法进行远程升级。如需升级请手动替换程序文件。</p>
                </div>
            </div>
        </div>
    </main>
    <?php require_once 'foot.php';?>
</div>
<script src="../assets/admin/js/codebase.app.min-5.5.js"></script>
<script src="../assets/admin/js/jquery.min.js"></script>
<script src="../assets/admin/js/time.js"></script>
<script src="../assets/layer/layer.js"></script>
</body>
</html>
