<?php
include('./Common/Core_brain.php');
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, viewport-fit=cover, user-scalable=no minimal-ui">
    <title><?php echo conf('Name');?> - <?php echo conf('SiteName');?></title>
    <meta name="keywords" content="<?php echo conf('Keywords');?>">
    <meta name="description" content="<?php echo conf('Descriptison');?>">
    <link rel="shortcut icon" href="../assets/media/various/favicon.png">
    <link rel="stylesheet" id="css-main" href="../assets/css/oneui.min-5.6.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-9HQDQJJYW7"></script>
    <script charset="UTF-8" id="LA_COLLECT" src="//sdk.51.la/js-sdk-pro.min.js"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());
        gtag('config', 'G-9HQDQJJYW7');
    </script>
    <style>
        .VAPTCHA-init-main {
            display: table;
            width: 100%;
            height: 100%;
            background-color: #eeeeee;
        }

        .VAPTCHA-init-loading {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
        }

        .VAPTCHA-init-loading>a {
            display: inline-block;
            width: 18px;
            height: 18px;
            border: none;
        }

        .VAPTCHA-init-loading .VAPTCHA-text {
            font-family: sans-serif;
            font-size: 12px;
            color: #cccccc;
            vertical-align: middle;
        }
        /* 成员查询结果卡片 */
        .member-result {
            text-align: center;
            padding: 18px 14px 16px;
            background: #f8fafc;
            border: 1px solid #e6ecf4;
            border-radius: 12px;
        }
        .member-result-avatar {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #fff;
            box-shadow: 0 4px 14px rgba(37, 48, 65, .12);
        }
        .member-result-name {
            margin-top: 10px;
            font-size: 15px;
            font-weight: 700;
            color: #253041;
        }
        .member-result-desc {
            margin: 4px 0 10px;
            font-size: 12.5px;
            line-height: 1.6;
            color: #6c757d;
        }
        .member-result-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            justify-content: center;
            margin-bottom: 12px;
        }
        .member-result-tag {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 9px;
            font-size: 12px;
            color: #5b6b80;
            background: #fff;
            border: 1px solid #e6ecf4;
            border-radius: 999px;
        }
        .member-result-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            justify-content: center;
        }
        .dark-mode .member-result {
            background: #1f2937;
            border-color: #2f3d4f;
        }
        .dark-mode .member-result-name { color: #e6ecf4; }
        .dark-mode .member-result-desc { color: #b3c0d1; }
        .dark-mode .member-result-tag {
            color: #bec8d5;
            background: #182130;
            border-color: #2f3d4f;
        }
    </style>
</head>
<main id="main-container">
<div class="content">
<?php
$my=isset($_GET['my'])?$_GET['my']:null;
if($my=='Query')
{
?>
  <div class="row">
    <div class="col-md-12">
      <form method="POST" class="row" onsubmit="return QueryTeam()" role="form">
        <div class="block block-rounded">
          <div class="block-header block-header-default text-center">
            <h3 class="block-title"><?php echo conf('Name');?></h3>
          </div>
          <div class="block-content">
            <div class="row justify-content-center py-sm-3 py-md-5">
              <div class="col-sm-10 col-md-8">
                <div class="mb-3">
                  <label class="form-label" for="keyword">QQ / Telegram</label>
                  <input type="text" class="form-control form-control-alt" id="keyword" name="keyword" placeholder="输入 QQ 号或 TG 用户名" autocomplete="off">
                  <div class="form-text">支持团队成员的 QQ 号或 Telegram 用户名，带 @ 也可以。</div>
                </div>
                <div id="query-result" class="mb-2"></div>
                <?php if(conf('Vaptcha_Open') == 1) {?>
                <div id="vaptchaContainer" class="mb-4">
                    <div class="VAPTCHA-init-main">
                        <div class="VAPTCHA-init-loading">
                            <a href="javascript:void(0)">
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="48px" height="60px" viewBox="0 0 24 30" style="enable-background: new 0 0 50 50; width: 14px; height: 14px; vertical-align: middle" xml:space="preserve">
                                    <rect x="0" y="9.22656" width="4" height="12.5469" fill="#CCCCCC">
                                        <animate attributeName="height" attributeType="XML" values="5;21;5" begin="0s" dur="0.6s" repeatCount="indefinite"></animate>
                                        <animate attributeName="y" attributeType="XML" values="13; 5; 13" begin="0s" dur="0.6s" repeatCount="indefinite"></animate>
                                    </rect>
                                    <rect x="10" y="5.22656" width="4" height="20.5469" fill="#CCCCCC">
                                        <animate attributeName="height" attributeType="XML" values="5;21;5" begin="0.15s" dur="0.6s" repeatCount="indefinite"></animate>
                                        <animate attributeName="y" attributeType="XML" values="13; 5; 13" begin="0.15s" dur="0.6s" repeatCount="indefinite"></animate>
                                    </rect>
                                    <rect x="20" y="8.77344" width="4" height="13.4531" fill="#CCCCCC">
                                        <animate attributeName="height" attributeType="XML" values="5;21;5" begin="0.3s" dur="0.6s" repeatCount="indefinite"></animate>
                                        <animate attributeName="y" attributeType="XML" values="13; 5; 13" begin="0.3s" dur="0.6s" repeatCount="indefinite"></animate>
                                    </rect>
                                </svg>
                            </a>
                            <span class="VAPTCHA-text">人机验证启动中...</span>
                        </div>
                    </div>
                </div>
                <?php }?>
              </div>
            </div>
          </div>
          <div class="block-content block-content-full block-content-sm bg-body-light text-center">
            <button type="submit" class="btn btn-alt-primary">
              <i class="fa fa-check opacity-50 me-1"></i> 立即查询
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
<?php
}
elseif($my=='Join')
{
?>
  <div class="row">
    <div class="col-md-12">
      <form method="POST" class="row" onsubmit="return JoinTeam()" role="form">
        <div class="block block-rounded">
          <div class="block-header block-header-default text-center">
            <h3 class="block-title"><?php echo conf('Name');?></h3>
          </div>
          <div class="block-content">
            <div class="row justify-content-center py-sm-3 py-md-5">
              <div class="col-sm-10 col-md-8">
                <div class="mb-4">
                  <label class="form-label" for="name">成员名称</label>
                  <input type="text" class="form-control form-control-alt" name="TeamName" placeholder="输入您的名称">
                </div>
                <div class="mb-4">
                  <label class="form-label" for="qq">成员QQ</label>
                  <input type="text" class="form-control form-control-alt" name="TeamQq" placeholder="输入您的QQ号">
                </div>
                <div class="mb-4">
                  <label class="form-label" for="tg">Telegram账号（选填）</label>
                  <input type="text" class="form-control form-control-alt" name="TeamTg" placeholder="输入您的TG用户名">
                </div>
                <div class="mb-4">
                  <label class="form-label" for="describe">成员简介</label>
                  <input type="text" class="form-control form-control-alt" name="TeamDescribe" placeholder="输入您的简介">
                </div>
                <div class="mb-4">
                  <label class="form-label" for="applyReason">申请加入理由</label>
                  <textarea class="form-control form-control-alt" name="TeamReason" rows="3" placeholder="请说明您为什么想加入我们"></textarea>
                </div>
                <?php if(conf('Vaptcha_Open') == 1) {?>
                <div id="vaptchaContainer" class="mb-4">
                    <div class="VAPTCHA-init-main">
                        <div class="VAPTCHA-init-loading">
                            <a href="javascript:void(0)">
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="48px" height="60px" viewBox="0 0 24 30" style="enable-background: new 0 0 50 50; width: 14px; height: 14px; vertical-align: middle" xml:space="preserve">
                                    <rect x="0" y="9.22656" width="4" height="12.5469" fill="#CCCCCC">
                                        <animate attributeName="height" attributeType="XML" values="5;21;5" begin="0s" dur="0.6s" repeatCount="indefinite"></animate>
                                        <animate attributeName="y" attributeType="XML" values="13; 5; 13" begin="0s" dur="0.6s" repeatCount="indefinite"></animate>
                                    </rect>
                                    <rect x="10" y="5.22656" width="4" height="20.5469" fill="#CCCCCC">
                                        <animate attributeName="height" attributeType="XML" values="5;21;5" begin="0.15s" dur="0.6s" repeatCount="indefinite"></animate>
                                        <animate attributeName="y" attributeType="XML" values="13; 5; 13" begin="0.15s" dur="0.6s" repeatCount="indefinite"></animate>
                                    </rect>
                                    <rect x="20" y="8.77344" width="4" height="13.4531" fill="#CCCCCC">
                                        <animate attributeName="height" attributeType="XML" values="5;21;5" begin="0.3s" dur="0.6s" repeatCount="indefinite"></animate>
                                        <animate attributeName="y" attributeType="XML" values="13; 5; 13" begin="0.3s" dur="0.6s" repeatCount="indefinite"></animate>
                                    </rect>
                                </svg>
                            </a>
                            <span class="VAPTCHA-text">人机验证启动中...</span>
                        </div>
                    </div>
                </div>
                <?php }?>
              </div>
            </div>
          </div>
          <div class="block-content block-content-full block-content-sm bg-body-light text-center">
            <button type="submit" class="btn btn-alt-primary">
              <i class="fa fa-check opacity-50 me-1"></i> 提交申请
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
  <?php
  }
  ?>
</div>
</main>
<script src="../assets/admin/js/codebase.app.min-5.5.js"></script>
<script type="text/javascript" src="./assets/admin/js/jquery.min.js"></script>
<script type="text/javascript" src="./assets/admin/js/main.min.js"></script>
<script src="./assets/layer/layer.js"></script>
<?php if(conf('Vaptcha_Open') == 1) {?>
<script src='https://v-cn.vaptcha.com/v3.js'></script>
<script>
var obj;
vaptcha({
  vid: '<?php echo conf('Vaptcha_Vid')?>', 
  type: 'click', 
  scene: 0, 
  container: '#vaptchaContainer', 
  offline_server: '#', 
  lang: 'zh-CN',
  https: true,
  color: '#5c8af7'
}).then(function (vaptchaObj) {
  obj = vaptchaObj;
  vaptchaObj.render();
  vaptchaObj.listen('close', function () {
  })
})
</script>
<?php }?>
<script>
var vaptcha_open = 0;
	function escapeHtml(s){
		return String(s == null ? '' : s).replace(/[&<>"']/g, function(ch){
			return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch];
		});
	}
	function renderMember(d){
		var qqUrl = 'https://qm.qq.com/cgi-bin/qm/qr?k=' + encodeURIComponent(d.qq);
		var tgBtn = d.tg
			? '<a class="btn btn-sm btn-alt-info" href="https://t.me/' + encodeURIComponent(d.tg) + '" target="_blank" rel="noopener"><i class="fa-brands fa-telegram"></i> TG</a>'
			: '';
		var tgTag = d.tg
			? '<span class="member-result-tag"><i class="fa-brands fa-telegram"></i>@' + escapeHtml(d.tg) + '</span>'
			: '';
		var html = ''
			+ '<div class="member-result">'
			+   '<img class="member-result-avatar" src="' + escapeHtml(d.avatar) + '" alt="">'
			+   '<div class="member-result-name">' + escapeHtml(d.name) + '</div>'
			+   '<div class="member-result-desc">' + escapeHtml(d.describe) + '</div>'
			+   '<div class="member-result-tags">'
			+     '<span class="member-result-tag"><i class="fa-brands fa-qq"></i>' + escapeHtml(d.qq) + '</span>'
			+     tgTag
			+   '</div>'
			+   '<div class="member-result-actions">'
			+     '<a class="btn btn-sm btn-alt-primary" href="' + qqUrl + '" target="_blank" rel="noopener"><i class="fa-brands fa-qq"></i> QQ</a>'
			+     '<a class="btn btn-sm btn-alt-secondary" href="mailto:' + encodeURIComponent(d.qq) + '@qq.com"><i class="fa-solid fa-envelope"></i> 邮箱</a>'
			+     tgBtn
			+   '</div>'
			+ '</div>';
		$('#query-result').html(html);
	}
	function QueryTeam(){
		if($("#vaptchaContainer").length>0) vaptcha_open=1;
		var query = $("button[type='submit']");
		var keyword = $.trim($("input[name='keyword']").val());
		var data = {keyword:keyword};
		if(keyword == ''){ layer.msg('请输入 QQ 号或 TG 用户名', {icon:2, time:1600}); return false; }
		if(vaptcha_open == 1){
			var token = obj.getToken();
			if(token == ""){ layer.msg('请先完成人机验证！'); return false; }
			data.token = token;
		}
		query.attr('disabled', 'true');
		var loading = layer.msg('正在查询中，请稍后...', {icon:16, time:0});
		$.ajax({
			type : 'POST',
			url : 'Ajax.php?act=Query_submit',
			data : data,
			dataType : 'json',
			success : function(res) {
				query.removeAttr('disabled');
				layer.close(loading);
				if(vaptcha_open == 1 && obj) obj.reset();
				if(res.code == 0 && res.data){
					renderMember(res.data);
					layer.msg('已找到该成员', {icon:1, time:1200});
				}else{
					$('#query-result').html('');
					layer.msg(res.msg || '查询失败', {icon:2, time:1800});
				}
			},
			error : function(){
				query.removeAttr('disabled');
				layer.close(loading);
				if(vaptcha_open == 1 && obj) obj.reset();
				layer.msg('网络异常，请稍后重试', {icon:2, time:1800});
			}
		});
		return false;
	}
		function JoinTeam(){
  		if($("#vaptchaContainer").length>0) vaptcha_open=1;
	    var join = $("button[type='submit']");
	  	var name=$("input[name='TeamName']").val();
	  	var qq=$("input[name='TeamQq']").val();
	  	var tg=$("input[name='TeamTg']").val();
	  	var describe=$("input[name='TeamDescribe']").val();
	  	var reason=$("textarea[name='TeamReason']").val();
    	var data = {name:name,qq:qq,tg:tg,describe:describe,reason:reason};
		if(name=='' || qq=='' || describe==''){ layer.msg('请确保每项都不为空', {icon:2, time:1500});}
	  	if(vaptcha_open==1){
		   	var token = obj.getToken();
		    if(token == ""){
		      	layer.msg('请先完成人机验证！'); return false;
		    }
		    var adddata = {token:token};
	  	}
	    join.attr('disabled', 'true');
        layer.msg('正在提交中，请稍后...');
		$.ajax({
			type : 'POST',
			url : 'Ajax.php?act=Join_submit',
            data: {name:name,qq:qq,tg:tg,describe:describe,reason:reason,token:token},
			dataType : 'json',
			success : function(data) {
			  if(data.code == 0){
			    layer.msg(data.msg, {icon:1, time:1500}, function(){window.location.reload()});
			  }else{
			    layer.msg(data.msg, {icon:2, time:1500});
		        join.removeAttr('disabled');
                if(vaptcha_open == 1 && obj) obj.reset();
			  }
			},
		});
	return false;
}
</script>
<!-- 弹窗内不加载背景音乐播放器 -->
</body>
</html>