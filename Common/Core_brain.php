<?php
/* 作者：CoCO —— 已去除联网授权验证/盗版上报/远程后门/自动删文件逻辑 */
error_reporting(E_ERROR | E_WARNING | E_PARSE);
ini_set('display_errors', 0);
if (defined('IN_CRONLITE')) {
    return;
}
define('IN_CRONLITE', true);
define('SYSTEM_ROOT', dirname(__FILE__) . '/');
define('ROOT', dirname(SYSTEM_ROOT) . '/');
define('config_path', dirname(__FILE__) . '/');
date_default_timezone_set('PRC');
$date = date('Y年m月d日');
include_once SYSTEM_ROOT . 'Autoloader.php';

Autoloader::register();
$isLogin = false;
$isUserLogin = false;
$mod = isset($mod) ? $mod : '';
if (is_file(SYSTEM_ROOT . '360safe/360webscan.php')) {
    require_once SYSTEM_ROOT . '360safe/360webscan.php';
}

// 数据库连接
require SYSTEM_ROOT . 'Database_Config.php';
if (!$dbconfig['user'] || !$dbconfig['pwd'] || !$dbconfig['dbname']) {
    header('Content-type:text/html;charset=utf-8');
    echo '您还未安装，请<a href="install">立即安装</a>';
    exit;
}
$DB = new \lib\PdoHelper($dbconfig);
$Admin = new \lib\Adminclass();

// 检查是否已安装
$Gets = new \lib\Gets();
if ($DB->query('SELECT 1 FROM nteam_config LIMIT 1') == false) {
    header('Content-type:text/html;charset=utf-8');
    echo '您还未安装，请<a href="install">立即安装</a>';
    exit;
}
include_once SYSTEM_ROOT . 'Core_Functions.php';
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'lifetime' => 86400,
]);
session_start();

// 管理员登录会话校验
if ($mod != 'install') {
    if (isset($_SESSION['adminUser'])) {
        $adminUser = $_SESSION['adminUser'];
        $ip = $Gets->ip();
        $city = '';
        $adminData = $DB->query("SELECT * FROM `nteam_admin` WHERE `adminUser` = '{$adminUser}'")->fetch();
        if (!empty($adminData)) {
            $isLogin = true;
        }
    }
    /* 管理员前台化（参考 OneTool）：前台用户表里 is_admin=1 的账号，
       登录用户中心后可直接「切换后台」，无需再输一次后台账号密码。
       普通账号 is_admin=0，走不到这里；后台 login.php 的密码登录保持原样。
       列还没迁移时先跳过，避免新部署白屏。 */
    if (!$isLogin && $mod == 'admin' && !empty($_SESSION['one_uid'])
        && $DB->getRow("SHOW COLUMNS FROM `nteam_user` LIKE 'is_admin'")) {
        $fu = $DB->getRow(
            "SELECT * FROM `nteam_user` WHERE `id`=? AND `is_admin`=1 AND `status`=1 LIMIT 1",
            array((int)$_SESSION['one_uid'])
        );
        if (!empty($fu)) {
            $_SESSION['adminUser'] = $fu['username'];
            $_SESSION['adminQq']   = isset($fu['qq']) ? $fu['qq'] : '';
            $adminData = array(
                'adminUser'    => $fu['username'],
                'adminQq'      => isset($fu['qq']) ? $fu['qq'] : '',
                'adminRank'    => 1,
                'adminLoginIp' => '',
                'fromUser'     => 1,
                'user_id'      => (int)$fu['id'],
            );
            $isLogin = true;
        }
    }
    if (!isset($notLogin) && $mod == 'admin' && !$isLogin) {
        header('Location:./login.php');
    }
}
