<?php
/**
 * 登录 / 注册表单（OneTool · Codebase 5.0 原生版式）
 *
 * 只有一套 UI：/user/login.php、register.php → ot_auth_page() 原版分栏右栏。
 * 首页不再弹卡片，水晶按钮直接跳这两个页（见 index.php）。
 *
 * 表单控件全部用 Codebase 5.0 原生类：form-floating / form-control /
 * form-label / form-check / btn-alt-primary / link-fx。
 * 每一个可选功能都由后台控制器 Common/OtConfig.php 的开关决定：
 *   记住我 / QQ 快捷登录 / 开放注册 / 找回密码 / 条款 / 注册QQ字段 / 图形验证码
 *
 * 表单普通 POST 回退，JS 存在时走 ajax（auth-panel.js）。
 */

if (!defined('IN_CRONLITE')) {
    exit('Access denied');
}
require_once __DIR__ . '/OtConfig.php';

function ap_h($s)
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/* ---------------- 主题配置（后台 Admin/set.php 可改） ---------------- */

/** 可选主题，键必须与 assets/onetool/css/themes/<key>.min-5.0.css 对应 */
function ot_theme_list()
{
    return array(
        'default'   => '默认 · Codebase 原色',
        'elegance'  => 'Elegance · 紫（推荐）',
        'pulse'     => 'Pulse · 蓝',
        'flat'      => 'Flat · 灰',
        'corporate' => 'Corporate · 靛',
        'earth'     => 'Earth · 棕',
    );
}

/** 后台配置的默认主题，非法值兜底 elegance */
function ot_user_theme()
{
    $all = ot_theme_list();
    $t   = trim((string)conf('User_Theme'));
    return isset($all[$t]) ? $t : 'elegance';
}

/** 是否允许访客自行切换（后台开关关掉后固定为主题配置） */
function ot_user_theme_switch()
{
    $v = conf('User_Theme_Switch');
    return $v === '' || (int)$v === 1;
}

/** 主题对应的 CSS href，default 交给 Codebase helper 处理 */
function ot_theme_href($name)
{
    return $name === 'default' ? 'default' : '/assets/onetool/css/themes/' . $name . '.min-5.0.css';
}

