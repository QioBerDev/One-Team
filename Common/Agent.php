<?php
/**
 * 代理 / 邀请返佣
 *
 * 后台「用户中心运营 → 代理中心」开 Agent_Open 并设比例后生效。
 * 好友通过 /user/register.php?ref=<邀请人UID> 注册，绑定 nteam_user.invite_by；
 * 好友在线充值入账时，按 Agent_Rate 给邀请人返佣到余额。
 * ref 唯一键做幂等，同一笔充值重复回调只会返一次。
 */

if (!defined('IN_CRONLITE')) {
    exit('Access denied');
}
require_once __DIR__ . '/OtConfig.php';
require_once __DIR__ . '/Wallet.php';

/** 记住邀请人：注册链接带 ?ref=UID，写 30 天 cookie */
function agent_remember_ref()
{
    $ref = isset($_GET['ref']) ? (int)$_GET['ref'] : 0;
    if ($ref > 0 && ot_on('Agent_Open')) {
        @setcookie('ot_ref', (string)$ref, time() + 30 * 86400, '/', '', false, true);
        $_COOKIE['ot_ref'] = (string)$ref;
    }
}

/** 注册时读出邀请人，必须存在且不是自己 */
function agent_inviter($DB)
{
    if (!ot_on('Agent_Open')) {
        return 0;
    }
    $ref = isset($_COOKIE['ot_ref']) ? (int)$_COOKIE['ot_ref'] : 0;
    if ($ref <= 0) {
        return 0;
    }
    $row = $DB->getRow("SELECT `id` FROM `nteam_user` WHERE `id`=? AND `status`=1 LIMIT 1", array($ref));
    return $row ? $ref : 0;
}

/**
 * 给邀请人返佣。$refSeed 必须是这笔充值唯一且稳定的标识（如 order:单号）。
 * @return bool 是否真的返了
 */
function agent_reward($DB, $buyerId, $amount, $refSeed)
{
    if (!ot_on('Agent_Open')) {
        return false;
    }
    $rate = ot_num('Agent_Rate', 0, 50);
    $amount = (float)$amount;
    if ($rate <= 0 || $amount <= 0) {
        return false;
    }
    $buyerId = (int)$buyerId;
    $row = $DB->getRow("SELECT `invite_by` FROM `nteam_user` WHERE `id`=? LIMIT 1", array($buyerId));
    if (!$row || (int)$row['invite_by'] <= 0) {
        return false;
    }
    $inviter = (int)$row['invite_by'];
    if ($inviter === $buyerId) {
        return false;
    }
    $bonus = round($amount * $rate / 100, 2);
    if ($bonus < 0.01) {
        return false;
    }
    return (bool)wallet_change(
        $DB, $inviter, $bonus, 'agent',
        '邀请返佣 · 用户#' . $buyerId . ' 充值 ¥' . number_format($amount, 2),
        'invite:' . preg_replace('/[^A-Za-z0-9_:\-]/', '', (string)$refSeed)
    );
}
