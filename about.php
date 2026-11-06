<?php
include("./Common/Core_brain.php");
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>关于我们 - <?php echo conf('Name');?> - <?php echo conf('SiteName');?></title>
    <meta name="keywords" content="关于我们,寒阳,独立开发者,<?php echo conf('Keywords');?>">
    <meta name="description" content="关于寒阳：一个喜欢折腾的独立开发者，专注 Web 开发、Linux 运维与开源工具。">
    <link rel="shortcut icon" href="assets/media/various/favicon.png">
    <link rel="stylesheet" id="css-main" href="assets/css/oneui.min-5.6.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/site-footer.v7.css?v=20261105q">
    <style>
        /* ===== 关于我们 ===== */
        .ab-hero {
            position: relative;
            overflow: hidden;
            padding: 4.5rem 0 3.5rem;
            text-align: center;
            background:
                radial-gradient(900px 300px at 50% -55%, rgba(76, 120, 221, 0.18), transparent 72%),
                #fff;
        }
        .ab-hero::after {
            content: "";
            position: absolute;
            inset: auto 0 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(76, 120, 221, 0.25), transparent);
        }
        .ab-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.32rem 0.85rem;
            margin-bottom: 1.15rem;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.06em;
            color: #3d63bd;
            background: rgba(76, 120, 221, 0.1);
            border: 1px solid rgba(76, 120, 221, 0.22);
            border-radius: 50rem;
        }
        .ab-hero h1 {
            margin: 0 0 0.75rem;
            font-size: clamp(1.75rem, 3vw, 2.5rem);
            font-weight: 800;
            letter-spacing: -0.01em;
        }
        .ab-hero .ab-sub-title {
            margin: 0;
            font-size: 1rem;
            font-weight: 500;
            color: #5b6b80;
        }
        .ab-hero .ab-meta {
            display: inline-flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.4rem 1.25rem;
            margin-top: 1rem;
            font-size: 0.8125rem;
            color: #8a94a6;
        }
        .ab-hero .ab-meta i { margin-right: 0.3rem; opacity: 0.8; }

        /* 目录 */
        .ab-toc {
            position: sticky;
            top: 5.5rem;
            padding: 1.1rem 1rem;
            background: #fff;
            border: 1px solid #e6eaf1;
            border-radius: 0.75rem;
        }
        .ab-toc-title {
            margin: 0 0 0.75rem;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #98a2b3;
        }
        .ab-toc a {
            display: flex;
            align-items: baseline;
            gap: 0.55rem;
            padding: 0.42rem 0.6rem;
            font-size: 0.8125rem;
            color: #5b6b80;
            text-decoration: none;
            border-left: 2px solid transparent;
            border-radius: 0 0.375rem 0.375rem 0;
            transition: color 0.15s ease, background-color 0.15s ease, border-color 0.15s ease;
        }
        .ab-toc a:hover {
            color: #4c78dd;
            background: rgba(76, 120, 221, 0.06);
            border-left-color: #4c78dd;
        }
        .ab-toc .toc-num {
            font-size: 0.6875rem;
            font-weight: 700;
            color: #b6c0cf;
            font-variant-numeric: tabular-nums;
        }

        /* 正文卡片 */
        .ab-card {
            padding: 3rem 2.25rem 1.5rem;
            background: #fff;
            border: 1px solid #e6eaf1;
            border-radius: 0.75rem;
        }
        .ab-section {
            padding-bottom: 2rem;
            margin-bottom: 2rem;
            border-bottom: 1px dashed #e9edf3;
        }
        .ab-section:last-of-type {
            padding-bottom: 0;
            margin-bottom: 0;
            border-bottom: 0;
        }
        .ab-title {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            margin: 0 0 1rem;
            font-size: 1.0625rem;
            font-weight: 700;
            color: #253041;
        }
        .ab-num {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            width: 1.9rem;
            height: 1.9rem;
            font-size: 0.75rem;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
            color: #4c78dd;
            background: rgba(76, 120, 221, 0.1);
            border-radius: 0.5rem;
        }
        .ab-sub {
            margin: 1.25rem 0 0.55rem;
            font-size: 0.9375rem;
            font-weight: 700;
            color: #34465c;
        }
        .ab-card p {
            margin: 0 0 0.9rem;
            font-size: 0.875rem;
            line-height: 1.95;
            color: #5b6b80;
        }
        .ab-card p:last-child { margin-bottom: 0; }
        .ab-card a { color: #4c78dd; text-decoration: none; }
        .ab-card a:hover { text-decoration: underline; }

        /* 联系按钮 */
        .ab-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.6rem;
            margin-top: 1.35rem;
        }
        .ab-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.1rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: #fff !important;
            text-decoration: none !important;
            border-radius: 0.5rem;
            transition: transform 0.15s ease, box-shadow 0.15s ease, opacity 0.15s ease;
        }
        .ab-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(37, 48, 65, 0.14);
            opacity: 0.95;
        }
        .ab-btn-primary { background: #4c78dd; }
        .ab-btn-info { background: #0891b2; }
        .ab-btn i { font-size: 1rem; }

        /* 暗色模式 */
        .dark-mode .ab-hero {
            background:
                radial-gradient(900px 300px at 50% -55%, rgba(76, 120, 221, 0.26), transparent 72%),
                #1b2432;
        }
        .dark-mode .ab-hero::after {
            background: linear-gradient(90deg, transparent, rgba(76, 120, 221, 0.35), transparent);
        }
        .dark-mode .ab-hero h1 { color: #eef2f7; }
        .dark-mode .ab-hero .ab-sub-title { color: #b3c0d1; }
        .dark-mode .ab-hero .ab-meta { color: #9fadc0; }
        .dark-mode .ab-badge {
            color: #9db6f5;
            background: rgba(76, 120, 221, 0.16);
            border-color: rgba(76, 120, 221, 0.3);
        }
        .dark-mode .ab-toc,
        .dark-mode .ab-card {
            background: #1f2937;
            border-color: #2f3d4f;
        }
        .dark-mode .ab-toc-title { color: #7f8da3; }
        .dark-mode .ab-toc a { color: #bec8d5; }
        .dark-mode .ab-toc a:hover {
            color: #9db6f5;
            background: rgba(76, 120, 221, 0.14);
            border-left-color: #4c78dd;
        }
        .dark-mode .ab-toc .toc-num { color: #6b7a90; }
        .dark-mode .ab-title { color: #e6ecf4; }
        .dark-mode .ab-sub { color: #cbd5e1; }
        .dark-mode .ab-num {
            color: #9db6f5;
            background: rgba(76, 120, 221, 0.18);
        }
        .dark-mode .ab-card p { color: #b3c0d1; }
        .dark-mode .ab-section { border-bottom-color: #2f3d4f; }
        .dark-mode .ab-card a { color: #7fa0f0; }

        @media (max-width: 991.98px) {
            .ab-hero { padding: 3rem 0 2.5rem; }
            .ab-card { padding: 1.5rem 1.25rem 1rem; }
            .ab-title { font-size: 1rem; }
            .ab-section { padding-bottom: 1.5rem; margin-bottom: 1.5rem; }
        }
        /* ab-card 角花装饰 */
        .ab-card {
            position: relative;
            overflow: hidden;
        }
        .ab-card::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image:
                url("/assets/img/decor/camellia-eucalyptus-tl.webp"),
                url("/assets/img/decor/camellia-eucalyptus-br.webp");
            background-size: min(32%, 180px) auto, min(30%, 170px) auto;
            background-position: 0% 0%, 100% 100%;
            background-repeat: no-repeat, no-repeat;
            opacity: .22;
            z-index: 0;
            pointer-events: none;
        }
        .ab-card > * {
            position: relative;
            z-index: 1;
        }
        .dark-mode .ab-card::before {
            opacity: .18;
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
                            <a class="nav-main-link active" href="about.php">
                                <i class="nav-main-link-icon fa fa-circle-info"></i>
                                <span class="nav-main-link-name"><strong>关于我们</strong></span>
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
            <div class="ab-hero">
                <div class="content content-full">
                    <span class="ab-badge"><i class="fa-regular fa-user"></i> 关于我们</span>
                    <h1>寒阳</h1>
                    <p class="ab-sub-title">一个喜欢折腾的独立开发者</p>
                    <div class="ab-meta">
                        <span><i class="fa-solid fa-code"></i>Web 开发</span>
                        <span><i class="fa-brands fa-linux"></i>Linux 运维</span>
                        <span><i class="fa-solid fa-cubes"></i>开源工具</span>
                    </div>
                </div>
            </div>

            <!-- 正文 -->
            <div class="bg-body-light">
                <div class="content content-full">
                    <div class="row g-4 py-5">
                        <!-- 目录 -->
                        <div class="col-lg-3 d-none d-lg-block">
                            <nav class="ab-toc" aria-label="页面目录">
                                <p class="ab-toc-title">页面目录</p>
                                <a href="#about"><span class="toc-num">01</span>关于我</a>
                                <a href="#stack"><span class="toc-num">02</span>技术方向</a>
                                <a href="#principle"><span class="toc-num">03</span>做事方式</a>
                                <a href="#site"><span class="toc-num">04</span>这个站点</a>
                                <a href="#contact"><span class="toc-num">05</span>联系我</a>
                            </nav>
                        </div>
                        <!-- 内容 -->
                        <div class="col-lg-9">
                            <div class="ab-card">
                                <section class="ab-section" id="about">
                                    <h3 class="ab-title"><span class="ab-num">01</span>关于我</h3>
                                    <p>我是寒阳，一个独立开发者。</p>
                                    <p>比起“全栈”这类标签，我更愿意说自己是个喜欢折腾的人。一个念头冒出来，就去把它做出来——可能是一套网站模板，一段自动化脚本，一个顺手的小工具，也可能只是把某台服务器的部署流程重新理一遍。</p>
                                    <p>这些年下来，技术于我更像一种表达方式。想法落在代码里，代码跑在机器上，机器再把结果交给使用它的人。这条链路越短、越干净，我就越满意。</p>
                                    <p>所以我信奉“代码即艺术”：不是把界面做得花哨，而是让结构清楚、让逻辑克制、让后来的人——包括三个月后的自己——一眼能看懂。</p>
                                </section>
                                <section class="ab-section" id="stack">
                                    <h3 class="ab-title"><span class="ab-num">02</span>技术方向</h3>
                                    <p>日常工作围绕三个方向展开。</p>
                                    <h4 class="ab-sub">Web 开发是主线</h4>
                                    <p>从页面结构、交互细节，到接口设计、数据落库，习惯把一整条链路走完，而不是只停在自己熟悉的那一层。写页面时会想着后端怎么接，写接口时会想着前端怎么用。</p>
                                    <h4 class="ab-sub">Linux 运维是底座</h4>
                                    <p>服务器的选型、环境的搭建、部署与回滚、日志与监控——这些不显眼的部分，决定了上面那些东西能不能稳稳地跑着。它们很少被看见，但一旦出问题，所有被看见的东西都会停。</p>
                                    <h4 class="ab-sub">开源工具是养分</h4>
                                    <p>日常大量使用开源项目，也习惯在读源码的过程中理解它们为什么这样设计。遇到合适的机会，会把踩过的坑、改过的补丁回馈回去。用别人的东西，也给别人留点东西。</p>
                                </section>
                                <section class="ab-section" id="principle">
                                    <h3 class="ab-title"><span class="ab-num">03</span>做事方式</h3>
                                    <p>三条自己一直在守的规矩。</p>
                                    <h4 class="ab-sub">简洁优先</h4>
                                    <p>能用一层解决的不做两层，能删掉的代码就删掉。复杂度是有成本的，而且利息很高——今天省下的抽象，往往是半年后要还的债。</p>
                                    <h4 class="ab-sub">可维护优先</h4>
                                    <p>写代码的时候会想：半年后别人接手，能不能在半小时内看懂这里在干什么。想不清楚的地方，通常意味着设计还没到位，而不是文档没写够。</p>
                                    <h4 class="ab-sub">长期优先</h4>
                                    <p>不追求一次做完所有事，但要求每一次改动都让东西变得更好一点。慢一点没关系，别留坑。</p>
                                </section>
                                <section class="ab-section" id="site">
                                    <h3 class="ab-title"><span class="ab-num">04</span>这个站点</h3>
                                    <p>这里是寒阳网络的工作台，也是对外的一扇窗。</p>
                                    <p>作品项目记录做过的东西，公告与动态同步最近的进展，团队成员可以在这里核验身份——网络上冒名的事不少，我们希望大家能有一个准确的查询入口。如果你也想加入，页面上同样留着入口。</p>
                                    <p>站点本身也是作品的一部分：它跑在自己维护的服务器上，用着自己顺手的技术栈，改版过很多次，以后还会继续改。</p>
                                </section>
                                <section class="ab-section" id="contact">
                                    <h3 class="ab-title"><span class="ab-num">05</span>联系我</h3>
                                    <p>如果你有合作意向、技术问题想交流，或者只是单纯想聊聊，都欢迎找我。</p>
                                    <p>比起长篇的邮件，我更习惯即时一点的沟通方式——下面两个入口都开着。</p>
                                    <div class="ab-actions">
                                        <a class="ab-btn ab-btn-primary" href="https://qm.qq.com/cgi-bin/qm/qr?k=157777491" target="_blank" rel="noopener noreferrer">
                                            <i class="fa-brands fa-qq"></i><span>作者 QQ</span>
                                        </a>
                                        <a class="ab-btn ab-btn-info" href="https://t.me/UI3I4s" target="_blank" rel="noopener noreferrer">
                                            <i class="fa-brands fa-telegram"></i><span>作者 TG</span>
                                        </a>
                                    </div>
                                </section>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 页脚 -->
            <?php require __DIR__ . '/Common/site_footer.php'; ?>
        </main>
    </div>
    <script src="assets/js/oneui.app.min-5.6.js"></script>
    <script src="assets/js/jquery.min.js"></script>
    <script src="https://myhkw.cn/api/player/<?php echo conf_index('Index_Music') ?>" id="myhk" key="<?php echo conf_index('Index_Music') ?>" m="1"></script>
    <script>
        // 目录平滑滚动 + 当前项高亮
        (function () {
            var links = Array.prototype.slice.call(document.querySelectorAll('.ab-toc a'));
            if (!links.length) return;
            var sections = links.map(function (a) {
                return document.getElementById(a.getAttribute('href').slice(1));
            }).filter(Boolean);

            links.forEach(function (a) {
                a.addEventListener('click', function (e) {
                    var el = document.getElementById(a.getAttribute('href').slice(1));
                    if (!el) return;
                    e.preventDefault();
                    var top = el.getBoundingClientRect().top + window.pageYOffset - 96;
                    window.scrollTo({ top: top, behavior: 'smooth' });
                });
            });

            function sync() {
                var pos = window.pageYOffset + 140;
                var current = 0;
                sections.forEach(function (sec, i) {
                    if (sec.offsetTop <= pos) current = i;
                });
                links.forEach(function (a, i) {
                    a.style.borderLeftColor = i === current ? '#4c78dd' : 'transparent';
                    a.style.color = i === current ? '#4c78dd' : '';
                    a.style.background = i === current ? 'rgba(76,120,221,.06)' : '';
                });
            }
            window.addEventListener('scroll', sync, { passive: true });
            sync();
        })();
    </script>
</body>
</html>
