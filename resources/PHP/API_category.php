<?php
	if(!isset($GLOBALS['CLIENT'])){if($_SERVER['SERVER_NAME'] !== 'localhost'){$GLOBALS['CLIENT'] = $_SERVER['SERVER_NAME'];}else{$r = preg_match('/([a-zA-Z0-9\.]+)\/g\/PHP\/([a-zA-Z0-9_]+)\.php$/',$_SERVER['REQUEST_URI'],$m);if(!$r){exit;}$GLOBALS['CLIENT'] = $m[1];}}

	$GLOBALS['tables']['categories'] = array('_id_'=>'INTEGER AUTOINCREMENT','categoryParentID'=>'INTEGER DEFAULT 0','categoryTitle'=>'TEXT','categoryTitleFixed'=>'TEXT','categoryDescription'=>'TEXT');
	$GLOBALS['db']['category'] = '../clients/'.$GLOBALS['CLIENT'].'/db/blueCommerce.db';

	function category_updateSchemaByTable($table = false,$db = false){
//FIXME: habría que usar la API para backup de common
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['category']);$shouldClose = true;}
		include_once('inc_databaseSqlite3.php');
		$r = sqlite3_updateTableSchema($table,$db);
		print_r($r);
		if($shouldClose){$db->close();}
	}

	function category_helper_parseCategoriesByLang($categories = false,$language){
		foreach($categories as &$category){
			$d = preg_match('/<div lang=\''.$language.'\'>(.*?)<\/div>/ms',$category['categoryTitle'],$m);	if(!$d){$d = preg_match('/<div lang=\'default\'>(.*?)<\/div>/ms',$category['categoryTitle'],$m);}if($d){$category['categoryTitle'] = $m[1];}else{$category['categoryTitle'] = '';}
			$d = preg_match('/<div lang=\''.$language.'\'>(.*?)<\/div>/ms',$category['categoryTitleFixed'],$m);	if(!$d){$d = preg_match('/<div lang=\'default\'>(.*?)<\/div>/ms',$category['categoryTitleFixed'],$m);}if($d){$category['categoryTitleFixed'] = $m[1];}else{$category['categoryTitleFixed'] = '';}
			$d = preg_match('/<div lang=\''.$language.'\'>(.*?)<\/div>/ms',$category['categoryDescription'],$m);	if(!$d){$d = preg_match('/<div lang=\'default\'>(.*?)<\/div>/ms',$category['categoryDescription'],$m);}if($d){$category['categoryDescription'] = $m[1];}else{$category['categoryDescription'] = '';}
		}
		return $categories;
	}

	function category_save($params,$db = false,$noencode = false){
		$keys = ','.implode(',',array_keys($params)).',';
		$r = preg_match_all('/(categoryTitle|categoryDescription)_(default|[a-z]{2}\-[a-z]{2})/',$keys,$valid);
		if($r){$valid = $valid[0];}else{$valid = array();}
		$valid = array_merge($valid,array('id','categoryParentID'));
		$valid = array_fill_keys($valid,'');
		foreach($params as $k=>$v){if(!isset($valid[$k])){unset($params[$k]);}}

		include_once('inc_databaseSqlite3.php');
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['category']);$shouldClose = true;}

		/* Debemos comprobar si se está actualizando o no un producto */
		$oldCategory = false;
		if(isset($params['id'])){$params['id'] = preg_replace('/[^0-9]*/','',$params['id']);if(empty($params['id'])){unset($params['id']);}}
		if(isset($params['id'])){$oldCategory = category_getByID($params['id'],$db,true);if($oldCategory === false){unset($params['id']);}}

		include_once('inc_strings.php');
		$category = array('categoryTitle'=>'','categoryTitleFixed'=>'','categoryDescription'=>'');
		if(isset($params['categoryParentID'])){
			//FIXME: no estaría mal comprobar que existe
			$category['categoryParentID'] = $params['categoryParentID'];
		}
		/* Agrupamos los títulos */
		foreach($params as $k=>$v){
			if($k == 'categoryParent'){$category['categoryParent'] = $v;}
			$pos = strpos($k,'_');if($pos === false){continue;}
			$keyHeader = substr($k,0,$pos+1);
			$lang = substr($k,$pos+1);

			$pKey = substr($keyHeader,0,-1);if(!isset($category[$pKey])){continue;}
			$category[$pKey] .= '<div lang=\''.$lang.'\'>'.$v.'</div>';
			/* QUIRKS */
			switch($pKey){
				case 'categoryTitle':$category['categoryTitleFixed'] .= '<div lang=\''.$lang.'\'>'.strings_stringToURL($v).'</div>';break;
			}
		}

		if($oldCategory !== false){$category['_id_'] = $oldCategory['id'];}

		$r = sqlite3_insertIntoTable('categories',$category,$db);
		if($r['OK'] === false){return json_encode(array('errorCode'=>$r['errno'],'errorDescription'=>$r['error'],'file'=>__FILE__,'line'=>__LINE__));}
		$row = category_getByID($r['id'],$db,true);

		if($shouldClose){$db->close();}
		if($noencode){return $row;}
		return json_encode(array('errorCode'=>(int)0,'data'=>$row));
	}

	function category_getByID($pID,$db = false,$noencode = false){
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['category'],SQLITE3_OPEN_READONLY);$shouldClose = true;}
		$row = $db->querySingle('SELECT * FROM categories WHERE id = \''.$pID.'\'',1);
		if($shouldClose){$db->close();}
		if($noencode && ($row === false || count($row) == 0)){return false;}
		if($noencode){return $row;}
		return json_encode(array('errorCode'=>(int)0,'data'=>$row));
	}

	function category_getByTitleFixed($titleFixed,$db = false,$noencode = false){
		$titleFixed = preg_replace('/[^a-zA-Z0-9\-]*/','',$titleFixed);
		if($titleFixed === ''){return json_encode(array('errorCode'=>1,'errorDescription'=>'INVALID_TITLEFIXED','file'=>__FILE__,'line'=>__LINE__));}
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['category']);$shouldClose = true;}

		$query = 'SELECT * FROM categories WHERE categoryTitleFixed LIKE \'%>'.$titleFixed.'<%\'';
		$row = $db->querySingle($query,1);
		if($row === false){if($shouldClose){$db->close();}return json_encode(array('errorCode'=>$db->lastErrorCode(),'errorDescripcion'=>$db->lastErrorMsg(),'file'=>__FILE__,'line'=>__LINE__));}
		if(count($row) < 1 && $noencode){if($shouldClose){$db->close();}return false;}

		if($shouldClose){$db->close();}
		if($noencode){return $row;}
		return json_encode(array('errorCode'=>(int)0,'data'=>$row));
	}

	function category_remove($cID,$db = false,$noencode = false){
		//FIXME: si eliminamos, es posible que esta categoria sea padre de otras categorias
		// debemos actualizar el identificador a 0 donde correspondan
		include_once('API_product.php');
		/* La llamada puede tardar bastante y no debemos mantener la base de datos bloqueada */
		$products = product_getByCategoryIDs($cID,false,true);
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['category']);$shouldClose = true;}
		foreach($products as $product){
			$categs = str_replace(','.$cID.',',',',$product['productCategories']);
			$upd = array('id'=>$product['id'],'productCategories'=>$categs);
			$r = product_save($upd,$db,true);
		}
		$r = $db->exec('DELETE FROM categories WHERE id = \''.$cID.'\';');
		$c = $r ? $db->changes() : 0;
		if($shouldClose){$db->close();}
		sleep(1);
		if($c < 1){$a = array('errorCode'=>1,'errorDescripcion'=>'INVALID_CATEGORY','file'=>__FILE__,'line'=>__LINE__);return $noencode ? $a : json_encode($a);}
		if($noencode){return true;}
		return '{"errorCode":"0"}';
	}

	/* DEPRECATED: usar getWhere */
	function category_getCategories($db = false,$noencode = false,$limit = false,$whereClause = false,$orderBy = 'id DESC'){
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['category']);$shouldClose = true;}
		//if(strpos($lang,'-') !== false){$lang = strtoupper(substr($lang,0,2));}
		$query = 'SELECT * FROM categories '.(($whereClause !== false) ? 'WHERE '.$whereClause : '').' ORDER BY '.$db->escapeString($orderBy);
		if($limit !== false){$limit = preg_replace('/[^0-9,]*/','',$limit);$query .= ' LIMIT '.$limit;}
		//echo $query;
		$r = $db->query($query);
		$rows = array();if($r){while($row = $r->fetchArray(SQLITE3_ASSOC)){$rows[] = $row;}}
		if($shouldClose){$db->close();}
		if($noencode){return $rows;}
		return json_encode(array('errorCode'=>(int)0,'data'=>$rows));
	}

	function category_getWhere($whereClause = false,$db = false,$noencode = false,$limit = false,$orderBy = 'id DESC',$indexBy = false){
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['category']);$shouldClose = true;}
		$query = 'SELECT * FROM categories '.(($whereClause !== false) ? 'WHERE '.$whereClause : '').' ORDER BY '.$db->escapeString($orderBy);
		if($limit !== false){$limit = preg_replace('/[^0-9,]*/','',$limit);$query .= ' LIMIT '.$limit;}
		//echo $query;
		$r = $db->query($query);
		$rows = array();if($r){while($row = $r->fetchArray(SQLITE3_ASSOC)){$rows[$row['id']] = $row;}}
		if($shouldClose){$db->close();}
		if($noencode){return $rows;}
		return json_encode(array('errorCode'=>(int)0,'data'=>$rows));
	}

	function category_resolveIds($pIDs,$db = false,$noencode = false,$indexBy = 'id'){
		if(!is_array($pIDs)){$pIDs = preg_replace('/[^0-9,]*/','',$pIDs);$pIDs = explode(',',$pIDs);}
		$pIDs = array_diff(array_unique($pIDs),array(''));
		if(count($pIDs) < 1){return ($noencode) ? array() : '{"errorCode":0,"data":[]}';}
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['category'],SQLITE3_OPEN_READONLY);$shouldClose = true;}

		$whereClause = '(';foreach($pIDs as $i){$whereClause .= ' id = \''.$i.'\' OR ';}$whereClause = substr($whereClause,0,-4).')';
		$query = 'SELECT * FROM categories WHERE '.$whereClause;
		$r = @$db->query($query);
		$rows = array();
		if($r){while($row = $r->fetchArray(SQLITE3_ASSOC)){$rows[$row[$indexBy]] = $row;}}

		if($shouldClose){$db->close();}
		if($noencode){return $rows;}
		return json_encode(array('errorCode'=>(int)0,'data'=>$rows));
	}
?>
