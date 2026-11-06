<?php
/**
 * 退出登录
 */
require dirname(__DIR__) . '/Common/Core_brain.php';
require dirname(__DIR__) . '/Common/User.php';

user_logout();
header('Location: /index.php');
exit;
