<?php
require_once __DIR__ . '/../Common/OtConfig.php';
require_once __DIR__ . '/../Common/ModeSwitch.php';
/* 登录后台即确认管理员账号已落地到用户中心（幂等，只补不覆盖） */
ms_sync_admin($DB);
?>
<style>
/* 顶部模式切换按钮：醒目、和侧栏菜单区分开 */
.ms-switch-link {
  background: linear-gradient(120deg, rgba(59,130,246,.16), rgba(139,92,246,.16));
  border-left: 3px solid #5b8def;
  border-radius: 6px;
  margin-bottom: 6px;
}
.ms-switch-link .nav-main-link-icon,
.ms-switch-link .nav-main-link-name { color: #2f6bff; }
.ms-switch-link:hover { background: linear-gradient(120deg, rgba(59,130,246,.28), rgba(139,92,246,.28)); }
.ms-switch-badge {
  display: inline-block; margin-left: 6px; padding: 1px 7px;
  font-size: 11px; line-height: 1.5; border-radius: 20px;
  background: #5b8def; color: #fff; font-weight: 600;
}
.ms-switch-btn {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 7px 14px; border-radius: 8px; font-size: 13px; font-weight: 600;
  background: #5b8def; color: #fff; text-decoration: none;
  box-shadow: 0 3px 10px rgba(91,141,239,.3);
  transition: transform .15s ease, box-shadow .15s ease;
}
.ms-switch-btn:hover { color: #fff; transform: translateY(-1px); box-shadow: 0 5px 16px rgba(91,141,239,.45); }
</style>
  <nav id="sidebar">
  <div class="sidebar-content">
    <div class="content-header justify-content-lg-center">
      <div>
        <span class="smini-visible fw-bold tracking-wide fs-lg">
          M<span class="text-primary">Y</span>
        </span>
        <a class="link-fx fw-bold tracking-wide mx-auto" href="index.php" title="后台首页">
          <span class="smini-hidden">
            <i class="fa fa-fire text-primary"></i>
            <span class="fs-4 text-dual"><?php echo conf('Name');?></span>
          </span>
        </a>
      </div>
      <div>
        <button type="button" class="btn btn-sm btn-alt-danger d-lg-none" data-toggle="layout" data-action="sidebar_close">
          <i class="fa fa-fw fa-xmark"></i>
        </button>
      </div>
    </div>
    <div class="js-sidebar-scroll">
      <div class="content-side content-side-user px-0 py-0">
        <div class="smini-visible-block animated fadeIn px-3">
          <img class="img-avatar img-avatar32" src="https://q1.qlogo.cn/g?b=qq&nk=<?=$_SESSION['adminQq']?>&s=100" alt="MoYi-Team">
        </div>
        <div class="smini-hidden text-center mx-auto">
          <a class="img-link" href="javascript:void(0)">
            <img class="img-avatar" src="https://q1.qlogo.cn/g?b=qq&nk=<?=$_SESSION['adminQq']?>&s=100" alt="MoYi=Team">
          </a>
          <ul class="list-inline mt-3 mb-0">
            <li class="list-inline-item">
              <a class="link-fx text-dual fs-sm fw-semibold text-uppercase" href="javascript:void(0)"><?=$_SESSION['adminUser']?></a>
            </li>
            <li class="list-inline-item">
              <a class="link-fx text-dual" data-toggle="layout" data-action="dark_mode_toggle" href="javascript:void(0)">
                <i class="fa fa-burn"></i>
              </a>
            </li>
            <li class="list-inline-item">
              <a class="link-fx text-dual" href="./login.php?logout">
                <i class="fa fa-sign-out-alt"></i>
              </a>
            </li>
          </ul>
        </div>
      </div>
      <div class="content-side content-side-full">
        <ul class="nav-main">
          <?php /* 顶部模式切换：在后台时点它进前台用户中心（先生要求把「后台首页」改成切换模式）。
                   后台账号已镜像进 nteam_user，切过去免密；切回来靠前台页头的「进入管理员操作台」。 */ ?>
          <li class="nav-main-item">
            <a class="nav-main-link ms-switch-link" href="<?php echo htmlspecialchars(ms_ticket_url('user'), ENT_QUOTES, 'UTF-8'); ?>">
              <i class="nav-main-link-icon fa fa-shuffle"></i>
              <span class="nav-main-link-name"><b>前台用户中心</b></span>
              <span class="ms-switch-badge">切换</span>
            </a>
          </li>
          <li class="nav-main-item">
            <a class="nav-main-link" href="index.php">
              <i class="nav-main-link-icon fa fa-gauge-high"></i>
              <span class="nav-main-link-name">后台首页</span>
            </a>
          </li>
          <?php if($adminData['adminRank']==1){?>
          <li class="nav-main-heading">Function</li>
          <!-- 「管理员管理」已去掉：管理员改成前端用户表里的账号（is_admin），
               只有管理员账号登录用户中心后才会显示「切换后台」 -->
          <li class="nav-main-item">
            <a class="nav-main-link" href="tlist.php">
              <i class="nav-main-link-icon fa fa-pencil-ruler"></i>
              <span class="nav-main-link-name"><b>成员管理</b></span>
            </a>
          </li>
          <li class="nav-main-item">
            <a class="nav-main-link" href="content.php">
              <i class="nav-main-link-icon fa fa-newspaper"></i>
              <span class="nav-main-link-name"><b>文章 / 动态 / 公告</b></span>
            </a>
          </li>
          <?php } if($adminData['adminRank']==1 || $adminData['adminRank']==2 ){ ?>
          <li class="nav-main-item">
            <a class="nav-main-link" href="plist.php">
              <i class="nav-main-link-icon fa fa-layer-group"></i>
              <span class="nav-main-link-name"><b>项目管理</b></span>
            </a>
          </li>
          <!-- 用户管理 + 用户中心运营 = 一个板块，内部用 tab 切换 -->
          <li class="nav-main-item">
            <a class="nav-main-link" href="umember.php">
              <i class="nav-main-link-icon fa fa-users"></i>
              <span class="nav-main-link-name"><b>用户中心</b> <small class="opacity-50">管理 + 运营</small></span>
            </a>
          </li>
          <?php if($adminData['adminRank']==1){ ?>
          <!-- QQ 互联 / 注册登录 / 彩虹聚合登录 = 独立板块 -->
          <li class="nav-main-item">
            <a class="nav-main-link" href="oauth.php">
              <i class="nav-main-link-icon fa fa-key"></i>
              <span class="nav-main-link-name"><b>注册登录 / 聚合登录</b></span>
            </a>
          </li>
          <?php if(function_exists('ot_on') && ot_on('Updater_Open')){ ?>
          <li class="nav-main-item">
            <a class="nav-main-link" href="updater.php">
              <i class="nav-main-link-icon fa fa-rotate"></i>
              <span class="nav-main-link-name"><b>在线更新检测</b></span>
            </a>
          </li>
          <?php } ?>
          <!-- 订单 / 卡密 / 余额 = 支付配置板块，内部用 tab 切换 -->
          <li class="nav-main-item">
            <a class="nav-main-link" href="pay_set.php">
              <i class="nav-main-link-icon fa fa-credit-card"></i>
              <span class="nav-main-link-name"><b>支付配置</b> <small class="opacity-50">订单 / 卡密 / 余额</small></span>
            </a>
          </li>
          <?php } ?>
          <li class="nav-main-heading">Build</li>
          <?php } if($adminData['adminRank']==1){ ?>
            <li class="nav-main-item">
            <a class="nav-main-link" href="set.php">
              <i class="nav-main-link-icon fa fa-gears"></i>
              <span class="nav-main-link-name"><b>网站配置</b></span>
            </a>
            </li>
            <li class="nav-main-item">
            <a class="nav-main-link" href="foot_set.php">
              <i class="nav-main-link-icon fa fa-shoe-prints"></i>
              <span class="nav-main-link-name"><b>页脚配置</b></span>
            </a>
            </li>
            <li class="nav-main-item">
            <a class="nav-main-link" href="module.php">
              <i class="nav-main-link-icon fa fa-box"></i>
              <span class="nav-main-link-name"><b>模块配置</b></span>
            </a>
            </li>
            <li class="nav-main-item">
            <a class="nav-main-link" href="smtp.php">
              <i class="nav-main-link-icon fa fa-envelope-open"></i>
              <span class="nav-main-link-name"><b>邮箱配置</b></span>
            </a>
            </li>
            <li class="nav-main-item">
            <a class="nav-main-link" href="expand.php">
              <i class="nav-main-link-icon fa fa-bomb"></i>
              <span class="nav-main-link-name"><b>拓展配置</b></span>
            </a>
            </li>
            <li class="nav-main-heading">Expand</li>
            <li class="nav-main-item">
              <a class="nav-main-link" href="team_approval.php">
                <i class="nav-main-link-icon fa fa-user-plus"></i>
                <span class="nav-main-link-name"><b>成员审批</b></span>
              </a>
            </li>
            <li class="nav-main-item">
              <a class="nav-main-link" href="system_log.php">
                <i class="nav-main-link-icon fa fa-bar-chart"></i>
                <span class="nav-main-link-name"><b>系统日志</b></span>
              </a>
            </li>
            <?php } if($adminData['adminRank']==1){ ?>
            <li class="nav-main-item">
              <a class="nav-main-link" href="update.php">
                <i class="nav-main-link-icon fa fa-globe-americas"></i>
                <span class="nav-main-link-name"><b>系统更新</b></span>
              </a>
            </li>
            <?php } ?>
            <li class="nav-main-item">
              <a class="nav-main-link" href="essay.php">
                <i class="nav-main-link-icon fa fa-pen-nib"></i>
                <span class="nav-main-link-name"><b>作者随笔</b></span>
              </a>
            </li>
        </ul>
      </div>
    </div>
  </div>
</nav>
  <header id="page-header">
  <div class="content-header">
    <div class="space-x-1">
      <button type="button" class="btn btn-sm btn-alt-secondary" data-toggle="layout" data-action="sidebar_toggle">
        <i class="fa fa-fw fa-bars"></i>
      </button>
      <div class="dropdown d-inline-block">
        <button type="button" class="btn btn-sm btn-alt-secondary" id="page-header-themes-dropdown" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-haspopup="true" aria-expanded="false">
          <i class="fa fa-fw fa-wrench"></i>
        </button>
        <div class="dropdown-menu dropdown-menu-lg p-0" aria-labelledby="page-header-themes-dropdown">
          <div class="p-3 bg-body-light rounded-top">
            <h5 class="h6 text-center mb-0">
              界面颜色
            </h5>
          </div>
          <div class="p-3">
            <div class="row g-0 text-center">
              <div class="col-2">
                <a class="text-default" data-toggle="theme" data-theme="default" href="javascript:void(0)">
                  <i class="fa fa-2x fa-circle"></i>
                </a>
              </div>
              <div class="col-2">
                <a class="text-elegance" data-toggle="theme" data-theme="../assets/admin/css/themes/elegance.min-5.5.css" href="javascript:void(0)">
                  <i class="fa fa-2x fa-circle"></i>
                </a>
              </div>
              <div class="col-2">
                <a class="text-pulse" data-toggle="theme" data-theme="../assets/admin/css/themes/pulse.min-5.5.css" href="javascript:void(0)">
                  <i class="fa fa-2x fa-circle"></i>
                </a>
              </div>
              <div class="col-2">
                <a class="text-flat" data-toggle="theme" data-theme="../assets/admin/css/themes/flat.min-5.5.css" href="javascript:void(0)">
                  <i class="fa fa-2x fa-circle"></i>
                </a>
              </div>
              <div class="col-2">
                <a class="text-corporate" data-toggle="theme" data-theme="../assets/admin/css/themes/corporate.min-5.5.css" href="javascript:void(0)">
                  <i class="fa fa-2x fa-circle"></i>
                </a>
              </div>
              <div class="col-2">
                <a class="text-earth" data-toggle="theme" data-theme="../assets/admin/css/themes/earth.min-5.5.css" href="javascript:void(0)">
                  <i class="fa fa-2x fa-circle"></i>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="space-x-1">
      <a class="ms-switch-btn" href="<?php echo htmlspecialchars(ms_ticket_url('user'), ENT_QUOTES, 'UTF-8'); ?>" title="切换到前台用户中心">
        <i class="fa fa-shuffle"></i> 前台用户中心
      </a>
      <div class="dropdown d-inline-block">
        <button type="button" class="btn btn-sm fw-semibold px-4 py-2" style="position:relative; overflow:hidden; background: rgba(255,255,255,0.15); backdrop-filter: blur(12px); border-radius: 50px; border: 1.5px solid rgba(255,165,2,0.4); color: #ffa502; box-shadow: 0 4px 15px rgba(255,165,2,0.2);" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          <span class="position-relative z-10"><?php echo htmlspecialchars(isset($adminData['adminUser']) ? $adminData['adminUser'] : ''); ?></span>
          <i class="fa fa-angle-down ms-1 position-relative z-10"></i>
          <span class="position-absolute top-0 left-0 w-100 h-100" style="background: linear-gradient(90deg, transparent, rgba(255,255,255,0.5), transparent); animation: shine 2.5s infinite;"></span>
        </button>
        <style>
        @keyframes shine {
          0% { transform: translateX(-100%); }
          100% { transform: translateX(100%); }
        }
        </style>
        <style>
        @keyframes wave {
          0% { transform: translateX(0); }
          100% { transform: translateX(-50%); }
        }
        </style>
        <div class="dropdown-menu dropdown-menu-md dropdown-menu-end p-0" aria-labelledby="page-header-user-dropdown">
          <div class="px-2 py-3 bg-body-light rounded-top">
            <h5 class="h6 text-center mb-0">
              <?=$_SESSION['adminUser']?>
            </h5>
          </div>
          <div class="p-2">
            <a class="dropdown-item d-flex align-items-center justify-content-between space-x-1" href="edit_pwd.php">
              <span>修改密码</span>
              <i class="fa fa-fw fa-user opacity-25"></i>
            </a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item d-flex align-items-center justify-content-between space-x-1" href="set.php" data-toggle="layout" data-action="side_overlay_toggle">
              <span>网站配置</span>
              <i class="fa fa-fw fa-wrench opacity-25"></i>
            </a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item d-flex align-items-center justify-content-between space-x-1" href="./login.php?logout">
              <span>退出登入</span>
              <i class="fa fa-fw fa-sign-out-alt opacity-25"></i>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div id="page-header-loader" class="overlay-header bg-primary">
    <div class="content-header">
      <div class="w-100 text-center">
        <i class="far fa-sun fa-spin text-white"></i>
      </div>
    </div>
  </div>
</header>