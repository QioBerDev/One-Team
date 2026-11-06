<?php
/**
 * 后台板块 tab 条
 *
 * 先生要求把菜单收拢：用户管理 + 用户中心运营 = 一个板块；
 * 订单 / 卡密 / 余额 = 支付配置板块；QQ 互联 + 注册登录 = 聚合登录板块。
 * 侧栏只留一个入口，板块内用这条 tab 互链，页面本身不用改结构。
 *
 * 用法：在页面 `<div class="content">` 之后调 admin_tabs('组名', '当前文件名');
 */

if (!defined('IN_CRONLITE')) {
    exit('Access denied');
}

/** 第 4 位是最低可见等级：1 = 仅超级管理员，2 = 超级管理员 + 普通管理员 */
function admin_tabs($group, $active = '', $rank = 1)
{
    $sets = array(
        'users' => array(
            array('umember.php', 'fa fa-users',        '用户管理',       2),
            array('uconfig.php', 'fa fa-sliders',      '用户中心运营',   1),
        ),
        'pay' => array(
            array('pay_set.php', 'fa fa-credit-card',  '支付配置',       1),
            array('order.php',   'fa fa-receipt',      '订单管理',       2),
            array('card.php',    'fa fa-ticket',       '卡密管理',       2),
            array('wallet.php',  'fa fa-wallet',       '余额管理',       2),
        ),
        'oauth' => array(
            array('oauth.php',   'fa fa-key',          '聚合登录 / 注册登录', 1),
            array('set.php',     'fa fa-image',        '登录页左侧大图', 1),
            array('uconfig.php', 'fa fa-sliders',      '用户中心运营',   1),
        ),
    );
    if (!isset($sets[$group])) {
        return;
    }
    $rank = (int)$rank;
    echo '<ul class="nav nav-tabs nav-tabs-block nav-tabs-alt push mb-4" role="tablist">';
    foreach ($sets[$group] as $t) {
        if ($rank !== 1 && $t[3] === 1) {
            continue;
        }
        $on = $active === $t[0] ? ' active' : '';
        echo '<li class="nav-item">'
           . '<a class="nav-link' . $on . '" href="' . htmlspecialchars($t[0], ENT_QUOTES, 'UTF-8') . '">'
           . '<i class="' . htmlspecialchars($t[1], ENT_QUOTES, 'UTF-8') . ' me-1"></i>'
           . htmlspecialchars($t[2], ENT_QUOTES, 'UTF-8')
           . '</a></li>';
    }
    echo '</ul>';
}
