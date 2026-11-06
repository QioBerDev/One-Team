<?php
/**
 * 帮助中心 FAQ（OneTool console/user/faq.html 版式）
 *
 * 内容由后台「用户中心运营 → FAQ 条目」维护，一行一条「问题|答案」。
 * 开关 Faq_Open 关闭时侧栏不出现，直接访问本页给出统一提示。
 * 分组：1 使用说明 / 2 常见问题 / 3 联系客服，卡片间距按原版拉开。
 */
require dirname(__DIR__) . '/Common/Core_brain.php';
require dirname(__DIR__) . '/Common/Schema.php';
require dirname(__DIR__) . '/Common/User.php';
require_once dirname(__DIR__) . '/Common/OtConfig.php';
require __DIR__ . '/_layout.php';

user_require_login('/user/faq.php');
$u = user_current();

if (!ot_on('Faq_Open')) {
    ot_feature_closed($u, '帮助中心', '管理员暂时关闭了帮助中心。', 'faq');
}

$items    = ot_faq_items();
$kfUrl    = function_exists('ot_kf_url') ? ot_kf_url() : '';
$kfQq     = function_exists('ot_kf_qq') ? ot_kf_qq() : '';
$kfNotice = ot_cfg('Kf_Notice');
account_head('帮助中心', $u, 'faq');
?>
<h2 class="content-heading">常见问题 <small class="text-muted fs-sm fw-normal">有问题先看这里</small></h2>

<!-- 1. 使用说明 -->
<div class="block block-rounded">
    <div class="block-header block-header-default">
        <h3 class="block-title"><strong>1.</strong> 使用说明</h3>
        <div class="block-options"><button type="button" class="btn-block-option"><i class="si si-question"></i></button></div>
    </div>
    <div class="block-content block-content-full">
        <p class="fs-sm text-muted mb-3">先看看这几个常用入口，大部分问题都能在这里自己解决。</p>
        <div class="row g-3 items-push ot-quick">
            <?php
            $links = array(
                array('/user/vip.php',     'fa fa-chess-queen', '会员权益', '查看 VIP / SVIP 折扣与免费下载规则'),
                array('/user/order.php',   'fa fa-receipt',     '我的订单', '查订单号、支付状态与交付内容'),
                array('/user/account.php', 'fa fa-user-cog',    '账户设置', '改昵称、绑定邮箱、修改密码'),
            );
            foreach ($links as $l) { ?>
            <div class="col-md-4">
                <a class="block block-rounded block-link-shadow text-center h-100 mb-0" href="<?php echo $l[0]; ?>">
                    <div class="block-content py-4">
                        <i class="<?php echo $l[1]; ?> fa-2x text-primary mb-3"></i>
                        <p class="fw-semibold mb-1"><?php echo $l[2]; ?></p>
                        <p class="fs-xs text-muted mb-0"><?php echo $l[3]; ?></p>
                    </div>
                </a>
            </div>
            <?php } ?>
        </div>
    </div>
</div>

<!-- 2. 常见问题 -->
<div class="block block-rounded">
    <div class="block-header block-header-default">
        <h3 class="block-title"><strong>2.</strong> 常见问题</h3>
        <div class="block-options"><button type="button" class="btn-block-option"><i class="si si-question"></i></button></div>
    </div>
    <div class="block-content block-content-full">
        <?php if (!$items) { ?>
        <div class="alert alert-info mb-0">管理员还没有填写 FAQ 条目，稍后再来看看。</div>
        <?php } else { ?>
        <div id="ot-faq" role="tablist" aria-multiselectable="true">
            <?php foreach ($items as $i => $it) {
                $qid = 'ot_faq_q' . $i;
                $answer = strip_tags((string)$it['a'], '<br><b><strong><i><em><code><a><span>');
                $answer = preg_replace('/javascript\s*:/i', '', $answer);
                $answer = nl2br($answer);
            ?>
            <div class="block block-bordered block-rounded mb-2">
                <div class="block-header" role="tab" id="ot_faq_h<?php echo $i; ?>">
                    <a class="fw-semibold text-body-color-dark" data-bs-toggle="collapse" href="#<?php echo $qid; ?>"
                       aria-expanded="<?php echo $i === 0 ? 'true' : 'false'; ?>" aria-controls="<?php echo $qid; ?>">
                        <span class="text-primary me-1">Q<?php echo $i + 1; ?>.</span> <?php echo wk_h($it['q']); ?>
                    </a>
                </div>
                <div id="<?php echo $qid; ?>" class="collapse<?php echo $i === 0 ? ' show' : ''; ?>" role="tabpanel"
                     aria-labelledby="ot_faq_h<?php echo $i; ?>" data-bs-parent="#ot-faq">
                    <div class="block-content border-top fs-sm py-3"><p class="mb-0"><?php echo $answer; ?></p></div>
                </div>
            </div>
            <?php } ?>
        </div>
        <?php } ?>
    </div>
</div>

<!-- 3. 联系客服：OneTool 首页「联系客服」对应的落地卡片 -->
<div class="block block-rounded bg-gd-dusk">
    <div class="block-content bg-white-5 py-4">
        <div class="row align-items-center g-3">
            <div class="col-md-8">
                <h3 class="h4 fw-bold text-white mb-2"><i class="fa fa-headset me-2"></i> 还没解决问题？联系客服</h3>
                <p class="fs-sm text-white-75 mb-1">
                    <?php if ($kfQq !== '') { ?>客服 QQ：<b class="text-white"><?php echo wk_h($kfQq); ?></b><br><?php } ?>
                    <?php echo wk_h($kfNotice); ?>；反馈时请带上你的 UID 或订单号，处理更快。
                </p>
                <p class="fs-xs text-white-50 mb-0">也可以到 <a class="link-fx text-white" href="/about.php">关于我们</a> 查看其它联系方式。</p>
            </div>
            <div class="col-md-4 text-md-end">
                <?php if ($kfUrl !== '') { ?>
                <a class="btn btn-lg btn-alt-primary px-4" href="<?php echo wk_h($kfUrl); ?>" target="_blank" rel="noopener">
                    <i class="fa-brands fa-qq me-1"></i> 立即联系客服
                </a>
                <?php } else { ?>
                <a class="btn btn-lg btn-alt-primary px-4" href="/about.php"><i class="fa fa-arrow-right me-1"></i> 查看联系方式</a>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
<?php account_foot(); ?>
