<?php
	function helper_getClientLangPhrases(){
		$r = shell_exec('grep -R "{%LANG" ../clients/'.$GLOBALS['CLIENT'].'/views/');
		$r = preg_match_all('/\{%LANG_([a-zA-Z0-9_]+)%\}/',$r,$m);
		return $m;
	}

	function main(){
		$m = helper_getClientLangPhrases();

		/* Veamos el número de idiomas disponibles */
		$langs = array();
		$langFolder = '../clients/'.$GLOBALS['CLIENT'].'/db/lang/';
		if($handle = opendir($langFolder)){while(false !== ($file = readdir($handle))){if(($file[0]!='.')){$l = substr($file,0,-4);$langs[$l] = array('lang'=>$l,'langFile'=>$file);}}closedir($handle);}

		foreach($langs as $k=>$lang){
			$file = $langFolder.$lang['langFile'];
			$blob = file_get_contents($file);
			$r = preg_match_all('/\$LANG\[\'([^\']+)\'\] = \'[^\']+\'/',$blob,$phrases);
//print_r($phrases);
			$langs[$k]['langRoute'] = $file;
			$langs[$k]['langTranslated'] = array_intersect($phrases[1],$m[1]);
			$langs[$k]['langUntranslated'] = array_diff($m[1],$langs[$k]['langTranslated']);
			$langs[$k]['langOvertranslated'] = array_diff($phrases[1],$langs[$k]['langTranslated']);
		}
		//print_r($m);

		$totalPhrases = count($m[1]);
		echo T,T,T,'<div class=\'block\'><h2>Idiomas soportados por la plataforma actualmente</h2>',N,
		T,T,T,'<p>Estos son los diferentes idiomas que están instalados dentro de la plataforma actualmente. A través de la siguiente tabla puedes editar y completar los campos de idioma de cada fichero de traducciones.</p>',N,
		T,T,T,'<table><thead><tr><td><span>id</span></td><td>Completed</td><td>Overtranslated</td><td></td></tr></thead><tbody>',N;
		foreach($langs as $lang){
			$currentPhrases = count($lang['langTranslated']);
			$overtranslatedPhrases = count($lang['langOvertranslated']);
			echo T,T,T,T,'<tr><td><span class=\'mono\'>',$lang['lang'],'</span>',(is_writable($lang['langRoute']) ? '' : ' ( <img class=\'warn\' src=\'g/images/icons/warning_panel.png\'> fichero sin permisos de escritura )'),'</td>',
			'<td>',$currentPhrases,'/',$totalPhrases,' (',round(($currentPhrases/$totalPhrases*100),2),' % completed)</td>',
			'<td>',$overtranslatedPhrases,'</td>',
			'<td><a href=\'',$GLOBALS['baseURL_currentASSIS'],'edit/',$lang['lang'],'\'>edit</a></td>',
			'</tr>',N;
		}
		echo T,T,T,'</tbody></table></div>',N;
		echo '<ul class=\'pager\'><li><a href=\'',$GLOBALS['baseURL_currentASSIS'],'last/page/',($GLOBALS['currentPage']-1),'/\'>prev</a></li><li>',$GLOBALS['currentPage'],'</li><li><a href=\'',$GLOBALS['baseURL_currentASSIS'],'last/page/',($GLOBALS['currentPage']+1),'/\'>next</a></li></ul>';
		//print_r($langs);
	}

	function edit($lang){
		$langFolder = '../clients/'.$GLOBALS['CLIENT'].'/db/lang/';
		include($langFolder.$lang.'.php');
		$m = helper_getClientLangPhrases();
		$phrases = array_unique($m[1]);

		echo '<form method=\'post\' action=\'',$GLOBALS['baseURL_currentASSIS'],'save/',$lang,'\'>',N,
		'<div class=\'langTable\'>',N;
		foreach($phrases as $phrase){echo '<div><span>',$phrase,'</span> <div class=\'inputTextSimple\'><textarea name=\'',$phrase,'\'>',(isset($LANG[$phrase]) ? $LANG[$phrase] : ''),'</textarea></div></div>',N;}
		echo '</div>',N,
		'<input type=\'submit\' value=\'enviar\'/>',N,
		'<form>',N;
	}

	function save($lang){
		//print_r($_POST);
		if(count($_POST) > 0){
			$langFile = '../clients/'.$GLOBALS['CLIENT'].'/db/lang/'.$lang.'.php';
			if(!is_writable(dirname($langFile))){$GLOBALS['warn'] = 'Unable to write lang changes. Please, check the permission rights in the lang folder.';return main();}
			$blob = '<?php'.N.T.'$LANG = &$GLOBALS[\'LANG\'];'.N;
			foreach($_POST as $k=>$v){
				$string = json_encode($v,(JSON_HEX_QUOT | JSON_HEX_APOS));
				if($string === '""'){continue;}
				$string = str_replace(array('\u00e1','\u00e9','\u00ed','\u00f3','\u00fa','\u00f1',
					 '\u00c1','\u00c9','\u00cd','\u00d3','\u00da','\u00d1'),
				   array('á','é','í','ó','ú','ñ',
					 'Á','É','Í','Ó','Ú','Ñ'),$string);
				$string = preg_replace('/\\\\u([0-9abcdef]{4})/','&#x$1;',$string);
				$string = substr($string,1,-1);
				$string = str_replace('\/','/',$string);
				$blob .= T.'$LANG[\''.$k.'\'] = \''.$string.'\';'.N;
			}
			$blob .= '?>';
			$fp = fopen($langFile,'w');fwrite($fp,$blob);fclose($fp);
			header('Location: '.$GLOBALS['baseURL_currentASSIS']);exit;
		}
	}
?>
<style>
	.langTable > div{clear:both;border-bottom:1px solid #AAA;}
	.langTable > div > span{float:left;width:250px;}
	.langTable > div > div{margin-left:250px;}
	.langTable > div > div > textarea{width:100%;height:130px;}
</style>
