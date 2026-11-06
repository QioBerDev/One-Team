<script src="save-guard.js?v=20261106f"></script>
<footer id="page-footer">
  <div class="content py-3">
    <div class="row fs-sm">
      <div class="col-sm-6 order-sm-2 py-1 text-center text-sm-end">
        <a class="fw-semibold" href="https://beian.miit.gov.cn/" target="_blank"><?php echo conf('ICP');?></a>
      </div>
      <div class="col-sm-6 order-sm-1 py-1 text-center text-sm-start">
        &copy; <span data-toggle="year-copy"></span> <a class="fw-semibold" href="//<?php echo conf('Url');?>" target="_blank"><?php echo conf('Name');?></a>
      </div>
    </div>
  </div>
</footer>
<style>
    .table tbody tr {
        transition: all 0.2s ease;
    }
    .table tbody tr:hover {
        background-color: rgba(0,0,0,0.03);
    }
    .btn {
        transition: all 0.2s ease;
    }
    .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    }
    /* 图片预览全局兜底：不引前台 auth-panel.css，任何后台页渲染
       ot_render_group('image') 或上传预览都不会按原尺寸撑爆表单 */
    .ot-hero-preview-wrap{position:relative;width:100%;height:150px;overflow:hidden;
        border:1px solid rgba(0,0,0,.12);border-radius:10px;background:#f4f6f9;
        display:flex;align-items:center;justify-content:center}
    .ot-hero-preview-wrap img{display:block;max-width:100%;max-height:100%;
        width:auto;height:auto;object-fit:contain}
    .ot-hero-preview-wrap .ot-hero-preview-tip{position:absolute;right:8px;bottom:6px;
        font-size:11px;color:#6c757d;background:rgba(255,255,255,.85);padding:2px 6px;border-radius:4px}
</style>
<script>
/* WAF（流盾）对 /Admin/* 有 JS 挑战且票据短时效：
   ajax POST 撞上挑战只会拿到 503 的 HTML（jQuery 解析不了 JSON → error），
   而且 ajax 不执行挑战 JS，重试也没用。
   对策：先用隐藏 iframe 发一次 GET —— 浏览器会自动跑挑战 JS 落 cookie —— 再重试。 */
function uploadImgSolve(cb){
    var ifr = document.createElement('iframe');
    ifr.style.cssText = 'position:fixed;left:-9999px;top:0;width:1px;height:1px;border:0';
    ifr.src = 'ajax.php?act=upload';
    var done = false;
    var fin = function(){
        if(done) return;
        done = true;
        clearTimeout(timer);
        if(ifr.parentNode) ifr.parentNode.removeChild(ifr);
        cb();
    };
    /* 挑战页 onload 后 JS 还要算题换 cookie，留 800ms；5s 兜底防挂死 */
    var timer = setTimeout(fin, 5000);
    ifr.onload = function(){ setTimeout(fin, 800); };
    document.body.appendChild(ifr);
}
function uploadImg(input, fieldName){
    var file = input.files[0];
    if(!file) return;
    var btn = input.previousElementSibling.querySelector('button');
    var oldText = btn.innerText;
    btn.innerText = '上传中...';
    btn.disabled = true;
    var attempt = 0;
    var send = function(){
        var fd = new FormData();
        fd.append('file', file);
        $.ajax({
            url: 'ajax.php?act=upload',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(res){
                btn.innerText = oldText;
                btn.disabled = false;
                if(res.code == 0){
                    var $f = $('input[name="'+fieldName+'"]');
                    $f.val(res.url).trigger('input');
                    /* 不依赖 jQuery trigger 是否调用原生 oninput：显式跑一遍，
                       保证登录页大图 / Logo / 首页背景等预览上传后立刻更新 */
                    var el = $f[0];
                    if (el && typeof el.oninput === 'function') { el.oninput.call(el); }
                    /* 兜底：OtConfig 渲染的预览 id 是 ot_<KEY>_preview，直接补一次 */
                    var pv = document.getElementById('ot_' + fieldName + '_preview')
                          || document.getElementById(fieldName + '_preview');
                    if (pv && pv.tagName === 'IMG') {
                        pv.src = (typeof logoUrl === 'function') ? logoUrl(res.url) : res.url;
                    }
                    layer.msg('上传成功', {icon:1, time:1500});
                }else{
                    layer.msg(res.msg, {icon:2});
                }
            },
            error: function(xhr){
                var body = (xhr && xhr.responseText) ? xhr.responseText : '';
                var challenged = (xhr && xhr.status === 503) || body.indexOf('<html') !== -1;
                if(challenged && attempt < 2){
                    attempt++;
                    btn.innerText = '安全校验中...';
                    uploadImgSolve(send);
                    return;
                }
                btn.innerText = oldText;
                btn.disabled = false;
                layer.msg(challenged ? '上传被安全防护拦截，请稍后再试' : '上传失败', {icon:2});
            }
        });
    };
    send();
}
</script>