<?php
/**
 * 彩虹易支付 · 公共库（V1 / V2 自动识别）
 *
 * 版本识别（自动，无需手选）：
 *   配了「商户私钥 + 平台公钥」→ V2（RSA，SHA256WithRSA，走 /api/pay/*）
 *   只配了「商户密钥 KEY」      → V1（MD5，走 submit.php / api.php）
 *   两者都配                    → 优先 V2（官方现行标准）
 *
 * 签名规则（两版通用的拼串部分）：
 *   1. 参数按 key 的 ASCII 升序排
 *   2. sign、sign_type、空值、数组 不参与签名
 *   3. 拼成 a=b&c=d（值不做 URL 编码）
 *   V1：sign = md5(拼接串 . KEY)，小写
 *   V2：sign = base64(SHA256WithRSA(拼接串, 商户私钥))
 *
 * 文档：https://pays.org.cn/doc/index.html（V2）/ v1_legacy_api.html（V1）
 */

if (!defined('IN_CRONLITE')) {
    exit('Access denied');
}

/** 读支付配置：Epay_Api / Epay_Pid / Epay_Key / Epay_MchPri / Epay_PubKey / Epay_Wx / Epay_Ali */
function epay_conf($name, $default = '')
{
    $v = conf('Epay_' . $name);
    return ($v === '' || $v === null) ? $default : $v;
}

/**
 * 自动识别接口版本
 * @return string 'v2' | 'v1' | ''（没配好）
 */
function epay_version()
{
    static $ver = null;
    if ($ver !== null) {
        return $ver;
    }
    $hasRsa = epay_conf('MchPri') !== '' && epay_conf('PubKey') !== '';
    $hasMd5 = epay_conf('Key') !== '';
    $ver = $hasRsa ? 'v2' : ($hasMd5 ? 'v1' : '');
    return $ver;
}

/** 版本的中文名，后台展示用 */
function epay_version_name()
{
    $v = epay_version();
    if ($v === 'v2') {
        return 'V2（RSA 签名）';
    }
    if ($v === 'v1') {
        return 'V1（MD5 签名）';
    }
    return '未识别（密钥未配置）';
}

/** 总开关 + 商户号 + 版本可识别，才算配好 */
function epay_ready()
{
    return conf('Epay_Open') == 1
        && epay_conf('Pid') !== ''
        && epay_version() !== '';
}

/** 渠道开关：wxpay / alipay 是否启用 */
function epay_channel_on($channel)
{
    if ($channel === 'wxpay') {
        return conf('Epay_Wx') == 1;
    }
    if ($channel === 'alipay') {
        return conf('Epay_Ali') == 1;
    }
    return false;
}

/** 有没有至少一个渠道开着 */
function epay_any_channel()
{
    return epay_channel_on('wxpay') || epay_channel_on('alipay');
}

function epay_api()
{
    return rtrim(epay_conf('Api', 'https://pays.org.cn/'), '/') . '/';
}

/**
 * 站点对外地址，拼 notify_url / return_url 用。
 * 不做任何手工配置，按当前请求自动识别。
 */
function epay_base_url()
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    $host = isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] !== ''
        ? $_SERVER['HTTP_HOST']
        : 'localhost';
    // Host 头可以伪造，但这里只用于拼给自己的回调地址，平台侧访问不通会直接失败，不会写错账
    $host = preg_replace('/[^A-Za-z0-9\.\-:\[\]]/', '', $host);
    return ($https ? 'https://' : 'http://') . $host . '/';
}

/** 拼待签名串：key 升序，剔除 sign/sign_type/空值/数组，值不 URL 编码 */
function epay_sign_str(array $params)
{
    ksort($params, SORT_STRING);
    $pairs = array();
    foreach ($params as $k => $v) {
        if ($k === 'sign' || $k === 'sign_type') {
            continue;
        }
        if ($v === '' || $v === null || is_array($v)) {
            continue;
        }
        $pairs[] = $k . '=' . $v;
    }
    return implode('&', $pairs);
}

