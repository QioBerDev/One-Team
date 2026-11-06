<?php
include("./Common/Core_brain.php");
$act=isset($_GET['act'])?daddslashes($_GET['act']):null;

switch ($act) {
	case 'contact':
		$name=daddslashes(htmlspecialchars(strip_tags(trim($_POST['name']))));
		$email=daddslashes(htmlspecialchars(strip_tags(trim($_POST['email']))));
		$subject=daddslashes(htmlspecialchars(strip_tags(trim($_POST['subject']))));
		$message=daddslashes(htmlspecialchars(strip_tags(trim($_POST['message']))));
		if ($name==null || $email==null || $subject==null || $message==null) {
			exit('{"code":-1,"msg":"请确保每项都不为空"}');
		}
	    if(conf('Mail_Name') == '' || conf('Mail_Pwd') == ''){exit('{"code":-1,"msg":"请先配置邮箱信息"}');}
		$admins=$DB->query("SELECT * FROM nteam_admin WHERE id=1");
		while($admin = $admins->fetch()){
			$email = $admin['adminQq'].'@qq.com';
		}
		$sub = '网页收到新留言啦~~';
		$msg = "姓名：".$name."
		        邮件：".$email."
		        主题：".$subject."
		        内容：".$message;      // 邮件正文
		$result = send_mail($email, $sub, $msg);
		if($result===true){// 发送邮件
			if ($DB->exec("INSERT INTO `nteam_leave_messages` (`name`,`email`,`subject`,`message`,`intime`) values ('".$name."','".$email."','".$subject."','".$message."','".$date."')")) {
				echo 'OK';
			};
		}else{
			file_put_contents('mail.log',$result);
			exit('{"code":-1,"msg":"留言发送失败"}');
		}
		break;
	case 'subscribe':
	@header('Content-Type: application/json; charset=UTF-8');
		$email=daddslashes(htmlspecialchars(strip_tags(trim($_POST['email']))));
		if (conf('Vaptcha_Open') == 1) {
			$token = $_POST['token'];
		}
		if ($email=='') {
			exit('{"code":-1,"msg":"请确保每项都不为空"}');
		}
		if (conf('Vaptcha_Open') == 1 && $token=='') {
			exit('{"code":-1,"msg":"请先完成人机验证"}');
		}else{
		    if(conf('Mail_Name') == '' || conf('Mail_Pwd') == ''){exit('{"code":-1,"msg":"请先配置邮箱信息"}');}
    		$admins=$DB->query("SELECT * FROM nteam_admin WHERE id=1");
    		while($admin = $admins->fetch()){
    			$email = $admin['adminQq'].'@qq.com';
    		}
    		$sub = '官网有需要订阅的小伙伴哦~~';
    		$msg = "邮箱：".$email;
    		$result = send_mail($email, $sub, $msg);
    		if($result===true){// 发送邮件
    			$email = $_POST['email'];
    			$sub = '成功订阅~~';
    			$msg = '感谢订阅'.conf('Name').'新闻！';
    			$result = send_mail($email, $sub, $msg);
    			if ($result===true) {
    				exit('{"code":1,"msg":"订阅成功！"}');
    			}else{
    				exit('{"code":-1,"msg":"订阅失败！"}');
    			}
    		}
		}
		break;
	case 'Query_submit':
		@header('Content-Type: application/json; charset=UTF-8');
		$keyword = isset($_POST['keyword']) ? $_POST['keyword'] : (isset($_POST['qq']) ? $_POST['qq'] : '');
		$keyword = trim($keyword);
		$keyword = ltrim($keyword, '@');
		$keyword = addslashes($keyword);
		if ($keyword === '') {
			exit('{"code":-1,"msg":"请输入 QQ 号或 Telegram 用户名"}');
		}
		if (conf('Vaptcha_Open') == 1) {
			$token = isset($_POST['token']) ? $_POST['token'] : '';
			if ($token == '') {
				exit('{"code":-1,"msg":"请先完成人机验证"}');
			}
		}
		$fields = "id, name, qq, tg, `describe`";
		$cond   = "Audit_status=1 AND is_show=1";
		$row = $DB->getRow("SELECT {$fields} FROM nteam_team_member WHERE {$cond} AND qq = '{$keyword}' LIMIT 1");
		if (!$row) {
			$row = $DB->getRow("SELECT {$fields} FROM nteam_team_member WHERE {$cond} AND tg = '{$keyword}' LIMIT 1");
		}
		if (!$row) {
			exit('{"code":-1,"msg":"未查询到该成员，请核对 QQ 或 TG 后重试"}');
		}
		$avatar = 'https://q1.qlogo.cn/g?b=qq&nk=' . rawurlencode($row['qq']) . '&s=100';
		exit(json_encode(array(
			'code' => 0,
			'msg'  => '查询成功',
			'data' => array(
				'name'     => $row['name'],
				'describe' => $row['describe'],
				'qq'       => $row['qq'],
				'tg'       => $row['tg'],
				'avatar'   => $avatar
			)
		), JSON_UNESCAPED_UNICODE));
		break;
	case 'Join_submit':
		@header('Content-Type: application/json; charset=UTF-8');
		$name = addslashes($_POST['name']);
		$qq = addslashes($_POST['qq']);
		$tg = addslashes($_POST['tg'] ?? '');
		$describe = addslashes($_POST['describe']);
		$reason = addslashes($_POST['reason'] ?? '');
		if (conf('Vaptcha_Open') == 1) {
			$token = $_POST['token'];
		}
		if(isset($_SESSION['Join_submit']) && $_SESSION['Join_submit']>time()-300){
			exit('{"code":-1,"msg":"请勿频繁申请"}');
		}
		if ($name=='' || $qq=='' || $describe=='') {
			exit('{"code":-1,"msg":"请确保每项都不为空"}');
		}
		if (conf('Vaptcha_Open') == 1 && $token=='') {
			exit('{"code":-1,"msg":"请先完成人机验证"}');
		}else{
	        if(conf('Mail_Name') == '' || conf('Mail_Pwd') == ''){exit('{"code":-1,"msg":"当前未配置邮箱信息，无法发送邮件！"}');}
			$sds=$DB->exec("INSERT INTO `nteam_team_member` (`name`, `qq`, `tg`, `describe`, `reason`, `is_show`, `Audit_status`, `intime`) VALUES ('{$name}', '{$qq}', '{$tg}', '{$describe}', '{$reason}', 0, 0, NOW())");
			$id=$DB->lastInsertId();
			if(!$sds){
				exit('{"code":-1,"msg":"申请提交失败！"}');
			}else{
				$admins=$DB->query("SELECT * FROM nteam_admin WHERE id=1");
				while($admin = $admins->fetch()){
					$email = $admin['adminQq'].'@qq.com';
				}
				$sub = '有小伙伴想加入我们哦！';
				$msg = "赶紧前往后台查看吧！！！";
				$result = send_mail($email, $sub, $msg);
				if ($result===true) {
				    $_SESSION['Join_submit']=time();
					exit('{"code":0,"msg":"申请提交成功！"}');
				}else{
					exit('{"code":-1,"msg":"申请提交失败！"}');
				}
			}
		}
		break;
	default:
		exit('{"code":-4,"msg":"No Act"}');
		break;
}
?>