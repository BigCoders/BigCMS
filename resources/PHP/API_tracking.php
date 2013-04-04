<?php
	$GLOBALS['db']['tracking'] = '../clients/'.$GLOBALS['CLIENT'].'/db/tracking.db';
	$GLOBALS['tables']['trackedElements'] = array('_id_'=>'INTEGER AUTOINCREMENT','indx'=>'INTEGER','count'=>'INTEGER','description'=>'TEXT');
	$GLOBALS['tables']['tracking'] = array('_id_'=>'INTEGER AUTOINCREMENT','trackingUser'=>'INTEGER','trackingIP'=>'TEXT','trackingUserAgent'=>'TEXT','trackingURL'=>'TEXT','trackingReferer'=>'TEXT','trackingDate'=>'TEXT','trackingTime'=>'TEXT','trackingStamp'=>'TEXT','trackingTag'=>'TEXT');

	function tracking_trackElement_increase($index,$description = false,$db = false,$noencode = false){
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['tracking']);$shouldClose = true;}
		$elem = tracking_trackElement_get($index,$db,true);
		$track = array('indx'=>$index,'count'=>1,'description'=>(($description) ? $description : ''));
		if($elem !== false){$elem['count']++;$track = array('_id_'=>$elem['id'],'count'=>$elem['count']);}
		include_once('inc_databaseSqlite3.php');
		$r = sqlite3_insertIntoTable('trackedElements',$track,$db);
		if($r['OK'] === false){if($shouldClose){$db->close();}$a = array('errorCode'=>$r['errno'],'errorDescription'=>$r['error'],'file'=>__FILE__,'line'=>__LINE__);return ($noencode) ? $a : json_encode($a);}

		$ret = ($elem !== false) ? $elem : $track;
		if($shouldClose){$db->close();}
		if($noencode){return $ret;}
		return json_encode(array('errorCode'=>(int)0,'data'=>$ret));
	}

	function tracking_trackElement_get($index,$db = false,$noencode = false){
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['tracking'],SQLITE3_OPEN_READONLY);$shouldClose = true;}
		$query = 'SELECT * FROM trackedElements WHERE indx = \''.$index.'\'';
		$row = @$db->querySingle($query,1);
		if($shouldClose){$db->close();}
		if($noencode && ($row === false || count($row) == 0)){return false;}
		if($noencode){return $row;}
		return json_encode(array('errorCode'=>(int)0,'data'=>$row));
	}

	function tracking_touch($db = false,$noencode = false){
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['tracking']);$shouldClose = true;}
		$trackingUser = (isset($GLOBALS['userLogged']) && $GLOBALS['userLogged'] !== false) ? $GLOBALS['userLogged']['id'] : 0;
		$track = array('trackingUser'=>$trackingUser,'trackingIP'=>$_SERVER['REMOTE_ADDR'],'trackingUserAgent'=>(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'unknown'),'trackingURL'=>'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'],'trackingReferer'=>(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : ''),'trackingDate'=>date('Y-m-d'),'trackingTime'=>date('H:i:s'),'trackingStamp'=>time());
		include_once('inc_databaseSqlite3.php');
		$r = sqlite3_insertIntoTable('tracking',$track,$db);
		if($r['OK'] === false){if($shouldClose){$db->close();}$a = array('errorCode'=>$r['errno'],'errorDescription'=>$r['error'],'file'=>__FILE__,'line'=>__LINE__);return ($noencode) ? $a : json_encode($a);}
		if($shouldClose){$db->close();}
		return true;
	}
?>