/** V1：MD5 签名，小写 */
function epay_sign_md5(array $params, $key)
{
    return md5(epay_sign_str($params) . $key);
}

/** V1 验签，恒定时比较防时序侧信道 */
function epay_verify_md5(array $params, $key)
{
    if ($key === '' || empty($params['sign'])) {
        return false;
    }
    $expect = epay_sign_md5($params, $key);
    return hash_equals($expect, strtolower((string)$params['sign']));
}

/**
 * 把商户后台复制来的密钥整理成 PEM。
 * 自动兼容：带/不带 PEM 头、PKCS#8 / PKCS#1、各种换行。
 */
function epay_pem($raw, $isPrivate)
{
    $raw = trim((string)$raw);
    if ($raw === '') {
        return '';
    }
    $raw = str_replace(array("\r\n", "\r"), "\n", $raw);
    if (strpos($raw, '-----BEGIN') !== false) {
        return rtrim($raw) . "\n";
    }
    $body = preg_replace('/\s+/', '', $raw);
    if ($isPrivate) {
        return "-----BEGIN PRIVATE KEY-----\n" . chunk_split($body, 64, "\n") . "-----END PRIVATE KEY-----\n";
    }
    return "-----BEGIN PUBLIC KEY-----\n" . chunk_split($body, 64, "\n") . "-----END PUBLIC KEY-----\n";
}

/** 载入商户私钥，兼容 PKCS#8 / PKCS#1 两种外壳 */
function epay_priv_key($raw)
{
    $pem = epay_pem($raw, true);
    if ($pem === '') {
        return false;
    }
    $key = openssl_pkey_get_private($pem);
    if ($key !== false) {
        return $key;
    }
    // PKCS#8 外壳装不下就换 PKCS#1（RSA PRIVATE KEY）再试
    if (preg_match('/-----BEGIN PRIVATE KEY-----(.*?)-----END PRIVATE KEY-----/s', $pem, $m)) {
        $key = openssl_pkey_get_private(
            "-----BEGIN RSA PRIVATE KEY-----\n" . trim($m[1]) . "\n-----END RSA PRIVATE KEY-----\n"
        );
        if ($key !== false) {
            return $key;
        }
    }
    return false;
}

/** 载入平台公钥，兼容 X.509 / PKCS#1 两种外壳 */
function epay_pub_key($raw)
{
    $pem = epay_pem($raw, false);
    if ($pem === '') {
        return false;
    }
    $key = openssl_pkey_get_public($pem);
    if ($key !== false) {
        return $key;
    }
    if (preg_match('/-----BEGIN PUBLIC KEY-----(.*?)-----END PUBLIC KEY-----/s', $pem, $m)) {
        $key = openssl_pkey_get_public(
            "-----BEGIN RSA PUBLIC KEY-----\n" . trim($m[1]) . "\n-----END RSA PUBLIC KEY-----\n"
        );
        if ($key !== false) {
            return $key;
        }
    }
    return false;
}

/** V2：SHA256WithRSA 签名，base64 输出 */
function epay_sign_rsa(array $params, $privRaw)
{
    $key = epay_priv_key($privRaw);
    if ($key === false) {
        return '';
    }
    $sig = '';
    if (!openssl_sign(epay_sign_str($params), $sig, $key, OPENSSL_ALGO_SHA256)) {
        return '';
    }
    return base64_encode($sig);
}

/** V2 验签 */
function epay_verify_rsa(array $params, $pubRaw)
{
    if (empty($params['sign'])) {
        return false;
    }
    $key = epay_pub_key($pubRaw);
    if ($key === false) {
        return false;
    }
    $sig = base64_decode((string)$params['sign'], true);
    if ($sig === false) {
        return false;
    }
    return openssl_verify(epay_sign_str($params), $sig, $key, OPENSSL_ALGO_SHA256) === 1;
}

