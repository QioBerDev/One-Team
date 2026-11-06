<?php
/**
 * 注册图形验证码（OneTool 原版注册页自带）
 *
 * 用服务端生成的 SVG 算术题，不依赖 GD 扩展，也不需要字体文件。
 * 答案只存 session，5 分钟过期，验证后立刻销毁（见 register.php）。
 */
require dirname(__DIR__) . '/Common/Core_brain.php';

$a = random_int(1, 9);
$b = random_int(1, 9);
$_SESSION['ot_captcha']    = (string)($a + $b);
$_SESSION['ot_captcha_at'] = time();

header('Content-Type: image/svg+xml; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$lines = '';
for ($i = 0; $i < 4; $i++) {
    $x1 = random_int(0, 160);
    $y1 = random_int(0, 58);
    $x2 = random_int(0, 160);
    $y2 = random_int(0, 58);
    $c  = array('#c7d2fe', '#fbcfe8', '#bbf7d0', '#fde68a')[$i];
    $lines .= '<line x1="' . $x1 . '" y1="' . $y1 . '" x2="' . $x2 . '" y2="' . $y2
        . '" stroke="' . $c . '" stroke-width="2"/>';
}
$dots = '';
for ($i = 0; $i < 12; $i++) {
    $dots .= '<circle cx="' . random_int(0, 160) . '" cy="' . random_int(0, 58)
        . '" r="1.5" fill="#94a3b8" opacity="0.6"/>';
}
$dx = random_int(-3, 3);
$dy = random_int(-2, 2);

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<svg xmlns="http://www.w3.org/2000/svg" width="160" height="58" viewBox="0 0 160 58">';
echo '<rect width="160" height="58" fill="#f8fafc"/>';
echo $lines . $dots;
echo '<text x="' . (18 + $dx) . '" y="' . (38 + $dy) . '" font-family="Poppins,Arial,sans-serif" '
    . 'font-size="24" font-weight="600" fill="#334155" letter-spacing="1">'
    . $a . ' + ' . $b . ' = ?</text>';
echo '</svg>';
