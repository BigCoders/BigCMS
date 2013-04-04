<?php
	$GLOBALS['db']['notifications'] = '../clients/'.$GLOBALS['CLIENT'].'/db/blueCommerce.db';
	$GLOBALS['tables']['notifications'] = array('_id_'=>'INTEGER AUTOINCREMENT','notificationParentID'=>'INTEGER','notificationUserID'=>'TEXT','notificationUserFrom'=>'TEXT','notificationUserClass'=>'TEXT','notificationDate'=>'TEXT','notificationTime'=>'TEXT','notificationTitle'=>'TEXT','notificationText'=>'TEXT','notificationTags'=>'TEXT','notificationStatus'=>'INTEGER','notificationModule'=>'TEXT');

	function notifications_add($not,$data = false,$db = false,$noencode = false){
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['notifications']);$shouldClose = true;}
		if(isset($not['template'])){$template = $not['template'];}
		$invalidParams = array('_id_','notificationDate','notificationTime');
		$validParams = array_fill_keys(array_diff(array_keys($GLOBALS['tables']['notifications']),$invalidParams),'');
		foreach($not as $k=>$v){if(!isset($validParams[$k])){unset($not[$k]);}}

		//FIXME: si no se está actualizando una notificación
		if(!isset($not['notificationParentID'])){$not['notificationParentID'] = 0;}
		$not['notificationDate'] = date('Y-m-d');
		$not['notificationTime'] = date('H:i:s');
		if(!isset($not['notificationStatus'])){$not['notificationStatus'] = 1;}

		if(isset($not['notificationTags'])){$not['notificationTags'] = preg_replace('/[^a-zA-Z0-9,]*/','',$not['notificationTags']);$not['notificationTags'] = ','.implode(',',array_diff(explode(',',$not['notificationTags']),array(''))).',';}

		//FIXME: soporte para plantillas a través de $not['template']

		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['cart']);$shouldClose = true;}
		include_once('inc_databaseSqlite3.php');
		$r = sqlite3_insertIntoTable('notifications',$not,$db);
		if($r['OK'] === false){if($shouldClose){$db->close();}$a = array('errorCode'=>$r['errno'],'errorDescription'=>$r['error'],'file'=>__FILE__,'line'=>__LINE__);return ($noencode) ? $a : json_encode($a);}

		include_once('API_mailing.php');
		include_once('API_users.php');
		/* Buscamos los usuarios afectados por la notificación */
		$whereClause = '';
		if(isset($not['notificationUserID']) && !empty($not['notificationUserID'])){$userIDs = array_diff(explode(',',$not['notificationUserID']),array(''));$whereClause .= '(';foreach($userIDs as $uID){$whereClause .= 'id = \''.$uID.'\' OR ';}$whereClause = substr($whereClause,0,-4).')';}
		if(isset($not['notificationUserClass']) && !empty($not['notificationUserClass'])){$userClasses = array_diff(explode(',',$not['notificationUserClass']),array(''));$whereClause .= ($whereClause != '') ? ' OR (' : '(';foreach($userClasses as $class){$whereClause .= 'userClass LIKE \'%,'.$class.',%\' OR ';}$whereClause = substr($whereClause,0,-4).')';}
		$users = users_getUsers($db,true,false,$whereClause);
		$receivers = array();foreach($users as $user){$receivers[$user['userMail']] = $user['userName'];}
		//FIXME: añadir más información y un enlace al asistente de notificaciones
		$mailingText = $not['notificationText'];
		if(count($receivers) > 0){$r = mailing_sendMail($receivers,'Notification ('.$not['notificationDate'].' '.$not['notificationTime'].') '.$not['notificationTitle'],$mailingText,false);}

		if($shouldClose){$db->close();}
		if($noencode){return true;}
		return '{"errorCode":"0"}';
	}

	function notifications_get($db = false,$noencode = false,$limit = false,$whereClause = false,$orderBy = 'id DESC',$indexBy = 'id'){
		if($orderBy === false){$orderBy = 'id DESC';}if($indexBy === false){$indexBy = 'id';}
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['notifications'],SQLITE3_OPEN_READONLY);$shouldClose = true;}
		$query = 'SELECT * FROM notifications '.(($whereClause !== false) ? 'WHERE '.$whereClause : '').' ORDER BY '.$db->escapeString($orderBy);
		if($limit !== false){$limit = preg_replace('/[^0-9,]*/','',$limit);$query .= ' LIMIT '.$limit;}
		//echo $query;
		$r = @$db->query($query);
		$rows = array();if($r){while($row = $r->fetchArray(SQLITE3_ASSOC)){$rows[$row[$indexBy]] = $row;}}
		if($shouldClose){$db->close();}
		if($noencode){return $rows;}
		return json_encode(array('errorCode'=>(int)0,'data'=>$rows));
	}
?>