/**
 * 回调验签：只认当前识别版本的算法，版本与 sign_type 不符一律打回。
 *   v2 -> 只收 RSA（防 V1 残留密钥被降级成 MD5 伪造）
 *   v1 -> 只收 MD5
 * 平台加扩展字段没事 —— 待签串本来就收所有非空参数。
 */
function epay_verify_notify(array $params)
{
    $st  = isset($params['sign_type']) ? strtoupper((string)$params['sign_type']) : '';
    $ver = epay_version();
    if ($ver === 'v2') {
        if ($st !== '' && $st !== 'RSA') {
            return false;
        }
        return epay_verify_rsa($params, epay_conf('PubKey'));
    }
    if ($ver === 'v1') {
        if ($st === 'RSA') {
            return false;
        }
        return epay_verify_md5($params, epay_conf('Key'));
    }
    return false;
}

/**
 * 商户订单号：14 位时间 + 6 位随机 = 20 位
 * 绝不用自增 ID —— 可枚举，且撞单后无法追。
 */
function epay_new_no()
{
    return date('YmdHis') . str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * 从后台录入的价格字符串里抠出金额
 *   "2999" / "￥2,999.00" / "2999元"  -> 2999.00
 *   "面议" / ""                      -> 0.00（0 表示不可在线支付）
 */
function epay_parse_money($s)
{
    $s = trim((string)$s);
    if ($s === '') {
        return 0.0;
    }
    $s = str_replace(array(',', '，'), '', $s);
    if (!preg_match('/(\d+(?:\.\d{1,2})?)/', $s, $m)) {
        return 0.0;
    }
    $n = round((float)$m[1], 2);
    return $n > 0 ? $n : 0.0;
}

/** 支付方式中文名 */
function epay_type_name($t)
{
    $map = array(
        'alipay'   => '支付宝',
        'wxpay'    => '微信支付',
        'qqpay'    => 'QQ 钱包',
        'bank'     => '银联',
        'jdpay'    => '京东支付',
        'paypal'   => 'PayPal',
        'douyinpay'=> '抖音支付',
        'balance'  => '余额支付',
        'free'     => '会员免费',
    );
    return isset($map[$t]) ? $map[$t] : ($t === '' ? '收银台' : $t);
}

/** 订单状态中文 */
function epay_status_name($s)
{
    $map = array(0 => '待支付', 1 => '已支付', 2 => '已退款', 3 => '已关闭');
    return isset($map[(int)$s]) ? $map[(int)$s] : '未知';
}

/** 写支付日志。日志表本身出错也不能影响主流程。 */
function epay_log($DB, $direction, array $payload, $ok, $out_trade_no = '')
{
    try {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            $json = '';
        }
        $DB->query(
            "INSERT INTO `nteam_pay_log` (`out_trade_no`,`direction`,`ok`,`payload`,`ip`,`created_at`)
             VALUES (?,?,?,?,?,?)",
            array(
                substr((string)$out_trade_no, 0, 32),
                substr((string)$direction, 0, 12),
                $ok ? 1 : 0,
                $json,
                substr((string)$ip, 0, 45),
                time(),
            )
        );
    } catch (\Throwable $e) {
        // 日志失败就算了，不能因为写日志把回调搞挂
    }
}

/** 简易 HTTP，5 秒超时，返回 [body, http_code] */
function epay_http($url, $post = null)
{
    if (!function_exists('curl_init')) {
        return array('', 0);
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_USERAGENT      => 'one-site/1.0',
    ));
    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($post) ? http_build_query($post) : $post);
    }
    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return array($body === false ? '' : $body, $code);
}

/**
 * 落账 + 自动交付。notify 和 return 兜底共用这一段，逻辑只有一份。
 * @return bool true = 本次真的改了状态（第一次确认支付）
 */
