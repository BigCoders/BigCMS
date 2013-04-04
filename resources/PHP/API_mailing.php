<?php
	if(!isset($GLOBALS['CLIENT'])){if($_SERVER['SERVER_NAME'] !== 'localhost'){$GLOBALS['CLIENT'] = $_SERVER['SERVER_NAME'];}else{$r = preg_match('/([a-zA-Z0-9\.]+)\/g\/PHP\/([a-zA-Z0-9_]+)\.php$/',$_SERVER['REQUEST_URI'],$m);if(!$r){exit;}$GLOBALS['CLIENT'] = $m[1];}}

	$GLOBALS['pool']['mailing'] = '../clients/'.$GLOBALS['CLIENT'].'/db/MAILS/';
//FIXME: el título debe ir en el fichero de template
//FIXME: la dirección de envio de correo debe poderse configurar

	function mailing_sendMail($destMails,$subject = false,$body,$data = array(),$notification = true){
		include_once('../clients/'.$GLOBALS['CLIENT'].'/db/config.php');
		include_once('phpMailer/class.phpmailer.php');
		$mail = new phpmailer();
		$mail->IsSMTP();
		$mail->SMTPAuth = true;
		$mail->Username = $GLOBALS['blueCommerce']['mailerInfo']['senderMail'];
		$mail->Password = $GLOBALS['blueCommerce']['mailerInfo']['senderPass'];
		$mail->SMTPSecure = 'ssl';
		$mail->Host = $GLOBALS['blueCommerce']['mailerInfo']['senderSMTPHost'];
		$mail->Port = $GLOBALS['blueCommerce']['mailerInfo']['senderSMTPPort'];
		$mail->CharSet = 'UTF-8';

		$mail->From = $GLOBALS['blueCommerce']['mailerInfo']['senderMail'];
		$mail->FromName = $GLOBALS['blueCommerce']['mailerInfo']['senderName'];

		$templateName = false;
		if(substr($body,0,9) == 'TEMPLATE:'){$templateName = substr($body,9);if(substr($templateName,-4) != '.txt'){$templateName .= '.txt';}$r = mailing_loadTemplate($templateName);extract($r);if(empty($body)){return false;}}
		if($templateName !== false && (isset($GLOBALS['blueCommerce']['mailing'][$templateName]) || isset($GLOBALS['blueCommerce']['mailing']['all'])) ){
			$m = $GLOBALS['blueCommerce']['mailing'];$copys = array();
			if(isset($m[$templateName])){$copys = array_merge($copys,$m[$templateName]);}
			if(isset($m['all'])){$copys = array_merge($copys,$m['all']);}
			$copys = array_unique($copys);
			if($destMails !== 'dummy'){foreach($copys as $m=>$n){$mail->AddBCC($m,$n);}}
			else{$destMails = $copys;}
		}

		foreach($destMails as $m=>$n){$mail->AddAddress($m,$n);}
		//$mail->AddReplyTo("info@site.com", "Information");
		$mail->WordWrap = 50;
		//$mail->AddAttachment("c:\\temp\\js-bak.sql");  // add attachments
		//$mail->AddAttachment("c:/temp/11-10-00.zip");

		$mail->IsHTML(true);
		$mail->Subject = $subject;
		$mail->Body = $body;
		$r = $mail->Send();
		if(!$r && $notification){
			include_once('API_notifications.php');
			$not = array('notificationUserID'=>false,'notificationUserFrom'=>false,'notificationUserClass'=>'admin,manager','notificationTitle'=>'UNABLE TO SEND MAIL',
			'notificationText'=>'<code>Dest: '.serialize($destMails).'<br>Subject: '.$subject.'</code>'.$body,'notificationTags'=>'critical','notificationStatus'=>99,'notificationModule'=>'MAIL');
			$r = notifications_add($not,$_POST);
		}
		return true;
	}

	function mailing_loadTemplate($templateName){
		if(substr($templateName,-4) == '.txt'){$templateName = substr($templateName,0,-4);}
		$templateFile = '../clients/'.$GLOBALS['CLIENT'].'/db/mails/'.$templateName.'.txt';
		if(!file_exists($templateFile)){return false;}
		$templateCont = file_get_contents($templateFile);
		include_once('inc_common.php');
		$templateCont = common_replaceInMailTemplate($templateCont,$GLOBALS);

		$r = preg_match('/^[^\n]*\n/sm',$templateCont,$m);

		$templateTitle = substr($m[0],0,-1);
		$templateCont = substr($templateCont,strlen($m[0]));
		return array('body'=>$templateCont,'subject'=>$templateTitle);
	}
?>
