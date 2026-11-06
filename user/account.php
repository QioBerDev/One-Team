<?php
/**
 * 账户设置：昵称 / 邮箱 / 密码（OneTool console/user/profile.html 版式）
 */
require dirname(__DIR__) . '/Common/Core_brain.php';
require dirname(__DIR__) . '/Common/Pay.php';
require dirname(__DIR__) . '/Common/Schema.php';
require_once dirname(__DIR__) . '/Common/User.php';
require_once dirname(__DIR__) . '/Common/AvatarFrames.php';
require_once dirname(__DIR__) . '/Common/Wallet.php';
require __DIR__ . '/_layout.php';

pay_schema_init($DB);
user_require_login('/user/account.php');

$u   = user_current();
$uid = (int)$u['id'];
$ok  = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = isset($_POST['act']) ? (string)$_POST['act'] : '';
    /* 旧表单兼容：老的「更新资料 / 保存邮箱」两个 action 统一收敛到 profile；
       缺的字段沿用库里的当前值，浏览器缓存的旧页面提交也不会把资料清空 */
    if ($act === 'nickname' || $act === 'email') {
        $act = 'profile';
    }

    if (!user_csrf_check()) {
        $err = '页面已过期，请重新提交';
    } elseif ($act === 'profile') {
        $curNick = (string)($u['nickname'] !== '' ? $u['nickname'] : $u['username']);
        $nick = trim(isset($_POST['nickname']) ? (string)$_POST['nickname'] : $curNick);
        $qq   = trim(isset($_POST['qq']) ? (string)$_POST['qq'] : (string)$u['qq']);
        $mail = trim(isset($_POST['email']) ? (string)$_POST['email'] : (string)$u['email']);
        $len  = function_exists('mb_strlen') ? mb_strlen($nick, 'UTF-8') : strlen($nick);
        if ($nick === '' || $len > 20) {
            $err = '昵称要 1-20 个字';
        } elseif ($qq !== '' && !preg_match('/^[1-9][0-9]{4,11}$/', $qq)) {
            $err = 'QQ 号格式不对';
        } elseif (($m = user_check_email($mail)) !== '') {
            $err = $m;
        } elseif ($mail !== '' && $DB->getRow("SELECT `id` FROM `nteam_user` WHERE `email`=? AND `id`<>? LIMIT 1", array($mail, $uid))) {
            $err = '这个邮箱已经绑定到别的账号了';
        } else {
            /* 基本资料一张卡一次写完：昵称 / QQ（决定头像）/ 邮箱（找回密码）。
               邮箱留空写 NULL，避开 uk_email 唯一键，管理员镜像账号也不会撞 */
            $DB->query(
                "UPDATE `nteam_user` SET `nickname`=?, `qq`=?, `email`=? WHERE `id`=?",
                array($nick, $qq, $mail === '' ? null : $mail, $uid)
            );
            $ok = '基本资料已更新';
        }
    } elseif ($act === 'pass') {
        $old  = isset($_POST['old_password']) ? (string)$_POST['old_password'] : '';
        $new  = isset($_POST['new_password']) ? (string)$_POST['new_password'] : '';
        $new2 = isset($_POST['new_password2']) ? (string)$_POST['new_password2'] : '';
        if (!user_pass_ok($DB, $u, $old)) {
            $err = '当前密码不对';
        } elseif (($m = user_check_password($new)) !== '') {
            $err = $m;
        } elseif ($new !== $new2) {
            $err = '两次输入的新密码不一致';
        } elseif ($new === $old) {
            $err = '新密码不能跟旧密码一样';
        } else {
            $DB->query("UPDATE `nteam_user` SET `pass_hash`=? WHERE `id`=?",
                array(password_hash($new, PASSWORD_DEFAULT), $uid));
            /* 改密后旧会话全部失效，防别人拿着旧 cookie 继续用 */
            $_SESSION = array();
            session_regenerate_id(true);
            $_SESSION[user_session_key()] = $uid;
            $ok = '密码已修改，其它设备上的登录已失效';
            $u  = $DB->getRow("SELECT * FROM `nteam_user` WHERE `id`=? LIMIT 1", array($uid));
        }
    } elseif ($act === 'avatar_style') {
        /* 头像装扮：一次提交处理「上传头像 / 选挂件 / 恢复默认」。
           全部走站内普通 POST（不依赖 ajax），避开 WAF 对异步请求的 JS 挑战。 */
        $clear = isset($_POST['avatar_clear']) && (string)$_POST['avatar_clear'] === '1';
        $frameKey = isset($_POST['avatar_frame'])
            ? user_avatar_frame_key($_POST['avatar_frame'])
            : user_avatar_frame_key(isset($u['avatar_frame']) ? $u['avatar_frame'] : '');
        $newAvatar = trim((string)$u['avatar']);
        $oldAvatar = $newAvatar;

        if ($clear) {
            $newAvatar = '';
            $frameKey  = '';
        } elseif (isset($_FILES['avatar_file']) && is_array($_FILES['avatar_file'])
                  && (int)$_FILES['avatar_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $f = $_FILES['avatar_file'];
            if ((int)$f['error'] !== UPLOAD_ERR_OK) {
                $err = '上传失败（错误码 ' . (int)$f['error'] . '）';
            } elseif ((int)$f['size'] <= 0 || (int)$f['size'] > 3 * 1024 * 1024) {
                $err = '头像图片请在 3MB 以内';
            } else {
                $ext   = strtolower(pathinfo((string)$f['name'], PATHINFO_EXTENSION));
                $allow = array('jpg', 'jpeg', 'png', 'gif', 'webp');
                if (!in_array($ext, $allow, true)) {
                    $err = '只支持 JPG / PNG / GIF / WEBP 图片';
                } else {
                    $info = @getimagesize($f['tmp_name']);
                    if (!$info || empty($info['mime']) || strpos((string)$info['mime'], 'image/') !== 0) {
                        $err = '文件不是有效图片';
                    } else {
                        $dir = dirname(__DIR__) . '/uploads/avatar';
                        if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
                        /* 文件名固定格式，配合 user_avatar_upload_url() 白名单，
                           用户可控的文件名一个字符都不进库 */
                        $fname = 'u' . $uid . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
                        $dest  = $dir . '/' . $fname;
                        if (!move_uploaded_file($f['tmp_name'], $dest)) {
                            $err = '保存图片失败，请检查 uploads 目录权限';
                        } else {
                            @chmod($dest, 0644);
                            $newAvatar = 'uploads/avatar/' . $fname;
                        }
                    }
                }
            }
        }

        if ($err === '') {
            $DB->query("UPDATE `nteam_user` SET `avatar`=?, `avatar_frame`=? WHERE `id`=?",
                array($newAvatar, $frameKey, $uid));
            /* 只删白名单内的旧站内头像，绝不拿用户输入拼删除路径 */
            if ($oldAvatar !== '' && $oldAvatar !== $newAvatar && user_avatar_upload_url($oldAvatar) !== '') {
                $oldPath = dirname(__DIR__) . '/' . $oldAvatar;
                if (is_file($oldPath)) { @unlink($oldPath); }
            }
            $ok = '头像装扮已保存';
        }
    }
}

