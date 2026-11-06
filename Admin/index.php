<?php
$mod = 'admin';
include('../Common/Core_brain.php');
$admin_nums=$DB->getColumn("SELECT count(*) from nteam_admin WHERE 1");
$member_nums=$DB->getColumn("SELECT count(*) from nteam_team_member WHERE 1");
$project_nums=$DB->getColumn("SELECT count(*) from nteam_project_list WHERE 1");
$mysqlversion=$DB->query("select VERSION()")->fetch();
$admin=$DB->query("SELECT * FROM nteam_admin WHERE 1");
while($admin = $admin->fetch()){
?>
<!doctype html>
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
<div class="row">
  <div class="col-6 col-xl-3">
    <a class="block block-rounded block-link-shadow text-end" href="javascript:void(0)">
      <div class="block-content block-content-full d-sm-flex justify-content-between align-items-center">
        <div class="d-none d-sm-block">
          <i class="fa fa-timeline fa-2x opacity-25"></i>
        </div>
        <div>
          <div class="fs-3 fw-semibold" id="clock"></div>
          <div class="fs-sm fw-semibold text-uppercase text-muted">实时时间</div>
        </div>
      </div>
    </a>
  </div>
  <div class="col-6 col-xl-3">
    <a class="block block-rounded block-link-shadow text-end" href="javascript:void(0)">
      <div class="block-content block-content-full d-sm-flex justify-content-between align-items-center">
        <div class="d-none d-sm-block">
          <i class="fa fa-arrows-down-to-people fa-2x opacity-25"></i>
        </div>
        <div>
          <div class="fs-3 fw-semibold"><?php echo $admin_nums;?> 位</div>
          <div class="fs-sm fw-semibold text-uppercase text-muted">管理员总数</div>
        </div>
      </div>
    </a>
  </div>
  <div class="col-6 col-xl-3">
    <a class="block block-rounded block-link-shadow text-end" href="javascript:void(0)">
      <div class="block-content block-content-full d-sm-flex justify-content-between align-items-center">
        <div class="d-none d-sm-block">
          <i class="fa fa-users fa-2x opacity-25"></i>
        </div>
        <div>
          <div class="fs-3 fw-semibold"><?php echo $member_nums;?> 位</div>
          <div class="fs-sm fw-semibold text-uppercase text-muted">成员总数</div>
        </div>
      </div>
    </a>
  </div>
  <div class="col-6 col-xl-3">
    <a class="block block-rounded block-link-shadow text-end" href="javascript:void(0)">
      <div class="block-content block-content-full d-sm-flex justify-content-between align-items-center">
        <div class="d-none d-sm-block">
          <i class="fa fa-shopping-bag fa-2x opacity-25"></i>
        </div>
        <div>
          <div class="fs-3 fw-semibold"><?php echo $project_nums;?> 个</div>
          <div class="fs-sm fw-semibold text-uppercase text-muted">项目总数</div>
        </div>
      </div>
    </a>
  </div>
</div>
<div class="row">
  <div class="col-md-12">
      <div class="block block-rounded">
          <div class="block-header block-header-default">
              <h3 class="block-title"><b>站点公告</b></h3>
          </div>
          <div class="block-content">
              <div class="js-slider slick-nav-black slick-nav-hover" data-dots="true" data-autoplay="true" data-arrows="true">
                  <div class="col-12">
                      <div class="block block-rounded">
                          <div class="block-content text-center">
                              <i class="si si-disc fa-2x"></i>
                              <p class="text-muted fs-sm">
                                  <b><?php echo conf_index('Notice');?></b>
                              </p>
                          </div>
                      </div>
                  </div>
              </div>
          </div>
      </div>
  </div>
</div>
<div class="row">
    <div class="col-lg-16 mb-2">
        <div class="block block-rounded mb-0">
            <div class="block-header block-header-default">
                <h3 class="block-title"><b>服务器信息</b></h3>
            </div>
            <div class="block-content block-content-full" data-toggle="slimscroll" data-height="259px">
                <div class="fw-medium fs-sm">
                    <div class="border-start border-4 rounded-2 border-primary mb-2">
                        <div class="rounded p-2 text-pulse-light" id="notice">
                            <p class="m-1 text-center">PHP 版本：<?php echo phpversion() ?> <?php if(ini_get('safe_mode')) { echo '线程安全'; } else { echo '非线程安全'; } ?></p>
                            <p class="m-1 text-center">MySQL 版本：<?php echo $mysqlversion[0] ?></p>
                            <p class="m-1 text-center">服务器软件：<?php echo $_SERVER['SERVER_SOFTWARE'] ?></p>
                            <p class="m-1 text-center">程序最大运行时间：<?php echo ini_get('max_execution_time') ?>s</p>
                            <p class="m-1 text-center">POST许可：<?php echo ini_get('post_max_size'); ?></p>
                            <p class="m-1 text-center">文件上传许可：<?php echo ini_get('upload_max_filesize'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
&nbsp;
  <div class="row">
    <div class="col-md-4">
      <div class="block block-rounded">
        <div class="block-content block-content-full">
          <div class="py-3 text-center">
            <div class="mb-3">
              <i class="fa fa-bars-progress fa-4x text-primary"></i>
            </div>
            <div class="fs-4 fw-semibold">现在去设置网站</div>
            <div class="pt-3">
              <a class="btn btn-alt-primary" href="set.php">
                <i class="fa fa-gear opacity-50 me-1"></i> <b>系统设置</b>
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="block block-rounded">
        <div class="block-content block-content-full">
          <div class="py-3 text-center">
            <div class="mb-3">
              <i class="fa fa fa-book-tanakh fa-4x text-info"></i>
            </div>
            <div class="fs-4 fw-semibold">现在去管理项目</div>
            <div class="pt-3">
              <a class="btn btn-alt-info" href="plist.php">
                <i class="fa fa-users opacity-50 me-1"></i> <b>项目管理</b>
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="block block-rounded">
        <div class="block-content block-content-full">
          <div class="py-3 text-center">
            <div class="mb-3">
              <i class="fa fa-check fa-4x text-success"></i>
            </div>
            <div class="fs-4 fw-semibold">现在去更新程序</div>
            <div class="pt-3">
              <a class="btn btn-alt-success" href="update.php">
                <i class="fa fa-arrow-up opacity-50 me-1"></i> <b>程序更新</b>
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</main>
<?php require_once 'foot.php'; ?>
</div>
<script src="../assets/admin/js/codebase.app.min-5.5.js"></script>
<script src="../assets/js/jquery.min.js"></script>
<script src="../assets/admin/js/time.js"></script>
</body>
</html>
<?php
}
?>