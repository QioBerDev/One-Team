<?php
/**
 * 易支付 · 同步跳转（return_url）
 *
 * 这个页面只负责把用户送回订单页，绝不在这里单独决定「已支付」。
 * 回调可能比用户先到，也可能晚到；晚到就主动查一次单兜底。
 */
require dirname(__DIR__) . '/Common/Core_brain.php';
require dirname(__DIR__) . '/Common/Pay.php';
require dirname(__DIR__) . '/Common/Schema.php';

pay_schema_init($DB);

$out = isset($_GET['out_trade_no']) ? preg_replace('/[^A-Za-z0-9]/', '', (string)$_GET['out_trade_no']) : '';
if ($out === '') {
    header('Location: /works.php');
    exit;
}

$order = $DB->getRow("SELECT * FROM `nteam_order` WHERE `out_trade_no`=? LIMIT 1", array($out));
if (!$order) {
    header('Location: /works.php');
    exit;
}

/* 还没落账就主动查一次，别让用户白付（V1/V2 查单结果已抹平成统一结构） */
if ((int)$order['status'] === 0 && epay_ready()) {
    $q = epay_query_order($out);
    if (is_array($q) && !empty($q['paid'])) {
        $money = isset($q['money']) ? (float)$q['money'] : 0;
        if ((int)round($money * 100) === (int)round(((float)$order['money']) * 100)) {
            epay_settle($DB, $order, isset($q['trade_no']) ? $q['trade_no'] : '', isset($q['type']) ? $q['type'] : '');
            epay_log($DB, 'return', $q, 1, $out);
        } else {
            epay_log($DB, 'return', $q, 0, $out);
        }
    } elseif ($q !== false) {
        epay_log($DB, 'return', $q, 0, $out);
    }
}

header('Location: /user/order.php?no=' . urlencode($out));
exit;
