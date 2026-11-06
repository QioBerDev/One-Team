<?php
/**
 * 前后台模式切换（唯一入口）
 *
 *   /switch.php?to=user&t=xxx   后台 → 前台用户中心
 *   /switch.php?to=admin&t=xxx  前台用户中心 → 管理员操作台
 *
 * 两边会话互不覆盖：
 *   后台 $_SESSION['adminUser']  前台 $_SESSION['one_uid']
 * 切换时只补齐目标端会话，来源端会话保留，随时可以切回来。
 * 一次性 ticket 防跨站诱导与刷新重复切；普通用户 (is_admin=0) 永远进不了后台。
 */
$mod = 'switch';
$notLogin = true;
require __DIR__ . '/Common/Core_brain.php';
require_once __DIR__ . '/Common/User.php';
require_once __DIR__ . '/Common/ModeSwitch.php';

$dir = isset($_GET['to']) ? (string)$_GET['to'] : '';
$t   = isset($_GET['t']) ? (string)$_GET['t'] : '';

function ms_fail($msg)
{
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="zh-CN"><head><meta charset="utf-8"><title>切换未完成</title>'
       . '<style>body{font:16px/1.7 -apple-system,"Segoe UI","Microsoft YaHei",sans-serif;'
       . 'background:#f4f6f9;color:#334155;display:flex;align-items:center;justify-content:center;'
       . 'min-height:100vh;margin:0}.box{background:#fff;border-radius:14px;padding:32px 40px;'
       . 'box-shadow:0 12px 40px rgba(15,23,42,.12);text-align:center;max-width:420px}'
       . 'h1{font-size:19px;margin:0 0 12px}a{display:inline-block;margin-top:18px;padding:9px 22px;'
       . 'border-radius:8px;background:#3b82f6;color:#fff;text-decoration:none;font-size:14px}</style></head>'
       . '<body><div class="box"><h1>' . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . '</h1>'
       . '<div style="color:#64748b;font-size:14px">链接可能已失效，请返回原页面重新点击切换按钮。</div>'
       . '<a href="/index.php">返回官网</a></div></body></html>';
    exit;
}

if (($dir !== 'user' && $dir !== 'admin') || !ms_ticket_check($dir, $t)) {
    ms_fail('切换链接已失效');
}

if ($dir === 'user') {
    /* 后台 → 前台：必须真的登录着后台 */
    $name = ms_admin_name();
    if ($name === '' || !$DB->getRow("SELECT `id` FROM `nteam_admin` WHERE `adminUser`=? LIMIT 1", array($name))) {
        ms_fail('后台登录状态已失效');
    }
    $u = ms_admin_user($DB, $name);
    if (!$u) {
        ms_fail('管理员账号尚未同步到用户中心');
    }
    ms_ticket_burn('user');
    user_set_login($u);              /* 写 one_uid + one_uname，session id 轮换 */
    header('Location: /user/index.php');
    exit;
}

/* 前台 → 后台：必须是管理员账号，普通用户直接拒绝 */
$u = user_current();
if (!ms_is_admin($u)) {
    ms_fail('当前账号不是管理员，无法进入管理员操作台');
}
ms_ticket_burn('admin');
/* 补齐后台会话；即使 nteam_admin 没有同名行，Core_brain 的 is_admin 分支也会放行 */
$_SESSION['adminUser'] = (string)$u['username'];
$_SESSION['adminQq']   = isset($u['qq']) ? (string)$u['qq'] : '';
header('Location: /Admin/index.php');
exit;
