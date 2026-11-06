<?php
/* 作者：CoCO —— 已去除远程版本检查（授权服务器已停止服务） */
$cfg = (include __DIR__ . '/Auth.php');
$v = isset($cfg['app_version']) ? $cfg['app_version'] : '';
echo '<div class="fw-semibold text-flat">v' . $v . '-version</div>';
echo '<div class="mt-1"><p class="mb-0"><b>当前程序版本</b></p></div>';
echo '<div class="fs-xs text-muted"><b>' . date('Y-m-d H:i:s') . '</b></div>';
