<?php
	/*****************CONFIGURATION (COLOCAR ESTO EN UN ARCHIVO DE CONFIGURACION GENERAL) **************************/

	//declare the database name 
	$GLOBALS['DB']['DATABASE']='../db/blueCommerce.db';

	//declare the table structure
	$GLOBALS['tables']['status'] = array(
						'_id_'=>'INTEGER AUTOINCREMENT',
						'idstatus'=>'TEXT',
						'status'=>'TEXT'
						);
	
	/*****************END OFF CONFIGURATION**************************/

	//values ​​inserted/update if the table does not exist, create it
	function status_insert($status,$db = false){
		$valid = array('id'=>0,'idstatus'=>0,'status'=>0);
		foreach($product as $k=>$v){if(!isset($valid[$k])){unset($product[$k]);}}

		if(isset($status['status'])){
			$status['status'] = preg_replace('/[^a-zA-Z0-9 áéíóúñÁÉÍÓÚÑ]*/','',$status['status']);
			if(empty($status['status'])){unset($status['status']);}
		}

		if(isset($status['idstatus'])){
			$status['idstatus'] = preg_replace('/[^0-9]*/','',$status['idstatus']);
			if($status['idstatus'] === ''){unset($status['idstatus']);}
		}

		//
		include_once('inc_databaseSqlite3.php');
		//
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['DB']['DATABASE']);$shouldClose = true;}

		if(isset($status['id'])){
			$status['id'] = preg_replace('/[^0-9]*/','',$status['id']);
			if(!empty($status['id'])){$status['_id_'] = $status['id'];unset($status['id']);}
			else{unset($status['id']);}
		}

		$r = sqlite3_insertIntoTable('status',$status,$db);
		
		if($r['OK'] === false){return json_encode(array('errorCode'=>$r['errno'],'errorDescription'=>$r['error'],'file'=>__FILE__,'line'=>__LINE__));}
		list(,$row) = json_decode(product_getById($r['lastID'],$db),1);

		if($shouldClose){$db->close();}
		return json_encode(array('errorCode'=>(int)0,'data'=>$row));
	}

	//updated Status by ID
	function status_updateById($sID,$productStatus=2,$db = false){
		$pID = preg_replace('/[^0-9]*/','',$sID);
		if($pID === ''){return json_encode(array('errorCode'=>1,'errorDescription'=>'PRODUCTID_INVALID','file'=>__FILE__,'line'=>__LINE__));}
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['DB']['DATABASE']);$shouldClose = true;}
		$query = 'UPDATE status SET status=\''.$status.'\' WHERE id = \''.$sID.'\'';
		$r = $db->query($query);
		if(!$r){return json_encode(array('errorCode'=>$db->lastErrorCode(),'errorDescripcion'=>$db->lastErrorMsg(),'file'=>__FILE__,'line'=>__LINE__));}else{return json_encode(array('errorCode'=>(int)0));}
		if($shouldClose){$db->close();}
	}

	//search by ID (FALLA NO RETORNA VALOR!!)
	function status_getById($sID,$db = false){
		return status_resolveIds(array($sID),$db);
	}

	//search by IDs
	function status_resolveIds($sIDs,$db = false){
		if(!is_array($sIDs)){
			$sIDs = preg_replace('/[^0-9,]*/','',$sIDs);
			$sIDs = explode(',',$sIDs);
		}
		$sIDs = array_unique($sIDs);

		$whereClause = '(';
		foreach($sIDs as $i){
			$whereClause .= ' id = \''.$i.'\' OR ';
		}
		$whereClause = substr($whereClause,0,-4).')';

		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['DB']['DATABASE']);$shouldClose = true;}

		$query = 'SELECT * FROM status WHERE '.$whereClause;

		$r = $db->query($query);
		$rows = array();
		if($r){while($row = $r->fetchArray(SQLITE3_ASSOC)){$rows[] = $row;}}

		if($shouldClose){$db->close();}
		return json_encode(array('errorCode'=>(int)0,'data'=>$rows));
	}

	//
	function status_list($db = false){
		
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['DB']['DATABASE']);$shouldClose = true;}

		$query = 'SELECT * FROM status ORDER BY status';

		$r = $db->query($query);
		$rows = array();
		if($r){while($row = $r->fetchArray(SQLITE3_ASSOC)){$rows[] = $row;}}

		if($shouldClose){$db->close();}
		return json_encode(array('errorCode'=>(int)0,'data'=>$rows));
	}
?>
