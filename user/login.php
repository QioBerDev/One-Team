<?php
/**
 * 登录（OneTool 原版分栏，首页按钮直接进这里）
 */
require dirname(__DIR__) . '/Common/Core_brain.php';
require dirname(__DIR__) . '/Common/Pay.php';
require dirname(__DIR__) . '/Common/Schema.php';
require dirname(__DIR__) . '/Common/User.php';
require dirname(__DIR__) . '/Common/AuthForms.php';
require_once dirname(__DIR__) . '/Common/OtConfig.php';
require __DIR__ . '/_layout.php';

pay_schema_init($DB);

$to = account_redirect_target('/user/index.php');
if (user_current() !== null) {
    header('Location: ' . $to);
    exit;
}

$err = '';
$val = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $val  = isset($_POST['login']) ? trim((string)$_POST['login']) : '';
    $pass = isset($_POST['password']) ? (string)$_POST['password'] : '';

    if (!user_csrf_check()) {
        $err = '页面已过期，请重新提交';
    } elseif (!user_ip_throttle($DB, 'login', 10, 600)) {
        $err = '尝试次数过多，请 10 分钟后再试';
    } elseif ($val === '' || $pass === '') {
        $err = '请填写账号和密码';
    } else {
        $row = $DB->getRow("SELECT * FROM `nteam_user` WHERE `username`=? LIMIT 1", array($val));
        if (!$row) {
            $row = $DB->getRow("SELECT * FROM `nteam_user` WHERE `email`=? LIMIT 1", array($val));
        }
        if (!$row) {
            /* 不告诉对方账号存不存在 */
            $err = '账号或密码不对';
        } elseif ((int)$row['lock_until'] > time()) {
            $err = '失败次数太多，账号暂时锁到 ' . date('H:i', (int)$row['lock_until']) . '，稍后再试';
        } elseif ((int)$row['status'] !== 1) {
            $err = '这个账号已被停用，有问题请联系客服';
        } elseif (!user_pass_ok($DB, $row, $pass)) {
            user_login_fail($DB, $row);
            $left = 5 - ((int)$row['fail_count'] + 1);
            $err = $left > 0 ? ('账号或密码不对，还可以试 ' . $left . ' 次') : '失败次数太多，账号锁定 15 分钟';
        } else {
            /* 老哈希需要平滑升级时在这里补 password_needs_rehash */
            if (password_needs_rehash($row['pass_hash'], PASSWORD_DEFAULT)) {
                $DB->query("UPDATE `nteam_user` SET `pass_hash`=? WHERE `id`=?",
                    array(password_hash($pass, PASSWORD_DEFAULT), (int)$row['id']));
            }
            user_login_ok($DB, $row);
            user_set_login($row);
            /* 「记住我」：把 PHPSESSID 从会话 cookie 延长到 30 天 */
            if (ot_on('Auth_Remember_Open') && isset($_POST['remember']) && (string)$_POST['remember'] === '1') {
                @setcookie(session_name(), session_id(), time() + 30 * 86400, '/', '', false, true);
            }
            if (isset($_POST['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode(array('ok' => true, 'redirect' => $to));
                exit;
            }
            header('Location: ' . $to);
            exit;
        }
    }
    /* ajax 提交：错误原样回 JSON，页面只负责展示 */
    if (isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode(array('ok' => false, 'err' => $err !== '' ? $err : '登录失败，请重试'));
        exit;
    }
}

/* OneTool 原版 login.html 分栏：左大图 hero + 右窄栏 form-floating 表单。
   先生要求「登录里面有注册功能」：右栏内放登录 / 注册两个视图，
   点「创建账户」就地切换，不用回首页、不弹下方卡片。 */
$tab = (isset($_GET['tab']) && $_GET['tab'] === 'register' && ot_on('Auth_Register_Open'))
    ? 'register' : 'login';
auth_head(
    $tab === 'register' ? '创建新账户' : '欢迎来到您的用户中心',
    $tab === 'register' ? '请填写您的详细资料' : '请登录',
    'login'
);
if (isset($_GET['reset'])) {
    account_notice('密码已重置，请用新密码登录', 'ok');
}
?>
<div class="ap-view" id="ap-view-login"<?php echo $tab === 'register' ? ' hidden' : ''; ?>>
<?php ap_form_login($to, $val, $err); ?>
</div>
<?php if (ot_on('Auth_Register_Open')) { ?>
<div class="ap-view" id="ap-view-register"<?php echo $tab !== 'register' ? ' hidden' : ''; ?>>
<?php ap_form_register($to, array('username' => '', 'email' => '', 'qq' => ''), ''); ?>
</div>
<?php } ?>
<?php ap_social_login(); ?>
<?php auth_foot();
