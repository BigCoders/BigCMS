<?php
	if(!isset($GLOBALS['CLIENT'])){
		if($_SERVER['SERVER_NAME'] !== 'localhost'){$GLOBALS['CLIENT'] = $_SERVER['SERVER_NAME'];}
		else{$r = preg_match('/([a-zA-Z0-9\.]+)\/g\/PHP\/([a-zA-Z0-9_]+)\.php(\?.*?)$/',$_SERVER['REQUEST_URI'],$m);
		if(!$r){exit;}$GLOBALS['CLIENT'] = $m[1];}
	}

	if(isset($_POST['command'])){
		$command = $_POST['command'];unset($_POST['command']);
		include_once('API_users.php');users_isLogged();if($GLOBALS['userLogged'] === false){return false;}
		switch($command){
			case 'changeMetaInformation':echo html_changeMetaInformation($_POST);break;
		}
		exit;
	}

	function html_changeMetaInformation($data = false,$db = false,$noencode = false){
		/* Esta funcion depende del fichero db/html/meta_$lang.php */
		if($data === false){$a = array('errorCode'=>1,'errorDescription'=>'INVALID_DATA','file'=>__FILE__,'line'=>__LINE__);return ($noencode) ? $a : json_encode($a);}
		$pool = '../clients/'.$GLOBALS['CLIENT'].'/db/html/';
		/* Comprobamos que la carpeta exista o intentamos crearla */
		if(!file_exists($pool)){mkdir($pool,0777,1);if(!file_exists($pool)){$a = array('errorCode'=>1,'errorDescription'=>'NOT_WRITABLE','file'=>__FILE__,'line'=>__LINE__);return ($noencode) ? $a : json_encode($a);}chmod($pool,0777);}
		include_once('inc_common.php');

		$templates = array();
		foreach($data as $k=>$v){
			if(substr($k,-4) == '_php'){$k = substr($k,0,-4);}
			$k = explode('_',$k);
			$templates[$k[1]][$k[2].'.php'][$k[0]] = $v;
		}

//FIXME: comprobar los idiomas permitidos
		foreach($templates as $lang=>$values){
			$fileName = 'meta_'.$lang.'.php';
			$file = $pool.$fileName;
			if(!is_writable($pool)){$a = array('errorCode'=>1,'errorDescription'=>'NOT_WRITABLE','file'=>__FILE__,'line'=>__LINE__);return ($noencode) ? $a : json_encode($a);}
			if(!file_exists($file)){
				$r = file_put_contents($file,'<?php'."\n\t".'$GLOBALS[\'META\'][\'all\'] = array(\'HTML_TITLE\'=>\'\',\'HTML_DESCRIPTION\'=>\'\');'."\n".'?>');
				if($r === false){$a = array('errorCode'=>2,'errorDescription'=>'NOT_WRITABLE','file'=>__FILE__,'line'=>__LINE__);return ($noencode) ? $a : json_encode($a);}
			}
			$currentConfig = file_get_contents($file);
			foreach($values as $k=>$v){
				$string = '';
				if(is_array($v)){$string = "\t".'$GLOBALS[\'META\'][\''.$k.'\'] = '.str_replace(array('":"','","','":{"','"}','{"','[',']','":array','":null','\'),"'),array('\'=>\'','\',\'','\'=>array(\'','\')','array(\'','array(',')','\'=>array','\'=>\'\'','\'),\''),json_encode($v,(JSON_HEX_QUOT | JSON_HEX_APOS))).';'."\n";}
				else{$string = "\t".'$GLOBALS[\'META\'][\''.$k.'\'] = \''.substr(json_encode($v,(JSON_HEX_QUOT | JSON_HEX_APOS)),1,-1).'\';'."\n";}
				$currentConfig = preg_replace('/\t.GLOBALS\[\'META\'\]\[\''.$k.'\'\] = .*?;\n/','',$currentConfig);
				$currentConfig = str_replace('?>',$string.'?>',$currentConfig);
			}

			$currentConfig = str_replace(array('\u00e1','\u00e9','\u00ed','\u00f3','\u00fa','\u00f1',   '\u00c1','\u00c9','\u00cd','\u00d3','\u00da','\u00d1', '\/'),
						array('á','é','í','ó','ú','ñ',  'Á','É','Í','Ó','Ú','Ñ', '/'),$currentConfig);
			$currentConfig = preg_replace('/\\\\u([0-9abcdef]{4})/','&#x$1;',$currentConfig);
			$currentConfig = str_replace(array('\'HTMLtitle\'','\'HTMLdescription\''),array('\'HTML_TITLE\'','\'HTML_DESCRIPTION\''),$currentConfig);

			$backupFile = common_file_createBackup('db/html/'.$fileName);
			$r = file_put_contents($file,$currentConfig);
			$r = common_file_lintCheck('db/html/'.$fileName);
			/* Entonces debemos cargar el fichero de backup */
			if($r == false){common_file_loadBackup($backupFile);}
		}
		//print_r($currentConfig);

		$a = array('errorCode'=>(int)0);
		return ($noencode) ? $product : json_encode($a);
	}
?>
