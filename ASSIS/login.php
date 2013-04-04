<?php
	$assisURL = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];

	if(count($_POST) > 0){
		$r = users_login($_POST['userMail'],$_POST['userPass'],false,true);
		if($r !== false){header('Location: '.$assisURL);}
	}
?><!doctype html>
<html>
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8">
	<title>BlueCommerce Login</title>
</head>
<body>
	<form action='' method='POST'>
		<ul>
			<li>login <input name='userMail'/></li>
			<li>Pass <input name='userPass' type='password'/></li>
			<li><input type='submit' value='enviar'/></li>
		</ul>
	</form>
</body>
</html>
