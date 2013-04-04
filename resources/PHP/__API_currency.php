<?php
	//error handler
	//error_reporting(0);

	//declare the database name 
	//$GLOBALS['DB']['DATABASE']='../db/blueCommerce.db';


	if(!isset($GLOBALS['CLIENT'])){
		if($_SERVER['SERVER_NAME'] !== 'localhost'){$GLOBALS['CLIENT'] = $_SERVER['SERVER_NAME'];}
		else{$r = preg_match('/([a-zA-Z0-9\.]+)\/g\/PHP\/([a-zA-Z0-9_]+)\.php$/',$_SERVER['REQUEST_URI'],$m);if(!$r){exit;}$GLOBALS['CLIENT'] = $m[1];}
	}

	//declare the database name 
	echo $GLOBALS['db']['product'] = '../clients/'.$GLOBALS['CLIENT'].'/db/blueCommerce.db';




exit;




	//declare the table structure
	$GLOBALS['tables']['languages'] = array(
					'_id_'=>'INTEGER AUTOINCREMENT',
					'idlanguage'=>'TEXT',
					'language'=>'TEXT'
					);
	
	/*****************END OFF CONFIGURATION**************************/

	//values ​​inserted/update if the table does not exist, create it
	function language_insert($language,$db = false){
		$valid = array('id'=>0,'idlanguage'=>0,'language'=>0);
		foreach($language as $v){if(!isset($valid[$v])){unset($language[$v]);}}

		if(isset($language['productTitle'])){
			$language['productTitle'] = preg_replace('/[^a-zA-Z0-9 áéíóúñÁÉÍÓÚÑ]*/','',$language['language']);
			if(empty($language['language'])){unset($language['language']);}
		}
		//
		include_once('inc_databaseSqlite3.php');
		//
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['DB']['DATABASE']);$shouldClose = true;}

		if(isset($language['id'])){
			$language['id'] = preg_replace('/[^0-9]*/','',$language['id']);
			if(!empty($language['id'])){$language['_id_'] = $language['id'];unset($language['id']);}
			else{unset($language['id']);}
		}

		$r = sqlite3_insertIntoTable('languages',$language,$db);
		
		if($r['OK'] === false){return json_encode(array('errorCode'=>$r['errno'],'errorDescription'=>$r['error'],'file'=>__FILE__,'line'=>__LINE__));}
		list(,$row) = json_decode(product_getById($r['lastID'],$db),1);

		if($shouldClose){$db->close();}
		return json_encode(array('errorCode'=>(int)0,'data'=>$row));
	}

	//search by ID (FALLA NO RETORNA VALOR!!)
	function language_getById($lID,$db = false){
		return product_resolveIds(array($lID),$db);
	}

	//search by IDs
	function language_resolveIds($lIDs,$db = false){
		if(!is_array($lIDs)){
			$lIDs = preg_replace('/[^0-9,]*/','',$lIDs);
			$lIDs = explode(',',$lIDs);
		}
		$lIDs = array_unique($lIDs);

		$whereClause = '(';
		foreach($lIDs as $i){
			$whereClause .= ' id = \''.$i.'\' OR ';
		}
		$whereClause = substr($whereClause,0,-4).')';

		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['DB']['DATABASE']);$shouldClose = true;}

		$query = 'SELECT * FROM language WHERE '.$whereClause;

		$r = $db->query($query);
		$rows = array();
		if($r){while($row = $r->fetchArray(SQLITE3_ASSOC)){$rows[] = $row;}}

		if($shouldClose){$db->close();}
		return json_encode(array('errorCode'=>(int)0,'data'=>$rows));
	}

	//
	function currency_list($db = false){
		
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['DB']['DATABASE']);$shouldClose = true;}

		$query = 'SELECT * FROM languages ORDER BY language ';

		$r = $db->query($query);
		$rows = array();
		if($r){while($row = $r->fetchArray(SQLITE3_ASSOC)){$rows[] = $row;}}

		if($shouldClose){$db->close();}
		return json_encode(array('errorCode'=>(int)0,'data'=>$rows));
	}
?>
