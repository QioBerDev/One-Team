<?php
/**
 * 易支付 · 异步通知
 *
 * 铁律：
 *   1. 本文件第一行就是 <?php，前面不能有空格、BOM、注释
 *   2. 任何分支都必须输出 success 或 fail，不能抛 HTML / 警告
 *   3. 金额只认本地订单，回调里的 money 只用来比对
 *   4. 幂等靠 UPDATE ... WHERE status=0 的受影响行数，不做「先查后改」
 */
require dirname(__DIR__) . '/Common/Core_brain.php';
require dirname(__DIR__) . '/Common/Pay.php';
require dirname(__DIR__) . '/Common/Schema.php';

pay_schema_init($DB);

$p   = is_array($_GET) ? $_GET : array();
$out = isset($p['out_trade_no']) ? (string)$p['out_trade_no'] : '';
$ok  = false;

/* 未配置、签名不符，直接打回。验签按报文 sign_type 自动分发 MD5/RSA */
if (!epay_ready()) {
    epay_log($DB, 'notify', $p, 0, $out);
    exit('fail');
}
if (!epay_verify_notify($p)) {
    epay_log($DB, 'notify', $p, 0, $out);
    exit('fail');
}

/* 非成功状态：确认收到即可，别让平台一直重试 */
$status = isset($p['trade_status']) ? (string)$p['trade_status'] : '';
if ($status !== 'TRADE_SUCCESS') {
    epay_log($DB, 'notify', $p, 1, $out);
    exit('success');
}

/* 单号白名单：自己生成的是 20 位数字 */
if (!preg_match('/^[A-Za-z0-9]{8,32}$/', $out)) {
    epay_log($DB, 'notify', $p, 0, $out);
    exit('fail');
}

$order = $DB->getRow("SELECT * FROM `nteam_order` WHERE `out_trade_no`=? LIMIT 1", array($out));
if (!$order) {
    epay_log($DB, 'notify', $p, 0, $out);
    exit('fail');
}

/* 金额比对：两边都按分算成整数，避免浮点误差 */
$paid  = (int)round(((float)(isset($p['money']) ? $p['money'] : 0)) * 100);
$local = (int)round(((float)$order['money']) * 100);
if ($paid !== $local || $local <= 0) {
    epay_log($DB, 'notify', $p, 0, $out);
    exit('fail');
}

$trade_no = isset($p['trade_no']) ? (string)$p['trade_no'] : '';
$pay_type = isset($p['type']) ? (string)$p['type'] : '';
epay_settle($DB, $order, $trade_no, $pay_type);
$ok = true;

epay_log($DB, 'notify', $p, $ok, $out);
exit('success');
