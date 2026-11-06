<?php
/**
 * 彩虹聚合登录 · 业务层
 *
 * 接口文档：https://u.cccyun.cc/doc.php
 *   Step1 act=login    → 拿第三方跳转 url（微信/支付宝另有 qrcode）
 *   Step3 回调带上 type + code
 *   Step4 act=callback → 拿 social_uid / nickname / faceimg / access_token
 *   act=query          → 按 social_uid 复查用户信息
 *
 * social_uid 是识别用户的唯一字段，本站用 nteam_oauth 表做 (type, social_uid)
 * 与站内账号的绑定。state 校验 + 10 分钟过期 + 一次性，防 CSRF / 重放。
 *
 * 只做「登录 / 绑定」，不下载、不写本地文件。所有配置读 Common/OtConfig.php。
 */

if (!defined('IN_CRONLITE')) {
    exit('Access denied');
}
require_once __DIR__ . '/OtConfig.php';

/** 聚合登录网关 */
function oa_gateway()
{
    return 'https://u.cccyun.cc/connect.php';
}

/** GET 一个 JSON 接口：curl 优先，file_get_contents 兜底 */
function oa_http_json($url)
{
    $raw = '';
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 12,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT      => 'OneTeam-OAuth/1.0',
        ));
        $raw = curl_exec($ch);
        curl_close($ch);
    }
    if (!is_string($raw) || $raw === '') {
        $ctx = stream_context_create(array(
            'http' => array('timeout' => 12, 'header' => "User-Agent: OneTeam-OAuth/1.0\r\n"),
        ));
        $raw = @file_get_contents($url, false, $ctx);
    }
    if (!is_string($raw) || $raw === '') {
        return array();
    }
    $j = json_decode($raw, true);
    return is_array($j) ? $j : array();
}

