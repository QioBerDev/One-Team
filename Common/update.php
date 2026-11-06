<?php
/* 作者：CoCO —— 已禁用远程更新执行（授权服务器已停止服务） */
$mod = 'admin';
include __DIR__ . '/Core_brain.php';
if ($adminData['adminRank'] == 2) {
    exit('您的账号没有权限使用此功能');
}
echo '<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>系统更新</title>
<style>body{font-family:"Helvetica Neue",Arial,"PingFang SC","Microsoft YaHei",sans-serif;background:#edf1f4;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;} .box{background:#fff;padding:40px 56px;border-radius:8px;text-align:center;box-shadow:0 1px 3px rgba(0,0,0,.08);} h3{margin-top:0;} p{color:#666;line-height:1.8;} a{color:#2ecc71;text-decoration:none;}</style>
</head>
<body>
<div class="box">
<h3>系统更新</h3>
<p>在线更新服务已停止，无法执行远程升级。<br>当前程序可正常使用，请勿依赖在线更新功能。<br>如需升级请手动替换程序文件。</p>
<p><a href="/Admin/update.php">返回</a></p>
</div>
</body>
</html>';
