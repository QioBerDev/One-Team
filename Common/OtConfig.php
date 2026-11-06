<?php
/**
 * 用户中心 / 登录注册页 / 聚合登录 —— 后台控制器配置定义（唯一真源）
 *
 * 后台 Admin/set.php（登录页左侧大图）、Admin/oauth.php（注册登录 + 聚合登录）、
 * Admin/uconfig.php（用户中心运营）与前台 /user/ 全部读这里。
 * 新增一个开关只需在 ot_config_defs() 加一行：
 *   - DDL 由 site_schema_init() 自动补列
 *   - 后台表单由 ot_render_group() 自动出控件
 *   - 前台由 ot_cfg() / ot_on() 读值，列还没建时用 def 兜底，绝不白屏
 *
 * group: auth 登录页大图 | oauth 注册登录 + 聚合登录 | oauth_hidden 不自动渲染
 *        vip 会员权益 | ops 运营 | agent 代理 | updater 更新器
 * type : text | textarea | bool | num | money | select | image
 */

if (!defined('IN_CRONLITE')) {
    exit('Access denied');
}
/* 防御：被 require/include 混用时不重复声明函数 */
if (function_exists('ot_config_defs')) {
    return;
}

function ot_config_defs()
{
    return array(

        /* ---------------- 登录注册页左侧大图（后台「网站配置」） ---------------- */
        'Auth_Hero_Image' => array(
            'group' => 'auth', 'type' => 'image',
            'label' => 'PC 端左侧大图',
            'def'   => '/assets/onetool/media/photos/photo26@2x.jpg',
            'ddl'   => "VARCHAR(500) NOT NULL DEFAULT ''",
            'hint'  => '填充式铺满左栏（background-size: cover），可从内置图库选、上传，或填完整 URL；大分辨率图不会被裁掉一半',
        ),
        'Auth_Hero_Fit' => array(
            'group' => 'auth', 'type' => 'select', 'label' => '左侧大图填充方式',
            'def' => 'cover', 'ddl' => "VARCHAR(16) NOT NULL DEFAULT 'cover'",
            'options' => array(
                'cover'   => '填充铺满（裁边、不留白）',
                'contain' => '完整显示（不裁切、可能留白）',
            ),
            'hint'  => '大分辨率横图若人物被裁掉，改选「完整显示」即可看到整张图；背景只铺在左栏，不会被右侧表单盖住',
        ),
        'Auth_Hero_Position' => array(
            'group' => 'auth', 'type' => 'select', 'label' => '左侧大图对齐位置',
            'def' => 'center center', 'ddl' => "VARCHAR(32) NOT NULL DEFAULT 'center center'",
            'options' => array(
                'center center' => '居中',
                'center top'    => '靠上',
                'center bottom' => '靠下',
                'left center'   => '靠左',
                'right center'  => '靠右',
            ),
            'hint'  => '填充铺满时决定保留画面的哪一部分',
        ),
        'Auth_Hero_Overlay' => array(
            'group' => 'auth', 'type' => 'select', 'label' => '左侧大图覆盖色',
            'def' => '', 'ddl' => "VARCHAR(32) NOT NULL DEFAULT ''",
            'options' => array(
                ''             => '原版（登录黑色 / 注册棕橙）',
                'bg-black-50'  => '统一 · 黑色半透',
                'bg-earth-op'  => '统一 · 棕橙',
                'bg-black-25'  => '统一 · 浅黑',
                'bg-gd-dusk'   => '统一 · 暮色渐变',
            ),
            'hint' => '只影响 PC 端左栏叠色，右栏表单区保持 Codebase 原生白底',
        ),
        'Auth_Slogan_Login' => array(
            'group' => 'auth', 'type' => 'text', 'label' => '登录页左侧标语',
            'def' => 'Get Inspired and Create.', 'ddl' => "VARCHAR(255) NOT NULL DEFAULT ''",
            'hint' => '原版文案：Get Inspired and Create.',
        ),
        'Auth_Slogan_Reg' => array(
            'group' => 'auth', 'type' => 'text', 'label' => '注册页左侧标语',
            'def' => "We're very happy you are joining our community!",
            'ddl' => "VARCHAR(255) NOT NULL DEFAULT ''",
        ),

        /* ---------------- 彩虹聚合登录（https://u.cccyun.cc/doc.php） ---------------- */
        'Oa_Open' => array(
            'group' => 'oauth', 'type' => 'bool', 'label' => '开启彩虹聚合登录', 'def' => 0,
            'ddl' => "TINYINT NOT NULL DEFAULT 0",
            'hint' => '开启后登录页底部出现第三方登录按钮；未填 AppID / AppKey 时不会渲染',
        ),
        'Oa_AppId' => array(
            'group' => 'oauth', 'type' => 'text', 'label' => '聚合登录 AppID', 'def' => '',
            'ddl' => "VARCHAR(64) NOT NULL DEFAULT ''",
            'hint' => '在彩虹聚合登录后台获取',
        ),
        'Oa_AppKey' => array(
            'group' => 'oauth', 'type' => 'text', 'label' => '聚合登录 AppKey', 'def' => '',
            'ddl' => "VARCHAR(128) NOT NULL DEFAULT ''",
        ),
        'Oa_Callback' => array(
            'group' => 'oauth', 'type' => 'text', 'label' => '回调地址', 'def' => '',
            'ddl' => "VARCHAR(255) NOT NULL DEFAULT ''",
            'hint' => '留空自动使用 https://当前域名/user/oauth.php?act=callback；填了就用填写的完整 URL，并在聚合后台同步配置',
        ),
        'Oa_Auto_Reg' => array(
            'group' => 'oauth', 'type' => 'bool', 'label' => '未绑定时自动创建账号', 'def' => 1,
            'ddl' => "TINYINT NOT NULL DEFAULT 1",
            'hint' => '第三方账号首次登录时自动建号并登录；关闭后必须先有站内账号并绑定',
        ),

        /* ---------------- 注册 / 登录 / QQ 互联（与聚合登录同一个后台板块） ---------------- */
        'Auth_Register_Open' => array(
            'group' => 'oauth', 'type' => 'bool', 'label' => '开放新用户注册', 'def' => 1,
            'ddl' => "TINYINT NOT NULL DEFAULT 1",
            'hint' => '关闭后登录页内的注册视图与「创建账户」隐藏，直接访问 /user/register.php 也会拒绝',
        ),
        'Auth_QQ_Open' => array(
            'group' => 'oauth', 'type' => 'bool', 'label' => 'QQ 互联直连（旧版）', 'def' => 0,
            'ddl' => "TINYINT NOT NULL DEFAULT 0",
            'hint' => '建议改用上面的彩虹聚合登录 qq 渠道；两者都开时优先走聚合登录',
        ),
        'Auth_QQ_AppId' => array(
            'group' => 'oauth', 'type' => 'text', 'label' => 'QQ 互联 AppID', 'def' => '',
            'ddl' => "VARCHAR(64) NOT NULL DEFAULT ''",
        ),
        'Auth_QQ_AppKey' => array(
            'group' => 'oauth', 'type' => 'text', 'label' => 'QQ 互联 AppKey', 'def' => '',
            'ddl' => "VARCHAR(128) NOT NULL DEFAULT ''",
        ),
        'Auth_Reg_QQ' => array(
            'group' => 'oauth', 'type' => 'bool', 'label' => '注册时收集 QQ 号', 'def' => 0,
            'ddl' => "TINYINT NOT NULL DEFAULT 0",
            'hint' => '原版注册表单自带 QQ 字段，开启后写入 nteam_user.qq',
        ),
        'Auth_Reg_Captcha' => array(
            'group' => 'oauth', 'type' => 'bool', 'label' => '注册图形验证码', 'def' => 0,
            'ddl' => "TINYINT NOT NULL DEFAULT 0",
            'hint' => '原版注册表单自带验证码；本站用服务端 SVG 算术验证码，不依赖 GD 扩展',
        ),
        'Auth_Remember_Open' => array(
            'group' => 'oauth', 'type' => 'bool', 'label' => '登录页「记住我」', 'def' => 1,
            'ddl' => "TINYINT NOT NULL DEFAULT 1",
        ),
        'Auth_Forgot_Open' => array(
            'group' => 'oauth', 'type' => 'bool', 'label' => '找回密码入口', 'def' => 1,
            'ddl' => "TINYINT NOT NULL DEFAULT 1",
            'hint' => '关闭后登录/注册页隐藏入口，/user/forgot.php 直接提示未开放',
        ),
        'Auth_Terms_Open' => array(
            'group' => 'oauth', 'type' => 'bool', 'label' => '注册「我同意条款」', 'def' => 1,
            'ddl' => "TINYINT NOT NULL DEFAULT 1",
        ),
        'Auth_Terms_Url' => array(
            'group' => 'oauth', 'type' => 'text', 'label' => '用户条款 / 免责声明地址',
            'def' => '/disclaimer.php', 'ddl' => "VARCHAR(255) NOT NULL DEFAULT ''",
        ),
        /* 渠道勾选由 Admin/oauth.php 自己渲染，这里只登记列名，不自动出控件 */
        'Oa_Types' => array(
            'group' => 'oauth_hidden', 'type' => 'text', 'label' => '启用的登录渠道',
            'def' => 'qq,wx,alipay', 'ddl' => "VARCHAR(255) NOT NULL DEFAULT ''",
        ),

        /* ---------------- 会员方案与权益 ---------------- */
        'Vip_P_Vip_M' => array('group' => 'vip', 'type' => 'money', 'label' => 'VIP · 包月', 'def' => 38.00, 'ddl' => "DECIMAL(10,2) NOT NULL DEFAULT 38.00"),
        'Vip_P_Vip_Q' => array('group' => 'vip', 'type' => 'money', 'label' => 'VIP · 包季', 'def' => 98.00, 'ddl' => "DECIMAL(10,2) NOT NULL DEFAULT 98.00"),
        'Vip_P_Vip_Y' => array('group' => 'vip', 'type' => 'money', 'label' => 'VIP · 年费', 'def' => 298.00, 'ddl' => "DECIMAL(10,2) NOT NULL DEFAULT 298.00"),
        'Vip_P_Svip_M' => array('group' => 'vip', 'type' => 'money', 'label' => 'SVIP · 包月', 'def' => 68.00, 'ddl' => "DECIMAL(10,2) NOT NULL DEFAULT 68.00"),
        'Vip_P_Svip_Q' => array('group' => 'vip', 'type' => 'money', 'label' => 'SVIP · 包季', 'def' => 168.00, 'ddl' => "DECIMAL(10,2) NOT NULL DEFAULT 168.00"),
        'Vip_P_Svip_Y' => array('group' => 'vip', 'type' => 'money', 'label' => 'SVIP · 年费', 'def' => 498.00, 'ddl' => "DECIMAL(10,2) NOT NULL DEFAULT 498.00"),
        'Vip_Discount' => array(
            'group' => 'vip', 'type' => 'num', 'label' => 'VIP 购买源码折扣（%）', 'def' => 80,
            'ddl' => "TINYINT UNSIGNED NOT NULL DEFAULT 80",
            'hint' => '填 80 = 八折，填 100 = 不打折；VIP 实付价 = 原价 × 折扣 / 100',
        ),
        'Svip_Free' => array(
            'group' => 'vip', 'type' => 'bool', 'label' => 'SVIP 全部源码免费下载', 'def' => 0,
            'ddl' => "TINYINT NOT NULL DEFAULT 0",
            'hint' => '开启后 SVIP 在有效期内购买源码直接 0 元下单并自动交付，不需要支付',
        ),

        /* ---------------- 运营：公告 / FAQ / 商店入口 ---------------- */
        'Notice_Open' => array(
            'group' => 'ops', 'type' => 'bool', 'label' => '公告强提醒弹窗', 'def' => 0,
            'ddl' => "TINYINT NOT NULL DEFAULT 0",
            'hint' => '照 OneTool console/index.html：进入用户中心弹 layer 公告，10 秒倒计时 + 「不再显示」',
        ),
        'Notice_Title' => array(
            'group' => 'ops', 'type' => 'text', 'label' => '公告标题', 'def' => '系统公告',
            'ddl' => "VARCHAR(255) NOT NULL DEFAULT ''",
        ),
        'Notice_Content' => array(
            'group' => 'ops', 'type' => 'textarea', 'label' => '公告内容',
            'def' => '欢迎使用用户中心，如有问题请联系客服。', 'ddl' => "TEXT NULL",
            'hint' => '支持简单 HTML；内容为空时不弹窗',
        ),
        'Notice_Once' => array(
            'group' => 'ops', 'type' => 'bool', 'label' => '提供「不再显示」按钮', 'def' => 1,
            'ddl' => "TINYINT NOT NULL DEFAULT 1",
            'hint' => '点过之后写 cookie，7 天内不再弹；改了公告标题会自动重新弹',
        ),
        'Faq_Open' => array(
            'group' => 'ops', 'type' => 'bool', 'label' => '帮助中心 FAQ', 'def' => 1,
            'ddl' => "TINYINT NOT NULL DEFAULT 1",
            'hint' => '开启后用户中心侧栏出现「帮助中心」，指向 /user/faq.php',
        ),
        'Faq_Content' => array(
            'group' => 'ops', 'type' => 'textarea', 'label' => 'FAQ 条目',
            'def' => "如何购买源码？|在「选购源码」里挑项目，支持微信、支付宝和余额支付，付款后自动交付。\n忘记密码怎么办？|登录页点「忘记密码?」，验证码会发到账号绑定的邮箱。\n卡密怎么兑换？|在「卡密激活」输入卡密，余额或会员立即到账。\n余额可以提现吗？|余额仅用于站内购买，不支持提现。\n会员有什么权益？|VIP 购买源码享折扣，SVIP 可免费下载，具体以会员中心说明为准。",
            'ddl' => "TEXT NULL",
            'hint' => '一行一条，格式：问题|答案；答案支持简单 HTML',
        ),
        'Card_Open' => array(
            'group' => 'ops', 'type' => 'bool', 'label' => '卡密激活入口', 'def' => 1,
            'ddl' => "TINYINT NOT NULL DEFAULT 1",
        ),
        'Money_Open' => array(
            'group' => 'ops', 'type' => 'bool', 'label' => '充值余额入口', 'def' => 1,
            'ddl' => "TINYINT NOT NULL DEFAULT 1",
        ),

        /* ---------------- 联系客服 / 清理缓存（OneTool console/head.html + index.html） ---------------- */
        'Kf_Open' => array(
            'group' => 'ops', 'type' => 'bool', 'label' => '联系客服入口', 'def' => 1,
            'ddl' => "TINYINT NOT NULL DEFAULT 1",
            'hint' => '照 OneTool 控制台首页六宫格与侧栏：用户中心、帮助中心底部出现「联系客服」',
        ),
        'Kf_QQ' => array(
            'group' => 'ops', 'type' => 'text', 'label' => '客服 QQ 号', 'def' => '',
            'ddl' => "VARCHAR(16) NOT NULL DEFAULT ''",
            'hint' => '留空自动取后台管理员账号的 QQ 号；Windows 走 tencent://，手机走 mqq://，其它走 wpa.qq.com',
        ),
        'Kf_Notice' => array(
            'group' => 'ops', 'type' => 'text', 'label' => '客服服务时间说明',
            'def' => '工作日 09:00 - 22:00', 'ddl' => "VARCHAR(255) NOT NULL DEFAULT ''",
        ),
        'Cache_Open' => array(
            'group' => 'ops', 'type' => 'bool', 'label' => '清理缓存入口', 'def' => 1,
            'ddl' => "TINYINT NOT NULL DEFAULT 1",
            'hint' => '照 OneTool 侧栏「清理缓存」：只清浏览器本地缓存并刷新，不动服务端文件与数据库',
        ),

        /* ---------------- 代理 / 分站 ---------------- */
        'Agent_Open' => array(
            'group' => 'agent', 'type' => 'bool', 'label' => '代理中心 / 邀请返佣', 'def' => 0,
            'ddl' => "TINYINT NOT NULL DEFAULT 0",
            'hint' => '开启后用户中心侧栏出现「代理中心」，可拿专属邀请链接；好友首次充值按下方比例返佣到余额',
        ),
        'Agent_Rate' => array(
            'group' => 'agent', 'type' => 'num', 'label' => '充值返佣比例（%）', 'def' => 10,
            'ddl' => "TINYINT UNSIGNED NOT NULL DEFAULT 10",
            'hint' => '0–50；返佣走 wallet_change() 的 ref 幂等，同一笔充值不会重复返',
        ),

        /* ---------------- 在线更新器（只读检测） ---------------- */
        'Updater_Open' => array(
            'group' => 'updater', 'type' => 'bool', 'label' => '在线更新检测', 'def' => 0,
            'ddl' => "TINYINT NOT NULL DEFAULT 0",
            'hint' => '后台显示版本信息与远程检测结果；只做只读检测，不自动覆盖线上文件',
        ),
        'Updater_Version' => array(
            'group' => 'updater', 'type' => 'text', 'label' => '当前版本号', 'def' => '2.0.0',
            'ddl' => "VARCHAR(32) NOT NULL DEFAULT ''",
        ),
        'Updater_Check_Url' => array(
            'group' => 'updater', 'type' => 'text', 'label' => '版本检测接口',
            'def' => '', 'ddl' => "VARCHAR(255) NOT NULL DEFAULT ''",
            'hint' => '返回 JSON：{"version":"2.1.0","log":"更新说明","url":"下载地址"}；留空则只显示本地版本',
        ),
    );
}