/** 回调基址：后台填了就用填的，留空按当前域名自动拼 */
function oa_callback_base()
{
    $v = trim((string)ot_cfg('Oa_Callback'));
    if ($v !== '') {
        return $v;
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
    return ($https ? 'https://' : 'http://') . $host . '/user/oauth.php?act=callback';
}

/** 凭据是否齐（不齐就不渲染登录按钮，也不发起请求） */
function oa_ready()
{
    return ot_on('Oa_Open')
        && trim((string)ot_cfg('Oa_AppId')) !== ''
        && trim((string)ot_cfg('Oa_AppKey')) !== '';
}

/** Step1：取第三方跳转地址，成功返回 array('url'=>...)，失败 array('err'=>...) */
function oa_login_target($type)
{
    $all = oa_channels();
    if (!isset($all[$type])) {
        return array('err' => '不支持的登录方式');
    }
    if (!oa_ready()) {
        return array('err' => '站点尚未开启聚合登录或没填 AppID / AppKey');
    }
    $state = bin2hex(random_bytes(16));
    $_SESSION['oa_state'] = $state;
    $_SESSION['oa_type']  = $type;
    $_SESSION['oa_at']    = time();

    $base = oa_callback_base();
    $sep  = strpos($base, '?') === false ? '?' : '&';
    $back = $base . $sep . 'state=' . rawurlencode($state);

    $url = oa_gateway() . '?' . http_build_query(array(
        'act'          => 'login',
        'appid'        => ot_cfg('Oa_AppId'),
        'appkey'       => ot_cfg('Oa_AppKey'),
        'type'         => $type,
        'redirect_uri' => $back,
    ));
    $j = oa_http_json($url);
    if (!isset($j['code']) || (int)$j['code'] !== 0 || empty($j['url'])) {
        return array('err' => '聚合登录网关返回：' . (isset($j['msg']) ? (string)$j['msg'] : '无响应或超时'));
    }
    return array('url' => (string)$j['url']);
}

/** 回调 state 校验：一次性 + 10 分钟过期；返回 array(type, err) */
function oa_consume_state()
{
    $state = isset($_GET['state']) ? (string)$_GET['state'] : '';
    $saved = isset($_SESSION['oa_state']) ? (string)$_SESSION['oa_state'] : '';
    $type  = isset($_SESSION['oa_type']) ? (string)$_SESSION['oa_type'] : '';
    $at    = isset($_SESSION['oa_at']) ? (int)$_SESSION['oa_at'] : 0;
    /* 一次性：不管成功失败都清掉 */
    unset($_SESSION['oa_state'], $_SESSION['oa_type'], $_SESSION['oa_at']);

    if ($state === '' || $saved === '' || !hash_equals($saved, $state)) {
        return array('', '登录状态校验失败，请重新发起登录');
    }
    if (time() - $at > 600) {
        return array('', '登录请求已超时，请重新发起登录');
    }
    $all = oa_channels();
    if (!isset($all[$type])) {
        return array('', '登录方式已失效，请重新发起登录');
    }
    return array($type, '');
}

/** Step4：用 code 换用户信息，成功返回信息数组，失败 array('err'=>...) */
function oa_fetch_user($type, $code)
{
    $url = oa_gateway() . '?' . http_build_query(array(
        'act'    => 'callback',
        'appid'  => ot_cfg('Oa_AppId'),
        'appkey' => ot_cfg('Oa_AppKey'),
        'type'   => $type,
        'code'   => $code,
    ));
    $j = oa_http_json($url);
    if (!isset($j['code']) || (int)$j['code'] !== 0) {
        return array('err' => '获取用户信息失败：' . (isset($j['msg']) ? (string)$j['msg'] : '网关无响应'));
    }
    if (empty($j['social_uid'])) {
        return array('err' => '网关没有返回 social_uid，无法识别账号');
    }
    return array(
        'social_uid'    => (string)$j['social_uid'],
        'nickname'      => isset($j['nickname']) ? (string)$j['nickname'] : '',
        'faceimg'       => isset($j['faceimg']) ? (string)$j['faceimg'] : '',
        'access_token'  => isset($j['access_token']) ? (string)$j['access_token'] : '',
        'type'          => $type,
    );
}

/** 查绑定关系 */
function oa_find_binding($DB, $type, $socialUid)
{
    return $DB->getRow(
        "SELECT * FROM `nteam_oauth` WHERE `type`=? AND `social_uid`=? LIMIT 1",
        array($type, $socialUid)
    );
}

/** 写 / 更新绑定（唯一键 (type,social_uid) 保证幂等） */
function oa_bind($DB, $userId, $info)
{
    $ok = $DB->query(
        "INSERT INTO `nteam_oauth` (`user_id`,`type`,`social_uid`,`nickname`,`faceimg`,`bind_time`)
         VALUES (?,?,?,?,?,?)
         ON DUPLICATE KEY UPDATE `user_id`=VALUES(`user_id`), `nickname`=VALUES(`nickname`),
                                 `faceimg`=VALUES(`faceimg`), `bind_time`=VALUES(`bind_time`)",
        array(
            (int)$userId,
            (string)$info['type'],
            (string)$info['social_uid'],
            mb_substr((string)$info['nickname'], 0, 60),
            mb_substr((string)$info['faceimg'], 0, 400),
            time(),
        )
    );
    /* 同步到用户表：前台用户中心侧栏 / 首页 / 资料卡都读这一列 */
    if (function_exists('user_sync_face') && !empty($info['faceimg'])) {
        user_sync_face($DB, (int)$userId, (string)$info['faceimg']);
    }
    return $ok;
}

/** 首次登录自动建号：随机密码（不可直接密码登录，可走找回密码或绑定） */
function oa_create_user($DB, $info)
{
    if (!function_exists('user_check_username')) {
        require_once __DIR__ . '/User.php';
    }
    if (!function_exists('agent_inviter')) {
        require_once __DIR__ . '/Agent.php';
    }
    $seed = $info['type'] . '|' . $info['social_uid'];
    $uname = 'oa' . substr(md5($seed), 0, 10);
    $i = 0;
    while ($DB->getRow("SELECT `id` FROM `nteam_user` WHERE `username`=? LIMIT 1", array($uname))) {
        $i++;
        $uname = 'oa' . substr(md5($seed . '|' . $i), 0, 10);
        if ($i > 20) {
            return 0;
        }
    }
    $nick = trim(preg_replace('/[^\p{L}\p{N}_\-\x{4e00}-\x{9fa5}]/u', '', (string)$info['nickname']));
    if ($nick === '') {
        $all  = oa_channels();
        $nick = isset($all[$info['type']]) ? $all[$info['type']]['name'] . '用户' : '第三方用户';
    }
    $nick = mb_substr($nick, 0, 20);
    $hash = password_hash(bin2hex(random_bytes(18)), PASSWORD_DEFAULT);
    $stmt = $DB->query(
        "INSERT INTO `nteam_user` (`username`,`email`,`pass_hash`,`nickname`,`qq`,`invite_by`,`status`,`reg_time`,`reg_ip`)
         VALUES (?,?,?,?,?,?,1,?,?)",
        array($uname, null, $hash, $nick, '', agent_inviter($DB), time(), user_ip())
    );
    if ($stmt === false) {
        return 0;
    }
    return (int)$DB->lastInsertId();
}

/** 用户登录态写入（复用 User.php，保持 session_regenerate_id 一致） */
function oa_login_user($DB, $uid)
{
    $row = $DB->getRow("SELECT * FROM `nteam_user` WHERE `id`=? LIMIT 1", array((int)$uid));
    if (!$row) {
        return array('err' => '账号不存在');
    }
    if ((int)$row['status'] !== 1) {
        return array('err' => '这个账号已被停用，有问题请联系客服');
    }
    if (!function_exists('user_set_login')) {
        require_once __DIR__ . '/User.php';
    }
    user_set_login($row);
    return array('uid' => (int)$uid);
}
