<?php
	function sqlite3_createTable($tableName,$array,$db = false){
		$shouldClose = false;if(!$db){$shouldClose = true;$db = new SQLite3($GLOBALS['databasePath']);}

		$query = 'CREATE TABLE \''.$tableName.'\' (';
		$tableKeys = array();
		$hasAutoIncrement = false;
		foreach($array as $k=>$v){
			if(preg_match('/^_[a-zA-Z0-9]*_$/',$k)){
				$key = preg_replace('/^_|_$/','',$k);
				if(strpos($v,'INTEGER AUTOINCREMENT') !== false){$query .= '\''.$key.'\' INTEGER PRIMARY KEY AUTOINCREMENT,';continue;}
				$query .= '\''.$key.'\' '.$v.',';$tableKeys[] = $key;continue;
			}
			$query .= '\''.$k.'\' '.$v.',';
		}
		if(count($tableKeys) > 0){$query .= 'PRIMARY KEY ('.implode(',',$tableKeys).'),';}
		$query = substr($query,0,-1).');';

		$r = @$db->exec($query);
		$ret = array('OK'=>$r,'error'=>$db->lastErrorMsg(),'errno'=>$db->lastErrorCode(),'query'=>$query);
		if($shouldClose){$db->close();}
		return $ret;
	}

	function sqlite3_insertIntoTable($tableName,$array,$db = false,$aTableName = false){
		$shouldClose = false;if(!$db){$shouldClose = true;$db = new SQLite3($GLOBALS['databasePath']);}
		$tableKeys = array();foreach($array as $key=>$value){
			$array[$key] = $db->escapeString($value);
			if(preg_match('/^_[a-zA-Z0-9]*_$/',$key)){$newkey = preg_replace('/^_|_$/','',$key);$tableKeys[$newkey] = $array[$newkey] = $value;unset($array[$key]);}
		}

		$query = 'INSERT INTO "'.$tableName.'" ';
		$tableIds = $tableValues = '(';
		/* SQL uses single quotes to delimit string literals. */
		foreach($array as $key=>$value){$tableIds .= '\''.$key.'\',';$tableValues .= '\''.$value.'\',';}
		$tableIds = preg_replace('/,$/',')',$tableIds);
		$tableValues = preg_replace('/,$/',');',$tableValues);
		$query .= $tableIds.' VALUES '.$tableValues;

		$r = @$db->exec($query);
		$lastID = $db->lastInsertRowID();
		if(!$r && $db->lastErrorCode() == 19 && count($tableKeys) > 0){
			$query = 'UPDATE "'.$tableName.'" SET ';
			$tableKeysValues = array_keys($tableKeys);
			foreach($array as $key=>$value){if(in_array($key,$tableKeysValues)){continue;}$query .= '\''.$key.'\'=\''.$value.'\',';}
			$query = preg_replace('/,$/','',$query);
			$whereClause = ' WHERE';
			foreach($tableKeys as $k=>$v){$whereClause .= ' '.$k.' = \''.$v.'\' AND';}
			$whereClause = preg_replace('/ AND ?$/',' ',$whereClause);
			$query .= $whereClause;

			$r = @$db->exec($query);
			$lastID = array_shift($tableKeys);
		}

		$ret = array('OK'=>$r,'id'=>$lastID,'error'=>$db->lastErrorMsg(),'errno'=>$db->lastErrorCode(),'query'=>$query);
		//$ret['errno'] == 1 && table shouts has no column named
		//FIXME: $ret['errno'] == 1 NO es siempre !table_Exists
		if($aTableName === false){unset($aTableName);}
		if(  !$ret['OK'] && $ret['errno'] == 1 && (isset($GLOBALS['tables'][$tableName]) || isset($aTableName,$GLOBALS['tables'][$aTableName]))  ){
			$ret = sqlite3_createTable($tableName,(isset($aTableName) ? $GLOBALS['tables'][$aTableName] : $GLOBALS['tables'][$tableName]),$db);
			if(!$ret['OK']){return $ret;}
			$r = @$db->exec($query);$ret = array('OK'=>$r,'id'=>$db->lastInsertRowID(),'error'=>$db->lastErrorMsg(),'errno'=>$db->lastErrorCode(),'query'=>$query);
		}
		if($shouldClose){$db->close();}
		return $ret;
	}

	function sqlite_safeQuery($q,$db){
		$r = @$db->query($q);
		$errorCode = $db->lastErrorCode();$errorDescription = $db->lastErrorMsg();
		$secure = 0;while($secure < 5 && !$r && $db->lastErrorCode() == 5){usleep(200000);$r = @$db->query($query);$secure++;}
		return array('r'=>$r,'errorCode'=>$errorCode,'errorDescription'=>$errorDescription);
	}

	function sqlite3_tableExists($tableName,$db = false){
		$r = $db->query("SELECT * FROM sqlite_master WHERE name = '".$tableName."';");
		if(!$r || !($row = $r->fetchArray(SQLITE3_ASSOC))){return false;}
		return true;
	}

	/* $origTableName = string - $tableSchema = string ($GLOBALS['tables'][$tableSchema]) */
	function sqlite3_updateTableSchema($origTableName,$db = false,$tableID = 'id',$schemaName = false){
		$tableName = $origTableName;if(!$schemaName){$schemaName = $tableName;}
		$tableSchema = $GLOBALS['tables'][$schemaName];

		/* Averiguamos las keys automáticamente */
		$tableKeys = array();foreach($tableSchema as $key=>$value){if(substr($key,0,1) == '_' && substr($key,-1) == '_'){
		$newkey = substr($key,1,-1);$tableKeys[$newkey] = $key;}}
		print_r($tableKeys);

		$shouldClose = false;
		$fields = implode(',',array_diff(array_keys($tableSchema),array_values($tableKeys)));
		foreach($tableKeys as $k=>$v){$fields .= ','.$k.' as '.$v;}
		$i = 0;
		$continue = true;while($continue){
			$i++;
			$r = @$db->query('SELECT '.$fields.' FROM '.$origTableName);
			if($i > 40){echo 'UNABLE TO QUERY FIELDS: \'SELECT '.$fields.' FROM '.$origTableName.'\'';exit;}
			if($r){break;}
			if(!$r && substr($db->lastErrorMsg(),0,14) == 'no such column'){
				$errorField = substr($db->lastErrorMsg(),16);
				$fields = preg_replace('/(^|,)'.$errorField.'(,|$)/',',',$fields);
				if($fields[0] == ','){$fields = substr($fields,1);}
			}
		}
		$rows = array();if($r){while($row = $r->fetchArray(SQLITE3_ASSOC)){$rows[] = $row;}}

		$r = $db->exec('ALTER TABLE '.$origTableName.' RENAME TO '.$origTableName.'_backup');
		//$r = $db->exec('DROP TABLE IF EXISTS '.$origTableName);
		//if(!$r){return json_encode(array('errorCode'=>1));}

		foreach($rows as $arr){
			$r = sqlite3_insertIntoTable($tableName,$arr,$db);
			if(!$r['OK'] && $r['errno'] == 1){sqlite3_createTable($tableName,$GLOBALS['tables'][$tableSchema],$db);$r = sqlite3_insertIntoTable($tableName,$arr,$db);}
			if(!$r['OK']){if($shouldClose){$db->close();}return json_encode(array('errorCode'=>$r['errno'],'errorDescripcion'=>$r['error'],'query'=>$r['query'],'file'=>__FILE__,'line'=>__LINE__));}
		}

		$r = $db->exec('DROP TABLE IF EXISTS '.$origTableName.'_backup');
		if(!$r){return json_encode(array('errorCode'=>1));}

		if($shouldClose){$db->close();}
		return json_encode(array("errorCode"=>(int)0));
	}
?>
