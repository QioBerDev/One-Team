<?php
/**
 * 前台用户会话 · 公共库
 *
 * 依赖 Core_brain.php 里已经 session_start() 过。
 * 密码一律 password_hash()，登录成功必须 session_regenerate_id(true)。
 */

if (!defined('IN_CRONLITE')) {
    exit('Access denied');
}
require_once __DIR__ . '/AvatarFrames.php';

function user_ip()
{
    return isset($_SERVER['REMOTE_ADDR']) ? substr($_SERVER['REMOTE_ADDR'], 0, 45) : '';
}

function user_session_key()
{
    return 'one_uid';
}

/** 当前登录用户，未登录返回 null */
function user_current()
{
    global $DB;
    static $cache = false;
    if ($cache !== false) {
        return $cache;
    }
    $uid = isset($_SESSION[user_session_key()]) ? (int)$_SESSION[user_session_key()] : 0;
    if ($uid <= 0) {
        $cache = null;
        return null;
    }
    $row = $DB->getRow("SELECT * FROM `nteam_user` WHERE `id`=? LIMIT 1", array($uid));
    if (!$row || (int)$row['status'] !== 1) {
        unset($_SESSION[user_session_key()]);
        $cache = null;
        return null;
    }
    $cache = $row;
    return $row;
}

/** 登录态写入。session id 必须换，防固定攻击。 */
function user_set_login(array $row)
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
    $_SESSION[user_session_key()] = (int)$row['id'];
    $_SESSION['one_uname'] = $row['username'];
}

