<?php
include("./Common/Core_brain.php");
$ds_updated = '2026 年 10 月 26 日';
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>免责声明 - <?php echo conf('Name');?> - <?php echo conf('SiteName');?></title>
    <meta name="keywords" content="免责声明,法律条款,<?php echo conf('Keywords');?>">
    <meta name="description" content="<?php echo conf('Name');?> 免责声明：关于本站内容、外部链接、使用风险、知识产权与用户行为规范等事项的说明。">
    <link rel="shortcut icon" href="assets/media/various/favicon.png">
    <link rel="stylesheet" id="css-main" href="assets/css/oneui.min-5.6.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/site-footer.v7.css?v=20261105q">
    <style>
        /* ===== 免责声明页 ===== */
        .ds-hero {
            position: relative;
            overflow: hidden;
            padding: 4.5rem 0 3.5rem;
            text-align: center;
            background:
                radial-gradient(900px 300px at 50% -55%, rgba(76, 120, 221, 0.18), transparent 72%),
                #fff;
        }
        .ds-hero::after {
            content: "";
            position: absolute;
            inset: auto 0 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(76, 120, 221, 0.25), transparent);
        }
        .ds-badge {
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
        .ds-hero h1 {
            margin: 0 0 0.75rem;
            font-size: clamp(1.75rem, 3vw, 2.5rem);
            font-weight: 800;
            letter-spacing: -0.01em;
        }
        .ds-hero p {
            margin: 0;
            font-size: 0.875rem;
            color: #6c757d;
        }
        .ds-hero .ds-meta {
            display: inline-flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.4rem 1.25rem;
            margin-top: 1rem;
            font-size: 0.8125rem;
            color: #8a94a6;
        }
        .ds-hero .ds-meta i { margin-right: 0.3rem; opacity: 0.8; }

        /* 目录 */
        .ds-toc {
            position: sticky;
            top: 5.5rem;
            padding: 1.1rem 1rem;
            background: #fff;
            border: 1px solid #e6eaf1;
            border-radius: 0.75rem;
        }
        .ds-toc-title {
            margin: 0 0 0.75rem;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #98a2b3;
        }
        .ds-toc a {
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
        .ds-toc a:hover {
            color: #4c78dd;
            background: rgba(76, 120, 221, 0.06);
            border-left-color: #4c78dd;
        }
        .ds-toc .toc-num {
            font-size: 0.6875rem;
            font-weight: 700;
            color: #b6c0cf;
            font-variant-numeric: tabular-nums;
        }

        /* 正文卡片 */
        .ds-card {
            padding: 3rem 2.25rem 1.5rem;
            background: #fff;
            border: 1px solid #e6eaf1;
            border-radius: 0.75rem;
        }
        .ds-section {
            padding-bottom: 2rem;
            margin-bottom: 2rem;
            border-bottom: 1px dashed #e9edf3;
        }
        .ds-section:last-of-type {
            padding-bottom: 0;
            margin-bottom: 0;
            border-bottom: 0;
        }
        .ds-title {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            margin: 0 0 1rem;
            font-size: 1.0625rem;
            font-weight: 700;
            color: #253041;
        }
        .ds-num {
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
        .ds-card p {
            margin: 0 0 0.9rem;
            font-size: 0.875rem;
            line-height: 1.9;
            color: #5b6b80;
        }
        .ds-card p:last-child { margin-bottom: 0; }
        .ds-list {
            margin: 0 0 1rem;
            padding: 0;
            list-style: none;
        }
        .ds-list li {
            position: relative;
            padding-left: 1.1rem;
            margin-bottom: 0.45rem;
            font-size: 0.875rem;
            line-height: 1.85;
            color: #5b6b80;
        }
        .ds-list li::before {
            content: "";
            position: absolute;
            left: 0.1rem;
            top: 0.72rem;
            width: 5px;
            height: 5px;
            background: #b9c4d4;
            border-radius: 50%;
        }
        .ds-card a { color: #4c78dd; text-decoration: none; }
        .ds-card a:hover { text-decoration: underline; }

        /* 页脚提示 */
        .ds-footnote {
            display: flex;
            align-items: flex-start;
            gap: 0.7rem;
            margin-top: 1.5rem;
            padding: 1rem 1.1rem;
            font-size: 0.8125rem;
            line-height: 1.75;
            color: #6c757d;
            background: #f7f9fc;
            border: 1px solid #e9edf3;
            border-radius: 0.5rem;
        }
        .ds-footnote i {
            margin-top: 0.2rem;
            color: #4c78dd;
        }

        /* 暗色模式 */
        .dark-mode .ds-hero {
            background:
                radial-gradient(900px 300px at 50% -55%, rgba(76, 120, 221, 0.26), transparent 72%),
                #1b2432;
        }
        .dark-mode .ds-hero::after {
            background: linear-gradient(90deg, transparent, rgba(76, 120, 221, 0.35), transparent);
        }
        .dark-mode .ds-hero h1 { color: #eef2f7; }
        .dark-mode .ds-hero p,
        .dark-mode .ds-hero .ds-meta { color: #9fadc0; }
        .dark-mode .ds-badge {
            color: #9db6f5;
            background: rgba(76, 120, 221, 0.16);
            border-color: rgba(76, 120, 221, 0.3);
        }
        .dark-mode .ds-toc,
        .dark-mode .ds-card {
            background: #1f2937;
            border-color: #2f3d4f;
        }
        .dark-mode .ds-toc-title { color: #7f8da3; }
        .dark-mode .ds-toc a { color: #bec8d5; }
        .dark-mode .ds-toc a:hover {
            color: #9db6f5;
            background: rgba(76, 120, 221, 0.14);
            border-left-color: #4c78dd;
        }
        .dark-mode .ds-toc .toc-num { color: #6b7a90; }
        .dark-mode .ds-title { color: #e6ecf4; }
        .dark-mode .ds-num {
            color: #9db6f5;
            background: rgba(76, 120, 221, 0.18);
        }
        .dark-mode .ds-card p,
        .dark-mode .ds-list li { color: #b3c0d1; }
        .dark-mode .ds-list li::before { background: #4d5d73; }
        .dark-mode .ds-section { border-bottom-color: #2f3d4f; }
        .dark-mode .ds-card a { color: #7fa0f0; }
        .dark-mode .ds-footnote {
            color: #9fadc0;
            background: #182130;
            border-color: #2f3d4f;
        }
        .dark-mode .ds-footnote i { color: #7fa0f0; }

        @media (max-width: 991.98px) {
            .ds-hero { padding: 3rem 0 2.5rem; }
            .ds-card { padding: 1.5rem 1.25rem 1rem; }
            .ds-title { font-size: 1rem; }
            .ds-section { padding-bottom: 1.5rem; margin-bottom: 1.5rem; }
        }
        /* ds-card 角花装饰 */
        .ds-card {
            position: relative;
            overflow: hidden;
        }
        .ds-card::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image:
                url("/assets/img/decor/laurel-ginkgo-tl.webp"),
                url("/assets/img/decor/laurel-ginkgo-br.webp");
            background-size: min(32%, 180px) auto, min(30%, 170px) auto;
            background-position: 0% 0%, 100% 100%;
            background-repeat: no-repeat, no-repeat;
            opacity: .22;
            z-index: 0;
            pointer-events: none;
        }
        .ds-card > * {
            position: relative;
            z-index: 1;
        }
        .dark-mode .ds-card::before {
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
                            <a class="nav-main-link" href="news.php">
                                <i class="nav-main-link-icon fa fa-bullhorn"></i>
                                <span class="nav-main-link-name"><strong>公告与动态</strong></span>
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
            <div class="ds-hero">
                <div class="content content-full">
                    <span class="ds-badge"><i class="fa-solid fa-shield-halved"></i> 法律条款</span>
                    <h1>免责声明</h1>
                    <p>请在使用本站前仔细阅读以下条款</p>
                    <div class="ds-meta">
                        <span><i class="fa-regular fa-calendar"></i>最后更新：<?php echo $ds_updated;?></span>
                        <span><i class="fa-regular fa-clock"></i>预计阅读 4 分钟</span>
                        <span><i class="fa-solid fa-file-lines"></i>共 10 条</span>
                    </div>
                </div>
            </div>

            <!-- 正文 -->
            <div class="bg-body-light">
                <div class="content content-full">
                    <div class="row g-4 py-5">
                        <!-- 目录 -->
                        <div class="col-lg-3 d-none d-lg-block">
                            <nav class="ds-toc" aria-label="条款目录">
                                <p class="ds-toc-title">条款目录</p>
                                <a href="#general"><span class="toc-num">01</span>总则</a>
                                <a href="#content"><span class="toc-num">02</span>内容与信息</a>
                                <a href="#links"><span class="toc-num">03</span>外部链接</a>
                                <a href="#risk"><span class="toc-num">04</span>使用风险</a>
                                <a href="#ip"><span class="toc-num">05</span>知识产权</a>
                                <a href="#conduct"><span class="toc-num">06</span>用户行为规范</a>
                                <a href="#privacy"><span class="toc-num">07</span>隐私与数据</a>
                                <a href="#change"><span class="toc-num">08</span>声明的变更</a>
                                <a href="#law"><span class="toc-num">09</span>适用法律与争议解决</a>
                                <a href="#contact"><span class="toc-num">10</span>联系我们</a>
                            </nav>
                        </div>
                        <!-- 内容 -->
                        <div class="col-lg-9">
                            <div class="ds-card">
                                <section class="ds-section" id="general">
                                    <h3 class="ds-title"><span class="ds-num">01</span>总则</h3>
                                    <p>本免责声明（以下简称“本声明”）适用于您访问和使用 <?php echo conf('Name');?>（以下简称“本站”）所提供的全部页面、内容与服务。</p>
                                    <p>您开始浏览、注册、下载或以任何方式使用本站，即视为已完整阅读、理解并同意本声明的全部条款。若您不同意其中任何内容，请立即停止使用本站。</p>
                                </section>
                                <section class="ds-section" id="content">
                                    <h3 class="ds-title"><span class="ds-num">02</span>内容与信息</h3>
                                    <p>本站所发布的文章、公告、动态、作品展示、技术资料等信息，均以“现状”提供，仅用于信息分享与技术交流目的。</p>
                                    <p>我们力求内容准确、及时，但不对其完整性、可靠性、时效性或适用性作出任何明示或默示的保证。因内容更新滞后、表述偏差或第三方转载造成的误解，本站不承担相应责任。</p>
                                    <ul class="ds-list">
                                        <li>本站不保证任何内容在您所在地区合法、可用或不侵犯第三方权益；</li>
                                        <li>本站有权在不另行通知的情况下修改、删除或停止发布任何内容；</li>
                                        <li>用户依据本站内容作出的任何决策与行为，风险由用户自行承担。</li>
                                    </ul>
                                </section>
                                <section class="ds-section" id="links">
                                    <h3 class="ds-title"><span class="ds-num">03</span>外部链接</h3>
                                    <p>本站可能包含指向第三方网站、平台或资源的链接。这些链接仅为方便访问者而设置，本站无法控制其内容、隐私政策或可用性。</p>
                                    <p>本站不对任何第三方网站的内容、服务、安全性或合法性负责，也不因您通过本站链接访问第三方而产生的任何损失承担责任。访问第三方站点时，请自行判断并遵守其使用条款。</p>
                                </section>
                                <section class="ds-section" id="risk">
                                    <h3 class="ds-title"><span class="ds-num">04</span>使用风险</h3>
                                    <p>本站按现有技术条件提供服务，但不保证服务不中断、无延迟、无错误或不含病毒等有害成分。</p>
                                    <p>对于因下列原因导致的任何直接或间接损失，本站不承担责任：</p>
                                    <ul class="ds-list">
                                        <li>网络故障、运营商线路调整、域名解析异常；</li>
                                        <li>服务器维护、升级、遭受攻击或不可抗力事件；</li>
                                        <li>用户设备、浏览器、网络环境不兼容或配置不当；</li>
                                        <li>用户自行下载、安装、运行第三方资源所致的问题。</li>
                                    </ul>
                                </section>
                                <section class="ds-section" id="ip">
                                    <h3 class="ds-title"><span class="ds-num">05</span>知识产权</h3>
                                    <p>本站的页面设计、文字内容、图形标识、程序代码等，除特别注明外，其著作权及相关权利归本站或相应权利人所有。</p>
                                    <p>未经书面许可，任何单位或个人不得以复制、镜像、采集、爬取、转载、改编等方式将本站内容用于商业用途。经许可转载的，须注明来源并保留原文链接。</p>
                                    <p>本站展示的第三方商标、产品名称、Logo 等，其权利归各自所有者，本站仅作识别与说明之用。</p>
                                </section>
                                <section class="ds-section" id="conduct">
                                    <h3 class="ds-title"><span class="ds-num">06</span>用户行为规范</h3>
                                    <p>访问者在使用本站时应遵守中华人民共和国相关法律法规及所在地法律，不得利用本站从事下列行为：</p>
                                    <ul class="ds-list">
                                        <li>发布、传播违法、侵权、淫秽、暴力或虚假信息；</li>
                                        <li>对本站进行扫描、渗透、拒绝服务攻击或任何形式的破坏；</li>
                                        <li>未经授权抓取、批量复制本站数据或干扰服务正常运行；</li>
                                        <li>冒用本站或他人名义从事商业活动、诈骗或其他不当行为。</li>
                                    </ul>
                                    <p>对违反上述规范的行为，本站有权采取限制访问、封禁账号、保留证据并依法向有关部门举报等措施。</p>
                                </section>
                                <section class="ds-section" id="privacy">
                                    <h3 class="ds-title"><span class="ds-num">07</span>隐私与数据</h3>
                                    <p>本站仅收集为提供服务所必需的最小范围信息（如访问日志、必要的联系信息），并采取合理措施保护其安全。</p>
                                    <p>除法律法规要求或经您授权外，本站不会向第三方出售、出租或披露您的个人信息。第三方统计、客服等组件的数据处理遵循其各自的隐私政策。</p>
                                </section>
                                <section class="ds-section" id="change">
                                    <h3 class="ds-title"><span class="ds-num">08</span>声明的变更</h3>
                                    <p>本站有权根据业务调整、法律变化或运营需要，随时修订本声明。修订后的内容自在本页面发布之日起生效。</p>
                                    <p>建议您定期查阅本页面以了解最新条款。若修订后您继续使用本站，即视为接受修订内容。</p>
                                </section>
                                <section class="ds-section" id="law">
                                    <h3 class="ds-title"><span class="ds-num">09</span>适用法律与争议解决</h3>
                                    <p>本声明的订立、效力、解释与履行，均适用中华人民共和国法律（不含港澳台地区法律）。</p>
                                    <p>因本声明或使用本站所产生的争议，双方应首先友好协商解决；协商不成的，可向本站运营方所在地有管辖权的人民法院提起诉讼。</p>
                                </section>
                                <section class="ds-section" id="contact">
                                    <h3 class="ds-title"><span class="ds-num">10</span>联系我们</h3>
                                    <p>如您对本声明存有疑问、异议，或发现本站内容存在侵权、违规情形，欢迎通过以下方式与我们联系，我们将在合理期限内处理：</p>
                                    <ul class="ds-list">
                                        <li>支持服务：<a href="mailto:cherubimand@gmail.com">cherubimand@gmail.com</a></li>
                                        <li>违规投诉：<a href="mailto:qiober@qq.com">qiober@qq.com</a></li>
                                    </ul>
                                </section>
                                <div class="ds-footnote">
                                    <i class="fa-solid fa-circle-info"></i>
                                    <div>本声明自发布之日起生效。若您继续使用本站，即表示您已阅读并同意上述全部条款。如有疑问，欢迎通过上方邮箱与我们联系。</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 页脚 -->
            <?php require __DIR__ . '/Common/site_footer.php'; ?>
        </main>
    </div>
    <script src="assets/js/oneui.app.min-5.6.js" defer></script>
    <script src="assets/js/jquery.min.js" defer></script>
    <script>
        // 目录平滑滚动 + 当前项高亮
        (function () {
            var links = Array.prototype.slice.call(document.querySelectorAll('.ds-toc a'));
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
