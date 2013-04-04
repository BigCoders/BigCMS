<?php
	if(!isset($GLOBALS['CLIENT'])){
		if($_SERVER['SERVER_NAME'] !== 'localhost'){$GLOBALS['CLIENT'] = $_SERVER['SERVER_NAME'];}
		else{$r = preg_match('/([a-zA-Z0-9\.]+)\/g\/PHP\/([a-zA-Z0-9_]+)\.php(\?.*?|)$/',$_SERVER['REQUEST_URI'],$m);
		if(!$r){exit;}$GLOBALS['CLIENT'] = $m[1];}
	}

	$GLOBALS['db']['menus'] = '../clients/'.$GLOBALS['CLIENT'].'/db/menus/';
	$GLOBALS['array2json_replace'] = array('":"','","','":{"','"}','{"','[',']','":array','":null','\'),"','":');
	$GLOBALS['array2json_replacement'] = array('\'=>\'','\',\'','\'=>array(\'','\')','array(\'','array(',')','\'=>array','\'=>\'\'','\'),\'','\'=>');

	//$r =  menus_createNew('uu prueba',true);
	function menus_createNew($menuTitle,$noencode = false){
		include_once('inc_strings.php');
		$menuName = strings_stringToURLWithDot($menuTitle);
		$menuFile = $menuName.'.php';
		$menuObj = array('menuTitle'=>$menuTitle,'menuName'=>$menuName,'menuFile'=>$menuFile);
		if(file_exists($GLOBALS['db']['menus'].$menuFile)){$a = array('errorCode'=>1,'errorDescription'=>'MENU_ALREADY_EXISTS','file'=>__FILE__,'line'=>__LINE__);return $noencode ? $a : json_encode($a);}
		$blob = '<?php'."\n\t".'$menuInfo = array('.str_replace($GLOBALS['array2json_replace'],$GLOBALS['array2json_replacement'],substr(json_encode($menuObj,JSON_HEX_APOS),1,-1)).');'."\n\t".'$menu = array();'."\n".'?>';
		$blob = str_replace(array('\u00e1','\u00e9','\u00ed','\u00f3','\u00fa','\u00f1','\u00c1','\u00c9','\u00cd','\u00d3','\u00da','\u00d1'),
		    array('á','é','í­','ó','ú','ñ','Á','É','Í','Ó','Ú','Ñ'),$blob);
		$blob = stripslashes(preg_replace('/\\\\u([0-9abcdef]{4})/','&#x$1;',$blob));
		$blob = str_replace(array('array("','");'),array('array(\'','\');'),$blob);
		$fp = fopen($GLOBALS['db']['menus'].$menuFile,'w');fwrite($fp,$blob);fclose($fp);
		chmod($GLOBALS['db']['menus'].$menuFile,0777);
		$r = menus_createIndex(true);
		if($noencode){return $menuObj;}
		echo '{"errorCode":0}';
	}

	//menus_createIndex();
	function menus_createIndex($noencode = false){
		include_once('inc_strings.php');
		$indexFile = $GLOBALS['db']['menus'].'_index.php';
		if(file_exists($indexFile) && !is_writable($indexFile)){if($noencode){return false;}return json_encode(array('errorCode'=>1,'errorDescription'=>'INDEX_FILE_NOT_WRITABLE','file'=>__FILE__,'line'=>__LINE__));}

		$menus = menus_getAllFiles();

		if(!defined('T')){define('T',"\t");}
		if(!defined('N')){define('N',"\n");}

		$blob = '<?php'.N;
		foreach($menus as $menuName){
			include($GLOBALS['db']['menus'].$menuName);
			//FIXME: esto hay que cambiarlo
			$menuName = strings_stringToURLWithDot(substr($menuName,0,-4));
			$menuCount = count($menu);
			$item = array('menuName'=>$menuName,'menuItems'=>$menuCount);
			$blob .= T.'$menus[\''.$menuName.'\'] = array('.str_replace($GLOBALS['array2json_replace'],$GLOBALS['array2json_replacement'],substr(json_encode($item,JSON_HEX_APOS),1,-1)).');'.N;
		}
		$blob .= '?>';
		$blob = str_replace(array('\u00e1','\u00e9','\u00ed','\u00f3','\u00fa','\u00f1','\u00c1','\u00c9','\u00cd','\u00d3','\u00da','\u00d1'),
		    array('á','é','í­','ó','ú','ñ','Á','É','Í','Ó','Ú','Ñ'),$blob);
		$blob = stripslashes(preg_replace('/\\\\u([0-9abcdef]{4})/','&#x$1;',$blob));
		$blob = str_replace(array('array("','");'),array('array(\'','\');'),$blob);
		$fp = fopen($indexFile,'w');fwrite($fp,$blob);fclose($fp);
		if($noencode){return true;}
		return '{"errorCode":0}';
	}

	function menus_getAllFiles(){$files = array();if($handle = opendir($GLOBALS['db']['menus'])){while(false !== ($file = readdir($handle))){if($file[0]!='.' && $file!='_index.php'){$files[] = $file;}}closedir($handle);}return $files;}
	function menus_getMenu($menuName,$noencode = false){
		if(substr($menuName,-4) != '.php'){$menuName .= '.php';}
		if(!file_exists($GLOBALS['db']['menus'].$menuName)){if($noencode){return false;}return json_encode(array('errorCode'=>1,'errorDescription'=>'MENU_NOT_EXISTS','file'=>__FILE__,'line'=>__LINE__));}
		include($GLOBALS['db']['menus'].$menuName);
		return $menu;
	}

	function menus_menuRemove($menuName,$noencode){
		$menuFile = $menuName.'.php';
		if(file_exists($GLOBALS['db']['menus'].$menuFile)){unlink($GLOBALS['db']['menus'].$menuFile);}
		menus_createIndex(true);
		if($noencode){return true;}
		return '{"errorCode":0}';
	}

	//menus_appendItem('sidebar',array('title'=>'Cerezas','class'=>'link','href'=>'%%clientURL%%c/cerezas/'));
	//exit;
	function menus_appendItem($menuName,$items,$noencode = false){
		if(substr($menuName,-4) != '.php'){$menuName .= '.php';}
		if(!file_exists($GLOBALS['db']['menus'].$menuName)){if($noencode){return false;}return json_encode(array('errorCode'=>1,'errorDescription'=>'MENU_NOT_EXISTS','file'=>__FILE__,'line'=>__LINE__));}
		include($GLOBALS['db']['menus'].$menuName);
		//FIXME: esto merece algunas comprobaciones extra

		if(!defined('T')){define('T',"\t");}
		if(!defined('N')){define('N',"\n");}

		$blob = '<?php'.N.T.'$menu = array();'.N.'?>';
		/* Detectamos si el elemento es único */
		if(isset($items['title'])){$items = array($items);}

		foreach($items as $item){
			if(isset($item['index']) && isset($menu[$item['index']])){$menu[$item['index']] = array_merge($menu[$item['index']],$item);}
			else{$menu[] = array_merge($item,array('index'=>0));}
		}

		ksort($menu);$newMenu = array();$i = 0;foreach($menu as $k=>$n){$newMenu[$i] = array_merge($n,array('index'=>$i));$i++;}$menu = $newMenu;

		foreach($menu as $item){
			$line = T.'$menu['.$item['index'].'] = array('.str_replace($GLOBALS['array2json_replace'],$GLOBALS['array2json_replacement'],substr(json_encode($item,JSON_HEX_QUOT | JSON_HEX_APOS),1,-1)).');'.N;
			$blob = str_replace('?>',$line.'?>',$blob);
		}

		$blob = str_replace(array('\u00e1','\u00e9','\u00ed','\u00f3','\u00fa','\u00f1','\u00c1','\u00c9','\u00cd','\u00d3','\u00da','\u00d1'),
		    array('á','é','í­','ó','ú','ñ','Á','É','Í','Ó','Ú','Ñ'),$blob);
		$blob = stripslashes(preg_replace('/\\\\u([0-9abcdef]{4})/','&#x$1;',$blob));
		$blob = str_replace(array('array("','");'),array('array(\'','\');'),$blob);
		$fp = fopen($GLOBALS['db']['menus'].$menuName,'w');fwrite($fp,$blob);fclose($fp);
		if($noencode){return true;}
		echo '{"errorCode":0}';
	}

	function menus_itemSave($menuName,$item,$noencode = false){
		if(substr($menuName,-4) != '.php'){$menuName .= '.php';}
		include($GLOBALS['db']['menus'].$menuName);
		$menu[$item['index']] = array_merge($menu[$item['index']],$item);
		$r = menus_appendItem($menuName,$menu,true);
		if($noencode){return true;}
		echo '{"errorCode":0}';
	}