/** 读配置：列不存在 / 值为空时回退到默认值，前台任何情况下都不会白屏 */
function ot_cfg($key)
{
    $defs = ot_config_defs();
    if (!isset($defs[$key])) {
        return '';
    }
    $v = conf($key);
    if ($v === '' || $v === null || $v === false) {
        return $defs[$key]['def'];
    }
    return $v;
}

/** 开关型配置：非空且为 1 才算开；列未建时用默认值 */
function ot_on($key)
{
    return (int)ot_cfg($key) === 1;
}

/** 数值型配置，带上下限钳制 */
function ot_num($key, $min = 0, $max = 100)
{
    $v = (int)ot_cfg($key);
    return max($min, min($max, $v));
}

/* ---------------- 联系客服（照 OneTool console/index.html 的 UA 适配） ---------------- */

/** 客服 QQ：后台可配；留空回退到后台管理员账号的 QQ，开箱即用 */
function ot_kf_qq()
{
    $qq = preg_replace('/\D/', '', (string)ot_cfg('Kf_QQ'));
    if ($qq !== '') {
        return $qq;
    }
    if (isset($GLOBALS['DB']) && is_object($GLOBALS['DB'])) {
        $a = $GLOBALS['DB']->getRow(
            "SELECT `adminQq` FROM `nteam_admin` WHERE `adminQq`>0 ORDER BY `id` ASC LIMIT 1"
        );
        if ($a && (string)$a['adminQq'] !== '') {
            return preg_replace('/\D/', '', (string)$a['adminQq']);
        }
    }
    return '';
}