function epay_settle($DB, array $order, $trade_no, $pay_type)
{
    $trade_no = substr(preg_replace('/[^A-Za-z0-9_\-]/', '', (string)$trade_no), 0, 64);
    $allow = array('alipay', 'wxpay', 'qqpay', 'bank', 'jdpay', 'paypal', 'douyinpay', 'balance', 'free');
    if (!in_array($pay_type, $allow, true)) {
        $pay_type = '';
    }
    $out = preg_replace('/[^A-Za-z0-9]/', '', (string)$order['out_trade_no']);
    $now = time();

    /* 余额 / 卡密库按需加载，notify 与 return 不用额外 require */
    if (!function_exists('wallet_parse_remark')) {
        require_once __DIR__ . '/Wallet.php';
    }

    $affected = $DB->exec(
        "UPDATE `nteam_order`
            SET `status`=1, `trade_no`='{$trade_no}', `pay_type`='{$pay_type}',
                `pay_time`={$now}, `notify_time`={$now}
          WHERE `out_trade_no`='{$out}' AND `status`=0"
    );
    if ($affected !== 1) {
        return false;
    }

    /* 自动交付：项目里配了 deliver 就写进订单 */
    $proj = $DB->getRow("SELECT `deliver` FROM `nteam_project_list` WHERE `id`=? LIMIT 1", array((int)$order['project_id']));
    $deliver = $proj && isset($proj['deliver']) ? trim((string)$proj['deliver']) : '';
    if ($deliver !== '') {
        $DB->query(
            "UPDATE `nteam_order` SET `deliver`=?, `deliver_state`=1 WHERE `id`=?",
            array($deliver, (int)$order['id'])
        );
    }

    /* 会员订单：remark=vip|等级|天数，支付后自动发放。
       会员库按需加载 —— notify/return 只 require 了 Pay/Schema，不引就永远不会发放 */
    if (!function_exists('vip_parse_remark')) {
        require_once __DIR__ . '/Vip.php';
    }
    $vip = vip_parse_remark(isset($order['remark']) ? $order['remark'] : '');
    if ($vip && $vip['level'] > 0 && (int)$order['user_id'] > 0) {
        $expire = vip_grant($DB, (int)$order['user_id'], $vip['level'], $vip['days']);
        if ($expire > 0) {
            $DB->query(
                "UPDATE `nteam_order` SET `deliver`=?, `deliver_state`=1 WHERE `id`=?",
                array('会员已发放：' . vip_level_name($vip['level']) . '，有效期至 ' . date('Y-m-d H:i', $expire), (int)$order['id'])
            );
        }
    }

    /* 充值余额订单：remark=money|金额，支付后自动入账（ref 幂等） */
    $recharge = wallet_parse_remark(isset($order['remark']) ? $order['remark'] : '');
    if ($recharge > 0 && (int)$order['user_id'] > 0) {
        $changed = wallet_change(
            $DB, (int)$order['user_id'], $recharge, 'recharge',
            '余额充值 · 订单 ' . $out, 'order:' . $out
        );
        if ($changed) {
            $DB->query(
                "UPDATE `nteam_order` SET `deliver`=?, `deliver_state`=1 WHERE `id`=?",
                array('余额已到账：¥' . number_format($recharge, 2), (int)$order['id'])
            );
            /* 邀请返佣：同一笔充值靠 ref 幂等，重复回调不会重复返 */
            if (!function_exists('agent_reward')) {
                require_once __DIR__ . '/Agent.php';
            }
            agent_reward($DB, (int)$order['user_id'], $recharge, 'order:' . $out);
        }
    }
    return true;
}

/**
 * 主动查单，用于 return 页兜底（回调丢了也不至于让用户白付）。
 * 返回统一结构：{ok, paid, money, trade_no, type, raw}，两版差异在这里抹平。
 */
