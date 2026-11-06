<?php
/**
 * 后台 · 聚合登录 / 注册登录 板块
 *
 * 先生要求：「qq互联注册登录这些做成一个板块」——
 * QQ 互联直连、新用户注册、验证码、条款、记住我、找回密码，
 * 连同彩虹聚合登录（https://u.cccyun.cc/doc.php）的 AppID / AppKey /
 * 回调 / 渠道开关，全部集中在这一页，配置真源仍是 Common/OtConfig.php。
 */
$mod = 'admin';
include(__DIR__ . '/../Common/Core_brain.php');
require_once(__DIR__ . '/../Common/Schema.php');
require_once(__DIR__ . '/../Common/OtConfig.php');
require_once(__DIR__ . '/../Common/Oauth.php');
require_once(__DIR__ . '/_tabs.php');

site_schema_init($DB);

if ($adminData['adminRank'] != 1) {
    echo '您的账号没有权限使用此功能';
    exit;
}

$enabledRaw = (string)ot_cfg('Oa_Types');
$enabled    = array();
foreach (explode(',', $enabledRaw) as $t) {
    $t = strtolower(trim($t));
    if ($t !== '') { $enabled[$t] = 1; }
}
$channels = oa_channels();
?>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>聚合登录 / 注册登录 - <?php echo htmlspecialchars(conf('Name'), ENT_QUOTES, 'UTF-8'); ?> - 后台管理中心</title>
  <link rel="shortcut icon" href="../assets/img/favicon.png">
  <link rel="stylesheet" id="css-main" href="../assets/admin/css/codebase.min-5.5.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="../assets/admin/css/decor.css">
  <style>
    .oa-ch-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px; }
    .oa-ch-item { display: flex; align-items: center; gap: 8px; padding: 10px 12px; border: 1px solid rgba(0,0,0,.1); border-radius: 8px; cursor: pointer; background: #fff; }
    .oa-ch-item:hover { border-color: var(--oa-color, #6c757d); }
    .oa-ch-item .oa-dot { width: 28px; height: 28px; border-radius: 6px; background: var(--oa-color, #6c757d); color: #fff; display: flex; align-items: center; justify-content: center; font-size: .8rem; font-weight: 700; flex: 0 0 28px; }
    .oa-ch-item code { font-size: .72rem; color: #6c757d; }
  </style>
</head>
<body>
<div id="page-container" class="sidebar-o enable-page-overlay side-scroll page-header-modern main-content-boxed">
<?php require_once __DIR__ . '/head.php'; ?>
<main id="main-container">
<div class="content">
  <?php admin_tabs('oauth', 'oauth.php', $adminData['adminRank']); ?>

  <form onsubmit="return saveSetting(this)" method="post" name="oauth-form">
    <input type="hidden" name="Oa_Types" id="Oa_Types" value="<?php echo htmlspecialchars($enabledRaw, ENT_QUOTES, 'UTF-8'); ?>">

    <div class="block block-rounded">
      <div class="block-header block-header-default">
        <h3 class="block-title"><b>彩虹聚合登录 + 注册 / 登录开关</b></h3>
        <div class="block-options"><small class="text-muted">
          <a href="https://u.cccyun.cc/doc.php" target="_blank" rel="noopener">接口文档 <i class="fa fa-external-link-alt"></i></a>
        </small></div>
      </div>
      <div class="block-content">
        <div class="row"><?php ot_render_group('oauth', 2); ?></div>
      </div>
    </div>

    <div class="block block-rounded">
      <div class="block-header block-header-default">
        <h3 class="block-title"><b>启用的登录渠道（对应登录页品牌色按钮）</b></h3>
        <div class="block-options"><small class="text-muted">勾选后保存，前台自动出现对应按钮</small></div>
      </div>
      <div class="block-content">
        <div class="oa-ch-grid">
          <?php foreach ($channels as $code => $c) { ?>
          <label class="oa-ch-item" style="--oa-color: <?php echo htmlspecialchars($c['color'], ENT_QUOTES, 'UTF-8'); ?>">
            <input type="checkbox" class="oa-ch" data-type="<?php echo htmlspecialchars($code, ENT_QUOTES, 'UTF-8'); ?>"
                   <?php echo isset($enabled[$code]) ? 'checked' : ''; ?> onchange="syncOaTypes()">
            <span class="oa-dot">
              <?php if ($c['icon'] !== '') { ?>
              <i class="<?php echo htmlspecialchars($c['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i>
              <?php } else { echo htmlspecialchars($c['text'], ENT_QUOTES, 'UTF-8'); } ?>
            </span>
            <span class="fw-semibold"><?php echo htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8'); ?></span>
            <code><?php echo htmlspecialchars($code, ENT_QUOTES, 'UTF-8'); ?></code>
          </label>
          <?php } ?>
        </div>
        <div class="alert alert-secondary d-flex align-items-center mt-3" role="alert">
          <i class="fa fa-circle-info me-2"></i>
          <div>渠道值与文档 <code>type</code> 参数一致：<code>qq / wx / alipay / sina / baidu / huawei / xiaomi / douyin / bilibili / dingtalk</code>。微信与支付宝在网关侧会返回 <code>qrcode</code> 扫码地址，由聚合平台自行处理。总开关关闭或没填 AppID / AppKey 时，前台不会渲染任何按钮。</div>
        </div>
      </div>
    </div>

    <div class="block block-rounded">
      <div class="block-header block-header-default">
        <h3 class="block-title"><b>回调地址与流程</b></h3>
      </div>
      <div class="block-content">
        <div class="row">
          <div class="col-md-12">
            <label class="form-label"><b>当前回调地址</b></label>
            <div class="input-group">
              <input class="form-control" id="oa-cb" readonly value="<?php echo htmlspecialchars(oa_callback_base(), ENT_QUOTES, 'UTF-8'); ?>">
              <button class="btn btn-alt-secondary" type="button" onclick="var e=document.getElementById('oa-cb');e.select();document.execCommand('copy');layer.msg('已复制');">复制</button>
            </div>
            <div class="form-text">在彩虹聚合登录后台把这个地址填成回调；聚合平台回调时会追加 <code>type</code> 与 <code>code</code>，本站用 <code>state</code> 校验 + 10 分钟过期防重放。</div>
          </div>
        </div>
        <div class="alert alert-info d-flex align-items-center mt-3" role="alert">
          <i class="fa fa-route me-2"></i>
          <div>流程：登录页按钮 → <code>/user/oauth.php?act=go&amp;type=xx</code> → 网关 <code>act=login</code> 取跳转 URL → 第三方授权 → 回调 <code>act=callback</code> 拿 <code>social_uid / nickname / faceimg</code> → 查 <code>nteam_oauth</code> 绑定 → 已绑定直接登录，未绑定按上方「自动创建账号」开关建号并绑定。已登录用户发起时会把该第三方账号绑到当前账号。</div>
        </div>
      </div>
    </div>

    <div class="block block-rounded">
      <div class="block-content">
        <button type="submit" class="btn btn-primary">
          <i class="fa fa-check opacity-50 me-1"></i> <b>保存全部登录设置</b>
        </button>
        <a class="btn btn-alt-secondary ms-2" href="/user/login.php" target="_blank">预览登录页</a>
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
function syncOaTypes(){
  var out = [];
  Array.prototype.forEach.call(document.querySelectorAll('.oa-ch'), function(el){
    if (el.checked) { out.push(el.getAttribute('data-type')); }
  });
  document.getElementById('Oa_Types').value = out.join(',');
}
function saveSetting(obj){
  syncOaTypes();
  return otSave(obj, 'ajax.php?act=set');
}
syncOaTypes();
</script>
</body>
</html>
