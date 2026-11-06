<?php
/**
 * 用户 / 订单 / 支付 —— 建表与字段补齐
 *
 * 只在表缺失时执行 DDL，请求内静态缓存一次，别每次请求都跑 CREATE。
 * 表前缀统一 nteam_，由 PdoHelper::dealPrefix 处理。
 */

if (!defined('IN_CRONLITE')) {
    exit('Access denied');
}

function pay_schema_init($DB)
{
    static $done = false;
    if ($done) {
        return true;
    }
    $done = true;

    $exists = $DB->query("SELECT 1 FROM `nteam_order` LIMIT 1");

    if ($exists === false) {
        pay_schema_create($DB);
    }

    pay_schema_alter($DB);
    return true;
}

function pay_schema_create($DB)
{
    /* 前台登录用户 */
    $DB->query("CREATE TABLE IF NOT EXISTS `nteam_user` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `username` VARCHAR(32) NOT NULL COMMENT '登录名',
        `email` VARCHAR(120) DEFAULT NULL COMMENT '邮箱，可为空',
        `pass_hash` VARCHAR(255) NOT NULL COMMENT 'password_hash 结果',
        `nickname` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '昵称',
        `faceimg` VARCHAR(400) NOT NULL DEFAULT '' COMMENT '第三方登录头像URL',
        `avatar` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '用户自传头像（uploads/avatar/ 相对路径，空则回落QQ头像）',
        `avatar_frame` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '头像挂件 key（Common/AvatarFrames.php 白名单）',
        `status` TINYINT NOT NULL DEFAULT 1 COMMENT '1正常 0封禁',
        `reg_time` INT UNSIGNED NOT NULL DEFAULT 0,
        `reg_ip` VARCHAR(45) NOT NULL DEFAULT '',
        `last_time` INT UNSIGNED NOT NULL DEFAULT 0,
        `last_ip` VARCHAR(45) NOT NULL DEFAULT '',
        `fail_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '连续登录失败次数',
        `lock_until` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '锁定到期时间',
        `vip_level` TINYINT NOT NULL DEFAULT 0 COMMENT '会员等级 0普通 1VIP 2SVIP',
        `vip_expire` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '会员到期时间 0=未开通',
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_username` (`username`),
        UNIQUE KEY `uk_email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='前台用户'");

    /* 订单 */
    $DB->query("CREATE TABLE IF NOT EXISTS `nteam_order` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `out_trade_no` VARCHAR(32) NOT NULL COMMENT '商户订单号',
        `trade_no` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '平台订单号',
        `user_id` INT UNSIGNED NOT NULL DEFAULT 0,
        `project_id` INT UNSIGNED NOT NULL DEFAULT 0,
        `project_name` VARCHAR(160) NOT NULL DEFAULT '',
        `money` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `pay_type` VARCHAR(16) NOT NULL DEFAULT '',
        `status` TINYINT NOT NULL DEFAULT 0 COMMENT '0待支付 1已支付 2已退款 3已关闭',
        `client_ip` VARCHAR(45) NOT NULL DEFAULT '',
        `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
        `pay_time` INT UNSIGNED NOT NULL DEFAULT 0,
        `notify_time` INT UNSIGNED NOT NULL DEFAULT 0,
        `deliver` TEXT NULL COMMENT '交付内容：下载地址 / 卡密 / 说明',
        `deliver_state` TINYINT NOT NULL DEFAULT 0 COMMENT '0未交付 1已交付',
        `remark` VARCHAR(255) NOT NULL DEFAULT '',
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_out_trade_no` (`out_trade_no`),
        KEY `idx_user` (`user_id`, `create_time`),
        KEY `idx_status` (`status`, `create_time`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='支付订单'");

    /* 回调原始报文，出问题拿它对齐平台侧 */
    $DB->query("CREATE TABLE IF NOT EXISTS `nteam_pay_log` (
        `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `out_trade_no` VARCHAR(32) NOT NULL DEFAULT '',
        `direction` VARCHAR(12) NOT NULL DEFAULT '' COMMENT 'notify/return/submit/query/refund',
        `ok` TINYINT NOT NULL DEFAULT 0 COMMENT '1 通过校验 0 未通过',
        `payload` TEXT NULL,
        `ip` VARCHAR(45) NOT NULL DEFAULT '',
        `created_at` INT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `idx_out` (`out_trade_no`),
        KEY `idx_time` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='支付日志'");

    return true;
}

function pay_schema_alter($DB)
{
    /* 全齐了就一条都别查，正常请求只花几次 SHOW */
    if ($DB->getRow("SHOW COLUMNS FROM `nteam_config` LIKE 'Epay_PubKey'")
        && $DB->getRow("SHOW COLUMNS FROM `nteam_project_list` LIKE 'deliver'")
        && $DB->getRow("SHOW COLUMNS FROM `nteam_user` LIKE 'vip_level'")
        && $DB->getRow("SHOW COLUMNS FROM `nteam_user` LIKE 'money'")
        && $DB->getRow("SHOW COLUMNS FROM `nteam_user` LIKE 'qq_openid'")
        && $DB->getRow("SHOW COLUMNS FROM `nteam_user` LIKE 'faceimg'")
        && $DB->getRow("SHOW COLUMNS FROM `nteam_user` LIKE 'avatar_frame'")
        && $DB->query("SELECT 1 FROM `nteam_card` LIMIT 1")
        && $DB->query("SELECT 1 FROM `nteam_money_log` LIMIT 1")) {
        return true;
    }

    /* nteam_config 补支付开关与商户参数
       Epay_MchPri/Epay_PubKey：V2（RSA）商户私钥/平台公钥
       Epay_Key：V1（MD5）商户密钥
       Epay_Wx/Epay_Ali：微信 / 支付宝渠道开关 */
    $cols = array(
        'Epay_Open'   => "TINYINT NOT NULL DEFAULT 0 COMMENT '易支付开关'",
        'Epay_Api'    => "VARCHAR(191) NOT NULL DEFAULT 'https://pays.org.cn/' COMMENT '接口地址'",
        'Epay_Pid'    => "VARCHAR(32) NOT NULL DEFAULT '' COMMENT '商户ID'",
        'Epay_Key'    => "VARCHAR(191) NOT NULL DEFAULT '' COMMENT 'V1商户密钥(MD5)'",
        'Epay_MchPri' => "TEXT NULL COMMENT 'V2商户私钥(RSA)'",
        'Epay_PubKey' => "TEXT NULL COMMENT 'V2平台公钥(RSA)'",
        'Epay_Wx'     => "TINYINT NOT NULL DEFAULT 0 COMMENT '微信支付渠道开关'",
        'Epay_Ali'    => "TINYINT NOT NULL DEFAULT 0 COMMENT '支付宝渠道开关'",
    );
    foreach ($cols as $name => $def) {
        if (!$DB->getRow("SHOW COLUMNS FROM `nteam_config` LIKE '{$name}'")) {
            $DB->query("ALTER TABLE `nteam_config` ADD COLUMN `{$name}` {$def}");
        }
    }

    /* 项目补一列：付款后自动交付的内容（下载地址 / 网盘链接 / 说明） */
    if (!$DB->getRow("SHOW COLUMNS FROM `nteam_project_list` LIKE 'deliver'")) {
        $DB->query("ALTER TABLE `nteam_project_list`
                    ADD COLUMN `deliver` TEXT NULL COMMENT '付款后自动交付内容'");
    }

    /* 用户补两列：会员等级 / 到期（权益规则另行定义，这里先落库） */
    if (!$DB->getRow("SHOW COLUMNS FROM `nteam_user` LIKE 'vip_level'")) {
        $DB->query("ALTER TABLE `nteam_user`
                    ADD COLUMN `vip_level` TINYINT NOT NULL DEFAULT 0 COMMENT '会员等级 0普通 1VIP 2SVIP',
                    ADD COLUMN `vip_expire` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '会员到期时间 0=未开通'");
    }

    /* 用户余额：所有加减走 wallet_change()，靠条件 UPDATE 保证不会扣成负数 */
    if (!$DB->getRow("SHOW COLUMNS FROM `nteam_user` LIKE 'money'")) {
        $DB->query("ALTER TABLE `nteam_user`
                    ADD COLUMN `money` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '账户余额'");
    }

    /* 登录注册页 / 代理 / QQ 登录要用的三个字段 */
    $userCols = array(
        'qq'         => "VARCHAR(16) NOT NULL DEFAULT '' COMMENT '注册时填写的QQ号'",
        'faceimg'    => "VARCHAR(400) NOT NULL DEFAULT '' COMMENT '第三方登录头像URL'",
        'avatar'     => "VARCHAR(255) NOT NULL DEFAULT '' COMMENT '用户自传头像（uploads/avatar/ 相对路径）'",
        'avatar_frame' => "VARCHAR(32) NOT NULL DEFAULT '' COMMENT '头像挂件 key（AvatarFrames 白名单）'",
        'qq_openid'  => "VARCHAR(64) NOT NULL DEFAULT '' COMMENT 'QQ快捷登录 openid'",
        'invite_by'  => "INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '邀请人UID'",
        'is_admin'   => "TINYINT NOT NULL DEFAULT 0 COMMENT '管理员账号 1是 0否（前台显示切换后台）'",
    );
    foreach ($userCols as $name => $def) {
        if (!$DB->getRow("SHOW COLUMNS FROM `nteam_user` LIKE '{$name}'")) {
            $DB->query("ALTER TABLE `nteam_user` ADD COLUMN `{$name}` {$def}");
        }
    }
    if (!$DB->getRow("SHOW INDEX FROM `nteam_user` WHERE `Key_name`='idx_qq_openid'")) {
        $DB->query("ALTER TABLE `nteam_user` ADD INDEX `idx_qq_openid` (`qq_openid`), ADD INDEX `idx_invite` (`invite_by`)");
    }

    /* 卡密：balance=余额 / vip=VIP天数 / svip=SVIP天数 */
    $DB->query("CREATE TABLE IF NOT EXISTS `nteam_card` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `batch` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '批次号',
        `code` VARCHAR(64) NOT NULL COMMENT '卡密',
        `type` VARCHAR(16) NOT NULL DEFAULT 'balance' COMMENT 'balance/vip/svip',
        `value` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '余额面额',
        `days` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '会员天数',
        `status` TINYINT NOT NULL DEFAULT 0 COMMENT '0未用 1已用 2禁用',
        `user_id` INT UNSIGNED NOT NULL DEFAULT 0,
        `used_time` INT UNSIGNED NOT NULL DEFAULT 0,
        `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
        `remark` VARCHAR(255) NOT NULL DEFAULT '',
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_code` (`code`),
        KEY `idx_batch` (`batch`),
        KEY `idx_status` (`status`),
        KEY `idx_user` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='卡密'");

    /* 余额流水：ref 唯一键做幂等，重复回调 / 并发兑换只会入账一次 */
    $DB->query("CREATE TABLE IF NOT EXISTS `nteam_money_log` (
        `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id` INT UNSIGNED NOT NULL,
        `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '变动额，正入负出',
        `balance` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '变动后余额',
        `type` VARCHAR(24) NOT NULL DEFAULT '' COMMENT 'recharge/card/consume/refund/admin',
        `memo` VARCHAR(255) NOT NULL DEFAULT '',
        `ref` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '幂等键',
        `admin` VARCHAR(64) NOT NULL DEFAULT '',
        `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_ref` (`ref`),
        KEY `idx_user` (`user_id`, `create_time`),
        KEY `idx_type` (`type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='余额流水'");

    /* 前台任意页面也会把聚合登录绑定表建好，避免首次第三方登录时报表不存在 */
    oa_schema_init($DB);
    return true;
}

/**
 * 站点配置补列（与支付无关的杂项）：网站 Logo。
 * 后台「网站配置」保存前调用，列不存在时先建，避免 UPDATE 静默失败。
 */
function site_schema_init($DB)
{
    static $done = false;
    if ($done) {
        return true;
    }
    $done = true;

    if (!$DB->getRow("SHOW COLUMNS FROM `nteam_config` LIKE 'Logo'")) {
        $DB->query("ALTER TABLE `nteam_config`
                    ADD COLUMN `Logo` VARCHAR(255) NOT NULL DEFAULT '/logo.webp' COMMENT '站点Logo'");
    }
    /* 用户中心主题：后台 Admin/set.php 配置，前台 /user/ 全站生效 */
    if (!$DB->getRow("SHOW COLUMNS FROM `nteam_config` LIKE 'User_Theme'")) {
        $DB->query("ALTER TABLE `nteam_config`
                    ADD COLUMN `User_Theme` VARCHAR(32) NOT NULL DEFAULT 'elegance' COMMENT '用户中心默认主题'");
    }
    if (!$DB->getRow("SHOW COLUMNS FROM `nteam_config` LIKE 'User_Theme_Switch'")) {
        $DB->query("ALTER TABLE `nteam_config`
                    ADD COLUMN `User_Theme_Switch` TINYINT NOT NULL DEFAULT 1 COMMENT '允许访客自行切换主题 1是 0否'");
    }

    /* 用户中心 / 登录注册页后台控制器配置列：唯一真源 Common/OtConfig.php。
       新增开关只改 ot_config_defs()，这里自动补列，不用再动 DDL。 */
    require_once __DIR__ . '/OtConfig.php';
    foreach (ot_config_defs() as $key => $d) {
        if (!$DB->getRow("SHOW COLUMNS FROM `nteam_config` LIKE '{$key}'")) {
            $DB->query("ALTER TABLE `nteam_config` ADD COLUMN `{$key}` {$d['ddl']}");
        }
    }

    /* 管理员前台化：nteam_user.is_admin（后台只认这个标记，不再单独维护管理员表） */
    if (!$DB->getRow("SHOW COLUMNS FROM `nteam_user` LIKE 'is_admin'")) {
        $DB->query("ALTER TABLE `nteam_user`
                    ADD COLUMN `is_admin` TINYINT NOT NULL DEFAULT 0 COMMENT '管理员账号 1是 0否',
                    ADD INDEX `idx_is_admin` (`is_admin`)");
    }
    /* 用户自传头像 + 头像挂件：后台只跑 site_schema_init 的页面也要能建列，
       两条 DDL 各自独立，重复执行零影响 */
    if (!$DB->getRow("SHOW COLUMNS FROM `nteam_user` LIKE 'avatar'")) {
        $DB->query("ALTER TABLE `nteam_user`
                    ADD COLUMN `avatar` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '用户自传头像'");
    }
    if (!$DB->getRow("SHOW COLUMNS FROM `nteam_user` LIKE 'avatar_frame'")) {
        $DB->query("ALTER TABLE `nteam_user`
                    ADD COLUMN `avatar_frame` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '头像挂件 key'");
    }
    /* 彩虹聚合登录绑定表 */
    oa_schema_init($DB);

    /* 管理员账号落地到用户中心：把 nteam_admin 的账号镜像进 nteam_user 并打 is_admin=1。
       幂等，只补不覆盖；先生要求「把我的管理员账号也放进用户中心」。 */
    require_once __DIR__ . '/ModeSwitch.php';
    ms_sync_admin($DB);
    return true;
}

/**
 * 彩虹聚合登录绑定表：(type, social_uid) 唯一，一个第三方账号只绑一个站内账号。
 */
function oa_schema_init($DB)
{
    static $done = false;
    if ($done) {
        return true;
    }
    $done = true;
    $DB->query("CREATE TABLE IF NOT EXISTS `nteam_oauth` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '站内用户UID',
        `type` VARCHAR(16) NOT NULL DEFAULT '' COMMENT '聚合登录渠道 code',
        `social_uid` VARCHAR(128) NOT NULL DEFAULT '' COMMENT '第三方唯一UID',
        `nickname` VARCHAR(60) NOT NULL DEFAULT '',
        `faceimg` VARCHAR(400) NOT NULL DEFAULT '',
        `bind_time` INT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_type_uid` (`type`,`social_uid`),
        KEY `idx_user` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='第三方聚合登录绑定'");
    return true;
}

/**
 * 找回密码验证码表（user/forgot.php 用）。
 * code 存哈希；一账号一次只允许一张有效单。
 */
function user_reset_schema_init($DB)
{
    static $done = false;
    if ($done) {
        return true;
    }
    $done = true;

    $DB->query("CREATE TABLE IF NOT EXISTS `nteam_user_reset` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id` INT UNSIGNED NOT NULL,
        `code_hash` VARCHAR(64) NOT NULL COMMENT '验证码哈希',
        `expire` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '过期时间',
        `used` TINYINT NOT NULL DEFAULT 0 COMMENT '0未用 1已用',
        `create_time` INT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `idx_user` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='找回密码验证码'");
    return true;
}
