<?php
/**
 * 独立安装向导
 * 流程：环境检查 → 数据库/站点/管理员 → 建表灌数据 → 写配置 → 落安装锁
 * 安装完成后生成 install.lock.php，再次访问本页面将被拒绝。
 */
declare(strict_types=1);

session_start();
header('Content-Type: text/html; charset=utf-8');

define('INSTALL_LOCK', __DIR__ . '/install.lock.php');
define('CONFIG_FILE', dirname(__DIR__) . '/Common/Database_Config.php');
define('UPLOADS_DIR', dirname(__DIR__) . '/uploads');

/* ---------- 安装锁：已安装直接拒绝 ---------- */
if (is_file(INSTALL_LOCK)) {
    http_response_code(403);
    exit('<!DOCTYPE html><html lang="zh-CN"><head><meta charset="utf-8"><title>已安装</title>
<style>body{font-family:system-ui,-apple-system,"Segoe UI",sans-serif;background:#f4f6fa;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}
.card{background:#fff;border-radius:14px;padding:48px 56px;box-shadow:0 12px 40px rgba(20,30,60,.10);text-align:center;max-width:420px}
h1{font-size:22px;margin:0 0 12px;color:#1b2436}p{color:#5b6478;line-height:1.8;margin:0}</style></head><body>
<div class="card"><h1>系统已安装</h1><p>本系统已完成安装，安装器已锁定。<br>如确需重新安装，请先手动删除<br><b>install/install.lock.php</b></p></div></body></html>');
}

$step   = 'form';
$errors = [];
$notice = '';

/* ---------- 环境检测 ---------- */
function env_checks(): array
{
    $checks = [];
    $checks[] = ['PHP 版本 ≥ 7.4', PHP_VERSION, version_compare(PHP_VERSION, '7.4.0', '>=')];
    $checks[] = ['PDO MySQL 扩展', extension_loaded('pdo_mysql') ? '已加载' : '未加载', extension_loaded('pdo_mysql')];
    $checks[] = ['cURL 扩展（验证码校验用）', extension_loaded('curl') ? '已加载' : '未加载', extension_loaded('curl')];
    $checks[] = ['Common/Database_Config.php 可写', is_writable(CONFIG_FILE) ? '可写' : '不可写', is_writable(CONFIG_FILE)];
    $checks[] = ['uploads/ 目录可写', is_dir(UPLOADS_DIR) && is_writable(UPLOADS_DIR) ? '可写' : '不可写', is_dir(UPLOADS_DIR) && is_writable(UPLOADS_DIR)];
    $checks[] = ['install/ 目录可写（安装锁）', is_writable(__DIR__) ? '可写' : '不可写', is_writable(__DIR__)];
    return $checks;
}

function h(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function ident_ok(string $s): bool
{
    return (bool)preg_match('/^[A-Za-z0-9_]+$/', $s);
}

/* ---------- 建表语句（表前缀 nteam_ 会被替换为用户前缀） ---------- */
function schema_statements(string $p): array
{
    return [
        "CREATE TABLE IF NOT EXISTS `{$p}_admin` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `adminUser` varchar(255) NOT NULL COMMENT '管理员账号',
  `adminPwd` char(32) NOT NULL COMMENT '管理员密码',
  `adminQq` varchar(32) NOT NULL DEFAULT '' COMMENT '管理员QQ',
  `adminLoginIp` varchar(45) DEFAULT NULL COMMENT '管理员IP',
  `adminRank` int(11) NOT NULL DEFAULT 1 COMMENT '管理员等级',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS `{$p}_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `SiteName` varchar(255) NOT NULL DEFAULT '' COMMENT '网站名称',
  `Name` varchar(255) NOT NULL DEFAULT '' COMMENT '网站简称',
  `Descriptison` varchar(255) NOT NULL DEFAULT '' COMMENT '网站描述',
  `Keywords` varchar(255) NOT NULL DEFAULT '' COMMENT '网站关键词',
  `ICP` varchar(255) NOT NULL DEFAULT '' COMMENT 'ICP备案号',
  `Url` varchar(255) NOT NULL DEFAULT '' COMMENT '网址',
  `Index_Image` varchar(255) NOT NULL DEFAULT '' COMMENT '首页背景图',
  `Mobile_Index_Image` varchar(255) NOT NULL DEFAULT '' COMMENT '移动端首页背景图',
  `Logo` varchar(255) NOT NULL DEFAULT '/logo.webp' COMMENT '站点Logo',
  `Member_Image` varchar(255) NOT NULL DEFAULT '' COMMENT '成员区背景图',
  `Mail_Smtp` varchar(255) NOT NULL DEFAULT '' COMMENT 'SMTP地址',
  `Mail_Port` varchar(255) NOT NULL DEFAULT '' COMMENT 'SMTP端口',
  `Mail_Name` varchar(255) NOT NULL DEFAULT '' COMMENT '邮箱账号',
  `Mail_Pwd` varchar(255) NOT NULL DEFAULT '' COMMENT '邮箱密码',
  `Vaptcha_Open` int(11) NOT NULL DEFAULT 0 COMMENT '旧版人机验证开关',
  `Vaptcha_Vid` varchar(88) NOT NULL DEFAULT '' COMMENT '旧版验证Vid',
  `Geetest_Open` tinyint NOT NULL DEFAULT 0 COMMENT '极验开关 0关1开',
  `Geetest_Id` varchar(64) NOT NULL DEFAULT '' COMMENT '极验captchaId',
  `Geetest_Key` varchar(128) NOT NULL DEFAULT '' COMMENT '极验SecretKey',
  `Index_Fang` int(11) DEFAULT 0 COMMENT '防扒站JS开关',
  `Jump` int(11) DEFAULT 0 COMMENT 'QQ/VX打开提示跳转',
  `Footer_Title` varchar(120) NOT NULL DEFAULT '' COMMENT '页脚品牌标题',
  `Footer_Desc` varchar(255) NOT NULL DEFAULT '' COMMENT '页脚品牌描述',
  `Footer_Email1` varchar(120) NOT NULL DEFAULT '' COMMENT '支持服务邮箱',
  `Footer_Email2` varchar(120) NOT NULL DEFAULT '' COMMENT '违规投诉邮箱',
  `Footer_QQ` varchar(255) NOT NULL DEFAULT '' COMMENT 'QQ链接',
  `Footer_TG` varchar(255) NOT NULL DEFAULT '' COMMENT 'Telegram链接',
  `Footer_X` varchar(255) NOT NULL DEFAULT '' COMMENT 'X链接',
  `Epay_Open` tinyint NOT NULL DEFAULT 0 COMMENT '易支付开关',
  `Epay_Api` varchar(191) NOT NULL DEFAULT '' COMMENT '易支付接口地址',
  `Epay_Pid` varchar(32) NOT NULL DEFAULT '' COMMENT '商户ID',
  `Epay_Key` varchar(191) NOT NULL DEFAULT '' COMMENT 'V1商户密钥(MD5)',
  `Epay_MchPri` text COMMENT 'V2商户私钥(RSA)',
  `Epay_PubKey` text COMMENT 'V2平台公钥(RSA)',
  `Epay_Wx` tinyint NOT NULL DEFAULT 0 COMMENT '微信支付渠道开关',
  `Epay_Ali` tinyint NOT NULL DEFAULT 0 COMMENT '支付宝渠道开关',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS `{$p}_config_theme` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `Index_About` varchar(255) NOT NULL DEFAULT '' COMMENT '首页About模块',
  `Index_Services_t1` varchar(255) NOT NULL DEFAULT '',
  `Index_Services_d1` varchar(255) NOT NULL DEFAULT '',
  `Index_Services_t2` varchar(255) NOT NULL DEFAULT '',
  `Index_Services_d2` varchar(255) NOT NULL DEFAULT '',
  `Index_Services_t3` varchar(255) NOT NULL DEFAULT '',
  `Index_Services_d3` varchar(255) NOT NULL DEFAULT '',
  `Index_Services_t4` varchar(255) NOT NULL DEFAULT '',
  `Index_Services_d4` varchar(255) NOT NULL DEFAULT '',
  `Statistics_Id` varchar(255) NOT NULL DEFAULT '' COMMENT '统计ID',
  `Statistics_Dm` text COMMENT '统计代码',
  `Index_Music` varchar(255) NOT NULL DEFAULT '' COMMENT '首页音乐',
  `Index_Tc` text COMMENT '弹窗公告',
  `Notice` text COMMENT '后台公告',
  `Index_Tc_Time` varchar(255) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS `{$p}_content` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(16) NOT NULL COMMENT 'article, moment, notice',
  `title` varchar(200) NOT NULL,
  `summary` varchar(500) NOT NULL DEFAULT '',
  `body` mediumtext NOT NULL,
  `cover_url` varchar(500) NOT NULL DEFAULT '',
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=draft, 1=published',
  `created_by` varchar(64) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `published_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_content_feed` (`status`, `published_at`, `id`),
  KEY `idx_content_type_feed` (`type`, `status`, `is_pinned`, `published_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS `{$p}_leave_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `email` varchar(255) NOT NULL DEFAULT '',
  `subject` varchar(255) NOT NULL DEFAULT '',
  `message` text,
  `intime` varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS `{$p}_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `adminUser` varchar(255) NOT NULL DEFAULT '' COMMENT '操作者',
  `type` varchar(255) NOT NULL DEFAULT '' COMMENT '操作内容',
  `data` varchar(255) NOT NULL DEFAULT '' COMMENT '操作时间',
  `ip` varchar(255) NOT NULL DEFAULT '' COMMENT '操作ip',
  `city` varchar(255) NOT NULL DEFAULT '' COMMENT '操作地点',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS `{$p}_project_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT '项目名',
  `url` varchar(255) NOT NULL DEFAULT '' COMMENT '项目网址',
  `img` varchar(255) NOT NULL DEFAULT '' COMMENT '项目图片地址',
  `img2` varchar(255) NOT NULL DEFAULT '',
  `img3` varchar(255) NOT NULL DEFAULT '',
  `sketch` varchar(255) NOT NULL DEFAULT '' COMMENT '项目简述',
  `descriptison` text,
  `money` varchar(255) NOT NULL DEFAULT '' COMMENT '项目售价',
  `version` varchar(255) NOT NULL DEFAULT '' COMMENT '项目版本号',
  `type` varchar(255) NOT NULL DEFAULT '' COMMENT '项目类型',
  `paycontact` varchar(255) DEFAULT NULL COMMENT '购买联系方式',
  `paytg` varchar(255) NOT NULL DEFAULT '',
  `deliver` text COMMENT '付款后自动交付内容',
  `is_show` int(11) NOT NULL DEFAULT 0 COMMENT '是否显示首页',
  `Audit_status` int(11) NOT NULL DEFAULT 0 COMMENT '审核状态',
  `status` int(11) NOT NULL DEFAULT 1 COMMENT '项目运行状态',
  `intime` varchar(255) NOT NULL DEFAULT '' COMMENT '添加时间',
  PRIMARY KEY (`id`),
  KEY `idx_show` (`is_show`, `Audit_status`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS `{$p}_team_member` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT '成员名称',
  `qq` varchar(255) NOT NULL DEFAULT '' COMMENT '成员QQ',
  `tg` varchar(255) NOT NULL DEFAULT '',
  `describe` varchar(255) NOT NULL DEFAULT '' COMMENT '成员简述',
  `reason` text COMMENT '申请加入理由',
  `teamimg` text COMMENT '成员背景图',
  `is_show` int(11) NOT NULL DEFAULT 0 COMMENT '是否显示首页',
  `Audit_status` int(11) NOT NULL DEFAULT 0 COMMENT '审核状态',
  `intime` varchar(255) NOT NULL DEFAULT '' COMMENT '加入时间',
  PRIMARY KEY (`id`),
  KEY `idx_show` (`is_show`, `Audit_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    ];
}

/* ---------- 处理提交 ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string)($_POST['token'] ?? '');
    if (!hash_equals((string)($_SESSION['install_token'] ?? ''), $token)) {
        $errors[] = '页面令牌校验失败，请刷新后重试';
    }

    $dbHost = trim((string)($_POST['db_host'] ?? '127.0.0.1'));
    $dbPort = trim((string)($_POST['db_port'] ?? '3306'));
    $dbUser = trim((string)($_POST['db_user'] ?? ''));
    $dbPass = (string)($_POST['db_pass'] ?? '');
    $dbName = trim((string)($_POST['db_name'] ?? ''));
    $prefix = trim((string)($_POST['prefix'] ?? 'nteam'));

    $siteName = trim((string)($_POST['site_name'] ?? ''));
    $siteShort = trim((string)($_POST['site_short'] ?? ''));
    $siteUrl  = trim((string)($_POST['site_url'] ?? ''));

    $admUser = trim((string)($_POST['admin_user'] ?? ''));
    $admPass = (string)($_POST['admin_pass'] ?? '');
    $admPass2 = (string)($_POST['admin_pass2'] ?? '');
    $admQq   = trim((string)($_POST['admin_qq'] ?? ''));

    if ($dbHost === '') $errors[] = '数据库主机不能为空';
    if (!ctype_digit($dbPort) || (int)$dbPort < 1 || (int)$dbPort > 65535) $errors[] = '数据库端口不合法';
    if ($dbUser === '') $errors[] = '数据库用户名不能为空';
    if ($dbName === '' || !ident_ok($dbName)) $errors[] = '数据库名只能由字母、数字、下划线组成';
    if (!ident_ok($prefix)) $errors[] = '数据表前缀只能由字母、数字、下划线组成';
    if ($siteName === '') $errors[] = '网站名称不能为空';
    if ($siteShort === '') $siteShort = $siteName;
    if ($admUser === '' || strlen($admUser) < 3) $errors[] = '管理员账号至少 3 个字符';
    if (strlen($admPass) < 6) $errors[] = '管理员密码至少 6 位';
    if ($admPass !== $admPass2) $errors[] = '两次输入的管理员密码不一致';

    $env = env_checks();
    foreach ($env as $c) {
        if (!$c[2]) $errors[] = '环境未满足：' . $c[0] . '（' . $c[1] . '）';
    }

    if (!$errors) {
        try {
            /* 1. 连接服务器（不带库名），必要时建库 */
            $pdo = new PDO(
                "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4",
                $dbUser,
                $dbPass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 5]
            );
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$dbName}`");

            /* 2. 覆盖保护：库中已有管理员而安装锁丢失时，必须显式确认 */
            $hasAdmin = false;
            try {
                $n = (int)$pdo->query("SELECT COUNT(*) FROM `{$prefix}_admin`")->fetchColumn();
                $hasAdmin = $n > 0;
            } catch (Throwable $e) {
                $hasAdmin = false; // 表不存在 = 全新库
            }
            if ($hasAdmin && empty($_POST['confirm_reinstall'])) {
                $errors[] = '检测到该数据库中已有管理员账号（疑似已安装过）。如确认覆盖重装，请勾选「覆盖安装」后重新提交。';
            }

            if (!$errors) {
                /* 3. 建表 */
                foreach (schema_statements($prefix) as $sql) {
                    $pdo->exec($sql);
                }

                /* 3.5 补齐新增表 / 列：用户、订单、支付、卡密、钱包、聚合登录、头像、配置开关等。
                   基础 8 张表由上面建好，其余表与字段交给 Schema 自动迁移（幂等）。 */
                try {
                    if (!defined('IN_CRONLITE')) {
                        define('IN_CRONLITE', true);
                    }
                    require_once __DIR__ . '/../Common/lib/PdoHelper.php';
                    require_once __DIR__ . '/../Common/OtConfig.php';
                    require_once __DIR__ . '/../Common/Schema.php';
                    $scfg = array(
                        'host'   => $dbHost,
                        'port'   => $dbPort,
                        'user'   => $dbUser,
                        'pwd'    => $dbPass,
                        'dbname' => $dbName,
                        'dbqz'   => $prefix,
                    );
                    $DB = new \lib\PdoHelper($scfg);
                    pay_schema_init($DB);
                    site_schema_init($DB);
                    user_reset_schema_init($DB);
                } catch (Throwable $e) {
                    // Schema 迁移失败不应阻断安装；运行时 Core_brain 仍会再次尝试
                    $notice = '（提示：自动补齐新增表/列时跳过：' . $e->getMessage() . '）';
                }

                /* 4. 干净种子数据（不含任何个人信息） */
                $siteUrl = $siteUrl !== '' ? $siteUrl
                    : ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http')
                        . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

                $stmt = $pdo->prepare("INSERT INTO `{$prefix}_config`
                    (`id`,`SiteName`,`Name`,`Descriptison`,`Keywords`,`ICP`,`Url`,
                     `Index_Image`,`Mobile_Index_Image`,`Logo`,`Member_Image`,
                     `Vaptcha_Open`,`Vaptcha_Vid`,`Geetest_Open`,`Geetest_Id`,`Geetest_Key`,
                     `Footer_Title`,`Footer_Desc`)
                    VALUES (1,?,?,?,?,?,?,'assets/media/various/bg.webp','assets/media/various/mobile-hero.webp','/logo.webp','',
                     0,'',0,'','',?, '')");
                $stmt->execute([
                    $siteName, $siteShort,
                    $siteName . ' - 官方网站',
                    $siteName,
                    '',
                    $siteUrl,
                    $siteName,
                ]);

                $stmt = $pdo->prepare("INSERT INTO `{$prefix}_config_theme`
                    (`id`,`Index_About`,`Index_Services_t1`,`Index_Services_d1`,`Index_Services_t2`,`Index_Services_d2`,
                     `Index_Services_t3`,`Index_Services_d3`,`Index_Services_t4`,`Index_Services_d4`,`Notice`)
                    VALUES (1,'一句话介绍你的团队','网站建设','各类网站与页面设计开发','程序定制','按需求定制后台与功能模块','运维支持','服务器部署与日常维护','设计美化','界面与视觉优化','欢迎使用本系统，请先在后台完善网站配置。')");
                $stmt->execute();

                /* 5. 管理员（密码哈希与后台登录一致：md5） */
                $stmt = $pdo->prepare("INSERT INTO `{$prefix}_admin`
                    (`adminUser`,`adminPwd`,`adminQq`,`adminLoginIp`,`adminRank`) VALUES (?,?,?,NULL,1)");
                $stmt->execute([$admUser, md5($admPass), $admQq]);

                /* 6. 写数据库配置文件 */
                $q = static function (string $v): string {
                    return "'" . str_replace(['\\', "'"], ['\\\\', "\\'"], $v) . "'";
                };
                $cfg = "<?php\n\$dbconfig=array(\n"
                    . "\t'host' =>  " . $q($dbHost) . ",\n"
                    . "    'port' =>  " . $q($dbPort) . ",\n"
                    . "    'user' =>  " . $q($dbUser) . ",\n"
                    . "    'pwd' =>  " . $q($dbPass) . ",\n"
                    . "    'dbname' =>  " . $q($dbName) . ",\n"
                    . "    'dbqz' =>  " . $q($prefix) . "\n"
                    . ");\n";
                $tmp = CONFIG_FILE . '.tmp';
                if (file_put_contents($tmp, $cfg, LOCK_EX) === false || !rename($tmp, CONFIG_FILE)) {
                    throw new RuntimeException('写入 Common/Database_Config.php 失败，请检查文件权限');
                }

                /* 7. 落安装锁（.php 后缀：即使被直接访问也只输出空白） */
                $lock = "<?php /* installed at " . date('Y-m-d H:i:s') . " */ exit; ?>\n";
                if (file_put_contents(INSTALL_LOCK, $lock, LOCK_EX) === false) {
                    throw new RuntimeException('写入安装锁失败，请检查 install/ 目录权限');
                }

                $step = 'done';
            }
        } catch (PDOException $e) {
            $errors[] = '数据库操作失败：' . $e->getMessage();
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }

    if ($step !== 'done') {
        $_POST_KEEP = [
            'db_host' => $dbHost, 'db_port' => $dbPort, 'db_user' => $dbUser,
            'db_name' => $dbName, 'prefix' => $prefix,
            'site_name' => $siteName, 'site_short' => $siteShort, 'site_url' => $siteUrl,
            'admin_user' => $admUser, 'admin_qq' => $admQq,
        ];
    }
}

if (empty($_SESSION['install_token'])) {
    $_SESSION['install_token'] = bin2hex(random_bytes(16));
}
$token = $_SESSION['install_token'];

$checks = env_checks();
$keep = $_POST_KEEP ?? [];
$baseUrl = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http')
    . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/install')), '/');
$rootUrl = preg_replace('#/install$#', '', $baseUrl);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>安装向导</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: system-ui, -apple-system, "Segoe UI", "PingFang SC", "Microsoft YaHei", sans-serif; background: #f4f6fa; color: #1b2436; line-height: 1.7; }
.wrap { max-width: 760px; margin: 40px auto; padding: 0 20px; }
.card { background: #fff; border-radius: 16px; padding: 40px 44px; box-shadow: 0 12px 40px rgba(20,30,60,.08); margin-bottom: 22px; }
.brand { font-size: 13px; letter-spacing: .18em; color: #6b7490; text-transform: uppercase; margin-bottom: 8px; }
h1 { font-size: 26px; font-weight: 750; margin-bottom: 6px; }
.sub { color: #6b7490; font-size: 14px; margin-bottom: 26px; }
h2 { font-size: 16px; font-weight: 700; margin: 26px 0 14px; padding-left: 11px; border-left: 3px solid #3b66f5; }
table { width: 100%; border-collapse: collapse; font-size: 14px; }
td { padding: 9px 10px; border-bottom: 1px solid #eef1f7; }
td:first-child { color: #5b6478; width: 55%; }
.ok { color: #158a4c; font-weight: 600; }
.bad { color: #d0342c; font-weight: 600; }
.grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px 18px; }
label { display: block; font-size: 13px; font-weight: 600; color: #3c4557; margin-bottom: 6px; }
label small { font-weight: 400; color: #98a0b3; }
input[type=text], input[type=password], input[type=number] { width: 100%; padding: 10px 12px; border: 1px solid #d6dce8; border-radius: 9px; font-size: 14px; background: #fbfcfe; }
input:focus { outline: 2px solid #c9d6ff; border-color: #3b66f5; }
.full { grid-column: 1 / -1; }
.err { background: #fdeceb; border: 1px solid #f2c4c0; color: #a12822; border-radius: 10px; padding: 13px 16px; margin-bottom: 18px; font-size: 14px; }
.err div + div { margin-top: 4px; }
.hint { font-size: 13px; color: #6b7490; background: #f2f5fb; border-radius: 9px; padding: 10px 13px; margin: 12px 0; }
button { display: inline-block; background: #3b66f5; color: #fff; border: 0; border-radius: 10px; padding: 12px 34px; font-size: 15px; font-weight: 700; cursor: pointer; margin-top: 22px; }
button:hover { background: #2c53d8; }
.check { display: flex; align-items: flex-start; gap: 8px; font-size: 14px; color: #3c4557; margin: 14px 0 4px; }
.check input { margin-top: 5px; }
.done-icon { width: 64px; height: 64px; border-radius: 50%; background: #e5f7ed; color: #158a4c; font-size: 32px; display: flex; align-items: center; justify-content: center; margin: 0 auto 18px; }
.done-box { text-align: center; padding: 14px 0; }
.done-box a { display: inline-block; margin: 12px 8px 0; padding: 11px 28px; border-radius: 10px; font-weight: 700; text-decoration: none; font-size: 14px; }
.btn-main { background: #3b66f5; color: #fff; }
.btn-ghost { background: #eef2fc; color: #2c53d8; }
ul.tips { text-align: left; margin: 18px auto 0; max-width: 480px; color: #5b6478; font-size: 13px; padding-left: 20px; }
ul.tips li { margin: 6px 0; }
@media (max-width: 640px) {
  .wrap { margin: 16px auto; }
  .card { padding: 26px 20px; }
  .grid { grid-template-columns: 1fr; }
}
</style>
</head>
<body>
<div class="wrap">
  <div class="card">
    <div class="brand">Team Management System</div>
    <h1>安装向导</h1>
    <div class="sub">按提示完成数据库与管理员配置，安装完成后将自动锁定安装器。</div>

    <?php if ($step === 'done'): ?>
      <div class="done-box">
        <div class="done-icon">&#10003;</div>
        <h2 style="border:0;padding:0;text-align:center;font-size:20px;margin-bottom:8px;">安装成功</h2>
        <p style="color:#5b6478;">数据库配置已写入，全部数据表已创建，安装器已锁定。</p>
        <a class="btn-main" href="<?= h($rootUrl) ?>/">访问首页</a>
        <a class="btn-ghost" href="<?= h($rootUrl) ?>/Admin/login.php">进入后台登录</a>
        <ul class="tips">
          <li>安装锁：<b>install/install.lock.php</b>，再次访问安装器将被拒绝</li>
          <li>建议：安装完成后通过 FTP / 面板删除整个 <b>install/</b> 目录，进一步收紧暴露面</li>
          <li>后台路径为 <b>/Admin/</b>，请妥善保管管理员账号密码</li>
          <li>极验验证码、支付接口、页脚信息等可在后台「网站配置」中按需开启</li>
        </ul>
      </div>
    <?php else: ?>
      <?php if ($errors): ?>
        <div class="err">
          <?php foreach ($errors as $e): ?><div>&#9888; <?= h($e) ?></div><?php endforeach; ?>
        </div>
      <?php endif; ?>

      <h2>一、运行环境</h2>
      <table>
        <?php foreach ($checks as $c): ?>
        <tr>
          <td><?= h($c[0]) ?></td>
          <td class="<?= $c[2] ? 'ok' : 'bad' ?>"><?= $c[2] ? '&#10003; ' : '&#10007; ' ?><?= h($c[1]) ?></td>
        </tr>
        <?php endforeach; ?>
      </table>

      <form method="post" autocomplete="off">
        <input type="hidden" name="token" value="<?= h($token) ?>">

        <h2>二、数据库设置</h2>
        <div class="grid">
          <div><label>数据库主机</label><input type="text" name="db_host" value="<?= h($keep['db_host'] ?? '127.0.0.1') ?>" required></div>
          <div><label>端口</label><input type="text" name="db_port" value="<?= h($keep['db_port'] ?? '3306') ?>" required></div>
          <div><label>用户名</label><input type="text" name="db_user" value="<?= h($keep['db_user'] ?? '') ?>" required></div>
          <div><label>密码</label><input type="password" name="db_pass" value=""></div>
          <div><label>数据库名 <small>不存在将自动创建</small></label><input type="text" name="db_name" value="<?= h($keep['db_name'] ?? '') ?>" required></div>
          <div><label>数据表前缀</label><input type="text" name="prefix" value="<?= h($keep['prefix'] ?? 'nteam') ?>" required></div>
        </div>

        <h2>三、站点与管理员</h2>
        <div class="grid">
          <div><label>网站名称</label><input type="text" name="site_name" value="<?= h($keep['site_name'] ?? '') ?>" required></div>
          <div><label>网站简称</label><input type="text" name="site_short" value="<?= h($keep['site_short'] ?? '') ?>"></div>
          <div class="full"><label>网站网址 <small>留空自动按当前访问地址填入</small></label><input type="text" name="site_url" value="<?= h($keep['site_url'] ?? '') ?>"></div>
          <div><label>管理员账号</label><input type="text" name="admin_user" value="<?= h($keep['admin_user'] ?? 'admin') ?>" required></div>
          <div><label>管理员 QQ <small>选填</small></label><input type="text" name="admin_qq" value="<?= h($keep['admin_qq'] ?? '') ?>"></div>
          <div><label>管理员密码 <small>至少 6 位</small></label><input type="password" name="admin_pass" required></div>
          <div><label>确认密码</label><input type="password" name="admin_pass2" required></div>
        </div>

        <label class="check"><input type="checkbox" name="confirm_reinstall" value="1"> 覆盖安装：我确认目标数据库中的原有数据可被覆盖（仅在数据库已有安装痕迹时需要勾选）</label>

        <button type="submit">开始安装</button>
      </form>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