if ($ok !== '') {
    $u = $DB->getRow("SELECT * FROM `nteam_user` WHERE `id`=? LIMIT 1", array($uid));
}

account_head('账户设置', $u, 'account');
?>
<?php account_notice($err, 'err'); account_notice($ok, 'ok'); ?>

<!-- 基本资料（OneTool profile.html 一张卡：昵称 / QQ 头像 / 邮箱） -->
<div class="block block-rounded">
    <div class="block-header block-header-default">
        <h3 class="block-title"><i class="fa fa-user-circle me-1 text-muted"></i> 基本资料</h3>
    </div>
    <div class="block-content">
        <form method="post">
            <?php echo user_csrf_field(); ?>
            <input type="hidden" name="act" value="profile">
            <div class="row items-push">
                <div class="col-lg-3 text-center">
                    <div class="push text-center">
                        <?php echo user_avatar_html($u, 'img-avatar img-avatar-thumb', 'bg-primary text-white'); ?>
                    </div>
                    <dl class="ot-profile-meta text-center">
                        <dt class="text-muted fw-normal">登录名</dt><dd class="fw-semibold"><?php echo wk_h($u['username']); ?></dd>
                        <dt class="text-muted fw-normal">QQ 号</dt><dd><?php echo trim((string)$u['qq']) !== '' ? wk_h($u['qq']) : '未填写'; ?></dd>
                        <dt class="text-muted fw-normal">邮箱</dt><dd><?php echo trim((string)$u['email']) !== '' ? wk_h($u['email']) : '未绑定'; ?></dd>
                        <dt class="text-muted fw-normal">注册时间</dt><dd><?php echo date('Y-m-d H:i', (int)$u['reg_time']); ?></dd>
                        <dt class="text-muted fw-normal">上次登录</dt><dd><?php echo (int)$u['last_time'] > 0 ? date('Y-m-d H:i', (int)$u['last_time']) : '—'; ?></dd>
                    </dl>
                </div>
                <div class="col-lg-7 offset-lg-1">
                    <div class="mb-4">
                        <label class="form-label" for="nickname">昵称</label>
                        <input class="form-control form-control-lg" id="nickname" name="nickname" maxlength="20"
                               value="<?php echo wk_h($u['nickname'] !== '' ? $u['nickname'] : $u['username']); ?>">
                    </div>
                    <div class="mb-4">
                        <label class="form-label" for="qq">QQ 号 <small class="text-muted fw-normal">（未上传头像时显示 QQ 头像，选填）</small></label>
                        <input class="form-control form-control-lg" id="qq" name="qq" maxlength="12" inputmode="numeric"
                               value="<?php echo wk_h((string)$u['qq']); ?>" placeholder="例如 10000">
                    </div>
                    <div class="mb-4">
                        <label class="form-label" for="email">邮箱地址 <small class="text-muted fw-normal">（找回密码用，留空表示解绑）</small></label>
                        <input class="form-control form-control-lg" id="email" name="email" type="email" maxlength="120"
                               value="<?php echo wk_h((string)$u['email']); ?>" placeholder="you@example.com">
                    </div>
                    <button class="btn btn-alt-primary" type="submit">保存基本资料</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- 邮箱已并入上方「基本资料」卡片，不再单独成块 -->

