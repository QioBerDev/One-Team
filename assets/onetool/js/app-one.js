/* ==========================================================================
   OneTool app.min.js 适配版（官网 PHP 版）
   --------------------------------------------------------------------------
   保留：x.ajax / x.postData / x.getval / x.notify / x.close / x.btn / x.reload
   去掉：ThinkPHP 硬编码路由（logout / clearCache / 模板占位符）、
         pjax 全局绑定、ClipboardJS 全局构造（未引插件会直接抛错）
   依赖：jQuery + layer + Codebase 5.0（jq-notify helper）+ bootstrap-notify
   ========================================================================== */
(function ($) {
    'use strict';

    var x = {
        ajax: function (url, data, success, fail) {
            var loading = window.layer ? layer.load(2) : 0;
            $.ajax({
                url: url,
                data: data,
                type: (data === null || data === undefined) ? 'get' : 'post',
                cache: false,
                success: function (res) {
                    x.close(loading);
                    if (typeof success === 'function') success(res);
                },
                error: function (err) {
                    x.close(loading);
                    if (typeof fail === 'function') fail(err);
                    else x.notify('网络链接错误', 'danger');
                }
            });
        },

        postData: function (url, parameter, callback, callerror, ajaxType, ajaxTime) {
            var loading = window.layer ? layer.load(2) : 0;
            $.ajax({
                type: ajaxType || 'POST',
                url: url,
                dataType: 'json',
                timeout: ajaxTime || 60000,
                data: parameter,
                success: function (res) {
                    x.close(loading);
                    if (callback) callback(res);
                },
                error: function () {
                    x.close(loading);
                    if (callerror) callerror();
                    else x.notify('网络链接错误', 'danger');
                }
            });
        },

        /* 取值；空值时按第 2 参数提示并聚焦 */
        getval: function (sel, msg) {
            var $el = $(sel);
            if ($el.length === 0) return '';
            var v = $el.val();
            if (!v) {
                if (msg === 1) $el.focus();
                else if (msg) { $el.focus(); x.notify(msg, 'danger'); }
                return '';
            }
            return v;
        },

        notify: function (message, type, align) {
            type = type || 'info';
            var icons = {
                info: 'fa fa-info me-1',
                success: 'fa fa-check me-1',
                warning: 'fa fa-exclamation-triangle me-1',
                danger: 'fa fa-times me-1'
            };
            if (window.Codebase && Codebase.helpers) {
                Codebase.helpers('jq-notify', {
                    align: align || 'right',
                    from: 'top',
                    type: type,
                    icon: icons[type] || icons.info,
                    message: message,
                    z_index: 39891015
                });
            } else if (window.layer) {
                layer.msg(message);
            } else {
                alert(message);
            }
        },

        close: function (a) {
            if (!window.layer) return;
            if (typeof a === 'number') layer.close(a);
            else if (a) layer.closeAll(a);
            else layer.closeAll();
        },

        btn: function (text, btn) {
            return layer.confirm(text || '', {
                btn: btn || '我知道了',
                closeBtn: 0,
                btnAlign: 'c',
                yes: function (i) { x.close(i); }
            });
        },

        /* 侧栏当前页高亮：Codebase 5.0 原生 open/active class */
        reload: function () {
            var pageUrl = window.location.href.split(/[?#]/)[0];
            $('#nav-main a').each(function () {
                var $a = $(this);
                if (this.href === pageUrl) {
                    $a.addClass('active');
                    $a.closest('.nav-main-item').addClass('open');
                } else {
                    $a.removeClass('active');
                }
            });
        }
    };

    window.x = x;

    /* 官网退出走 /user/logout.php，不再依赖 TP 的 ajax 路由 */
    window.otLogout = function () {
        if (!window.layer) { window.location.href = '/user/logout.php'; return; }
        layer.confirm('你确定退出登录吗?', {
            btn: ['确定', '取消'], closeBtn: 0
        }, function (i) {
            layer.close(i);
            window.location.href = '/user/logout.php';
        });
    };

    $(function () { x.reload(); });
})(jQuery);

/* 原版工具函数，页面脚本会用到 */
function trim(a) { return a.replace(/(^\s*)|(\s*$)/g, ''); }
function isnull(a) {
    return a == null || a === '' || a === 'undefined' || a === undefined ||
           a === false || a === 'false' || a === 'null';
}
function nv(b, a) { return isnull(b) ? (!isnull(a) ? a : '') : b; }
function setCookie(cname, cvalue, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
    document.cookie = cname + '=' + cvalue + '; expires=' + d.toUTCString() + '; path=/';
}
function getCookie(cname) {
    var name = cname + '=';
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) === ' ') c = c.substring(1);
        if (c.indexOf(name) === 0) return c.substring(name.length, c.length);
    }
    return '';
}
function clearCookie(name) { setCookie(name, '', -1); }
