<?php
$mod = 'admin';
include(__DIR__ . '/../Common/Core_brain.php');
if($adminData['adminRank']== 2) {
  echo "您的账号没有权限使用此功能";
  exit;
}
require_once(__DIR__ . '/../Common/Schema.php');
site_schema_init($DB);
require_once(__DIR__ . '/../Common/AuthForms.php');
require_once(__DIR__ . '/../Common/OtConfig.php');

/* Logo 预览路径归一：相对路径补前导 /，与前台页脚一致 */
function logo_preview_url($v)
{
    $v = trim((string)$v);
    if ($v === '') {
        return '/logo.webp';
    }
    if (!preg_match('#^(https?:)?//#i', $v) && $v[0] !== '/') {
        return '/' . $v;
    }
    return $v;
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
  <script async src="https://www.googletagmanager.com/gtag/js?id=G-9HQDQJJYW7"></script><script>window.dataLayer = window.dataLayer || [];function gtag(){dataLayer.push(arguments);}gtag('js', new Date());gtag('config', 'G-9HQDQJJYW7');</script>
    <link rel="stylesheet" href="../assets/admin/css/decor.css">
  <style>
  /* 后台图片预览统一限高：不引入前台 auth-panel.css，避免 img 按原尺寸撑爆表单 */
  .ot-hero-preview-wrap{position:relative;width:100%;height:150px;overflow:hidden;
    border:1px solid rgba(0,0,0,.12);border-radius:10px;background:#f4f6f9;
    display:flex;align-items:center;justify-content:center}
  .ot-hero-preview-wrap img{display:block;max-width:100%;max-height:100%;
    width:auto;height:auto;object-fit:contain}
  .ot-hero-preview-wrap .ot-hero-preview-tip{position:absolute;right:8px;bottom:6px;
    font-size:11px;color:#6c757d;background:rgba(255,255,255,.85);padding:2px 6px;border-radius:4px}
  </style>

</head>
<body>
<div id="page-container" class="sidebar-o enable-page-overlay side-scroll page-header-modern main-content-boxed">
<?php require_once 'head.php'; ?>
<main id="main-container">
<div class="content">
  <div class="block block-rounded">
    <div class="block-header block-header-default">
      <h3 class="block-title"><b>网站信息设置</b></h3>
    </div>
    <div class="block-content">
      <form onsubmit="return saveSetting(this)" method="post" name="edit-form">
        <div class="row">
          <div class="col-md-7">
            <div class="row mb-4">
              <div class="col-6">
                <label class="form-label" for="Name"><b>网站名称</b></label>
                <input type="text" class="form-control form-control-lg" name="Name" value="<?php echo conf('Name');?>" placeholder="请输入网站名称">
              </div>
              <div class="col-6">
                <label class="form-label" for="SiteName"><b>网站标题</b></label>
                <input type="text" class="form-control form-control-lg" name="SiteName" value="<?php echo conf('SiteName');?>" placeholder="请输入网站简称">
              </div>
            </div>
          </div>
          <div class="col-md-5">
            <div class="mb-4">
              <label class="form-label" for="Url"><b>网站域名</b></label>
              <input type="text" class="form-control form-control-lg" id="Url" name="Url" value="<?php echo conf('Url');?>" placeholder="请输入网站域名">
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-5">
            <div class="mb-4">
              <label class="form-label" for="Logo"><b>网站 Logo</b></label>
              <div class="input-group">
                <input type="text" class="form-control form-control-lg" id="Logo" name="Logo"
                       value="<?php echo htmlspecialchars(conf('Logo') ?: '/logo.webp', ENT_QUOTES, 'UTF-8');?>"
                       placeholder="/logo.webp 或上传图片"
                       oninput="document.getElementById('logo_preview').src=logoUrl(this.value)">
                <button class="btn btn-secondary" type="button" onclick="document.getElementById('up_logo').click()">上传</button>
              </div>
              <input type="file" id="up_logo" style="display:none" accept="image/*" onchange="uploadImg(this,'Logo')">
              <div class="d-flex align-items-center gap-2 mt-2">
                <img id="logo_preview" data-ot-preview="1" src="<?php echo htmlspecialchars(logo_preview_url(conf('Logo')), ENT_QUOTES, 'UTF-8');?>"
                     alt="Logo 预览" style="height:48px;border-radius:8px;border:1px solid rgba(0,0,0,.12);background:#fff;padding:2px">
                <small class="text-muted">站点 Logo（页脚品牌区展示），建议透明底 PNG / WebP；可填站内路径或完整 URL</small>
              </div>
            </div>
            <div class="mb-4">
              <label class="form-label" for="Iindex_Image"><b>首页背景图</b></label>
              <div class="input-group">
                <input type="text" class="form-control form-control-lg" id="index_image" name="index_image" value="<?php echo htmlspecialchars(conf('Index_Image'), ENT_QUOTES, 'UTF-8');?>" placeholder="请输入首页背景图Url" oninput="otImgPreview(this.value,'index_image_preview')">
                <button class="btn btn-secondary" type="button" onclick="document.getElementById('up_index_image').click()">上传</button>
              </div>
              <input type="file" id="up_index_image" style="display:none" accept="image/*" onchange="uploadImg(this,'index_image')">
              <div class="ot-hero-preview-wrap mt-2" style="height:150px">
                <img id="index_image_preview" data-ot-preview="1" src="<?php echo htmlspecialchars(logo_preview_url(conf('Index_Image')), ENT_QUOTES, 'UTF-8');?>" alt="首页背景图预览">
              </div>
            </div>
            <div class="mb-4">
              <label class="form-label" for="Member_Image"><b>成员区域背景图</b></label>
              <div class="input-group">
                <input type="text" class="form-control form-control-lg" name="Member_Image" value="<?php echo htmlspecialchars(conf('Member_Image'), ENT_QUOTES, 'UTF-8');?>" placeholder="成员展示区块的整体背景图" oninput="otImgPreview(this.value,'member_image_preview')">
                <button class="btn btn-secondary" type="button" onclick="document.getElementById('up_member_image').click()">上传</button>
              </div>
              <input type="file" id="up_member_image" style="display:none" accept="image/*" onchange="uploadImg(this,'Member_Image')">
              <div class="ot-hero-preview-wrap mt-2" style="height:150px">
                <img id="member_image_preview" data-ot-preview="1" src="<?php echo htmlspecialchars(logo_preview_url(conf('Member_Image')), ENT_QUOTES, 'UTF-8');?>" alt="成员区域背景图预览">
              </div>
            </div>
            <div class="mb-4">
              <label class="form-label" for="Mobile_Index_Image"><b>移动端首页背景图</b></label>
              <div class="input-group">
                <input type="text" class="form-control form-control-lg" name="Mobile_Index_Image" value="<?php echo htmlspecialchars(conf('Mobile_Index_Image'), ENT_QUOTES, 'UTF-8');?>" placeholder="手机端首页 Hero 背景图，建议竖版 1080×1920" oninput="otImgPreview(this.value,'mobile_index_image_preview')">
                <button class="btn btn-secondary" type="button" onclick="document.getElementById('up_mobile_index_image').click()">上传</button>
              </div>
              <input type="file" id="up_mobile_index_image" style="display:none" accept="image/*" onchange="uploadImg(this,'Mobile_Index_Image')">
              <div class="ot-hero-preview-wrap mt-2" style="height:150px">
                <img id="mobile_index_image_preview" data-ot-preview="1" src="<?php echo htmlspecialchars(logo_preview_url(conf('Mobile_Index_Image')), ENT_QUOTES, 'UTF-8');?>" alt="移动端首页背景图预览">
              </div>
            </div>
          </div>
          <div class="col-md-7">
            <div class="mb-4">
              <label class="form-label" for="Descriptison"><b>网站描述</b></label>
              <textarea class="form-control form-control-lg" rows="4" name="Descriptison" placeholder="请输入站点描述"><?php echo conf('Descriptison');?></textarea>
            </div>
            <div class="mb-4">
              <label class="form-label" for="Geetest_Open"><b>系统人机验证开关（极验 GT4）</b></label>
              <div class="space-x-2">
                <div class="form-check form-check-inline">
                  <label class="form-check-label">
                    <input class="form-check-input" type="radio" name="Geetest_Open" value="0" <?=conf('Geetest_Open')==0?"checked":""?>>禁用
                  </label>
                </div>
                <div class="form-check form-check-inline">
                  <label class="form-check-label">
                    <input class="form-check-input" type="radio" name="Geetest_Open" value="1" <?=conf('Geetest_Open')==1?"checked":""?>>启用
                  </label>
                </div>
              </div>
            </div>
            <div class="mb-4">
              <label class="form-label" for="Index_Fang"><b>防止xxs扒站JS开关</b></label>
              <div class="space-x-2">
                <div class="form-check form-check-inline">
                  <label class="form-check-label">
                    <input class="form-check-input" type="radio" name="index_fang" value="0" <?=conf('Index_Fang')==0?"checked":""?>>禁用
                  </label>
                </div>
                <div class="form-check form-check-inline">
                  <label class="form-check-label">
                    <input class="form-check-input" type="radio" name="index_fang" value="1" <?=conf('Index_Fang')==1?"checked":""?>>启用
                  </label>
                </div>
              </div>
            </div>
            <div class="mb-4">
              <label class="form-label" for="Jump"><b>QQVX访问跳出提示页</b></label>
              <div class="space-x-2">
                <div class="form-check form-check-inline">
                  <label class="form-check-label">
                    <input class="form-check-input" type="radio" name="Jump" value="0" <?=conf('Jump')==0?"checked":""?>>禁用
                  </label>
                </div>
                <div class="form-check form-check-inline">
                  <label class="form-check-label">
                    <input class="form-check-input" type="radio" name="Jump" value="1" <?=conf('Jump')==1?"checked":""?>>启用
                  </label>
                </div>
              </div>
            </div>
            <!-- 网站关键词 / ICP / 极验并入右列，避免 5+7+5 溢出换行 -->
            <div class="mb-4">
              <label class="form-label" for="Keywords"><b>网站关键词</b></label>
              <input type="text" class="form-control form-control-lg" name="Keywords" value="<?php echo conf('Keywords');?>" placeholder="请输入站点关键词">
            </div>
            <div>
              <div class="mb-4">
                <label class="form-label" for="ICP"><b>ICP备案号</b></label>
                <input type="text" class="form-control form-control-lg" id="ICP" name="ICP" value="<?php echo conf('ICP');?>" placeholder="请输入ICP备案号">
              </div>
            </div>
            <div>
              <div class="mb-4">
                <label class="form-label" for="Geetest_Id"><b>极验 Captcha ID &nbsp;&nbsp; <small>部署文档：<a href="https://docs.geetest.com/gt4/" target="_blank">极验 GT4 官方指南</a></small></b></label>
                <input type="text" class="form-control form-control-lg" id="Geetest_Id" name="Geetest_Id" value="<?php echo conf('Geetest_Id');?>" placeholder="请输入极验 Captcha ID">
              </div>
              <div class="mb-4">
                <label class="form-label" for="Geetest_Key"><b>极验 Captcha Key</b></label>
                <input type="text" class="form-control form-control-lg" id="Geetest_Key" name="Geetest_Key" value="<?php echo conf('Geetest_Key');?>" placeholder="请输入极验 Captcha Key">
              </div>
            </div>
          </div>
        </div><!-- /网站基础信息 row -->
        <div class="row">
          <div class="col-md-12">
            <div class="mb-4">
              <label class="form-label"><b>用户中心主题</b> &nbsp;<small class="text-muted">作用于 /user/ 控制台、登录、注册、找回密码页；Codebase 5.0 六套配色，保存即生效</small></label>
            </div>
          </div>
          <div class="col-md-6">
            <div class="mb-4">
              <label class="form-label" for="User_Theme"><b>默认主题</b></label>
              <select class="form-control form-control-lg" id="User_Theme" name="User_Theme">
                <?php foreach (ot_theme_list() as $k => $label) { ?>
                <option value="<?php echo htmlspecialchars($k, ENT_QUOTES, 'UTF-8'); ?>"<?php echo ot_user_theme() === $k ? ' selected' : ''; ?>><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
                <?php } ?>
              </select>
              <div class="form-text">访客首次打开时使用的配色，保存后刷新前台即生效</div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="mb-4">
              <label class="form-label"><b>主题切换开关</b></label>
              <div class="pt-2">
                <div class="form-check form-check-inline">
                  <input class="form-check-input" type="radio" name="User_Theme_Switch" id="uts1" value="1"<?php echo ot_user_theme_switch() ? ' checked' : ''; ?>>
                  <label class="form-check-label" for="uts1">显示画笔，允许访客自行切换</label>
                </div>
                <div class="form-check form-check-inline">
                  <input class="form-check-input" type="radio" name="User_Theme_Switch" id="uts0" value="0"<?php echo ot_user_theme_switch() ? '' : ' checked'; ?>>
                  <label class="form-check-label" for="uts0">隐藏画笔，锁定默认主题</label>
                </div>
              </div>
              <div class="form-text">控制台页头与登录页右上角会同步显示 / 隐藏该画笔</div>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-12">
            <div class="mb-4">
              <label class="form-label"><b>登录注册页左侧大图（OneTool 原版分栏）</b> &nbsp;<small class="text-muted">填充式铺满 PC 端左栏，可从内置图库选 / 上传 / 填 URL；注册开关、验证码、条款、QQ 互联与聚合登录已移到 <a href="oauth.php">「注册登录 / 聚合登录」</a> 板块</small></label>
            </div>
          </div>
          <?php ot_render_group('auth', 2); ?>
        </div>
        <div class="mb-4">
          <button type="submit" class="btn btn-primary">
            <i class="fa fa-check opacity-50 me-1"></i> <b>保存设置</b>
          </button>
        </div>
        </form>
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
function logoUrl(v){
  v = (v || '').trim();
  if (!v) return '/logo.webp';
  if (!/^(https?:)?\/\//i.test(v) && v.charAt(0) !== '/') v = '/' + v;
  return v;
}
/* 登录页左栏大图预览：完整显示原图（contain），上传 / 手填都即时生效 */
function otHeroPreview(v){
  var img = document.getElementById('ot_Auth_Hero_Image_preview');
  if (img) img.src = logoUrl(v);
}
/* 其余图片字段（Logo / 首页背景 / 成员背景 / 移动端背景）统一预览，
   先生要求「看看其他上传图片保存地方有没有这个问题」——一并补上即时反馈 */
function otImgPreview(v, id){
  var img = document.getElementById(id);
  if (img) img.src = logoUrl(v);
}
function checkURL(obj)
{
  var url = $(obj).val();

  if (url.indexOf(" ")>=0){
    url = url.replace(/ /g,"");
  }
  if (url.toLowerCase().indexOf("http://")<0 && url.toLowerCase().indexOf("https://")<0){
    url = "http://"+url;
  }
  if (url.slice(url.length-1)!="/"){
    url = url+"/";
  }
  $(obj).val(url);
}
function saveSetting(obj){
  return otSave(obj, 'ajax.php?act=set');
}
function saveSettings(obj){
  return otSave(obj, 'ajax.php?act=sets');
}
</script>
</body>
</html>