/** 按终端生成 QQ 临时会话链接；未开启或没有 QQ 时返回空串 */
function ot_kf_url()
{
    if (!ot_on('Kf_Open')) {
        return '';
    }
    $qq = ot_kf_qq();
    if ($qq === '') {
        return '';
    }
    $ua = strtolower(isset($_SERVER['HTTP_USER_AGENT']) ? (string)$_SERVER['HTTP_USER_AGENT'] : '');
    if (strpos($ua, 'windows nt') !== false) {
        return 'tencent://ContactInfo/?subcmd=ViewInfo&puin=0&uin=' . $qq;
    }
    if (strpos($ua, 'iphone') !== false || strpos($ua, 'ipad') !== false) {
        return 'mqq://im/chat?chat_type=wpa&uin=' . $qq . '&version=1&src_type=web';
    }
    if (strpos($ua, 'android') !== false) {
        return 'mqq://card/show_pslcard?src_type=internal&version=1&uin=' . $qq . '&card_type=person&source=sharecard';
    }
    return 'https://wpa.qq.com/msgrd?v=3&uin=' . $qq . '&site=qq&menu=yes';
}

/* ---------------- 登录页左侧大图：内置图库（已按先生要求精简） ---------------- */

function ot_hero_gallery()
{
    $base = '/assets/onetool/media/photos/';
    return array(
        $base . 'photo26@2x.jpg' => '暖色晨光（photo26）',
        $base . 'background.jpg' => '深色背景（background）',
    );
}