/** 可复用的主题切换画笔（登录页 / 控制台页头共用） */
function ot_theme_picker($id = 'ot-theme-drop')
{
    if (!ot_user_theme_switch()) {
        return;
    }
    ?>
    <div class="dropdown d-inline-block ot-theme-pick">
        <button type="button" class="btn btn-sm btn-alt-secondary" id="<?php echo ap_h($id); ?>"
                data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-haspopup="true" aria-expanded="false"
                title="界面颜色"><i class="fa fa-paint-brush"></i></button>
        <div class="dropdown-menu dropdown-menu-lg p-0" aria-labelledby="<?php echo ap_h($id); ?>">
            <div class="p-3 bg-body-light rounded-top"><h5 class="h6 text-center mb-0">界面颜色</h5></div>
            <div class="p-3">
                <div class="row g-0 text-center">
                    <?php foreach (ot_theme_list() as $name => $label) { ?>
                    <div class="col-4 mb-2">
                        <a class="text-<?php echo ap_h($name === 'default' ? 'default' : $name); ?> d-block"
                           data-toggle="theme" data-theme="<?php echo ap_h(ot_theme_href($name)); ?>"
                           href="javascript:void(0)" title="<?php echo ap_h($label); ?>">
                            <i class="fa fa-2x fa-circle"></i>
                            <div class="fs-xs text-muted mt-1"><?php echo ap_h(explode(' · ', $label)[0]); ?></div>
                        </a>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/* ---------------- 底部小链接（按后台开关渲染） ----------------
 * 「返回官网」已按先生要求移到右上角的 X 关闭键，这里不再重复。
 * 创建账户 / 返回登录 带 data-ap-tab：登录页内有注册视图时由 JS 就地切换，
 * 独立页没有对应视图则让链接正常跳转（无 JS 也能用）。
 * 创建账户靠左、忘记密码靠右，两端对齐。
 */

function ap_form_links($to, $mode = 'login')
{
    ?>
    <div class="ap-links d-flex align-items-center justify-content-between mt-4">
        <?php if ($mode === 'login') { ?>
            <?php if (ot_on('Auth_Register_Open')) { ?>
            <a class="fs-sm fw-medium link-fx text-muted" href="/user/register.php" data-ap-tab="register">创建账户</a>
            <?php } else { ?><span></span><?php } ?>
            <?php if (ot_on('Auth_Forgot_Open')) { ?>
            <a class="fs-sm fw-medium link-fx text-muted" href="/user/forgot.php">忘记密码?</a>
            <?php } else { ?><span></span><?php } ?>
        <?php } else { ?>
            <a class="fs-sm fw-medium link-fx text-muted" href="/user/login.php" data-ap-tab="login">返回登录</a>
            <?php if (ot_on('Auth_Forgot_Open')) { ?>
            <a class="fs-sm fw-medium link-fx text-muted" href="/user/forgot.php">找回密码</a>
            <?php } else { ?><span></span><?php } ?>
        <?php } ?>
    </div>
    <?php
}

/* ---------------- 第三方登录按钮（彩虹聚合登录 10 渠道） ----------------
 * 每个渠道一个品牌色圆角按钮，参照 https://u.cccyun.cc/doc.php 的 type 值。
 * 后台「注册登录 / 聚合登录」板块控制总开关、凭据与启用渠道。
 */
function ap_social_login()
{
    $types = oa_enabled_types();
    $all   = oa_channels();
    /* 旧版 QQ 互联直连：聚合 qq 渠道没开时仍给一个入口，保持兼容 */
    $legacyQq = ot_on('Auth_QQ_Open')
        && trim((string)ot_cfg('Auth_QQ_AppId')) !== ''
        && !in_array('qq', $types, true);
    if (!$types && !$legacyQq) {
        return;
    }
    ?>
    <div class="ap-social">
        <div class="ap-social-title"><span>其他方式登录</span></div>
        <div class="ap-social-list">
            <?php foreach ($types as $t) {
                $c = $all[$t];
                ?>
            <a class="ap-social-btn" style="--oa-color: <?php echo ap_h($c['color']); ?>"
               href="/user/oauth.php?act=go&amp;type=<?php echo ap_h($t); ?>"
               title="使用<?php echo ap_h($c['name']); ?>登录">
                <?php if ($c['icon'] !== '') { ?>
                <i class="<?php echo ap_h($c['icon']); ?>"></i>
                <?php } else { ?>
                <span class="ap-social-txt"><?php echo ap_h($c['text']); ?></span>
                <?php } ?>
                <span class="ap-social-name"><?php echo ap_h($c['name']); ?></span>
            </a>
            <?php } ?>
            <?php if ($legacyQq) { ?>
            <a class="ap-social-btn" style="--oa-color: #12b7f5" href="/user/qq.php" title="使用QQ互联登录">
                <i class="fa-brands fa-qq"></i><span class="ap-social-name">QQ互联</span>
            </a>
            <?php } ?>
        </div>
    </div>
    <?php
}

/* ---------------- 登录表单 ---------------- */

function ap_form_login($to = '/user/index.php', $val = '', $err = '')
{
    ?>
    <form class="ap-form js-validation-signin" method="post" action="/user/login.php" data-ap-ajax="1">
        <?php echo user_csrf_field(); ?>
        <input type="hidden" name="redirect" value="<?php echo ap_h($to); ?>">
        <div class="form-floating mb-4">
            <input class="form-control" id="ap-user" name="login" value="<?php echo ap_h($val); ?>"
                   placeholder="输入你的用户名或邮箱" autocomplete="username" required>
            <label class="form-label" for="ap-user">用户名或邮箱</label>
        </div>
        <div class="form-floating mb-4">
            <input class="form-control" id="ap-pass" name="password" type="password"
                   placeholder="输入你的密码" autocomplete="current-password" required>
            <label class="form-label" for="ap-pass">密码</label>
        </div>
        <?php if (ot_on('Auth_Remember_Open')) { ?>
        <div class="mb-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="ap-remember" name="remember" value="1" checked>
                <label class="form-check-label" for="ap-remember">记住我</label>
            </div>
        </div>
        <?php } ?>
        <div class="alert alert-danger ap-error" role="alert"<?php echo $err === '' ? ' hidden' : ''; ?>><?php echo ap_h($err); ?></div>
        <div class="mb-4">
            <button class="btn btn-lg btn-alt-primary fw-semibold ap-btn" type="submit">登录</button>
            <?php ap_form_links($to, 'login'); ?>
        </div>
    </form>
    <?php
}

/* ---------------- 注册表单 ---------------- */

function ap_form_register($to = '/user/index.php', $val = array(), $err = '')
{
    $username = isset($val['username']) ? (string)$val['username'] : '';
    $email    = isset($val['email']) ? (string)$val['email'] : '';
    $qq       = isset($val['qq']) ? (string)$val['qq'] : '';
    $captchaOn = ot_on('Auth_Reg_Captcha');
    ?>
    <form class="ap-form js-validation-signup" method="post" action="/user/register.php" data-ap-ajax="1">
        <?php echo user_csrf_field(); ?>
        <input type="hidden" name="redirect" value="<?php echo ap_h($to); ?>">
        <div class="form-floating mb-4">
            <input class="form-control" id="ap-reg-user" name="username" value="<?php echo ap_h($username); ?>"
                   placeholder="3-20 位，字母开头" autocomplete="username" required>
            <label class="form-label" for="ap-reg-user">用户名</label>
        </div>
        <div class="form-floating mb-4">
            <input class="form-control" id="ap-reg-email" name="email" type="email" value="<?php echo ap_h($email); ?>"
                   placeholder="you@example.com" autocomplete="email">
            <label class="form-label" for="ap-reg-email">邮箱（选填，找回密码用）</label>
        </div>
        <?php if (ot_on('Auth_Reg_QQ')) { ?>
        <div class="form-floating mb-4">
            <input class="form-control" id="ap-reg-qq" name="qq" type="number" value="<?php echo ap_h($qq); ?>"
                   placeholder="输入你的QQ" autocomplete="off">
            <label class="form-label" for="ap-reg-qq">QQ</label>
        </div>
        <?php } ?>
        <div class="form-floating mb-4">
            <input class="form-control" id="ap-reg-pass" name="password" type="password"
                   placeholder="至少 8 位，含字母和数字" autocomplete="new-password" required>
            <label class="form-label" for="ap-reg-pass">密码</label>
        </div>
        <div class="form-floating mb-4">
            <input class="form-control" id="ap-reg-pass2" name="password2" type="password"
                   placeholder="再输一次" autocomplete="new-password" required>
            <label class="form-label" for="ap-reg-pass2">确认密码</label>
        </div>
        <?php if ($captchaOn) { ?>
        <div class="row mb-4 g-2">
            <div class="col-6">
                <div class="form-floating">
                    <input class="form-control" id="ap-reg-captcha" name="captcha" placeholder="请输入计算结果"
                           inputmode="numeric" autocomplete="off" required>
                    <label class="form-label" for="ap-reg-captcha">验证码</label>
                </div>
            </div>
            <div class="col-6">
                <img class="border rounded w-100" id="ap-captcha-img" src="/user/captcha.php"
                     alt="点击刷新验证码" title="点击刷新" height="58"
                     onclick="this.src='/user/captcha.php?t='+Math.random();" style="cursor:pointer;object-fit:cover">
            </div>
        </div>
        <?php } ?>
        <?php if (ot_on('Auth_Terms_Open')) { ?>
        <div class="mb-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="ap-agree" name="agree" value="1" required>
                <label class="form-check-label" for="ap-agree">
                    我同意<a href="<?php echo ap_h(ot_cfg('Auth_Terms_Url')); ?>" target="_blank">条款</a>
                </label>
            </div>
        </div>
        <?php } ?>
        <div class="alert alert-danger ap-error" role="alert"<?php echo $err === '' ? ' hidden' : ''; ?>><?php echo ap_h($err); ?></div>
        <div class="mb-4">
            <button class="btn btn-lg btn-alt-primary fw-semibold ap-btn" type="submit">创建账户</button>
            <?php ap_form_links($to, 'register'); ?>
        </div>
    </form>
    <?php
}
