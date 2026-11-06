<?php
/**
 * 彩虹聚合登录 · 前台入口
 *
 * ?act=go&type=qq        → 向网关取第三方跳转地址并 302
 * ?act=callback&code=..  → 校验 state、换 social_uid、绑定 / 建号 / 登录
 *
 * 已登录用户访问 go 时，回调会把该第三方账号绑定到当前账号（账号绑定）。
 */
require dirname(__DIR__) . '/Common/Core_brain.php';
require dirname(__DIR__) . '/Common/Schema.php';
require dirname(__DIR__) . '/Common/User.php';
require dirname(__DIR__) . '/Common/AuthForms.php';
require_once dirname(__DIR__) . '/Common/OtConfig.php';
require_once dirname(__DIR__) . '/Common/Oauth.php';
require __DIR__ . '/_layout.php';

oa_schema_init($DB);

function oa_page_fail($title, $msg)
{
    auth_head($title, '第三方登录', 'login');
    echo '<div class="alert alert-danger d-flex align-items-center" role="alert">'
       . '<i class="fa fa-circle-exclamation me-2"></i><div>' . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . '</div></div>';
    echo '<div class="mt-4"><a class="btn btn-alt-primary w-100" href="/user/login.php">返回登录</a></div>';
    auth_foot();
    exit;
}

$act  = isset($_GET['act']) ? (string)$_GET['act'] : '';
$all  = oa_channels();
$open = oa_enabled_types();

/* ---------------- 发起登录 ---------------- */
if ($act === 'go') {
    $type = isset($_GET['type']) ? strtolower(trim((string)$_GET['type'])) : '';
    if (!in_array($type, $open, true)) {
        oa_page_fail('登录方式未开放', '这个第三方登录方式当前没有启用，请联系站点管理员。');
    }
    $r = oa_login_target($type);
    if (isset($r['err'])) {
        oa_page_fail('登录失败', $r['err']);
    }
    header('Location: ' . $r['url']);
    exit;
}

/* ---------------- 回调 ---------------- */
if ($act === 'callback') {
    list($type, $stateErr) = oa_consume_state();
    if ($stateErr !== '') {
        oa_page_fail('登录失败', $stateErr);
    }
    $code = isset($_GET['code']) ? (string)$_GET['code'] : '';
    if ($code === '') {
        oa_page_fail('登录失败', '回调里没有授权 code，请重新发起登录。');
    }
    $info = oa_fetch_user($type, $code);
    if (isset($info['err'])) {
        oa_page_fail('登录失败', $info['err']);
    }

    $bind = oa_find_binding($DB, $type, $info['social_uid']);

    /* 已登录 → 把这个第三方账号绑到当前账号 */
    $me = user_current();
    if ($me) {
        if ($bind && (int)$bind['user_id'] !== (int)$me['id']) {
            oa_page_fail('绑定失败', '这个' . $all[$type]['name'] . '账号已经绑定到别的站内账号了。');
        }
        oa_bind($DB, (int)$me['id'], $info);
        header('Location: /user/index.php?bound=' . rawurlencode($type));
        exit;
    }

    if ($bind) {
        $r = oa_login_user($DB, (int)$bind['user_id']);
        if (isset($r['err'])) {
            oa_page_fail('登录失败', $r['err']);
        }
        oa_bind($DB, (int)$bind['user_id'], $info);
        header('Location: /user/index.php');
        exit;
    }

    /* 未绑定：按后台开关决定是否自动建号 */
    if (!ot_on('Auth_Register_Open') || !ot_on('Oa_Auto_Reg')) {
        oa_page_fail('无法登录', '这个' . $all[$type]['name'] . '账号还没绑定站内账号，而站点当前不开放自动注册。请先用账号密码登录，再到用户中心绑定。');
    }
    $uid = oa_create_user($DB, $info);
    if ($uid <= 0) {
        oa_page_fail('登录失败', '自动创建账号失败，请稍后重试或联系管理员。');
    }
    oa_bind($DB, $uid, $info);
    $r = oa_login_user($DB, $uid);
    if (isset($r['err'])) {
        oa_page_fail('登录失败', $r['err']);
    }
    header('Location: /user/index.php?from=' . rawurlencode($type));
    exit;
}

/* 其它 act 一律回登录页 */
header('Location: /user/login.php');
exit;
