<?php
/**
 * 找回密码：邮箱验证码重置
 *
 * 流程：填用户名/邮箱 → 发 6 位验证码到绑定邮箱 → 输验证码 + 新密码
 * 防爆破：IP 限流 + 验证码 15 分钟过期 + 只存哈希 + 一次性使用
 */
require dirname(__DIR__) . '/Common/Core_brain.php';
require dirname(__DIR__) . '/Common/Schema.php';
require dirname(__DIR__) . '/Common/User.php';
require_once dirname(__DIR__) . '/Common/OtConfig.php';
require __DIR__ . '/_layout.php';

user_reset_schema_init($DB);

if (!ot_on('Auth_Forgot_Open')) {
    auth_head('找回密码', '功能未开放');
    echo '<div class="alert alert-warning d-flex align-items-center" role="alert">'
       . '<i class="fa fa-circle-exclamation me-2"></i><div>管理员暂时关闭了找回密码，如有需要请联系客服。</div></div>';
    echo '<div class="mt-4"><a class="btn btn-alt-primary" href="/user/login.php">返回登录</a></div>';
    auth_foot();
    exit;
}

if (user_current() !== null) {
    header('Location: /user/index.php');
    exit;
}

$err  = '';
$ok   = '';
$step = isset($_GET['step']) && $_GET['step'] === '2' ? 2 : 1;
$code = '';
$val  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = isset($_POST['act']) ? (string)$_POST['act'] : '';

    if (!user_csrf_check()) {
        $err = '页面已过期，请重新提交';
    } elseif ($act === 'send') {
        /* ---- 第一步：发验证码 ---- */
        $val = isset($_POST['login']) ? trim((string)$_POST['login']) : '';
        if (!user_ip_throttle($DB, 'pwreset', 3, 900)) {
            $err = '尝试次数过多，请 15 分钟后再试';
        } elseif ($val === '') {
            $err = '请填写用户名或邮箱';
        } else {
            $row = $DB->getRow("SELECT * FROM `nteam_user` WHERE `username`=? LIMIT 1", array($val));
            if (!$row) {
                $row = $DB->getRow("SELECT * FROM `nteam_user` WHERE `email`=? LIMIT 1", array($val));
            }
            if (!$row) {
                /* 不泄露账号是否存在 */
                $ok   = '如果该账号存在且绑定了邮箱，验证码已发送，请查收邮箱';
                $step = 1;
            } elseif ((int)$row['status'] !== 1) {
                $err = '这个账号已被停用，有问题请联系客服';
            } elseif (trim((string)$row['email']) === '') {
                $err = '这个账号没绑定邮箱，无法找回密码，请联系客服处理';
            } else {
                $code = (string)random_int(100000, 999999);
                $DB->query("UPDATE `nteam_user_reset` SET `used`=1 WHERE `user_id`=? AND `used`=0", array((int)$row['id']));
                $DB->query(
                    "INSERT INTO `nteam_user_reset` (`user_id`,`code_hash`,`expire`,`create_time`)
                     VALUES (?,?,?,?)",
                    array((int)$row['id'], hash('sha256', $code), time() + 900, time())
                );
                $site = conf('Name');
                $mail = '您好，您正在找回 ' . $site . ' 的账号密码。

验证码：' . $code . '
15 分钟内有效，请勿泄露给他人。

如果这不是您本人操作，请忽略本邮件。';
                $sent = send_mail($row['email'], $site . ' · 找回密码验证码', $mail);
                if ($sent === true) {
                    $ok   = '验证码已发送到您绑定的邮箱（' . substr($row['email'], 0, 3) . '***' . substr($row['email'], strpos($row['email'], '@')) . '），15 分钟内有效';
                    $step = 2;
                    $val  = $row['username'];
                } else {
                    $err = '邮件发送失败，请稍后再试或联系客服';
                }
            }
        }
    } elseif ($act === 'reset') {
        /* ---- 第二步：验码改密 ---- */
        $val    = isset($_POST['login']) ? trim((string)$_POST['login']) : '';
        $code   = isset($_POST['code']) ? trim((string)$_POST['code']) : '';
        $pass   = isset($_POST['password']) ? (string)$_POST['password'] : '';
        $pass2  = isset($_POST['password2']) ? (string)$_POST['password2'] : '';
        $step   = 2;

        if (!user_ip_throttle($DB, 'pwreset2', 10, 900)) {
            $err = '尝试次数过多，请 15 分钟后再试';
        } elseif ($val === '' || $code === '' || $pass === '') {
            $err = '请把信息填写完整';
        } elseif (($m = user_check_password($pass)) !== '') {
            $err = $m;
        } elseif ($pass !== $pass2) {
            $err = '两次输入的新密码不一致';
        } else {
            $row = $DB->getRow("SELECT * FROM `nteam_user` WHERE `username`=? LIMIT 1", array($val));
            if (!$row) {
                $row = $DB->getRow("SELECT * FROM `nteam_user` WHERE `email`=? LIMIT 1", array($val));
            }
            $rst = $row ? $DB->getRow(
                "SELECT * FROM `nteam_user_reset` WHERE `user_id`=? AND `used`=0 AND `expire`>? ORDER BY `id` DESC LIMIT 1",
                array((int)$row['id'], time())
            ) : null;

            if (!$row || !$rst) {
                $err = '验证码不对或已过期，请重新获取';
            } elseif (!hash_equals((string)$rst['code_hash'], hash('sha256', $code))) {
                $err = '验证码不对或已过期，请重新获取';
            } else {
                $DB->query("UPDATE `nteam_user_reset` SET `used`=1 WHERE `id`=?", array((int)$rst['id']));
                $DB->query(
                    "UPDATE `nteam_user` SET `pass_hash`=?, `fail_count`=0, `lock_until`=0 WHERE `id`=?",
                    array(password_hash($pass, PASSWORD_DEFAULT), (int)$row['id'])
                );
                header('Location: /user/login.php?reset=1');
                exit;
            }
        }
    }
}

