<?php
/**
 * 全站统一页脚（单源）。首页 / 作品 / 资讯 / 用户中心共用。
 * 桌面四栏网格，移动端塌缩为「品牌 + 链接胶囊 + 联系方式 + 社交」紧凑布局。
 * 依赖 works_helper.php 的 wk_h()。
 */
if (!defined('IN_CRONLITE')) {
    exit('Access denied');
}
require_once __DIR__ . '/works_helper.php';

$foot_nav = array(
    array('label' => '首页',       'href' => '/index.php'),
    array('label' => '作品项目',   'href' => '/works.php'),
    array('label' => '公告与动态', 'href' => '/news.php'),
);
$foot_res = array(
    array('label' => '关于我们',   'href' => '/about.php'),
    array('label' => '赞助打赏',   'href' => '/donate.php'),
    array('label' => '免责声明',   'href' => '/disclaimer.php', 'blank' => true),
);

/* 站点 Logo：后台可配，路径归一走 site_logo_url()（页头 PC 端同源） */
$foot_logo = site_logo_url();
?>
<footer id="page-footer">
    <div class="content content-full">
        <div class="site-footer">
            <div class="site-footer-statistics" id="statistics-widget"></div>

            <div class="site-footer-grid">
                <!-- 品牌 -->
                <div class="site-footer-brand">
                    <div class="site-footer-logo">
                        <img src="<?php echo wk_h($foot_logo); ?>" alt="<?php echo wk_h(conf('Name')); ?>">
                    </div>
                    <p class="site-footer-company"><?php echo conf('Footer_Title'); ?></p>
                    <p class="site-footer-address"><?php echo conf('Footer_Desc'); ?></p>
                </div>

                <!-- 网站导航 -->
                <div class="site-footer-col">
                    <h6 class="site-footer-heading">网站导航</h6>
                    <ul class="site-footer-links">
                        <?php foreach ($foot_nav as $fl) { ?>
                        <li><a href="<?php echo wk_h($fl['href']); ?>"><?php echo wk_h($fl['label']); ?></a></li>
                        <?php } ?>
                    </ul>
                </div>

                <!-- 资源链接 -->
                <div class="site-footer-col">
                    <h6 class="site-footer-heading">资源链接</h6>
                    <ul class="site-footer-links">
                        <?php foreach ($foot_res as $fl) { ?>
                        <li><a href="<?php echo wk_h($fl['href']); ?>"<?php echo !empty($fl['blank']) ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>><?php echo wk_h($fl['label']); ?></a></li>
                        <?php } ?>
                    </ul>
                </div>

                <!-- 联系方式 -->
                <div class="site-footer-col">
                    <h6 class="site-footer-heading">联系方式</h6>
                    <ul class="site-footer-links site-footer-contact">
                        <?php if (conf('Footer_Email1') != '') { ?>
                        <li>
                            <span class="site-footer-label">支持服务</span>
                            <a href="mailto:<?php echo conf('Footer_Email1'); ?>"><?php echo conf('Footer_Email1'); ?></a>
                        </li>
                        <?php } ?>
                        <?php if (conf('Footer_Email2') != '') { ?>
                        <li>
                            <span class="site-footer-label">违规投诉</span>
                            <a href="mailto:<?php echo conf('Footer_Email2'); ?>"><?php echo conf('Footer_Email2'); ?></a>
                        </li>
                        <?php } ?>
                    </ul>
                </div>

                <!-- 社交图标：独立一行，桌面贴在联系方式栏下方，移动端整行居中 -->
                <div class="site-footer-social-icons">
                    <?php if (conf('Footer_QQ') != '') { ?>
                    <a class="site-footer-social-icon" href="<?php echo conf('Footer_QQ'); ?>" target="_blank" rel="noopener noreferrer" title="QQ" aria-label="QQ">
                        <i class="fa-brands fa-qq"></i>
                        <span class="site-footer-social-label">QQ</span>
                    </a>
                    <?php } ?>
                    <?php if (conf('Footer_TG') != '') { ?>
                    <a class="site-footer-social-icon" href="<?php echo conf('Footer_TG'); ?>" target="_blank" rel="noopener noreferrer" title="Telegram" aria-label="Telegram">
                        <i class="fa-brands fa-telegram"></i>
                        <span class="site-footer-social-label">Telegram</span>
                    </a>
                    <?php } ?>
                    <?php if (conf('Footer_X') != '') { ?>
                    <a class="site-footer-social-icon" href="<?php echo conf('Footer_X'); ?>" target="_blank" rel="noopener noreferrer" title="X (Twitter)" aria-label="X (Twitter)">
                        <i class="fa-brands fa-x-twitter"></i>
                        <span class="site-footer-social-label">X (Twitter)</span>
                    </a>
                    <?php } ?>
                </div>
            </div>

            <div class="site-footer-bottom">
                <div class="site-footer-bottom-inner">
                    <span class="site-footer-copyright">Copyright &copy; 2022 - <span data-toggle="year-copy"></span> <?php echo wk_h(conf('Name')); ?>. All rights reserved.</span>
                    <span class="site-footer-divider">&middot;</span>
                    <a class="site-footer-icp" href="//beian.miit.gov.cn/" target="_blank" rel="noopener noreferrer"><i class="fa fa-location-arrow"></i><?php echo conf('ICP'); ?></a>
                </div>
            </div>
        </div>
    </div>
</footer>
