<?php
include("./Common/Core_brain.php");
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>赞助打赏 - <?php echo conf('Name');?> - <?php echo conf('SiteName');?></title>
    <meta name="keywords" content="赞助,打赏,赞赏,开源支持,<?php echo conf('Keywords');?>">
    <meta name="description" content="如果这个项目对你有帮助，欢迎扫码赞赏，你的支持是我持续维护、迭代功能的动力。">
    <link rel="shortcut icon" href="assets/media/various/favicon.png">
    <link rel="stylesheet" id="css-main" href="assets/css/oneui.min-5.6.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/site-footer.v7.css?v=20261105q">
    <style>
        /* ===== 赞助打赏 ===== */
        .dn-hero {
            position: relative;
            overflow: hidden;
            padding: 4.5rem 0 3.5rem;
            text-align: center;
            background:
                radial-gradient(900px 300px at 50% -55%, rgba(246, 178, 64, 0.18), transparent 72%),
                #fff;
        }
        .dn-hero::after {
            content: "";
            position: absolute;
            inset: auto 0 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(246, 178, 64, 0.3), transparent);
        }
        .dn-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.32rem 0.85rem;
            margin-bottom: 1.15rem;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.06em;
            color: #c8871b;
            background: rgba(246, 178, 64, 0.12);
            border: 1px solid rgba(246, 178, 64, 0.28);
            border-radius: 50rem;
        }
        .dn-hero h1 {
            margin: 0 0 0.75rem;
            font-size: clamp(1.75rem, 3vw, 2.5rem);
            font-weight: 800;
            letter-spacing: -0.01em;
        }
        .dn-hero .dn-sub-title {
            margin: 0;
            font-size: 1rem;
            font-weight: 500;
            color: #5b6b80;
        }

        .dn-card {
            max-width: 30rem;
            margin: 0 auto;
            padding: 2.25rem 1.75rem 1.75rem;
            text-align: center;
            background: #fff;
            border: 1px solid #eceff4;
            border-radius: 1rem;
            box-shadow: 0 18px 50px -28px rgba(31, 45, 61, 0.35);
        }
        .dn-card h2 {
            margin: 0 0 0.6rem;
            font-size: 1.3rem;
            font-weight: 800;
            color: #253041;
        }
        .dn-card .dn-desc {
            margin: 0 0 1.5rem;
            font-size: 0.9rem;
            line-height: 1.8;
            color: #5b6b80;
        }
        .dn-qr {
            display: block;
            width: min(280px, 72vw);
            height: auto;
            margin: 0 auto 1rem;
            border-radius: 0.85rem;
            border: 1px solid #eee;
            padding: 0.6rem;
            background: #fff;
            box-shadow: 0 10px 30px -18px rgba(31, 45, 61, 0.4);
        }
        .dn-qr-empty {
            width: min(280px, 72vw);
            margin: 0 auto 1rem;
            padding: 2rem 1rem;
            font-size: 0.85rem;
            line-height: 1.9;
            color: #97a3b4;
            border: 1px dashed #d8dee8;
            border-radius: 0.85rem;
            background: #fafbfd;
        }
        .dn-qr-empty code {
            color: #c8871b;
            background: rgba(246, 178, 64, 0.12);
            padding: 0.05rem 0.35rem;
            border-radius: 0.3rem;
            font-size: 0.82em;
        }
        .dn-tip {
            margin: 0;
            font-size: 0.82rem;
            color: #97a3b4;
        }
        .dn-thanks {
            margin: 1.75rem auto 0;
            max-width: 34rem;
            text-align: center;
            font-size: 0.85rem;
            line-height: 1.9;
            color: #7c8aa0;
        }
        .dark-mode .dn-hero { background: radial-gradient(900px 300px at 50% -55%, rgba(246, 178, 64, 0.16), transparent 72%), #1b2432; }
        .dark-mode .dn-hero h1 { color: #eef2f7; }
        .dark-mode .dn-hero .dn-sub-title { color: #b3c0d1; }
        .dark-mode .dn-card { background: #1f2937; border-color: #2f3d4f; }
        .dark-mode .dn-card h2 { color: #e6ecf4; }
        .dark-mode .dn-card .dn-desc { color: #b3c0d1; }
        .dark-mode .dn-qr { background: #fff; }
        .dark-mode .dn-qr-empty { background: #18222f; border-color: #2f3d4f; color: #8593a6; }
        @media (max-width: 991.98px) {
            .dn-hero { padding: 3rem 0 2.5rem; }
            .dn-card { padding: 1.75rem 1.25rem 1.5rem; }
        }
    </style>
</head>
<body>
    <div id="page-container" class="page-header-fixed main-content-boxed">
        <header id="page-header">
            <div class="content-header">
                <div class="d-flex align-items-center">
                    <button type="button" class="btn btn-alt-secondary d-lg-none me-2" onclick="window.location.href='index.php'" aria-label="返回首页"><i class="fa fa-arrow-left"></i></button>
                    <a class="fw-bold fs-lg tracking-wider text-dual me-2" href="index.php">
                        <img class="hd-brand-logo" src="<?php echo htmlspecialchars(site_logo_url(), ENT_QUOTES, 'UTF-8'); ?>" alt="Logo"><?php echo conf('Name');?> - <span class="fw-semibold"><?php echo conf('SiteName');?></span>
                    </a>
                </div>
                <div class="d-flex align-items-center">
                    <ul class="nav-main nav-main-horizontal nav-main-hover d-none d-lg-block me-2">
                        <li class="nav-main-item">
                            <a class="nav-main-link" href="index.php">
                                <i class="nav-main-link-icon fa fa-house"></i>
                                <span class="nav-main-link-name"><strong>首页</strong></span>
                            </a>
                        </li>
                        <li class="nav-main-item">
                            <a class="nav-main-link" href="works.php">
                                <i class="nav-main-link-icon fa fa-layer-group"></i>
                                <span class="nav-main-link-name"><strong>作品项目</strong></span>
                            </a>
                        </li>
                        <li class="nav-main-item">
                            <a class="nav-main-link" href="news.php">
                                <i class="nav-main-link-icon fa fa-bullhorn"></i>
                                <span class="nav-main-link-name"><strong>公告与动态</strong></span>
                            </a>
                        </li>
                        <li class="nav-main-item">
                            <a class="nav-main-link" href="about.php">
                                <i class="nav-main-link-icon fa fa-circle-info"></i>
                                <span class="nav-main-link-name"><strong>关于我们</strong></span>
                            </a>
                        </li>
                        <li class="nav-main-item">
                            <a class="nav-main-link active" href="donate.php">
                                <i class="nav-main-link-icon fa fa-heart"></i>
                                <span class="nav-main-link-name"><strong>赞助打赏</strong></span>
                            </a>
                        </li>
                    </ul>
                    <button type="button" class="btn btn-alt-secondary me-1" data-toggle="layout" data-action="dark_mode_toggle">
                        <i class="far fa-moon"></i>
                    </button>
                </div>
            </div>
        </header>
        <main id="main-container">
            <!-- Hero -->
            <div class="dn-hero">
                <div class="content content-full">
                    <span class="dn-badge"><i class="fa-solid fa-mug-hot"></i> 赞助支持</span>
                    <h1>请作者喝杯咖啡 ☕</h1>
                    <p class="dn-sub-title">你的支持，是我持续维护与迭代的动力</p>
                </div>
            </div>

            <!-- 正文 -->
            <div class="bg-body-light">
                <div class="content content-full">
                    <div class="row g-4 py-5">
                        <div class="col-12">
                            <div class="dn-card">
                                <h2>微信赞赏码</h2>
                                <p class="dn-desc">如果这个项目对你有帮助，欢迎扫码赞赏。<br>金额不限，哪怕一杯奶茶也好，都会让我开心一整天～</p>
                                <?php
                                $dn_qr = __DIR__ . '/assets/donate/wechat-reward.png';
                                if (is_file($dn_qr)) {
                                    echo '<img class="dn-qr" src="assets/donate/wechat-reward.png" alt="微信赞赏码">';
                                } else {
                                    echo '<div class="dn-qr-empty">微信赞赏码待上传<br>请将图片命名为 <code>wechat-reward.png</code><br>放到 <code>assets/donate/</code> 目录</div>';
                                }
                                ?>
                                <p class="dn-tip">微信扫一扫，赞赏不限金额</p>
                            </div>
                            <p class="dn-thanks">
                                本项目基于 GPL-3.0 开源，可自由使用与修改。赞助纯属自愿，不与任何功能绑定。
                                如果你不方便扫码，也欢迎在 <a href="https://github.com/" target="_blank" rel="noopener noreferrer" style="color:#c8871b;text-decoration:none;">GitHub</a> 上点个 Star ⭐ 支持一下。
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <?php require __DIR__ . '/Common/site_footer.php'; ?>
    </div>
</body>
</html>
