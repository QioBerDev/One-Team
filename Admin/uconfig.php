<?php
/**
 * 后台 · 用户中心运营总控
 *
 * 把前台已经做好的功能全部接上后台控制器（配置唯一真源 Common/OtConfig.php）：
 *   会员方案价格 / VIP 折扣 / SVIP 免费下载
 *   公告强提醒弹窗 / 帮助中心 FAQ / 卡密与余额入口
 *   代理中心邀请返佣 / 在线更新检测
 *
 * 登录注册页的左侧大图与开关在「网站信息设置」(set.php) 里，这里不重复。
 */
$mod = 'admin';
include(__DIR__ . '/../Common/Core_brain.php');
require_once(__DIR__ . '/../Common/Schema.php');
require_once(__DIR__ . '/../Common/OtConfig.php');
require_once(__DIR__ . '/../Common/Vip.php');

site_schema_init($DB);
pay_schema_init($DB);

if ($adminData['adminRank'] != 1) {
    echo '您的账号没有权限使用此功能';
    exit;
}
?>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>用户中心运营 - <?php echo htmlspecialchars(conf('Name'), ENT_QUOTES, 'UTF-8'); ?> - 后台管理中心</title>
  <link rel="shortcut icon" href="../assets/img/favicon.png">
  <link rel="stylesheet" id="css-main" href="../assets/admin/css/codebase.min-5.5.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="../assets/admin/css/decor.css">
</head>
<body>
<div id="page-container" class="sidebar-o enable-page-overlay side-scroll page-header-modern main-content-boxed">
<?php require_once __DIR__ . '/head.php'; ?>
<main id="main-container">
<div class="content">
  <?php require_once __DIR__ . '/_tabs.php'; admin_tabs('users', 'uconfig.php', $adminData['adminRank']); ?>
  <form onsubmit="return saveSetting(this)" method="post" name="uconfig-form">

    <div class="block block-rounded">
      <div class="block-header block-header-default">
        <h3 class="block-title"><b>会员方案与权益</b></h3>
        <div class="block-options"><small class="text-muted">改完保存，前台 /user/vip.php 与购买价格立即生效</small></div>
      </div>
      <div class="block-content">
        <div class="row"><?php ot_render_group('vip', 3); ?></div>
        <div class="alert alert-info d-flex align-items-center" role="alert">
          <i class="fa fa-circle-info me-2"></i>
          <div>
            VIP 实付价 = 源码原价 × <?php echo (int)ot_num('Vip_Discount', 0, 100); ?>%；
            SVIP <?php echo ot_on('Svip_Free') ? '当前为 <b>全站源码免费下载</b>（0 元下单自动交付）' : '当前按原价购买，可在上方开启免费下载'; ?>。
            会员到期后自动回落普通用户，价格与折扣实时读取本页配置。
          </div>
        </div>
      </div>
    </div>

    <div class="block block-rounded">
      <div class="block-header block-header-default">
        <h3 class="block-title"><b>公告 / 帮助中心 / 商店入口</b></h3>
        <div class="block-options"><small class="text-muted">对应 OneTool 42.5 的 #3 #8 与自助商店子菜单</small></div>
      </div>
      <div class="block-content">
        <div class="row"><?php ot_render_group('ops', 2); ?></div>
      </div>
    </div>

    <div class="block block-rounded">
      <div class="block-header block-header-default">
        <h3 class="block-title"><b>代理中心 / 邀请返佣</b></h3>
      </div>
      <div class="block-content">
        <div class="row"><?php ot_render_group('agent', 2); ?></div>
        <div class="alert alert-secondary d-flex align-items-center" role="alert">
          <i class="fa fa-link me-2"></i>
          <div>邀请链接格式：<code>/user/register.php?ref=用户UID</code>；好友注册后绑定邀请关系，在线充值入账时按比例返佣到余额，靠 <code>ref</code> 唯一键幂等。</div>
        </div>
      </div>
    </div>

    <div class="block block-rounded">
      <div class="block-header block-header-default">
        <h3 class="block-title"><b>在线更新检测</b></h3>
      </div>
      <div class="block-content">
        <div class="row"><?php ot_render_group('updater', 2); ?></div>
        <div class="alert alert-warning d-flex align-items-center" role="alert">
          <i class="fa fa-triangle-exclamation me-2"></i>
          <div>更新器只做<strong>只读版本检测</strong>，不会自动下载或覆盖线上文件（避免把更新接口变成攻击面）。检测结果在 <a href="updater.php">在线更新检测</a> 页查看。</div>
        </div>
      </div>
    </div>

    <div class="block block-rounded">
      <div class="block-content">
        <button type="submit" class="btn btn-primary">
          <i class="fa fa-check opacity-50 me-1"></i> <b>保存全部运营设置</b>
        </button>
        <a class="btn btn-alt-secondary ms-2" href="set.php">去网站配置改左侧大图</a>
      </div>
    </div>
  </form>
</div>
</main>
<?php require_once __DIR__ . '/foot.php'; ?>
</div>
<script src="../assets/admin/js/codebase.app.min-5.5.js"></script>
<script type="text/javascript" src="../assets/admin/js/jquery.min.js"></script>
<script src="../assets/layer/layer.js"></script>
<script>
function saveSetting(obj){
  return otSave(obj, 'ajax.php?act=set');
}
</script>
</body>
</html>