/** 图片地址归一：空用默认，相对路径补根，杜绝 URL 里的引号破坏 style */
function ot_hero_url($raw = null)
{
    $v = trim((string)($raw === null ? ot_cfg('Auth_Hero_Image') : $raw));
    if ($v === '') {
        $v = ot_cfg('Auth_Hero_Image');
    }
    if (!preg_match('#^(https?:)?//#i', $v) && $v[0] !== '/') {
        $v = '/' . $v;
    }
    return str_replace(array("'", '"', '(', ')', '\\', "\n", "\r"), '', $v);
}

/** 填充方式：cover / contain 白名单 */
function ot_hero_fit()
{
    $v = (string)ot_cfg('Auth_Hero_Fit');
    return $v === 'contain' ? 'contain' : 'cover';
}

/** 背景对齐位置：白名单，杜绝 style 注入 */
function ot_hero_position()
{
    $allow = array('center center', 'center top', 'center bottom', 'left center', 'right center');
    $v = (string)ot_cfg('Auth_Hero_Position');
    return in_array($v, $allow, true) ? $v : 'center center';
}

/** 覆盖色：只放行白名单里的 Codebase 类 */
function ot_hero_overlay($page = 'login')
{
    $v = (string)ot_cfg('Auth_Hero_Overlay');
    $allow = array('bg-black-50', 'bg-earth-op', 'bg-black-25', 'bg-gd-dusk');
    if (in_array($v, $allow, true)) {
        return $v;
    }
    return $page === 'register' ? 'bg-earth-op' : 'bg-black-50';
}