function epay_query_order($out_trade_no)
{
    $ver = epay_version();
    if ($ver === 'v2') {
        $params = array(
            'pid'          => epay_conf('Pid'),
            'out_trade_no' => $out_trade_no,
            'timestamp'    => (string)time(),
        );
        $params['sign'] = epay_sign_rsa($params, epay_conf('MchPri'));
        if ($params['sign'] === '') {
            return false;
        }
        $params['sign_type'] = 'RSA';
        list($body, $code) = epay_http(epay_api() . 'api/pay/query', $params);
        if ($code !== 200 || $body === '') {
            return false;
        }
        $d = json_decode($body, true);
        if (!is_array($d) || !isset($d['code'])) {
            return false;
        }
        $ok = (int)$d['code'] === 0;
        return array(
            'ok'      => $ok,
            'paid'    => $ok && isset($d['status']) && (int)$d['status'] === 1,
            'money'   => isset($d['money']) ? (float)$d['money'] : 0,
            'trade_no'=> isset($d['trade_no']) ? (string)$d['trade_no'] : '',
            'type'    => isset($d['type']) ? (string)$d['type'] : '',
            'raw'     => $d,
        );
    }
    if ($ver === 'v1') {
        $url = epay_api() . 'api.php?' . http_build_query(array(
            'act'          => 'order',
            'pid'          => epay_conf('Pid'),
            'key'          => epay_conf('Key'),
            'out_trade_no' => $out_trade_no,
        ));
        list($body, $code) = epay_http($url);
        if ($code !== 200 || $body === '') {
            return false;
        }
        $d = json_decode($body, true);
        if (!is_array($d) || !isset($d['code'])) {
            return false;
        }
        $ok = (int)$d['code'] === 1;
        return array(
            'ok'      => $ok,
            'paid'    => $ok && isset($d['status']) && (int)$d['status'] === 1,
            'money'   => isset($d['money']) ? (float)$d['money'] : 0,
            'trade_no'=> isset($d['trade_no']) ? (string)$d['trade_no'] : '',
            'type'    => isset($d['type']) ? (string)$d['type'] : '',
            'raw'     => $d,
        );
    }
    return false;
}

/**
 * 生成自动提交到收银台的表单（V1 → submit.php，V2 → api/pay/submit）
 * 用 POST：GET 容易被浏览器插件、Referer 策略、WAF 破坏参数顺序
 * @param array  $order   订单行
 * @param string $channel wxpay / alipay
 */
function epay_pay_form(array $order, $channel)
{
    $money = number_format((float)$order['money'], 2, '.', '');
    $name  = (string)$order['project_name'];

    if (epay_version() === 'v2') {
        $params = array(
            'pid'          => epay_conf('Pid'),
            'type'         => $channel,
            'out_trade_no' => $order['out_trade_no'],
            'notify_url'   => epay_base_url() . 'pay/notify.php',
            'return_url'   => epay_base_url() . 'pay/return.php',
            'name'         => $name,
            'money'        => $money,
            'timestamp'    => (string)time(),
        );
        $params['sign'] = epay_sign_rsa($params, epay_conf('MchPri'));
        $params['sign_type'] = 'RSA';
        $action = epay_api() . 'api/pay/submit';
    } else {
        /* 只传 V1 submit.php 文档列出的字段。
           部分易支付实现会按白名单过滤后再验签，多传一个 clientip 就会验签失败。 */
        $params = array(
            'pid'          => epay_conf('Pid'),
            'type'         => $channel,
            'out_trade_no' => $order['out_trade_no'],
            'notify_url'   => epay_base_url() . 'pay/notify.php',
            'return_url'   => epay_base_url() . 'pay/return.php',
            'name'         => $name,
            'money'        => $money,
            'sign_type'    => 'MD5',
        );
        $params['sign'] = epay_sign_md5($params, epay_conf('Key'));
        $action = epay_api() . 'submit.php';
    }

    $html  = '<form id="epayForm" method="post" action="' . htmlspecialchars($action, ENT_QUOTES, 'UTF-8') . '">';
    foreach ($params as $k => $v) {
        $html .= '<input type="hidden" name="' . htmlspecialchars($k, ENT_QUOTES, 'UTF-8')
              . '" value="' . htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8') . '">';
    }
    $html .= '</form>';
    return $html;
}
