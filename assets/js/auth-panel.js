/* /user/ 登录注册表单 ajax 提交 + 登录页内注册视图切换
   首页只有一个水晶按钮 → /user/login.php；登录页内含注册视图，点「创建账户」就地切换，
   独立页（/user/register.php）没有对应视图时链接正常跳转，无 JS 也能用。
   成功跳转，失败就地报错；验证码一次性，失败自动换一张；JS 挂了走普通 POST 回退。 */
(function () {
    'use strict';

    var VIEW_TEXT = {
        login:    ['欢迎来到您的用户中心', '请登录'],
        register: ['创建新账户', '请填写您的详细资料']
    };

    function refreshCaptcha(form) {
        var img = form.querySelector('#ap-captcha-img');
        if (img) { img.src = '/user/captcha.php?t=' + Math.random(); }
    }

    function bindForm(form) {
        if (form.getAttribute('data-ap-bound')) return;
        form.setAttribute('data-ap-bound', '1');
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var errBox = form.querySelector('.ap-error');
            var btn = form.querySelector('.ap-btn');
            var data = new FormData(form);
            data.append('ajax', '1');

            if (btn) { btn.disabled = true; }
            if (errBox) { errBox.hidden = true; errBox.textContent = ''; }

            function fail(msg) {
                if (btn) { btn.disabled = false; }
                if (errBox) { errBox.textContent = msg; errBox.hidden = false; }
                refreshCaptcha(form);
            }

            fetch(form.getAttribute('action'), {
                method: 'POST',
                body: data,
                credentials: 'same-origin',
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            })
                .then(function (r) { return r.json(); })
                .then(function (j) {
                    if (btn) { btn.disabled = false; }
                    if (j && j.ok) {
                        window.location.href = j.redirect || '/user/index.php';
                        return;
                    }
                    fail((j && j.err) || '操作失败，请重试');
                })
                .catch(function () { fail('网络异常，请重试'); });
        });
    }

    function showView(name) {
        var target = document.getElementById('ap-view-' + name);
        if (!target) { return false; }
        Array.prototype.forEach.call(document.querySelectorAll('.ap-view'), function (v) {
            v.hidden = (v !== target);
        });
        var brand = document.querySelector('.ot-auth-brand');
        if (brand && VIEW_TEXT[name]) {
            var h1 = brand.querySelector('h1'), h2 = brand.querySelector('h2');
            if (h1) { h1.textContent = VIEW_TEXT[name][0]; }
            if (h2) { h2.textContent = VIEW_TEXT[name][1]; }
        }
        document.title = (VIEW_TEXT[name] ? VIEW_TEXT[name][0] : '登录') + ' - ' +
            (brand ? brand.querySelector('span') ? brand.querySelector('span').textContent : '' : '');
        return true;
    }

    function bindTabs() {
        document.addEventListener('click', function (e) {
            var node = e.target;
            var link = null;
            while (node && node !== document) {
                if (node.getAttribute && node.getAttribute('data-ap-tab')) { link = node; break; }
                node = node.parentNode;
            }
            if (!link) { return; }
            if (showView(link.getAttribute('data-ap-tab'))) {
                e.preventDefault();
                var first = document.querySelector('#ap-view-' + link.getAttribute('data-ap-tab') + ' input');
                if (first) { try { first.focus({preventScroll: true}); } catch (err) { first.focus(); } }
            }
        });
    }

    function boot() {
        Array.prototype.forEach.call(document.querySelectorAll('form[data-ap-ajax]'), bindForm);
        bindTabs();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot, {once: true});
    } else {
        boot();
    }
})();