/* ---------------- 彩虹聚合登录：渠道定义 ----------------
 * 渠道 code 与 https://u.cccyun.cc/doc.php 的 type 参数一一对应。
 * icon 用 FontAwesome 6 brands；没有对应图标的渠道用中文简称方块。
 */

function oa_channels()
{
    return array(
        'qq'       => array('name' => 'QQ',       'icon' => 'fa-brands fa-qq',      'text' => '',    'color' => '#12b7f5'),
        'wx'       => array('name' => '微信',     'icon' => 'fa-brands fa-weixin',  'text' => '',    'color' => '#07c160'),
        'alipay'   => array('name' => '支付宝',   'icon' => 'fa-brands fa-alipay',  'text' => '',    'color' => '#1677ff'),
        'sina'     => array('name' => '微博',     'icon' => 'fa-brands fa-weibo',   'text' => '',    'color' => '#e6162d'),
        'baidu'    => array('name' => '百度',     'icon' => '',                     'text' => '百',  'color' => '#2932e1'),
        'huawei'   => array('name' => '华为',     'icon' => '',                     'text' => '华',  'color' => '#cf0a2c'),
        'xiaomi'   => array('name' => '小米',     'icon' => '',                     'text' => '米',  'color' => '#ff6900'),
        'douyin'   => array('name' => '抖音',     'icon' => 'fa-brands fa-tiktok',  'text' => '',    'color' => '#161823'),
        'bilibili' => array('name' => '哔哩哔哩', 'icon' => '',                     'text' => 'B',   'color' => '#00a1d6'),
        'dingtalk' => array('name' => '钉钉',     'icon' => '',                     'text' => '钉',  'color' => '#1e9fff'),
    );
}

