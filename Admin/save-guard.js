/* ==========================================================================
   后台保存链路统一守卫（全站，由 Admin/foot.php 引入）
   --------------------------------------------------------------------------
   修复先生报的两个 bug：
   1) 保存后「全屏变灰、只能刷新才恢复」——根因是各页 saveSetting 里
      layer.load 建了全屏遮罩，而 error 分支（WAF 503 挑战 / 返回非 JSON）
      漏了 layer.close(ii)，遮罩永久残留；成功后 layer.alert(closeBtn:false)
      又盖一层遮罩并强制 reload。
   2) 上传图片保存点排查：set/pset/tset 及 OtConfig 的上传都走同一个
      uploadImg() + ajax.php?act=upload，本身没有全屏遮罩；但它们的保存
      函数同样有 layer.load。这里从 layer.load 层做全局收口，任何页面、
      任何分支都不可能再留下灰屏。

   做法：
   - 包装 layer.load：强制去掉全屏遮罩，记录 index，15 秒兜底自动关；
   - 全局 ajaxComplete / ajaxError：无条件关闭所有残留 loading 层；
   - 提供 otSave()：统一保存，成功用无遮罩 layer.msg，不强制 reload，
     WAF 503 自动解票重试一次，并触发 ot:config-saved 让预览即时刷新。
   ========================================================================== */
(function () {
    'use strict';

    function boot() {
        if (!window.jQuery || !window.layer || !window.layer.load) {
            return setTimeout(boot, 30);
        }
        var $ = window.jQuery;

        /* ---- 1. 全局收口 layer.load：不再铺全屏遮罩 ---- */
        var origLoad = window.layer.load;
        var live = [];
        window.layer.load = function (icon, opt) {
            opt = opt || {};
            opt.shade = false;                 /* 关键：不产生全屏灰罩 */
            var idx = origLoad.call(window.layer, icon, opt);
            live.push(idx);
            setTimeout(function () {
                try { window.layer.close(idx); } catch (e) {}
            }, 15000);
            return idx;
        };

        function closeAllLoad() {
            for (var i = 0; i < live.length; i++) {
                try { window.layer.close(live[i]); } catch (e) {}
            }
            live = [];
        }

        $(document).on('ajaxComplete', closeAllLoad);
        $(document).on('ajaxError', closeAllLoad);
        $(document).on('ajaxStop', closeAllLoad);

        /* ---- 2. 统一保存 ---- */
        window.otSave = function (obj, url, opts) {
            opts = opts || {};
            var L = window.layer;
            var ii = L.load(0, { shade: false });
            var done = false;
            var timer = setTimeout(finish, 20000);
            function finish() {
                if (done) { return; }
                done = true;
                clearTimeout(timer);
                try { L.close(ii); } catch (e) {}
            }
            function send() {
                $.ajax({
                    type: 'POST',
                    url: url,
                    data: $(obj).serialize(),
                    dataType: 'json',
                    timeout: 20000,
                    success: function (d) {
                        finish();
                        if (d && (d.code === 0 || d.code === '0')) {
                            L.msg(d.msg || '保存成功', { icon: 1, time: 1800 });
                            $(document).trigger('ot:config-saved', [d, obj]);
                        } else {
                            L.msg((d && d.msg) || '保存失败', { icon: 2, time: 3200 });
                        }
                    },
                    error: function (xhr, status) {
                        finish();
                        var body = (xhr && xhr.responseText) ? xhr.responseText : '';
                        var challenged = (xhr && xhr.status === 503) ||
                            body.indexOf('<html') !== -1 || body.indexOf('<!DOCTYPE') !== -1;
                        /* WAF 流盾挑战：请求其实已落库，但响应被换成 HTML。
                           用隐藏 iframe 跑挑战 JS 落 cookie，再原样重发一次。 */
                        if (challenged && !opts.retried && typeof window.uploadImgSolve === 'function') {
                            window.uploadImgSolve(function () {
                                var again = Object.assign({}, opts, { retried: true });
                                window.otSave(obj, url, again);
                            });
                            return;
                        }
                        L.msg(challenged
                            ? '安全防护拦截了响应，配置可能已保存，请稍后刷新确认'
                            : '服务器错误，请重试', { icon: 2, time: 3800 });
                    },
                    complete: finish
                });
            }
            send();
            return false;
        };

        /* ---- 3. 保存成功后，配置里的图片预览即时去缓存刷新 ---- */
        $(document).on('ot:config-saved', function () {
            $('img[data-ot-preview]').each(function () {
                var img = this;
                var src = $(img).attr('src') || '';
                if (!src) { return; }
                var clean = src.replace(/([?&])_ot=\d+/, '$1').replace(/[?&]$/, '');
                $(img).attr('src', clean + (clean.indexOf('?') === -1 ? '?' : '&') + '_ot=' + Date.now());
            });
        });
    }

    boot();
})();
