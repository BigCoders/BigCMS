<?php
	function common_setClient($client){
		include('../clients/'.$client.'/db/config.php');

		$lang = false;
		if(isset($_COOKIE['lang']) && in_array($_COOKIE['lang'],$GLOBALS['blueCommerce']['langAllowed'])){$lang = $_COOKIE['lang'];}
		if($lang == false){$match = false;$langs = common_getLanguajes();while(!$match && $lang = array_shift($langs)){if(is_array($lang)){$lang = array_shift($lang);};if(!in_array($lang,$GLOBALS['blueCommerce']['langAllowed'])){$lang = false;continue;}$match = true;}if($lang == false){$lang = $GLOBALS['blueCommerce']['langFallback'];}}
		$GLOBALS['LANGCODE'] = $lang;

		$GLOBALS['PATH_VIEWS'] = '../clients/'.$client.'/views/';
		$GLOBALS['baseURL'] = $GLOBALS['clientURL'] = 'http://'.$_SERVER['SERVER_NAME'].'/';
		//if($_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['SERVER_NAME'] == $_SERVER['SERVER_ADDR']){
		if($_SERVER['SERVER_NAME'] != $client){
			$installationBasePath = preg_replace('/(^\/|\/resources\/PHP\/?$)/','',substr(dirname($_SERVER['SCRIPT_FILENAME']),strlen($_SERVER['DOCUMENT_ROOT'])));
			$GLOBALS['baseURL'] .= $installationBasePath.'/';
			$GLOBALS['clientURL'] = $GLOBALS['baseURL'].$client.'/';
		}

		$GLOBALS['currentURL'] = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];

		/* currentURI es igual a 'REQUEST_URI' excepto que evita la cabecera local de 'bluecommerce' y de esta
		 * manera podemos salvar diferencias */
		$GLOBALS['currentURI'] = $_SERVER['REQUEST_URI'];
		if($_SERVER['SERVER_NAME'] == 'localhost'){$GLOBALS['currentURI'] = substr($GLOBALS['currentURI'],strlen('/bluecommerce/'.$client));}
		$htmlFile = '../clients/'.$client.'/db/html/meta_'.$GLOBALS['LANGCODE'].'.php';
		if(file_exists($htmlFile)){include($htmlFile);}

		$GLOBALS['clientURL_encoded'] = urlencode($GLOBALS['clientURL']);
		$GLOBALS['currentURL_encoded'] = urlencode($GLOBALS['currentURL']);
	}
	function common_getClient(){
		if(isset($GLOBALS['CLIENT'])){return $GLOBALS['CLIENT'];}
		$client = preg_replace('/^www\./','',$_SERVER['SERVER_NAME']);
		$clientExists = file_exists('../clients/'.$client);
		if($clientExists){return $client;}

		$installationBasePath = preg_replace('/(^\/|\/resources\/PHP\/?$)/','',substr(dirname($_SERVER['SCRIPT_FILENAME']),strlen($_SERVER['DOCUMENT_ROOT'])));
		$client = substr($_SERVER['REQUEST_URI'],strlen($installationBasePath));
		$slash = strpos($client,'/');if($slash !== false){$client = substr($client,0,$slash);}
		$clientExists = file_exists('../clients/'.$client);
		if($clientExists){return $client;}

		/* Asistente */
		$r = preg_match('/\/blue[^\/]+\/([^\/]*)\/ASSIS\//',$_SERVER['REQUEST_URI'],$m);
		if($r){return $m[1];}

		return false;
	}

	function common_getLanguajes(){
		if(!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])){return array('1.0'=>array($GLOBALS['blueCommerce']['langFallback']));}
		$languageList = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
		$languages = array();
		$languageRanges = explode(',',trim($languageList));
		foreach($languageRanges as $languageRange){
			if(!preg_match('/(\*|[a-zA-Z0-9]{1,8}(?:-[a-zA-Z0-9]{1,8})*)(?:\s*;\s*q\s*=\s*(0(?:\.\d{0,3})|1(?:\.0{0,3})))?/',trim($languageRange),$match)){continue;}
			if(!isset($match[2])){$match[2] = '1.0';}
			else{$match[2] = (string)floatval($match[2]);}

			if(!isset($languages[$match[2]])){$languages[$match[2]] = array();}
			$languages[$match[2]][] = strtolower($match[1]);
		}
		krsort($languages);
		return $languages;
	}

	$GLOBALS['OUTPUT'] = false;
	function common_findKword($kword,$pool = false){if($pool == false){$pool = &$GLOBALS;}while(!isset($pool[$kword]) && ($b = strpos($kword,'_'))){$poolName = substr($kword,0,$b);$kword = substr($kword,$b+1);if(!isset($pool[$poolName])){return false;}$pool = &$pool[$poolName];}return (isset($pool[$kword])) ? $pool[$kword] : false;}
	function common_replaceInTemplate($blob,$pool = false,$reps = false){	
		if($reps === false){
			$hasElems = preg_match_all('/{%[a-zA-Z_]+%}/',$blob,$reps);
			if(!$hasElems){return $blob;}
			$reps = array_unique($reps[0]);
		}
if(isset($GLOBALS['debug'])){
print_r($reps);exit;
}
		$notFound = array();
		foreach($reps as $rep){
			$kword = substr($rep,2,-2);
			$word = common_findKword($kword,$pool);if(!$word){$notFound[] = $kword;continue;}
			$blob = str_replace($rep,$word,$blob);continue;
		}
		return $blob;
	}
	
	function common_replaceInMailTemplate($blob,$pool = false,$reps = false){
		preg_match_all('/\{%EACH_[a-zA-Z0-9_]+\{(.*?)\}%\}/sm',$blob,$reps);
		$reps = array_unique($reps[0]);
		foreach($reps as $rep){
			$firstKey = strpos($rep,'{',1);
			$kword = substr($rep,7,$firstKey-7);
			$words = common_findKword($kword);
			if(!is_array($words)){continue;}
			$repContent = substr($rep,$firstKey+1,-3);
			$repContent = preg_replace('/(^[\t\n]*|[\t\n]*$)/','',$repContent)."\n";
			/* Ahora dentro del contenido hay que encontrar los elementos reemplazables */
			$hasElems = preg_match_all('/{%[a-zA-Z_]+%}/',$repContent,$contentKwords);
			if($hasElems){$contentKwords = array_unique($contentKwords[0]);}
			$genContent = '';
			/* Algunas variables comunes deben estar en el pool de reemplazos así que se las añadimos inTime */
			foreach($words as $pool){if(!$hasElems){$genContent .= $repContent;continue;}
				$pool['clientURL'] = $GLOBALS['clientURL'];
				$genContent .= common_replaceInTemplate($repContent,$pool,$contentKwords);
			}
			$blob = str_replace($rep,$genContent,$blob);continue;
		}

		/* Ahora las redirecciones simples */
		preg_match_all('/{%[a-zA-Z0-9_]+%}/',$blob,$reps);
		$reps = array_unique($reps[0]);
		foreach($reps as $rep){
			$kword = substr($rep,2,-2);
			$word = common_findKword($kword);
			$blob = str_replace($rep,$word,$blob);continue;
		}
		return $blob;
	}
	
	function common_renderTemplate($t = false,$lang = false,$noloadbase = false){
		//$t = microtime(1);
		if($GLOBALS['LANGCODE'] !== false && $lang == false){$lang = $GLOBALS['LANGCODE'];}
		/* Soporte para múltiples idiomas en runtime */
		if($lang !== false){$langFile = '../clients/'.$GLOBALS['CLIENT'].'/db/lang/'.$lang.'.php';if(file_exists($langFile)){include($langFile);}}
		$cf = '../clients/'.$GLOBALS['CLIENT'].'/PHP/custom_functions.php';if(file_exists($cf)){include_once($cf);if(function_exists('custom_base')){custom_base();}}
		if($t !== false || !isset($GLOBALS['TEMPLATE_BODY']) || empty($GLOBALS['TEMPLATE_BODY'])){if(substr($t,-4) !== '.php'){$t .= '.php';}ob_start();include($GLOBALS['PATH_VIEWS'].$t);$GLOBALS['TEMPLATE_BODY'] = ob_get_contents();ob_end_clean();}


		/* *** METAinformación de la página *** */
		if(isset($GLOBALS['META'][$t])){$GLOBALS['META'][$t] = array_diff($GLOBALS['META'][$t],array(''));$currentMeta = array();if(isset($GLOBALS['HTML_TITLE'])){$currentMeta['HTML_TITLE'] = $GLOBALS['HTML_TITLE'];}if(isset($GLOBALS['HTML_DESCRIPTION'])){$currentMeta['HTML_DESCRIPTION'] = $GLOBALS['HTML_DESCRIPTION'];}$GLOBALS = array_merge($GLOBALS,$GLOBALS['META']['all'],$currentMeta,$GLOBALS['META'][$t]);}
		/* Comprobamos si hay reglas de URI para poder reemplazar */
		if(isset($GLOBALS['blueCommerce']['HTML_uriRules'][$GLOBALS['LANGCODE']])){$rules = $GLOBALS['blueCommerce']['HTML_uriRules'][$GLOBALS['LANGCODE']];foreach($rules as $k=>$data){if(preg_match('/'.preg_quote($data['URI_RULE'],'/').'/',$GLOBALS['currentURI'])){$GLOBALS = array_merge($GLOBALS,$data);}break;}}
		/* *** */


		if($noloadbase === false){ob_start();include($GLOBALS['PATH_VIEWS'].'base.php');$GLOBALS['OUTPUT'] = ob_get_contents();ob_end_clean();}
		else{$GLOBALS['OUTPUT'] = $GLOBALS['TEMPLATE_BODY'];unset($GLOBALS['TEMPLATE_BODY']);}
		/* Ahora tenemos todo el código que se va a renderizar en $GLOBALS['OUTPUT'], primero reemplazamos los elementos simples */
		$GLOBALS['OUTPUT'] = common_replaceInTemplate($GLOBALS['OUTPUT']);

		/* Primero vamos a detectar las condiciones del código */
		//preg_match_all('/{%IF_([^\{]+)\{(.*)\}%}/s',$GLOBALS['OUTPUT'],$reps);
		//$reps = array_unique($reps[0]);
		//FIXME TODO

		/* Detectamos el renderizado de páginas */
		if(strpos($GLOBALS['OUTPUT'],'{%PAGE_(')){
			preg_match_all('/\{%PAGE_\([^\(]+\){.*?\}%\}/sm',$GLOBALS['OUTPUT'],$reps);
			$reps = array_unique($reps[0]);
			include_once('API_pages.php');
			foreach($reps as $rep){
				$firstKey = strpos($rep,'{',1);
				$criterias = substr($rep,8,$firstKey-9);
				$criterias = explode('|',$criterias);
				$whereClause = '';
				foreach($criterias as $c){
					$p = strpos($c,':');$h = substr($c,0,$p);$v = substr($c,$p+1);
					switch($h){
						case 'tags':$whereClause .= '(pageTags LIKE \','.$v.',\') AND ';break;
						case 'lang':if($v = 'globals'){$v = $lang;}$whereClause .= '(pageLang = \''.$v.'\') AND ';break;
					}
				}
				if($whereClause == ''){continue;}
				$whereClause = substr($whereClause,0,-4);
				$page = page_getWhereSingle($whereClause,false,true);

				$repContent = substr($rep,$firstKey+1,-3);
				$repContent = preg_replace('/(^[\t\n]*|[\t\n]*$)/','',$repContent);
				$genContent = common_replaceInTemplate($repContent,$page);
				$GLOBALS['OUTPUT'] = str_replace($rep,$genContent,$GLOBALS['OUTPUT']);
			}
		}


		/* Segundo vamos a detectar los bucles de código */
		preg_match_all('/\{%EACH_[a-zA-Z0-9_]+\{(.*?)\}%\}/sm',$GLOBALS['OUTPUT'],$reps);
		$reps = array_unique($reps[0]);
		foreach($reps as $rep){
			$firstKey = strpos($rep,'{',1);
			$kword = substr($rep,7,$firstKey-7);
			$words = common_findKword($kword);
			if(!is_array($words)){continue;}
			$repContent = substr($rep,$firstKey+1,-3);
			$repContent = preg_replace('/(^[\t\n]*|[\t\n]*$)/','',$repContent);
			/* Ahora dentro del contenido hay que encontrar los elementos reemplazables */
			$hasElems = preg_match_all('/{%[a-zA-Z_]+%}/',$repContent,$contentKwords);
			if($hasElems){$contentKwords = array_unique($contentKwords[0]);}
			$genContent = '';
			/* Algunas variables comunes deben estar en el pool de reemplazos así que se las añadimos inTime */
			foreach($words as $pool){if(!$hasElems){$genContent .= $repContent;continue;}$pool['clientURL'] = $GLOBALS['clientURL'];$genContent .= common_replaceInTemplate($repContent,$pool,$contentKwords);}
			$GLOBALS['OUTPUT'] = str_replace($rep,$genContent,$GLOBALS['OUTPUT']);continue;
		}

		if(strpos($GLOBALS['OUTPUT'],'{%EACHFUNCTION_')){
			preg_match_all('/\{%EACHFUNCTION_[a-zA-Z0-9_]+\{(.*?)\}%\}/sm',$GLOBALS['OUTPUT'],$reps);
			$reps = array_unique($reps[0]);
			foreach($reps as $rep){
				$firstKey = strpos($rep,'{',1);
				$kword = substr($rep,15,$firstKey-15);
				$words = common_findKword($kword);
				if(!is_array($words)){continue;}
				$repContent = substr($rep,$firstKey+1,-3);

				$firstKey = strpos($repContent,'(');
				$funcName = substr($repContent,0,$firstKey);
				$params = explode(',',substr($repContent,$firstKey+1,-1));

				ob_start();
				foreach($words as $pool){$l = $params;foreach($l as $k=>$p){if($p == '{%}'){$l[$k] = $pool;continue;}$l[$k] = common_findKword(substr($p,2,-2),$pool);}call_user_func_array($funcName,$l);}
				$buff = ob_get_contents();
				ob_end_clean();
				$GLOBALS['OUTPUT'] = str_replace($rep,$buff,$GLOBALS['OUTPUT']);continue;
			}
		}

		/* Ahora las redirecciones simples */
		preg_match_all('/{%[a-zA-Z0-9_]+%}/',$GLOBALS['OUTPUT'],$reps);
		$reps = array_unique($reps[0]);
		foreach($reps as $rep){
			$kword = substr($rep,2,-2);
			$word = common_findKword($kword);
			$GLOBALS['OUTPUT'] = str_replace($rep,$word,$GLOBALS['OUTPUT']);continue;
		}

		//echo microtime(1)-$t;exit;
		/* Soporte para google Analytics */
		if(isset($GLOBALS['blueCommerce']['googleAnalytics'])){
			if(substr($GLOBALS['blueCommerce']['googleAnalytics'],0,3) == 'UA-'){
				$GLOBALS['blueCommerce']['googleAnalytics'] = '<script type="text/javascript">var _gaq = _gaq || [];_gaq.push(["_setAccount","'.$GLOBALS['blueCommerce']['googleAnalytics'].'"]);_gaq.push(["_trackPageview"]);(function(){var ga = document.createElement("script");ga.type = "text/javascript";ga.async = true;ga.src = ("https:" == document.location.protocol ? "https://ssl" : "http://www")+".google-analytics.com/ga.js";var s = document.getElementsByTagName("script")[0];s.parentNode.insertBefore(ga,s);})();</script>';
			}
			$GLOBALS['OUTPUT'] = preg_replace('/<\/body>/',$GLOBALS['blueCommerce']['googleAnalytics'],$GLOBALS['OUTPUT']);
		}
		
		$GLOBALS['OUTPUT'] = str_replace(array('\r','\t','\n'), '', $GLOBALS['OUTPUT']);
		return $GLOBALS['OUTPUT'];
	}

	function common_nakedTemplate($t){
		if(substr($t,-4) !== '.php'){$t .= '.php';}
		ob_start();
		include($GLOBALS['PATH_VIEWS'].$t);
		$GLOBALS['OUTPUT'] = ob_get_contents();
		ob_end_clean();
		return $GLOBALS['OUTPUT'];
	}

	function common_drawPaginator($numRowsTotal = false){
		if($numRowsTotal !== false){$GLOBALS['paginator']['numRowsTotal'] = $numRowsTotal;}
		if($GLOBALS['paginator']['numRowsTotal'] < 1){return;}
		$numPages = ceil($GLOBALS['paginator']['numRowsTotal']/$GLOBALS['paginator']['numRowsPerPage']);
		echo N,T,T,T,'<div class=\'pagination\'>',N,T,T,T,T;

		$currentPage = $GLOBALS['paginator']['currentPage'];
		$baseURL = $GLOBALS['currentURL'];
		if(substr($baseURL,-1) !== '/'){$baseURL .= '/';}

		if($currentPage > 2){echo '<a href=\'',$baseURL,'\'><span class=\'grey1FormButton\'>primera página</span></a>';}
		if($currentPage > 1){$target = 'pag/'.($currentPage-1);if($target == 'pag/1'){$target = '';}echo '<a href=\'',$baseURL,$target,'\'><span class=\'grey1FormButton\'>página anterior</span></a>';}
		$top = ($currentPage-3);if($top < 0){$top = 0;}
		$bottom = ($currentPage+2);if($bottom > $numPages){$bottom = $numPages;}
		while($top < $bottom){$top++;if($top == $currentPage){echo '<span>',$top,'</span>';continue;}echo '<a href=\'',$baseURL,'pag/',$top,'\'><span class=\'grey1FormButton\'>',$top,'</span></a>';}
		if($currentPage < $numPages){echo '<a href=\'',$baseURL,'pag/',($currentPage+1),'\'><span class=\'grey1FormButton\'>página siguiente</span></a>';}
		if($currentPage < $numPages-1){echo '<a href=\'',$baseURL,'pag/',$numPages,'\'><span class=\'grey1FormButton\'>última página</span></a>';}
		echo N,T,T,T,'</div>',N;
	}

	$GLOBALS['ARRAYS'] = array();
	function common_array_register($name,$elem,$id = 'id'){
		if(!isset($GLOBALS['ARRAYS'][$name])){$GLOBALS['ARRAYS'][$name] = array();}
		if(!isset($elem[$id]) && isset($elem[key($elem)][$id])){$GLOBALS['ARRAYS'][$name] = array_merge($GLOBALS['ARRAYS'][$name],$elem);return;}
		if(!isset($elem[$id])){return false;}
		$GLOBALS['ARRAYS'][$name][$elem[$id]] = $elem;
	}
	function common_array_toJS($name){
		$js = 'var '.$name.' = {};'.N;
		//FIXME: if not isset elem['id'] entonces hacemos el $key
		if(!isset($GLOBALS['ARRAYS'][$name]) || count($GLOBALS['ARRAYS'][$name]) < 1){return $js;}
		$key = key(current($GLOBALS['ARRAYS'][$name]));
		foreach($GLOBALS['ARRAYS'][$name] as $k=>$v){$js .= $name.'[\''.$v[$key].'\'] = \''.json_encode($v,JSON_HEX_APOS | JSON_HEX_QUOT).'\';'.N;}
		$js = str_replace(array('\\','\u00e1','\u00e9','\u00ed','\u00f3','\u00fa','\u00f1',   '\u00c1','\u00c9','\u00cd','\u00d3','\u00da','\u00d1'),
					array('\\\\','á','é','í','ó','ú','ñ',  'Á','É','Í','Ó','Ú','Ñ'),$js);
		$js = preg_replace('/\\\\u([0-9abcdef]{4})/','&#x$1;',$js);
		return $js;
	}

	function common_include_once($name){$cf = '../clients/'.$GLOBALS['CLIENT'].'/PHP/'.$name;if(file_exists($cf)){return include_once($cf);}else{return false;}}

	function common_indexByLang(&$elem){
		$d = preg_match_all('/<div lang=\'([a-z]{2}\-[a-z]{2}|default)\'>(.*?)<\/div>/ms',$elem,$m);
		$elem = array();if($d){foreach($m[0] as $k=>$v){$elem[$m[1][$k]] = $m[2][$k];}}
	}

	function common_file_createBackup($relativePath){
		$clientPath = '../clients/'.$GLOBALS['CLIENT'].'/';
		$filePath = $clientPath.$relativePath;
		$backupBasePath = $clientPath.'backup/';
		if(!file_exists($filePath)){return false;}
		if(!file_exists($backupBasePath)){mkdir($backupBasePath);if(!file_exists($backupBasePath)){return false;}}
		$elem = pathinfo($filePath);
		$time = time();
		$backupFile = base64_encode($relativePath.'{'.$time.'}').'.php';
		$backupPath = $backupBasePath.$backupFile;
		copy($filePath,$backupPath);
		if(!file_exists($backupPath)){return false;}
		return $backupFile;
	}
	function common_file_loadBackup($backupFile){
		$clientPath = '../clients/'.$GLOBALS['CLIENT'].'/';
		$backupPath = $clientPath.'backup/'.$backupFile;
		if(!file_exists($backupPath)){return false;}
		$decodedInfo = base64_decode(substr($backupFile,0,-4));
		$m = preg_match('/^(.*?)\{([0-9]*)\}$/',$decodedInfo,$parts);
		$filePath = $clientPath.$parts[1];
		copy($backupPath,$filePath);
		return true;
	}
	function common_file_lintCheck($relativePath){
		/* {true} para ok, {false} si tiene algún error */
		$r = shell_exec('php -l ../clients/'.$GLOBALS['CLIENT'].'/'.$relativePath);
		return (strpos($r,'No syntax errors detected in') !== false);
	}

	function common_config_save($vars){
		$config = '../clients/'.$GLOBALS['CLIENT'].'/db/config.php';
		$currentConfig = file_get_contents($config);
		foreach($vars as $k=>$v){
			$string = '';
			if(is_array($v)){$string = "\t".'$GLOBALS[\'blueCommerce\'][\''.$k.'\'] = '.str_replace(array('":"','","','":{"','"}','{"','}','[',']','":array','":null','\'),"'),array('\'=>\'','\',\'','\'=>array(\'','\')','array(\'',')','array(',')','\'=>array','\'=>\'\'','\'),\''),json_encode($v,(JSON_HEX_QUOT | JSON_HEX_APOS))).';'."\n";}
			else{$string = "\t".'$GLOBALS[\'blueCommerce\'][\''.$k.'\'] = \''.substr(json_encode($v,(JSON_HEX_QUOT | JSON_HEX_APOS)),1,-1).'\';'."\n";}
/*echo "\n".$string;
eval($string);
print_r($GLOBALS['blueCommerce']['mailing']);
exit;//*/
			$currentConfig = preg_replace('/\t.GLOBALS\[\'blueCommerce\'\]\[\''.$k.'\'\] = .*?;\n/','',$currentConfig);
			$currentConfig = str_replace('?>',$string.'?>',$currentConfig);
		}

		$currentConfig = str_replace(array('\u00e1','\u00e9','\u00ed','\u00f3','\u00fa','\u00f1',   '\u00c1','\u00c9','\u00cd','\u00d3','\u00da','\u00d1', '\/'),
					array('á','é','í','ó','ú','ñ',  'Á','É','Í','Ó','Ú','Ñ', '/'),$currentConfig);
		$currentConfig = preg_replace('/\\\\u([0-9abcdef]{4})/','&#x$1;',$currentConfig);

		$backupFile = common_file_createBackup('db/config.php');
		$ar = fopen($config,'w');fwrite($ar,$currentConfig);fclose($ar);
		$r = common_file_lintCheck('db/config.php');
		/* Entonces debemos cargar el fichero de backup */
		if($r == false){common_file_loadBackup($backupFile);}
		return true;
	}

	function common_message_push($text){
		if(!isset($_SESSION['messages'])){$_SESSION['messages'] = array();}
		$_SESSION['messages'][] = $text;
	}
	function common_message_shift(){
		if(!isset($_SESSION['messages']) || !count($_SESSION['messages'])){return false;}
		return array_shift($_SESSION['messages']);
	}
?>