function user_logout()
{
    unset($_SESSION[user_session_key()], $_SESSION['one_uname']);
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

/** 未登录直接踢到登录页，带回来地址 */
function user_require_login($base = '')
{
    if (user_current() !== null) {
        return;
    }
    $to = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/user/index.php';
    header('Location: ' . $base . 'user/login.php?redirect=' . urlencode($to));
    exit;
}

function user_csrf_token()
{
    if (empty($_SESSION['one_csrf'])) {
        $_SESSION['one_csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['one_csrf'];
}

function user_csrf_field()
{
    return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(user_csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function user_csrf_check()
{
    $t = isset($_POST['_csrf']) ? (string)$_POST['_csrf'] : '';
    return $t !== '' && !empty($_SESSION['one_csrf']) && hash_equals($_SESSION['one_csrf'], $t);
}

/**
 * 登录失败计数：5 次锁 15 分钟
 * 计数写在用户行上，同账号换 IP 也拦得住；同 IP 爆破换账号靠下面的 IP 限流。
 */
function user_login_fail($DB, array $row)
{
    $n = (int)$row['fail_count'] + 1;
    $lock = $n >= 5 ? time() + 900 : 0;
    $DB->query(
        "UPDATE `nteam_user` SET `fail_count`=?, `lock_until`=? WHERE `id`=?",
        array($n >= 5 ? 0 : $n, $lock, (int)$row['id'])
    );
}

function user_login_ok($DB, array $row)
{
    $DB->query(
        "UPDATE `nteam_user` SET `fail_count`=0, `lock_until`=0, `last_time`=?, `last_ip`=? WHERE `id`=?",
        array(time(), user_ip(), (int)$row['id'])
    );
}

/**
 * 校验登录密码：普通账号只走 bcrypt；管理员镜像账号额外认后台密码。
 *
 * 后台 nteam_admin.adminPwd = md5(明文)，前台 nteam_user.pass_hash =
 * password_hash()。ms_sync_admin() 建镜像账号时故意塞随机密码防止被爆破，
 * 于是先生拿后台账号密码登前台会一直提示「账号或密码不对」。
 * 这里在 bcrypt 不通过时，只对 is_admin=1 且 nteam_admin 里存在同名账号的行
 * 做一次 md5 比对，成功就地把 pass_hash 升级成 bcrypt；之后走标准校验，
 * 后台改密码后旧哈希失效也能靠这条自动追平。
 * 失败仍然由调用方计 fail_count / lock_until，不降低防爆破强度。
 */
function user_pass_ok($DB, array $row, $pass)
{
    $pass = (string)$pass;
    $h = isset($row['pass_hash']) ? (string)$row['pass_hash'] : '';
    if ($h !== '' && password_verify($pass, $h)) {
        return true;
    }
    if (empty($row['is_admin']) || (int)$row['is_admin'] !== 1) {
        return false;
    }
    $name = isset($row['username']) ? (string)$row['username'] : '';
    if ($name === '') {
        return false;
    }
    $a = $DB->getRow("SELECT `adminPwd` FROM `nteam_admin` WHERE `adminUser`=? LIMIT 1", array($name));
    if (!$a || !isset($a['adminPwd'])) {
        return false;
    }
    $stored = strtolower(trim((string)$a['adminPwd']));
    if (!preg_match('/^[a-f0-9]{32}$/', $stored) || !hash_equals($stored, md5($pass))) {
        return false;
    }
    /* 后台密码已核实：镜像账号哈希升级成前台标准 bcrypt，并清掉试错计数 */
    $DB->query(
        "UPDATE `nteam_user` SET `pass_hash`=?, `fail_count`=0, `lock_until`=0 WHERE `id`=?",
        array(password_hash($pass, PASSWORD_DEFAULT), (int)$row['id'])
    );
    return true;
}

/** 用户名规则：3-20 位，字母数字下划线，必须字母开头 */
function user_check_username($s)
{
    if (!preg_match('/^[A-Za-z][A-Za-z0-9_]{2,19}$/', (string)$s)) {
        return '用户名要 3-20 位，字母开头，只能字母数字下划线';
    }
    return '';
}

function user_check_password($s)
{
    $s = (string)$s;
    if (strlen($s) < 8 || strlen($s) > 64) {
        return '密码长度要在 8-64 位之间';
    }
    if (!preg_match('/[A-Za-z]/', $s) || !preg_match('/\d/', $s)) {
        return '密码要同时含字母和数字';
    }
    return '';
}

function user_check_email($s)
{
    $s = trim((string)$s);
    if ($s === '') {
        return '';
    }
    return filter_var($s, FILTER_VALIDATE_EMAIL) ? '' : '邮箱格式不对';
}

/** IP 维度：10 分钟内最多 5 次注册/登录尝试，防脚本刷 */
function user_ip_throttle($DB, $bucket, $limit = 5, $window = 600)
{
    $bucket = substr($bucket, 0, 12);
    $n = (int)$DB->getColumn(
        "SELECT COUNT(*) FROM `nteam_pay_log` WHERE `direction`=? AND `ip`=? AND `created_at`>?",
        array($bucket, user_ip(), time() - $window)
    );
    if ($n >= $limit) {
        return false;
    }
    epay_log($DB, $bucket, array('t' => time()), 1);
    return true;
}

/** 首字，给头像占位用；没 mbstring 也不能炸 */
function user_initial($s)
{
    $s = trim((string)$s);
    if ($s === '') {
        return '?';
    }
    return function_exists('mb_substr') ? mb_substr($s, 0, 1, 'UTF-8') : strtoupper(substr($s, 0, 1));
}

/**
 * 第三方头像 URL 白名单：只收 http(s) / 协议相对地址，挡 javascript: / data: 注入。
 * 返回空串表示不可用。
 */
function user_face_url($url)
{
    $url = trim((string)$url);
    if ($url === '' || !preg_match('#^(https?://|//)#i', $url)) {
        return '';
    }
    return function_exists('mb_substr') ? mb_substr($url, 0, 400) : substr($url, 0, 400);
}

/**
 * 前台头像来源（用户中心所有位置统一走这里，保证「同步」）
 *
 * 优先级：手填 QQ 号 > 第三方登录同步回来的 faceimg > 首字母占位。
 * QQ 号走 qlogo 实时拼，用户改了 QQ 号头像立刻跟着变；
 * QQ 互联 / 彩虹聚合登录拿不到 QQ 号，就用回调带回的 faceimg。
 */
/**
 * 站内上传头像路径白名单：只认 uploads/avatar/ 下的图片文件名，
 * 禁止 http(s) 外链、目录穿越与任意后缀（防把用户可控字符串塞进 img src）。
 */
function user_avatar_upload_url($v)
{
    $v = trim((string)$v);
    if ($v === '' || !preg_match('#^uploads/avatar/u\d+_[a-f0-9]{8,32}\.(jpg|jpeg|png|gif|webp)$#i', $v)) {
        return '';
    }
    return '/' . $v;
}

function user_avatar_url($u)
{
    if (!is_array($u)) {
        $u = array();
    }
    /* 优先级：用户自己上传 > QQ 号 > 第三方登录带回 > 首字母占位。
       上传为空（恢复默认）时自动回落到 QQ 头像，不用额外开关。 */
    $up = isset($u['avatar']) ? user_avatar_upload_url($u['avatar']) : '';
    if ($up !== '') {
        return $up;
    }
    $qq = isset($u['qq']) ? trim((string)$u['qq']) : '';
    if ($qq !== '' && preg_match('/^[1-9][0-9]{4,11}$/', $qq)) {
        return 'https://q2.qlogo.cn/g?b=qq&nk=' . rawurlencode($qq) . '&s=100';
    }
    return isset($u['faceimg']) ? user_face_url($u['faceimg']) : '';
}

/** 把第三方头像写回用户表（登录 / 绑定时调用）；返回是否真的写了 */
function user_sync_face($DB, $uid, $url)
{
    $url = user_face_url($url);
    if ($url === '' || (int)$uid <= 0) {
        return false;
    }
    $DB->query("UPDATE `nteam_user` SET `faceimg`=? WHERE `id`=?", array($url, (int)$uid));
    return true;
}

/**
 * 输出头像 HTML：底层首字母占位，上层 img 绝对定位覆盖。
 * 远程头像挂了 onerror 直接 remove，自动露回首字母，不留破图。
 * $class     Codebase 尺寸类，如 'img-avatar img-avatar32'
 * $fallback  占位层配色，如 'bg-primary text-white' / 'bg-white-25 text-white'
 */
function user_avatar_html($u, $class = 'img-avatar img-avatar-thumb', $fallback = 'bg-primary text-white')
{
    if (!is_array($u)) {
        $u = array();
    }
    $name = trim((string)(!empty($u['nickname']) ? $u['nickname'] : (isset($u['username']) ? $u['username'] : '')));
    $initial = function_exists('user_initial') ? user_initial($name)
        : (function_exists('mb_substr') ? mb_substr($name, 0, 1, 'UTF-8') : strtoupper(substr($name, 0, 1)));
    $cls = htmlspecialchars($class, ENT_QUOTES, 'UTF-8');
    $fb  = htmlspecialchars($fallback, ENT_QUOTES, 'UTF-8');
    /* 直接吃 Codebase 原生 .img-avatar（自带 border-radius:50%）。
       别再自作主张加内联圆角：上一版 border-radius:inherit 从没有圆角的父层
       继承到 0，把圆形头像压成了方形，连 img-avatar-thumb 的白色光环也是方的。 */
    $placeholder = '<span class="' . $cls . ' ' . $fb . ' d-inline-flex align-items-center justify-content-center fw-bold">'
        . htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') . '</span>';
    $url = user_avatar_url($u);
    if ($url === '') {
        $inner = $placeholder;
    } else {
        /* 只输出一个原生 img，不加包层、不覆盖圆角；远程头像挂了才换成同样的圆形占位块 */
        $js = 'this.outerHTML=' . json_encode($placeholder, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $inner = '<img class="' . $cls . '" src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" '
               . 'alt="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" '
               . 'referrerpolicy="no-referrer" loading="lazy" '
               . 'onerror="' . htmlspecialchars($js, ENT_QUOTES, 'UTF-8') . '">';
    }
    /* 挂件：只在用户选了之后才包一层定位容器。没选挂件时原样返回，
       绝不引入任何额外 DOM / 内联圆角，保证上一轮修好的正圆不受影响。
       包层只做定位，不给头像加圆角、不裁切；挂件本身是透明方形镂空图，
       pointer-events:none 让它不挡住头像链接的点击。 */
    $frame = isset($u['avatar_frame']) ? user_avatar_frame_url($u['avatar_frame']) : '';
    if ($frame === '') {
        return $inner;
    }
    return '<span class="ot-avatar-wrap">' . $inner
         . '<img class="ot-avatar-frame" src="' . htmlspecialchars($frame, ENT_QUOTES, 'UTF-8') . '" '
         . 'alt="" aria-hidden="true" draggable="false">'
         . '</span>';
}
