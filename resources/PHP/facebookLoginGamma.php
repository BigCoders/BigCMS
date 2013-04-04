<?php
	if(!strpos(getcwd(),'resources/PHP')){chdir('resources/PHP');}
	include_once('inc_common.php');
	$GLOBALS['CLIENT'] = common_getClient();
	include_once('API_users.php');

	$GLOBALS['baseURL'] = 'http://'.$_SERVER['SERVER_NAME'].'/';
	if($_SERVER['SERVER_NAME'] == 'localhost'){$GLOBALS['baseURL'] .= 'blueCommerce/';}
	$GLOBALS['currentURL'] = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];

	common_setClient($GLOBALS['CLIENT']);
	/* Estos datos son de las aplicaciones locales de facebook de Marcos, así que NO HACER GILIPOLLECES CON ellas :-) */
	if($_SERVER['HTTP_HOST'] == 'localhost'){$GLOBALS['blueCommerce']['facebookApp'] = array('appId'=>'144719738902056','secret'=>'d6d0df1d4ff6ce3ec8731e3e33170124','cookie'=>true);}
	if(!isset($GLOBALS['blueCommerce']['facebookApp'])){return false;}

	/* debemos guardar la anterior URL para volver al sition mediante el cuál se hizo login, aunque solo en caso
	 * de que el referer pertenezca al mismo dominio que estamos usando */
	//FIXME: TODO de momento redirigimos siempre a la principal
	$saveUri = $GLOBALS['clientURL'];

	/* Si el usuario ya se encuentra logueado nos movemos a la página correspondiente directamente */
	if(users_isLogged()){header('Location: '.$saveUri);exit;}

	require('facebookAPI_3.1.1.php');
	$facebook = new Facebook($GLOBALS['blueCommerce']['facebookApp']);
	$user = $facebook->getUser();

	$login_url = $facebook->getLoginUrl(array('scope'=>'email,user_birthday,publish_stream','redirect_uri'=>$GLOBALS['clientURL'].'facebookLogin/?uri='.base64_encode($saveUri)));

	if($user === 0){header('Location: '.$login_url);exit;}
	/* El usuario está logueado (en principio) y ha entrado el parámetro 'uri', que proviene de la vuelta de facebook,
	 * No salimos aún, intentamos actualizar los datos de usuario a partir de los del perfil de facebook */
	if(isset($_GET['uri'])){$saveUri = base64_decode($_GET['uri']);}

	try{$profile = $facebook->api('/me');}
	catch(Exception $e){$user = 0;header('Location: '.$login_url);exit;}
	if(empty($profile) || $profile == array()){echo 'There was an error.';exit;}
	/*print_r($profile);exit;*/

	$user = users_getByFacebookID($profile['id'],false,true);
	if($user === false){
		/* Ahora pueden ocurrir 2 cosas, que el usuario esté previamente registrado o que no */
		$user = users_getByMail($profile['email'],false,true);
		if($user === false){
			/* Tenemos que crear nuevo usuario */
			$name = $profile['first_name'].','.$profile['middle_name'].' '.$profile['last_name'];
			list($bMonth,$bDay,$bYear) = explode('/',$profile['birthday']);
			$birthDay = $bYear.'-'.str_pad($bMonth,2,'0',STR_PAD_LEFT).'-'.str_pad($bDay,2,'0',STR_PAD_LEFT);
			$mail = $profile['email'];

			$newUser = array('userName'=>$name,'userBirth'=>$birthDay,'userMail'=>$mail);
			$r = users_create($newUser,false,true);
			if(isset($r['errorDescription'])){echo 'There was an error.';exit;}

			$r = users_update($user['id'],array('userFacebookID'=>$profile['id'],'userIP'=>$_SERVER['REMOTE_ADDR'],'userLastLogin'=>date('Y-m-d H:i:s')),false,true);
			if(isset($r['errorDescription'])){echo 'There was an error.';exit;}
			$user = $r;
			/*if(file_exists('API_mails.php') && file_exists('../db/MAILS/templates/ES_facebook_activation_to_client.txt')){
				include_once('API_mails.php');
				$destName = array($user['userName']);
				$subject = 'Confirmación de tu alta en pidemesa';
				$destMails = array($user['userMail']);
				$r = mails_sendMail($destMails,$destName,$subject,'TEMPLATE:ES_facebook_activation_to_client',$user);
			}*/
		}else{
			/* Actualizar el anterior usuario */
			$r = users_update($user['id'],array('userFacebookID'=>$profile['id'],'userIP'=>$_SERVER['REMOTE_ADDR'],'userLastLogin'=>date('Y-m-d H:i:s')),false,true);
			if(isset($r['errorDescription'])){print_r($r);echo 'There was an error. 2';exit;}
			$user = $r;
		}
	}

	/* Sincronizamos el avatar del usuario */
	$file = file_get_contents('http://graph.facebook.com/'.$profile['id'].'/picture?type=large');
	$tmpName = microtime(1);
	$ar = fopen('/tmp/'.$tmpName,'w');fwrite($ar,$file);fclose($ar);
	users_avatar($user['id'],'/tmp/'.$tmpName);
	unlink('/tmp/'.$tmpName);

	$domain = '.'.$_SERVER['SERVER_NAME'];
	if($_SERVER['SERVER_NAME'] == 'localhost'){$domain = false;}
	setcookie('user',$user['userMail'],time()+72000,'/',$domain);
	setcookie('pass',$user['userPass'],time()+72000,'/',$domain);
	header('Location: '.$saveUri);exit;
?>
