<?php
	echo '<ul class=\'assisOptions\'>',
		'<li><a href=\''.$GLOBALS['baseURL_currentASSIS'].'\'>General</a></li>',
//		'<li><a href=\''.$GLOBALS['baseURL_currentASSIS'].'changeTitle\'>Cambiar título</a></li>',
//		'<li><a href=\''.$GLOBALS['baseURL_currentASSIS'].'validPayments\'>Cambiar métodos de pago</a></li>',
	'</ul>';

	function main(){
		if(isset($GLOBALS['message']) && $GLOBALS['message'] !== false){echo T,T,T,'<div class=\'message\'>',$message,'</div>',N;}
		$folder = '../clients/'.$GLOBALS['CLIENT'].'/db/mails/';
		$files = array();if($handle = opendir($folder)){while(false !== ($file = readdir($handle))){if($file[0]!='.' ){$files[] = $file;}}closedir($handle);}
		sort($files);
		echo T,T,T,'<div class=\'block\'><h2>Listado de plantillas para envio de mails</h2>',N,
		T,T,T,T,'<p>Estas son las plantillas que se usan para enviar los diferentes correos dentro del portal.</p>',N,
		T,T,T,'<table><thead><tr><td><span>Nombre de la platilla</span></td><td></td></tr></thead><tbody>',N;
		foreach($files as $t){
			echo T,T,T,T,'<tr><td>',$t,'</td>',
			'<td><a href=\'',$GLOBALS['baseURL_currentASSIS'],'edit/',$t,'#edit\'>Editar</a></td>',
			'<td><a href=\'',$GLOBALS['baseURL_currentASSIS'],'receivers/',$t,'\'>Copias</a></td>',
			'</tr>',N;
		}
		echo T,T,T,'</tbody></table>',N,
		T,T,T,'</div>',N;

		/* Vamos a autodetectar las partes del código de la tienda del cliente en las que se envían correos */
		$r = shell_exec('grep -R "mailing_sendMail" ../clients/'.$GLOBALS['CLIENT'].'/controllers/');
		$r = preg_match_all('/.*?controllers\/(?<controller>[a-z]+)\.php:.*?mailing_sendMail\((?<receivers>[^,]+),(?<title>[^,]+),(?<body>.TEMPLATE:[^,\)]+)/',$r,$m);
		echo T,T,T,'<div class=\'block\'><h2>Plantillas usadas en el cliente</h2>',N,
		T,T,T,T,'<p>Detección automática de envio de mails a través de las plantillas en las diferentes zonas de la aplicación.</p>',N,
		T,T,T,'<table><thead><tr><td><span>Nombre de la platilla</span></td><td>Controlador</td></tr></thead><tbody>',N;
		$templates = array();
		foreach($m[0] as $k=>$v){
			$templateName = substr($m['body'][$k],10,-1);
			$templates[] = $templateName;
			echo '<tr><td>',$templateName,'</td><td>',$m['controller'][$k],'</td></tr>',N;
			//print_r($);
		}
		echo T,T,T,'</tbody></table>',N,
		T,T,T,'</div>',N;

		$templates = array_fill_keys($templates,'');
print_r($GLOBALS['blueCommerce']['langAllowed']);
		foreach($templates as $t=>$v){
			if(!preg_match('/[A-Z]{2}_/',$t)){continue;}
			//FIXME: plantillas de idiomas en la deteccion automática arriba poner banderas en semitransparente por cada
			//lang-allowed en un td diferente, y si isset en ese idioma, entonces se pone completamente en color.
			echo $t;
		}
	}

	function edit($template){
		$folder = '../clients/'.$GLOBALS['CLIENT'].'/db/mails/';
		$template = preg_replace('/[^a-zA-Z0-9\._\-]*/','',$template);
		if(!file_exists($folder.$template)){$GLOBALS['message'] = 'La plantilla seleccionada no existe';return;}
		if(isset($_POST['templateBlob'])){
			if(substr($_POST['templateBlob'],-1) == N){$_POST['templateBlob'] = substr($_POST['templateBlob'],0,-1);}
			$ar = fopen($folder.$template,'w');fwrite($ar,$_POST['templateBlob']);fclose($ar);
			$GLOBALS['message'] = 'La plantilla "'.$template.'" se ha salvado correctamente';
		}

		if(isset($GLOBALS['message']) && $GLOBALS['message'] !== false){echo T,T,T,'<div class=\'message\'>',$GLOBALS['message'],'</div>',N;}

		echo T,T,T,'<div class=\'block\'><h2 id=\'edit\'>Editar Plantilla "',$template,'"</h2>',N,
		T,T,T,'<p>Edición de plantilla.</p>',N,
		T,T,T,'<form action=\'',$GLOBALS['baseURL_currentASSIS'],'edit/',$template,'#edit\' method=\'POST\'>',N,
		T,T,T,T,'<div style=\'border:1px solid #CCC;margin-bottom:10px;\'><textarea name=\'templateBlob\' wrap=\'off\' style=\'width:100%;height:250px;background:white;border:0;\'>',
		file_get_contents($folder.$template),'</textarea></div>',N,
		T,T,T,T,'<ul class=\'buttonHolder\'><li class=\'button assisButton\' onclick=\'form.submit(this);\'><div>Salvar Plantilla</div></li></ul>',N,
		T,T,T,'</form>',N,
		T,T,T,'</div>',N;
	}

	function receivers($template){
		if(count($_POST) > 0){
//FIXME: validar que existe la template
			$receivers = array_fill_keys(explode(',',$_POST['templateReceivers']),'receiver');
			$mailing = $GLOBALS['blueCommerce']['mailing'];
			$mailing[$template] = $receivers;
$r = common_config_save(array('mailing'=>$mailing));
//var_dump($r);
print_r($mailing);
$GLOBALS['blueCommerce']['mailing'] = $mailing;
		}

		$mailing = $GLOBALS['blueCommerce']['mailing'];
		if(!isset($mailing[$template])){
			$mailing[$template] = array();
		}
		$mails = array_keys($mailing[$template]);
		$mails = implode(',',$mails);

		echo T,T,T,'<div class=\'block\'><h2 id=\'edit\'>Recepción de correos "',$template,'"</h2>',N,
		T,T,T,'<p>Edición de plantilla.</p>',N,
		T,T,T,'<form action=\'',$GLOBALS['baseURL_currentASSIS'],'receivers/',$template,'#edit\' method=\'POST\'>',N,
		T,T,T,T,'<div class=\'inputTextSimple\'><input name=\'templateReceivers\' value=\'',$mails,'\'/></div>',
		T,T,T,T,'<ul class=\'buttonHolder\'><li class=\'button assisButton\' onclick=\'form.submit(this);\'><div>Salvar Correos</div></li></ul>',N,
		T,T,T,'</form>',N,
		T,T,T,'</div>',N;
	}
?>