//echo menus_itemChangeIndex('sidebar',0,1,true);
	function menus_itemChangeIndex($menuName,$index,$newIndex,$noencode){
//FIXME: se puede mejorar usando menus_appendItem
		if($index == $newIndex){return $noencode ? true : '{"errorCode":0}';}
		$menuFile = $menuName.'.php';
		if(!file_exists($GLOBALS['db']['menus'].$menuFile)){$a = array('errorCode'=>1,'errorDescription'=>'MENU_NOT_EXISTS','file'=>__FILE__,'line'=>__LINE__);return $noencode ? $a : json_encode($a);}
		include($GLOBALS['db']['menus'].$menuFile);
		if(!isset($menu[$index])){$a = array('errorCode'=>1,'errorDescription'=>'ITEM_NOT_EXISTS','file'=>__FILE__,'line'=>__LINE__);return $noencode ? $a : json_encode($a);}
		$tmp = isset($menu[$newIndex]) ? $menu[$newIndex] : false;
		$menu[$newIndex] = $menu[$index];unset($menu[$index]);
		if($tmp != false){$menu[$index] = $tmp;}
		ksort($menu);
		$menu = array_values($menu);
		foreach($menu as $k=>&$v){$v['index'] = $k;}
		$blob = file_get_contents($GLOBALS['db']['menus'].$menuFile);
		$blob = preg_replace('/\t\$menu[ \[]{1}.*?;\n/','',$blob);
		$blob = str_replace('?>',"\t".'$menu = array();'."\n".'?>',$blob);
		foreach($menu as $item){
			$line = T.'$menu['.$item['index'].'] = array('.str_replace($GLOBALS['array2json_replace'],$GLOBALS['array2json_replacement'],substr(json_encode($item,JSON_HEX_APOS),1,-1)).');'.N;
			$blob = str_replace('?>',$line.'?>',$blob);
		}
		$blob = str_replace(array('\u00e1','\u00e9','\u00ed','\u00f3','\u00fa','\u00f1','\u00c1','\u00c9','\u00cd','\u00d3','\u00da','\u00d1'),
		    array('á','é','í­','ó','ú','ñ','Á','É','Í','Ó','Ú','Ñ'),$blob);
		$blob = stripslashes(preg_replace('/\\\\u([0-9abcdef]{4})/','&#x$1;',$blob));
		$blob = str_replace(array('array("','");'),array('array(\'','\');'),$blob);
		$fp = fopen($GLOBALS['db']['menus'].$menuFile,'w');fwrite($fp,$blob);fclose($fp);
		if($noencode){return true;}
		echo '{"errorCode":0}';
	}


	//menus_removeItem('sidebar.php',3);
	//exit;
	function menus_itemRemove($menuName,$index,$noencode = false){
		if(substr($menuName,-4) != '.php'){$menuName .= '.php';}
		if(!file_exists($GLOBALS['db']['menus'].$menuName)){if($noencode){return false;}return json_encode(array('errorCode'=>1,'errorDescription'=>'MENU_NOT_EXISTS','file'=>__FILE__,'line'=>__LINE__));}
		if(isset($menu[$index])){unset($menu[$index]);}

		$blob = file_get_contents($GLOBALS['db']['menus'].$menuName);
		$blob = preg_replace('/\t\$menu\['.$index.'\] = array\(.*?\);\n/','',$blob);
		$fp = fopen($GLOBALS['db']['menus'].$menuName,'w');fwrite($fp,$blob);fclose($fp);
		include($GLOBALS['db']['menus'].$menuName);
		$r = menus_appendItem($menuName,$menu,true);

		if($noencode){return true;}
		echo '{"errorCode":0}';
	}
?>