<?php
/* 头像装扮：预览用独立 DOM，JS 直接改 src，不依赖重刷页面 */
$previewUrl   = user_avatar_url($u);
$previewFrame = user_avatar_frame_url(isset($u['avatar_frame']) ? $u['avatar_frame'] : '');
$previewName  = trim((string)($u['nickname'] !== '' ? $u['nickname'] : $u['username']));
$previewInitial = user_initial($previewName);
$curFrameKey = user_avatar_frame_key(isset($u['avatar_frame']) ? $u['avatar_frame'] : '');
?>
<!-- 头像装扮 -->
<div class="block block-rounded" id="ot-avatar-style">
    <div class="block-header block-header-default">
        <h3 class="block-title"><i class="fa fa-wand-magic-sparkles me-1 text-muted"></i> 头像装扮</h3>
        <div class="block-options">
            <span class="fs-xs text-muted">上传头像 / 自选挂件，全站头像同步生效</span>
        </div>
    </div>
    <div class="block-content">
        <form method="post" enctype="multipart/form-data" id="otAvatarForm">
            <?php echo user_csrf_field(); ?>
            <input type="hidden" name="act" value="avatar_style">
            <div class="row items-push">
                <div class="col-lg-4 col-xl-3">
                    <div class="ot-avatar-stage text-center" id="otAvatarStage">
                        <span class="ot-avatar-wrap" id="otAvatarWrap">
                            <?php if ($previewUrl !== '') { ?>
                            <img class="img-avatar img-avatar-thumb" id="otAvatarImg"
                                 src="<?php echo wk_h($previewUrl); ?>" alt="<?php echo wk_h($previewName); ?>"
                                 referrerpolicy="no-referrer">
                            <?php } else { ?>
                            <span class="img-avatar img-avatar-thumb bg-primary text-white d-inline-flex align-items-center justify-content-center fw-bold"
                                  id="otAvatarInitial"><?php echo wk_h($previewInitial); ?></span>
                            <?php } ?>
                            <?php if ($previewFrame !== '') { ?>
                            <img class="ot-avatar-frame" id="otAvatarFrameImg"
                                 src="<?php echo wk_h($previewFrame); ?>" alt="" aria-hidden="true" draggable="false">
                            <?php } ?>
                        </span>
                        <p class="fs-sm fw-semibold mb-1 mt-4">实时预览</p>
                        <p class="fs-xs text-muted mb-0">挂件会套在头像外圈，不影响圆形头像</p>
                    </div>
                </div>
                <div class="col-lg-8 col-xl-9">
                    <div class="mb-4">
                        <label class="form-label" for="avatar_file">上传头像</label>
                        <input class="form-control" type="file" id="avatar_file" name="avatar_file"
                               accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp">
                        <div class="form-text">支持 JPG / PNG / GIF / WEBP，不超过 3MB。留空表示保持当前头像；上传后优先于 QQ 头像显示。</div>
                        <div class="form-check form-switch mt-3">
                            <input class="form-check-input" type="checkbox" id="avatar_clear" name="avatar_clear" value="1">
                            <label class="form-check-label fs-sm" for="avatar_clear">恢复默认（清除上传的头像并取消挂件，回落到 QQ 头像 / 首字母）</label>
                        </div>
                    </div>

                    <label class="form-label">选择头像挂件</label>
                    <div class="ot-frame-grid" id="otFrameGrid">
                        <label class="ot-frame-item">
                            <input type="radio" name="avatar_frame" value="" data-frame="" <?php echo $curFrameKey === '' ? 'checked' : ''; ?>>
                            <span class="ot-frame-thumb ot-frame-none"><i class="fa fa-ban"></i></span>
                            <span class="ot-frame-name">无挂件</span>
                            <i class="fa fa-check ot-frame-check"></i>
                        </label>
                        <?php foreach (user_avatar_frames() as $fKey => $fName) { ?>
                        <label class="ot-frame-item">
                            <input type="radio" name="avatar_frame" value="<?php echo wk_h($fKey); ?>"
                                   data-frame="<?php echo wk_h(user_avatar_frame_url($fKey)); ?>"
                                   <?php echo $curFrameKey === $fKey ? 'checked' : ''; ?>>
                            <span class="ot-frame-thumb">
                                <img src="<?php echo wk_h(user_avatar_frame_preview($fKey)); ?>"
                                     alt="<?php echo wk_h($fName); ?>" loading="lazy">
                            </span>
                            <span class="ot-frame-name"><?php echo wk_h($fName); ?></span>
                            <i class="fa fa-check ot-frame-check"></i>
                        </label>
                        <?php } ?>
                    </div>

                    <button class="btn btn-alt-primary mt-4" type="submit">
                        <i class="fa fa-floppy-disk me-1"></i> 保存装扮
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var wrap   = document.getElementById('otAvatarWrap');
    var grid   = document.getElementById('otFrameGrid');
    var file   = document.getElementById('avatar_file');
    var clear  = document.getElementById('avatar_clear');
    if (!wrap || !grid) { return; }

    var frameImg = document.getElementById('otAvatarFrameImg');
    var baseUrl  = <?php echo json_encode($previewUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    var initial  = <?php echo json_encode($previewInitial, JSON_UNESCAPED_UNICODE); ?>;

    function setFrame(url) {
        if (!url) {
            if (frameImg) { frameImg.remove(); frameImg = null; }
            return;
        }
        if (!frameImg) {
            frameImg = document.createElement('img');
            frameImg.className = 'ot-avatar-frame';
            frameImg.id = 'otAvatarFrameImg';
            frameImg.alt = '';
            frameImg.setAttribute('aria-hidden', 'true');
            frameImg.draggable = false;
            wrap.appendChild(frameImg);
        }
        frameImg.src = url;
    }

    function setAvatar(url) {
        var img = document.getElementById('otAvatarImg');
        if (!img) {
            var ph = document.getElementById('otAvatarInitial');
            img = document.createElement('img');
            img.className = 'img-avatar img-avatar-thumb';
            img.id = 'otAvatarImg';
            img.alt = '';
            if (ph) { wrap.replaceChild(img, ph); }
            else { wrap.insertBefore(img, wrap.firstChild); }
        }
        img.removeAttribute('onerror');
        img.src = url;
    }

    function restoreDefault() {
        if (baseUrl !== '') {
            setAvatar(baseUrl);
        } else {
            var img = document.getElementById('otAvatarImg');
            if (img) {
                var ph = document.createElement('span');
                ph.className = 'img-avatar img-avatar-thumb bg-primary text-white d-inline-flex align-items-center justify-content-center fw-bold';
                ph.id = 'otAvatarInitial';
                ph.textContent = initial;
                wrap.replaceChild(ph, img);
            }
        }
        setFrame('');
        var none = grid.querySelector('input[value=""]');
        if (none) { none.checked = true; }
    }

    grid.addEventListener('change', function (e) {
        var t = e.target;
        if (t && t.name === 'avatar_frame') {
            if (clear && clear.checked) { clear.checked = false; }
            setFrame(t.getAttribute('data-frame') || '');
        }
    });

    if (file) {
        file.addEventListener('change', function () {
            var f = file.files && file.files[0];
            if (!f) { return; }
            if (clear && clear.checked) { clear.checked = false; }
            var rd = new FileReader();
            rd.onload = function (ev) { setAvatar(String(ev.target.result)); };
            rd.readAsDataURL(f);
        });
    }

    if (clear) {
        clear.addEventListener('change', function () {
            if (clear.checked) {
                restoreDefault();
                if (file) { file.value = ''; }
            }
        });
    }
})();
</script>

<!-- 修改密码 -->
<div class="block block-rounded">
    <div class="block-header block-header-default">
        <h3 class="block-title"><i class="fa fa-asterisk me-1 text-muted"></i> 修改密码</h3>
    </div>
    <div class="block-content">
        <form method="post">
            <?php echo user_csrf_field(); ?>
            <input type="hidden" name="act" value="pass">
            <div class="row items-push">
                <div class="col-lg-3"><p class="text-muted fs-sm mb-0">你可以在这里修改登录密码。改密后其它设备上的登录会失效。</p></div>
                <div class="col-lg-7 offset-lg-1">
                    <div class="mb-4">
                        <label class="form-label" for="old_password">当前密码</label>
                        <input class="form-control form-control-lg" id="old_password" name="old_password" type="password" autocomplete="current-password" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label" for="new_password">新密码</label>
                        <input class="form-control form-control-lg" id="new_password" name="new_password" type="password"
                               placeholder="至少 8 位，含字母和数字" autocomplete="new-password" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label" for="new_password2">确认新密码</label>
                        <input class="form-control form-control-lg" id="new_password2" name="new_password2" type="password" autocomplete="new-password" required>
                    </div>
                    <button class="btn btn-alt-primary" type="submit">修改</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php account_foot(); ?>