/** 当前启用的渠道 code 列表：总开关关 / 凭据没填 / 值非法一律返回空 */
function oa_enabled_types()
{
    if (!ot_on('Oa_Open')) {
        return array();
    }
    if (trim((string)ot_cfg('Oa_AppId')) === '' || trim((string)ot_cfg('Oa_AppKey')) === '') {
        return array();
    }
    $all = oa_channels();
    $out = array();
    foreach (explode(',', (string)ot_cfg('Oa_Types')) as $t) {
        $t = strtolower(trim($t));
        if (isset($all[$t])) {
            $out[] = $t;
        }
    }
    return array_values(array_unique($out));
}

/** FAQ 文本 → 条目数组 */
function ot_faq_items()
{
    $raw = (string)ot_cfg('Faq_Content');
    $out = array();
    foreach (preg_split('/\r?\n/u', $raw) as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '|') === false) {
            continue;
        }
        $parts = explode('|', $line, 2);
        $out[] = array('q' => trim($parts[0]), 'a' => trim($parts[1]));
    }
    return $out;
}

/** 后台表单：按 group 自动出控件（set.php / oauth.php / uconfig.php 共用） */
function ot_render_group($group, $cols = 2)
{
    $col = 'col-md-' . (int)(12 / max(1, (int)$cols));
    foreach (ot_config_defs() as $key => $d) {
        if ($d['group'] !== $group) {
            continue;
        }
        $v = (string)ot_cfg($key);
        $id = 'ot_' . $key;
        echo '<div class="' . $col . '"><div class="mb-4">';
        echo '<label class="form-label" for="' . $id . '"><b>' . htmlspecialchars($d['label'], ENT_QUOTES, 'UTF-8') . '</b></label>';

        if ($d['type'] === 'bool') {
            echo '<div class="pt-2">';
            echo '<div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="' . $key . '" id="' . $id . '_1" value="1"' . ($v === '1' ? ' checked' : '') . '>';
            echo '<label class="form-check-label" for="' . $id . '_1">启用</label></div>';
            echo '<div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="' . $key . '" id="' . $id . '_0" value="0"' . ($v !== '1' ? ' checked' : '') . '>';
            echo '<label class="form-check-label" for="' . $id . '_0">禁用</label></div>';
            echo '</div>';
        } elseif ($d['type'] === 'textarea') {
            echo '<textarea class="form-control" rows="6" id="' . $id . '" name="' . $key . '">' . htmlspecialchars($v, ENT_QUOTES, 'UTF-8') . '</textarea>';
        } elseif ($d['type'] === 'select') {
            echo '<select class="form-control form-control-lg" id="' . $id . '" name="' . $key . '">';
            foreach ($d['options'] as $k => $label) {
                echo '<option value="' . htmlspecialchars((string)$k, ENT_QUOTES, 'UTF-8') . '"' . ($v === (string)$k ? ' selected' : '') . '>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</option>';
            }
            echo '</select>';
        } elseif ($d['type'] === 'image') {
            echo '<div class="input-group">';
            echo '<select class="form-control form-control-lg" id="' . $id . '_gallery" onchange="if(this.value){document.getElementById(\'' . $id . '\').value=this.value;otHeroPreview(this.value)}">';
            echo '<option value="">— 选择内置图库 —</option>';
            foreach (ot_hero_gallery() as $url => $label) {
                echo '<option value="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"' . ($v === $url ? ' selected' : '') . '>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</option>';
            }
            echo '</select>';
            echo '<button class="btn btn-secondary" type="button" onclick="document.getElementById(\'' . $id . '_file\').click()">上传</button>';
            echo '</div>';
            echo '<input type="file" id="' . $id . '_file" style="display:none" accept="image/*" onchange="uploadImg(this,\'' . $key . '\')">';
            echo '<input type="text" class="form-control form-control-lg mt-2" id="' . $id . '" name="' . $key . '" value="' . htmlspecialchars($v, ENT_QUOTES, 'UTF-8') . '" placeholder="图片地址" oninput="otHeroPreview(this.value)">';
            /* 预览用 contain 完整显示整张图，不再用 cover 把大分辨率图裁成窄带。
               前台登录页左栏是 cover 填充铺满，这里只负责让先生看清上传的图全貌。 */
            echo '<div class="ot-hero-preview-wrap mt-2">'
               . '<img id="' . $id . '_preview" data-ot-preview="1" src="' . htmlspecialchars(ot_hero_url($v), ENT_QUOTES, 'UTF-8') . '" alt="登录页左侧大图预览">'
               . '</div>';
            echo '<div class="form-text">预览为完整原图；前台登录页左栏会按「填充铺满」(cover) 居中裁切，大分辨率图不会被切掉一半。</div>';
        } else {
            $type = $d['type'] === 'num' || $d['type'] === 'money' ? 'number' : 'text';
            $step = $d['type'] === 'money' ? ' step="0.01"' : '';
            echo '<input type="' . $type . '" class="form-control form-control-lg" id="' . $id . '" name="' . $key . '" value="' . htmlspecialchars($v, ENT_QUOTES, 'UTF-8') . '"' . $step . '>';
        }

        if (!empty($d['hint'])) {
            echo '<div class="form-text">' . htmlspecialchars($d['hint'], ENT_QUOTES, 'UTF-8') . '</div>';
        }
        echo '</div></div>';
    }
}

/** 只接受已登记的配置键，防止任意 POST 键拼进 UPDATE */
function ot_config_keys()
{
    return array_keys(ot_config_defs());
}
