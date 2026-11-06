<?php
/**
 * 清理缓存（OneTool console/head.html 的「清理缓存」对应页）
 *
 * 只读 + 浏览器侧清理：清 localStorage / sessionStorage 里的界面偏好、加版本戳刷新，
 * 不动服务端文件、不动数据库、不动登录会话（PHPSESSID 是 HttpOnly cookie）。
 */
require dirname(__DIR__) . '/Common/Core_brain.php';
require dirname(__DIR__) . '/Common/Schema.php';
require dirname(__DIR__) . '/Common/User.php';
require_once dirname(__DIR__) . '/Common/OtConfig.php';
require __DIR__ . '/_layout.php';

user_require_login('/user/cache.php');
$u = user_current();

if (!ot_on('Cache_Open')) {
    ot_feature_closed($u, '清理缓存', '管理员暂时关闭了清理缓存入口。', '');
}

$theme  = ot_user_theme();
$stamp  = '20261106g';
account_head('清理缓存', $u, '');
?>
<h2 class="content-heading">清理缓存 <small class="text-muted fs-sm fw-normal">界面显示不正常时点一下</small></h2>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="block block-rounded h-100 mb-0">
            <div class="block-header block-header-default">
                <h3 class="block-title"><i class="si si-refresh me-1 text-muted"></i> 浏览器缓存</h3>
            </div>
            <div class="block-content">
                <p class="fs-sm text-muted">
                    页面样式错乱、按钮点了没反应、换过主题还是旧配色时，清一次浏览器本地缓存即可。<br>
                    本页<strong>只清浏览器本地数据</strong>：不会删服务端文件、不会动数据库，也不会让你掉登录。
                </p>
                <div class="row g-2 mt-2">
                    <div class="col-sm-6">
                        <button type="button" class="btn btn-alt-primary w-100" onclick="otClearCache(false)">
                            <i class="fa fa-rotate me-1"></i> 清缓存并刷新
                        </button>
                    </div>
                    <div class="col-sm-6">
                        <button type="button" class="btn btn-alt-secondary w-100" onclick="otClearCache(true)">
                            <i class="fa fa-bolt me-1"></i> 强制刷新（带版本戳）
                        </button>
                    </div>
                </div>
                <div id="otCacheTip" class="alert alert-success mt-3 mb-0" style="display:none">
                    <i class="fa fa-check me-1"></i> 本地缓存已清，正在刷新页面…
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="block block-rounded h-100 mb-0">
            <div class="block-header block-header-default">
                <h3 class="block-title"><i class="fa fa-circle-info me-1 text-muted"></i> 当前环境</h3>
            </div>
            <div class="block-content block-content-full">
                <dl class="row mb-0 fs-sm">
                    <dt class="col-6 text-muted fw-normal">当前主题</dt><dd class="col-6 fw-semibold"><?php echo wk_h($theme); ?></dd>
                    <dt class="col-6 text-muted fw-normal">静态资源版本</dt><dd class="col-6 fw-semibold"><?php echo wk_h($stamp); ?></dd>
                    <dt class="col-6 text-muted fw-normal">PHP 版本</dt><dd class="col-6 fw-semibold"><?php echo wk_h(PHP_VERSION); ?></dd>
                    <dt class="col-6 text-muted fw-normal">服务器时间</dt><dd class="col-6 fw-semibold"><?php echo date('Y-m-d H:i'); ?></dd>
                    <dt class="col-6 text-muted fw-normal">浏览器</dt><dd class="col-6 text-truncate"><?php echo wk_h(mb_substr((string)(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''), 0, 40)); ?></dd>
                </dl>
                <hr class="my-3">
                <a class="btn btn-sm btn-alt-secondary w-100" href="/user/index.php">
                    <i class="fa fa-house-user me-1"></i> 返回用户中心
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function otClearCache(hard) {
    var tip = document.getElementById('otCacheTip');
    try {
        /* 只清界面偏好，保留带 one_/ot_ 前缀以外的第三方数据 */
        if (window.localStorage) {
            var drop = [];
            for (var i = 0; i < localStorage.length; i++) {
                var k = localStorage.key(i);
                if (/^(one_|ot_|codebase|theme|sidebar)/i.test(k)) { drop.push(k); }
            }
            for (var j = 0; j < drop.length; j++) { localStorage.removeItem(drop[j]); }
        }
        if (window.sessionStorage) { sessionStorage.clear(); }
    } catch (e) {}
    if (tip) { tip.style.display = 'block'; }
    var url = '/user/index.php';
    if (hard) { url += (url.indexOf('?') < 0 ? '?' : '&') + '_r=' + Date.now(); }
    setTimeout(function () { window.location.href = url; }, 350);
}
</script>
<?php account_foot(); ?>
