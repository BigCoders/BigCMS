<?php
	$GLOBALS['tables']['pages'] = array('_id_'=>'INTEGER AUTOINCREMENT','pageCreatedBy'=>'INTEGER','pageModifiedBy'=>'INTEGER','pageLang'=>'TEXT','pageTitle'=>'TEXT','pageTitleFixed'=>'TEXT UNIQUE','pageTitleHard'=>'INTEGER','pageDescription'=>'TEXT','pageText'=>'TEXT','pageTags'=>'TEXT','pageStatus'=>'INTEGER');
	$GLOBALS['DB_PAGES'] = '../clients/'.$GLOBALS['CLIENT'].'/db/blueCommerce.db';

	function page_save($params,$db = false,$noencode = false){
		//FIXME: hacer validaciones
		//FIXME: contemplar pageTitleHard para no modificar el pageTitle en cada salvado

		if(empty($params['id'])){unset($params['id']);}
		else{$params['_id_'] = $params['id'];unset($params['id']);}

		include_once('inc_strings.php');
		$params['pageTitleFixed'] = strings_stringToURL($params['pageTitle']);

		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['DB_PAGES']);$shouldClose = true;}
		include_once('inc_databaseSqlite3.php');
		$r = sqlite3_insertIntoTable('pages',$params,$db);
		if($r['OK'] === false){if($shouldClose){$db->close();}$a = array('errorCode'=>$r['errno'],'errorDescription'=>$r['error'],'file'=>__FILE__,'line'=>__LINE__);return ($noencode) ? $a : json_encode($a);}

		return $noencode ? true : '{"errorCode":"0"}';
	}

	function page_getWhereSingle($whereClause,$db = false,$noencode = false){
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['DB_PAGES'],SQLITE3_OPEN_READONLY);$shouldClose = true;}
		$row = @$db->querySingle('SELECT * FROM pages WHERE '.$whereClause,1);
		if($shouldClose){$db->close();}
		if($noencode && ($row === false || count($row) == 0)){return false;}
		if($noencode){return $row;}
		return json_encode(array('errorCode'=>(int)0,'data'=>$row));
	}

	function page_getWhere($whereClause,$db = false,$noencode = false){
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['DB_PAGES'],SQLITE3_OPEN_READONLY);$shouldClose = true;}
		$r = @$db->query('SELECT * FROM pages WHERE '.$whereClause);
		$rows = array();if($r){while($row = $r->fetchArray(SQLITE3_ASSOC)){$rows[] = $row;}}
		if($shouldClose){$db->close();}
		if($noencode){return $rows;}
		return json_encode(array('errorCode'=>(int)0,'data'=>$rows));
	}

	function page_getByID($id,$db = false,$noencode = false){
		$id = preg_replace('/[^0-9]*/','',$id);
		return page_getWhereSingle('id = '.$id,$db,$noencode);
	}
?>
