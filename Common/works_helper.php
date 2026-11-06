<?php
/* 作品页共用小工具：列表 / 详情都要用，抽出来避免两边各写一份 */

if (!function_exists('wk_h')) {
    function wk_h($s) {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('wk_date')) {
    /* 2026年10月08日 → 2026.10.08（长）/ 2026.10（短） */
    function wk_date($s, $short = true) {
        $s = trim((string)$s);
        if ($s === '') return '';
        if (preg_match('/(\d{4})\D+(\d{1,2})\D+(\d{1,2})/u', $s, $m)) {
            $y  = $m[1];
            $mo = str_pad($m[2], 2, '0', STR_PAD_LEFT);
            $d  = str_pad($m[3], 2, '0', STR_PAD_LEFT);
            return $short ? ($y . '.' . $mo) : ($y . '.' . $mo . '.' . $d);
        }
        return $s;
    }
}

if (!function_exists('wk_img')) {
    function wk_img($s) {
        $s = trim((string)$s);
        if ($s === '') return '';
        if ($s[0] !== '/' && stripos($s, 'http') !== 0) {
            $s = '/' . $s;
        }
        return $s;
    }
}

if (!function_exists('wk_first')) {
    /* 首字：没 mbstring 也不能炸 */
    function wk_first($s) {
        $s = trim((string)$s);
        if ($s === '') return '';
        return function_exists('mb_substr') ? mb_substr($s, 0, 1, 'UTF-8') : substr($s, 0, 1);
    }
}

if (!function_exists('wk_url')) {
    /* 后台只存域名的情况补协议 */
    function wk_url($s) {
        $s = trim((string)$s);
        if ($s === '') return '';
        if (stripos($s, 'http://') !== 0 && stripos($s, 'https://') !== 0 && strpos($s, '//') !== 0) {
            $s = '//' . $s;
        }
        return $s;
    }
}