auth_head($step === 2 ? '重置密码' : '找回账户密码',
          $step === 2 ? '输入邮箱验证码与新的登录密码' : '验证码会发到账号绑定的邮箱', 'forgot');
?>
                        <form class="js-validation-remind" method="post" autocomplete="on">
                            <?php echo user_csrf_field(); ?>
                            <?php account_notice($err, 'err'); ?>
                            <?php account_notice($ok, 'ok'); ?>
                            <?php if ($step === 1) { ?>
                            <input type="hidden" name="act" value="send">
                            <div class="form-floating mb-4">
                                <input class="form-control ap-input" id="ot-fp-login" name="login" value="<?php echo wk_h($val); ?>"
                                       placeholder="输入你的用户名或邮箱" autocomplete="username" required autofocus>
                                <label class="form-label ap-label" for="ot-fp-login">用户名或邮箱</label>
                            </div>
                            <div class="mb-4">
                                <button class="btn btn-lg btn-alt-primary fw-semibold ap-btn" type="submit">发送验证码</button>
                                <div class="ap-links d-flex align-items-center justify-content-between mt-4">
                                    <a class="fs-sm fw-medium link-fx text-muted" href="/user/login.php"><i class="fa fa-arrow-left opacity-50 me-1"></i>返回登录</a>
                                    <?php if (ot_on('Auth_Register_Open')) { ?>
                                    <a class="fs-sm fw-medium link-fx text-muted" href="/user/register.php">创建账户</a>
                                    <?php } else { ?><span></span><?php } ?>
                                </div>
                            </div>
                            <?php } else { ?>
                            <input type="hidden" name="act" value="reset">
                            <div class="form-floating mb-4">
                                <input class="form-control ap-input" id="ot-fp-login2" name="login" value="<?php echo wk_h($val); ?>"
                                       placeholder="输入你的用户名或邮箱" autocomplete="username" required>
                                <label class="form-label ap-label" for="ot-fp-login2">用户名或邮箱</label>
                            </div>
                            <div class="form-floating mb-4">
                                <input class="form-control ap-input" id="ot-fp-code" name="code" value="<?php echo wk_h($code); ?>"
                                       placeholder="6 位验证码" inputmode="numeric" maxlength="6"
                                       autocomplete="one-time-code" required autofocus>
                                <label class="form-label ap-label" for="ot-fp-code">邮箱验证码</label>
                            </div>
                            <div class="form-floating mb-4">
                                <input class="form-control ap-input" id="ot-fp-pass" name="password" type="password"
                                       placeholder="至少 8 位，含字母和数字" autocomplete="new-password" required>
                                <label class="form-label ap-label" for="ot-fp-pass">新密码</label>
                            </div>
                            <div class="form-floating mb-4">
                                <input class="form-control ap-input" id="ot-fp-pass2" name="password2" type="password"
                                       placeholder="再输一次" autocomplete="new-password" required>
                                <label class="form-label ap-label" for="ot-fp-pass2">确认新密码</label>
                            </div>
                            <div class="mb-4">
                                <button class="btn btn-lg btn-alt-primary fw-semibold ap-btn" type="submit">重置密码</button>
                                <div class="ap-links d-flex align-items-center justify-content-between mt-4">
                                    <a class="fs-sm fw-medium link-fx text-muted" href="/user/forgot.php">重新获取验证码</a>
                                    <a class="fs-sm fw-medium link-fx text-muted" href="/user/login.php"><i class="fa fa-arrow-left opacity-50 me-1"></i>返回登录</a>
                                </div>
                            </div>
                            <?php } ?>
                        </form>
<?php
auth_foot();
