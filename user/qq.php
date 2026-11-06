<?php
/**
 * QQ 快捷登录（QQ Connect OAuth2）
 *
 * 后台「网站配置 → 登录注册页」里开 Auth_QQ_Open 并填 AppID / AppKey 后生效。
 * 回调地址固定为 /user/qq.php?act=callback，需要在 QQ 互联后台填同一个地址。
 *
 * 流程：authorize → token → me(openid) → get_user_info → 按 openid 找号 / 自动建号 → 登录
 * 没绑定过且后台关闭注册时，拒绝建号并给出提示。
 */
require dirname(__DIR__) . '/Common/Core_brain.php';
require dirname(__DIR__) . '/Common/Pay.php';
require dirname(__DIR__) . '/Common/Schema.php';
require dirname(__DIR__) . '/Common/User.php';
require_once dirname(__DIR__) . '/Common/OtConfig.php';
require __DIR__ . '/_layout.php';

pay_schema_init($DB);

$act = isset($_GET['act']) ? (string)$_GET['act'] : 'login';
$appId  = trim((string)ot_cfg('Auth_QQ_AppId'));
$appKey = trim((string)ot_cfg('Auth_QQ_AppKey'));

function qq_fail($title, $msg)
{
    auth_head($title, 'QQ 快捷登录');
    echo '<div class="alert alert-warning d-flex align-items-center" role="alert">'
        . '<i class="fa fa-circle-exclamation me-2"></i><div>' . ap_h($msg) . '</div></div>';
    echo '<div class="mt-4"><a class="btn btn-alt-primary" href="/user/login.php">返回登录</a></div>';
    auth_foot();
    exit;
}

function qq_get($url)
{
    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 12,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_USERAGENT      => 'OneTeam-QQConnect/1.0',
    ));
    $body = curl_exec($ch);
    curl_close($ch);
    return $body === false ? '' : (string)$body;
}

function qq_callback_url()
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        ? 'https' : 'http';
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    return $scheme . '://' . $host . '/user/qq.php?act=callback';
}

if (!ot_on('Auth_QQ_Open') || $appId === '' || $appKey === '') {
    qq_fail('功能未开放', '管理员还没开启 QQ 快捷登录，或没填 AppID / AppKey。');
}

if ($act === 'login') {
    if (user_current() !== null) {
        header('Location: /user/index.php');
        exit;
    }
    $state = bin2hex(random_bytes(12));
    $_SESSION['ot_qq_state'] = $state;
    $url = 'https://graph.qq.com/oauth2.0/authorize?' . http_build_query(array(
        'response_type' => 'code',
        'client_id'     => $appId,
        'redirect_uri'  => qq_callback_url(),
        'state'         => $state,
        'scope'         => 'get_user_info',
    ));
    header('Location: ' . $url);
    exit;
}

if ($act !== 'callback') {
    qq_fail('请求不对', '这个地址只处理 QQ 登录回调。');
}

/* ---- 回调 ---- */
$state = isset($_GET['state']) ? (string)$_GET['state'] : '';
$code  = isset($_GET['code']) ? (string)$_GET['code'] : '';
if ($state === '' || !isset($_SESSION['ot_qq_state']) || !hash_equals((string)$_SESSION['ot_qq_state'], $state)) {
    qq_fail('登录已过期', 'state 校验没过，请重新点一次 QQ登录。');
}
unset($_SESSION['ot_qq_state']);
if ($code === '') {
    qq_fail('登录被取消', '没有拿到授权 code，可能是取消了授权。');
}

$cb = qq_callback_url();
$tokenRaw = qq_get('https://graph.qq.com/oauth2.0/token?' . http_build_query(array(
    'grant_type'    => 'authorization_code',
    'client_id'     => $appId,
    'client_secret' => $appKey,
    'code'          => $code,
    'redirect_uri'  => $cb,
)));
parse_str($tokenRaw, $tok);
$access = isset($tok['access_token']) ? (string)$tok['access_token'] : '';
if ($access === '') {
    qq_fail('授权失败', 'QQ 没返回 access_token：' . mb_substr($tokenRaw, 0, 160));
}

$meRaw = qq_get('https://graph.qq.com/oauth2.0/me?access_token=' . urlencode($access));
if (!preg_match('/"openid"\s*:\s*"([A-Za-z0-9_\-]+)"/', $meRaw, $m)) {
    qq_fail('授权失败', '没解析到 openid：' . mb_substr($meRaw, 0, 160));
}
$openid = $m[1];

$infoRaw = qq_get('https://graph.qq.com/user/get_user_info?' . http_build_query(array(
    'access_token'       => $access,
    'oauth_consumer_key' => $appId,
    'openid'             => $openid,
)));
$info = json_decode($infoRaw, true);
$nick = (is_array($info) && isset($info['nickname'])) ? trim((string)$info['nickname']) : '';
/* QQ 互联不返回 QQ 号，但会带头像：按清晰度优先级取一个同步回用户表 */
$face = '';
if (is_array($info)) {
    foreach (array('figureurl_qq_2', 'figureurl_2', 'figureurl_qq_1', 'figureurl_1', 'figureurl') as $fk) {
        if (!empty($info[$fk]) && is_string($info[$fk])) {
            $face = user_face_url((string)$info[$fk]);
            break;
        }
    }
}

$row = $DB->getRow("SELECT * FROM `nteam_user` WHERE `qq_openid`=? LIMIT 1", array($openid));
if (!$row) {
    if (!ot_on('Auth_Register_Open')) {
        qq_fail('注册已关闭', '这个 QQ 还没绑定账号，而站点当前不开放新用户注册。');
    }
    /* 自动建号：登录名用 openid 哈希，密码随机，只允许 QQ 登录 */
    $uname = 'qq' . substr(md5($openid), 0, 10);
    if ($DB->getRow("SELECT `id` FROM `nteam_user` WHERE `username`=? LIMIT 1", array($uname))) {
        $uname = 'qq' . substr(md5($openid . time()), 0, 10);
    }
    $hash = password_hash(bin2hex(random_bytes(18)), PASSWORD_DEFAULT);
    $ins = $DB->query(
        "INSERT INTO `nteam_user` (`username`,`email`,`pass_hash`,`nickname`,`faceimg`,`qq_openid`,`status`,`reg_time`,`reg_ip`)
         VALUES (?,?,?,?,?,?,1,?,?)",
        array($uname, null, $hash, $nick !== '' ? mb_substr($nick, 0, 32, 'UTF-8') : $uname, $face, $openid, time(), user_ip())
    );
    if ($ins === false) {
        qq_fail('建号失败', '写入用户表失败，请稍后再试或联系客服。');
    }
    $row = $DB->getRow("SELECT * FROM `nteam_user` WHERE `qq_openid`=? LIMIT 1", array($openid));
    if (!$row) {
        qq_fail('建号失败', '账号没写进去，请稍后再试。');
    }
}

if ((int)$row['status'] !== 1) {
    qq_fail('账号已停用', '这个账号已被停用，有问题请联系客服。');
}
user_login_ok($DB, $row);
/* 每次 QQ 登录都刷新一次头像，第三方换了头像前台跟着变 */
if ($face !== '') {
    user_sync_face($DB, (int)$row['id'], $face);
}
user_set_login($row);
header('Location: /user/index.php');
exit;
