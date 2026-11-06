<?php
/**
 * 前后台一体化 · 管理员账号落地与双向模式切换
 *
 * 先生要求：
 *   - 把后台 nteam_admin 的管理员账号也放进用户中心，判定为管理员账户；
 *   - 后台顶部变成切换模式（进后台 → 「前台用户中心」），
 *     前台用户中心顶部变成「进入管理员操作台」；
 *   - 其他普通用户不变（is_admin=0 完全看不到切换入口）。
 *
 * 会话模型：后台用 $_SESSION['adminUser']（nteam_admin），前台用
 * $_SESSION['one_uid']（nteam_user）。两者互不覆盖，switch.php 里
 * 按方向补齐另一边的会话，做到免密来回切换。
 */

if (!defined('IN_CRONLITE')) {
    exit('Access denied');
}

/** 当前前台用户是不是管理员（普通用户恒为 false） */
function ms_is_admin($u)
{
    return is_array($u)
        && isset($u['is_admin']) && (int)$u['is_admin'] === 1
        && isset($u['status']) && (int)$u['status'] === 1;
}

/** 后台当前登录的管理员账号名，未登录返回 '' */
function ms_admin_name()
{
    return isset($_SESSION['adminUser']) ? trim((string)$_SESSION['adminUser']) : '';
}

/**
 * 幂等迁移：把 nteam_admin 里的账号镜像进 nteam_user，打 is_admin=1。
 * 已存在同名前台账号 → 只补标记，不改密码/资料/会员，避免覆盖先生的数据。
 * 不存在 → 建一个随机密码的占位账号（只能从后台切换进入，不能被密码爆破）。
 * 返回本次新增/更新的条数。
 */
function ms_sync_admin($DB)
{
    static $done = false;
    if ($done) {
        return 0;
    }
    $done = true;

    if (!$DB->getRow("SHOW COLUMNS FROM `nteam_user` LIKE 'is_admin'")) {
        return 0;
    }
    $admins = $DB->getAll("SELECT `adminUser`, `adminQq`, `adminRank` FROM `nteam_admin`");
    if (!is_array($admins)) {
        return 0;
    }
    $n = 0;
    foreach ($admins as $a) {
        $name = trim((string)$a['adminUser']);
        if ($name === '') {
            continue;
        }
        $u = $DB->getRow("SELECT `id`, `is_admin` FROM `nteam_user` WHERE `username`=? LIMIT 1", array($name));
        if ($u) {
            if ((int)$u['is_admin'] !== 1) {
                $DB->query("UPDATE `nteam_user` SET `is_admin`=1 WHERE `id`=?", array((int)$u['id']));
                $n++;
            }
        } else {
            /* 随机 32 字节密码，没有任何人能用密码登录这个镜像账号 */
            $hash = password_hash(bin2hex(random_bytes(24)), PASSWORD_DEFAULT);
            $qq   = isset($a['adminQq']) ? (string)$a['adminQq'] : '';
            /* email 必须给 NULL：nteam_user.uk_email 是唯一键，
               多个管理员账号都写 '' 会撞 Duplicate entry '' 导致镜像建不出来。
               MySQL 唯一索引允许多个 NULL。 */
            $DB->query(
                "INSERT INTO `nteam_user`
                 (`username`,`email`,`pass_hash`,`nickname`,`status`,`reg_time`,`reg_ip`,`qq`,`is_admin`)
                 VALUES (?,?,?,?,1,?,?,?,1)",
                array($name, null, $hash, $name, time(), '127.0.0.1', $qq)
            );
            $n++;
        }
    }
    return $n;
}

/** 后台账号名 → 对应的前台用户行（切换前先跑一次同步） */
function ms_admin_user($DB, $name)
{
    ms_sync_admin($DB);
    $name = trim((string)$name);
    if ($name === '') {
        return null;
    }
    return $DB->getRow(
        "SELECT * FROM `nteam_user` WHERE `username`=? AND `is_admin`=1 AND `status`=1 LIMIT 1",
        array($name)
    );
}

/** 一次性切换票据：防止跨站诱导点击切换，也避免刷新重复切 */
function ms_ticket($dir)
{
    $k = 'ms_ticket_' . ($dir === 'admin' ? 'admin' : 'user');
    if (empty($_SESSION[$k])) {
        $_SESSION[$k] = bin2hex(random_bytes(12));
    }
    return (string)$_SESSION[$k];
}

function ms_ticket_url($dir)
{
    return '/switch.php?to=' . rawurlencode($dir) . '&t=' . rawurlencode(ms_ticket($dir));
}

function ms_ticket_check($dir, $t)
{
    $k = 'ms_ticket_' . ($dir === 'admin' ? 'admin' : 'user');
    return is_string($t) && $t !== '' && !empty($_SESSION[$k])
        && hash_equals((string)$_SESSION[$k], $t);
}

function ms_ticket_burn($dir)
{
    unset($_SESSION['ms_ticket_' . ($dir === 'admin' ? 'admin' : 'user')]);
}
