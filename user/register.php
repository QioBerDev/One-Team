<?php
/**
 * 注册（OneTool 原版分栏，首页按钮直接进这里）
 */
require dirname(__DIR__) . '/Common/Core_brain.php';
require dirname(__DIR__) . '/Common/Pay.php';
require dirname(__DIR__) . '/Common/Schema.php';
require dirname(__DIR__) . '/Common/User.php';
require dirname(__DIR__) . '/Common/AuthForms.php';
require_once dirname(__DIR__) . '/Common/OtConfig.php';
require dirname(__DIR__) . '/Common/Agent.php';
require __DIR__ . '/_layout.php';

pay_schema_init($DB);
agent_remember_ref();

if (!ot_on('Auth_Register_Open')) {
    auth_head('注册已关闭', '暂不开放新用户注册', 'register');
    echo '<div class="alert alert-warning d-flex align-items-center" role="alert">'
       . '<i class="fa fa-circle-exclamation me-2"></i><div>管理员暂时关闭了新用户注册，如有需要请联系客服。</div></div>';
    echo '<div class="mt-4"><a class="btn btn-alt-primary" href="/user/login.php">返回登录</a></div>';
    auth_foot();
    exit;
}

if (user_current() !== null) {
    header('Location: /user/index.php');
    exit;
}

$err  = '';
$good = '';
$val  = array('username' => '', 'email' => '', 'qq' => '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $val['username'] = isset($_POST['username']) ? trim((string)$_POST['username']) : '';
    $val['email']    = isset($_POST['email']) ? trim((string)$_POST['email']) : '';
    $val['qq']       = isset($_POST['qq']) ? preg_replace('/\D/', '', (string)$_POST['qq']) : '';
    $pass            = isset($_POST['password']) ? (string)$_POST['password'] : '';
    $pass2           = isset($_POST['password2']) ? (string)$_POST['password2'] : '';

    /* 后台开关：图形验证码（一次性） / 用户条款 */
    $capErr = '';
    if (ot_on('Auth_Reg_Captcha')) {
        $cap = isset($_POST['captcha']) ? trim((string)$_POST['captcha']) : '';
        $okCap = $cap !== ''
            && isset($_SESSION['ot_captcha'])
            && (string)$_SESSION['ot_captcha'] === $cap
            && (time() - (int)(isset($_SESSION['ot_captcha_at']) ? $_SESSION['ot_captcha_at'] : 0)) <= 300;
        unset($_SESSION['ot_captcha'], $_SESSION['ot_captcha_at']);
        if (!$okCap) { $capErr = '验证码不对或已过期，点图片换一张'; }
    }
    $termsErr = (ot_on('Auth_Terms_Open') && !isset($_POST['agree'])) ? '请先同意用户条款' : '';

    if (!user_csrf_check()) {
        $err = '页面已过期，请重新提交';
    } elseif ($capErr !== '') {
        $err = $capErr;
    } elseif ($termsErr !== '') {
        $err = $termsErr;
    } elseif (!user_ip_throttle($DB, 'reg', 5, 600)) {
        $err = '操作太频繁，请 10 分钟后再试';
    } elseif (($m = user_check_username($val['username'])) !== '') {
        $err = $m;
    } elseif (($m = user_check_email($val['email'])) !== '') {
        $err = $m;
    } elseif (($m = user_check_password($pass)) !== '') {
        $err = $m;
    } elseif ($pass !== $pass2) {
        $err = '两次输入的密码不一致';
    } elseif ($DB->getRow("SELECT `id` FROM `nteam_user` WHERE `username`=? LIMIT 1", array($val['username']))) {
        $err = '这个用户名已经被注册了';
    } elseif ($val['email'] !== '' && $DB->getRow("SELECT `id` FROM `nteam_user` WHERE `email`=? LIMIT 1", array($val['email']))) {
        $err = '这个邮箱已经绑定过账号了';
    } else {
        $hash   = password_hash($pass, PASSWORD_DEFAULT);
        $qqNum  = ot_on('Auth_Reg_QQ') ? $val['qq'] : '';
        $invite = agent_inviter($DB);
        $stmt = $DB->query(
            "INSERT INTO `nteam_user` (`username`,`email`,`pass_hash`,`nickname`,`qq`,`invite_by`,`status`,`reg_time`,`reg_ip`)
             VALUES (?,?,?,?,?,?,1,?,?)",
            array($val['username'], $val['email'] === '' ? null : $val['email'], $hash, $val['username'], $qqNum, $invite, time(), user_ip())
        );
        if ($stmt === false) {
            /* 并发撞唯一键时 pre-check 会漏，这里兜住 */
            $err = '注册失败，用户名或邮箱可能已被占用';
        } else {
            $row = $DB->getRow("SELECT * FROM `nteam_user` WHERE `id`=? LIMIT 1", array((int)$DB->lastInsertId()));
            if ($row) {
                user_set_login($row);
                if (isset($_POST['ajax'])) {
                    header('Content-Type: application/json');
                    echo json_encode(array('ok' => true, 'redirect' => account_redirect_target()));
                    exit;
                }
                header('Location: ' . account_redirect_target());
                exit;
            }
            $good = '注册成功，请登录';
        }
    }
    /* ajax 提交：错误原样回 JSON */
    if (isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(array('ok' => false, 'err' => $err !== '' ? $err : '注册失败，请重试'));
        exit;
    }
}

$to = account_redirect_target('/user/index.php');
/* OneTool 原版 reg.html 分栏：bg-earth-op + 左栏加入社区标语 */
auth_head('创建新账户', '请填写您的详细资料', 'register');
if ($good !== '') {
    account_notice($good, 'ok');
}
ap_form_register($to, $val, $err);
ap_social_login();
auth_foot();
