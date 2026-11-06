<?php
/* 本地测试用：php -S 的 router。
   include('../xx.php') 相对路径按 cwd 解析 —— chdir 到脚本目录后直接 require，
   避免 return false 走二次派发时 cwd 被重置。 */
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $path;

if (is_file($file)) {
    if (substr($file, -4) === '.php') {
        chdir(dirname($file));
        require $file;
        return true;
    }
    return false; /* 静态资源 */
}

if (is_dir($file)) {
    foreach (array('index.php', 'index.html') as $idx) {
        if (is_file($file . '/' . $idx)) {
            if (substr($idx, -4) === '.php') {
                chdir($file);
                require $file . '/' . $idx;
                return true;
            }
            return false;
        }
    }
}
http_response_code(404);
echo 'Not Found';
return true;
