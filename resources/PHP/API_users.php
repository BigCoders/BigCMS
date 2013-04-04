<?php
	if(!isset($GLOBALS['CLIENT'])){if($_SERVER['SERVER_NAME'] !== 'localhost'){$GLOBALS['CLIENT'] = $_SERVER['SERVER_NAME'];}else{$r = preg_match('/([a-zA-Z0-9\.]+)\/g\/PHP\/([a-zA-Z0-9_]+)\.php$/',$_SERVER['REQUEST_URI'],$m);if(!$r){exit;}$GLOBALS['CLIENT'] = $m[1];}}
	if(!isset($GLOBALS['userLogged'])){$GLOBALS['userLogged'] = false;}

	$GLOBALS['db']['users'] = '../clients/'.$GLOBALS['CLIENT'].'/db/blueCommerce.db';
	$GLOBALS['tables']['users'] = array('_id_'=>'INTEGER AUTOINCREMENT',
		'userName'=>'TEXT','userBirth'=>'TEXT','userAddress'=>'TEXT','userMail'=>'TEXT UNIQUE','userPass'=>'TEXT','userMagicWord'=>'TEXT',
		'userNick'=>'TEXT','userPhone'=>'TEXT','userGender'=>'TEXT','userCountry'=>'TEXT','userProvince'=>'TEXT','userCode'=>'TEXT',
		'userRegistered'=>'TEXT','userFacebookID'=>'TEXT','userClass'=>'TEXT','userStatus'=>'TEXT','userLat'=>'TEXT','userLng'=>'TEXT',
		'userLocationUpdated'=>'TEXT','userLastLogin'=>'TEXT','userIP'=>'TEXT'
	);
	$GLOBALS['API_users']['selectString'] = 'id,userName,userBirth,userAddress,userMail,userPass,userNick,userPhone,userGender,userCountry,userProvince,userRegistered,userFacebookID,userClass,userStatus,userLat,userLng,userLocationUpdated';

	if(isset($_POST['command'])){
		header('Content-type: text/json');
		//userSecurity();
		switch($_POST['command']){
			case 'changeClass':echo users_changeClass($_POST['userID'],$_POST['userClass']);break;
			
		}
		exit;
	}

	function users_create($data,$db = false,$noencode = false){
		$valid = array('userName'=>0,'userBirth'=>0,'userAddress'=>0,'userMail'=>0,'userPass'=>0,'userPhone'=>0,'userGender'=>0,'userCountry'=>0,'userProvince'=>0);
		include_once('inc_strings.php');
		foreach($data as $k=>$v){if(!isset($valid[$k])){unset($data[$k]);continue;}$data[$k] = strings_UTF8Encode($v);}
		$pass_a = array('?','$','¿','!','¡','{','}');
	    	$pass_b = array('a','e','i','o','u','b','c','d','f','g','h','j','k','l','m','n','p','q','r','s','t','v','w','x','y','z');

		/* Necesitamos tener la conexión con la base de datos desde aquí para las comprobaciones de algunos campos */
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['users']);$shouldClose = true;}
		$data['userName'] = preg_replace('/[^a-zA-ZáéíóúÁÉÍÓÚ ,]*/','',$data['userName']);
		if(empty($data['userName'])){$a = array('errorCode'=>1,'errorDescription'=>'NAME_ERROR','file'=>__FILE__,'line'=>__LINE__);return ($noencode) ? $a : json_encode($a);}
		if(!preg_match('/^[a-z0-9\._\+\-]+@[a-z0-9\.\-]+\.[a-z]{2,6}$/',$data['userMail'])){$a = array('errorCode'=>1,'errorDescription'=>'EMAIL_ERROR','file'=>__FILE__,'line'=>__LINE__);return $noencode ? $a : json_encode($a);}
		/* Comprobamos mail duplicado */
		if(users_getByMail($data['userMail'],$db,true) !== false){$a = array('errorCode'=>1,'errorDescription'=>'EMAIL_DUPLICATED','file'=>__FILE__,'line'=>__LINE__);return $noencode ? $a : json_encode($a);}
		if(!isset($data['userPass']) || empty($data['userPass'])){$data['userPass'] = '';for($a=0; $a<6; $a++){$data['userPass'] .= $pass_a[rand(0,(count($pass_a)-1))];$data['userPass'] .= $pass_b[rand(0,(count($pass_b)-1))];}}
		$data['userPass'] = sha1($data['userPass']);

		if(isset($data['userPhone']) && !empty($data['userPhone'])){
			if(strlen($data['userPhone']) < 9){$a = array('errorCode'=>1,'errorDescription'=>'MOBILE_ERROR','file'=>__FILE__,'line'=>__LINE__);return $noencode ? $a : json_encode($a);}
			if(substr($data['userPhone'],0,1) != 6){$a = array('errorCode'=>1,'errorDescription'=>'MOBILE_ERROR','file'=>__FILE__,'line'=>__LINE__);return $noencode ? $a : json_encode($a);}
		}

		if(!isset($data['userNick']) || empty($data['userNick'])){$data['userNick'] = sha1($data['userMail'].$data['userName']);}
		$date = date('Y-m-d H:i:s');
		$userCode = users_helper_generateCode($data['userMail']);
		$data = array_merge($data,array('userRegistered'=>$date,'userStatus'=>0,'userClass'=>',regular,','userCode'=>$userCode));

		include_once('inc_databaseSqlite3.php');
		$r = sqlite3_insertIntoTable('users',$data,$db);
		if($r['OK'] === false){$a = array('errorCode'=>$r['errno'],'errorDescription'=>$r['error'],'file'=>__FILE__,'line'=>__LINE__);return $noencode ? $a : json_encode($a);}

		$users = users_resolveIds(array($r['id']),$db,true);
		$user = array_shift($users);
		$user = array_merge($user,array('userCode'=>$userCode));

		if($shouldClose){$db->close();}
		if($noencode){return $user;}
		return json_encode(array('errorCode'=>0,'data'=>$user));
	}

	function users_helper_generateCode($userMail){$userCode = sha1($userMail.time().date('Y-m-d H:i:s'));return $userCode;}

	function users_update($id,$data = array(),$db = false,$noencode = false){
		$data['_id_'] = $id;
		if(isset($data['userBirth_day']) && isset($data['userBirth_month']) && isset($data['userBirth_year'])){$data['userBirth'] = $data['userBirth_year'].'-'.$data['userBirth_month'].'-'.$data['userBirth_day'];unset($data['userBirth_year'],$data['userBirth_month'],$data['userBirth_day']);}
		include_once('inc_strings.php');

		/* VALIDATION */
		if(isset($data['userName'])){$data['userName'] = preg_replace('/[^a-zA-ZáéíóúÁÉÍÓÚ ]*/','',strings_UTF8Encode($data['userName']));}
		if(isset($data['userPass'])){$data['userPass'] = sha1($data['userPass']);}
		if(isset($data['userBirth'])){$data['userBirth'] = preg_replace('/[^0-9\-]*/','',$data['userBirth']);}
		if(isset($data['userBirth']) && (!preg_match('/^[0-9]{4}\-[0-9]{2}\-[0-9]{2}$/',$data['userBirth']) || strtotime($data['userBirth']) < 1)){$r = array('errorCode'=>2,'errorDescription'=>'USERBIRTH_ERROR','file'=>__FILE__,'line'=>__LINE__);return ($noencode) ? $r : json_encode($r);}
		//if(isset($data['userBio'])){$data['userBio'] = strings_UTF8Encode($data['userBio']);}
		if(isset($data['userLat']) || isset($data['userLng'])){$data['userLocationUpdated'] = time();}

		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['users']);$shouldClose = true;}
		include_once('inc_databaseSqlite3.php');
		$r = sqlite3_insertIntoTable('users',$data,$db);
		if($r['OK'] === false){$a = array('errorCode'=>$r['errno'],'errorDescription'=>$r['error'],'file'=>__FILE__,'line'=>__LINE__);return $noencode ? $a : json_encode($a);}

		$users = users_resolveIds(array($r['id']),$db,true);
		$user = array_shift($users);

		if($shouldClose){$db->close();}
		if(isset($GLOBALS['userLogged']) && $GLOBALS['userLogged']['id'] == $id){$GLOBALS['userLogged'] = $user;}
		if($noencode){return $user;}
		return json_encode(array('errorCode'=>0,'data'=>$user));
	}

	function users_avatar($id,$file,$noencode = false){
		include_once('inc_images.php');
		if(empty($id)){$a = array('errorCode'=>1,'errorDescription'=>'INVALID_USERID','file'=>__FILE__,'line'=>__LINE__);return $noencode ? $a : json_encode($a);}
		$dest = '../clients/'.$GLOBALS['CLIENT'].'/db/users/'.$id.'/';
		if(!file_exists($dest)){@mkdir($dest);}
		if(!file_exists($dest)){$a = array('errorCode'=>1,'errorDescription'=>'USERS_FOLDER_NOT_WRITABLE','file'=>__FILE__,'line'=>__LINE__);return $noencode ? $a : json_encode($a);}
		$dest .= 'avatars/';
		if(!file_exists($dest)){@mkdir($dest);}
		if(!file_exists($dest)){$a = array('errorCode'=>1,'errorDescription'=>'USERS_FOLDER_NOT_WRITABLE','file'=>__FILE__,'line'=>__LINE__);return $noencode ? $a : json_encode($a);}

		images_square($file,$dest.'avatar32.jpeg',32);
		images_square($file,$dest.'avatar64.jpeg',64);
		images_square($file,$dest.'avatar300.jpeg',300);
		$r = array('errorCode'=>0);
		return ($noencode) ? $r : json_encode($r);
	}

	function users_login($userMail,$pass,$db = false,$noencode = false){
		if(empty($userMail)){return false;}
		$pass = sha1($pass);

		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['users']);$shouldClose = true;}
		$r = $db->query('SELECT '.$GLOBALS['API_users']['selectString'].' FROM users WHERE userMail = \''.$db->escapeString($userMail).'\' AND userPass = \''.$db->escapeString($pass).'\';');
		if($r && ($user = $r->fetchArray(SQLITE3_ASSOC))){
			$domain = '.'.$_SERVER['SERVER_NAME'];
			if($_SERVER['SERVER_NAME'] == 'localhost'){$domain = false;}
			setcookie('user',$userMail,time()+72000,'/',$domain);
			setcookie('pass',$pass,time()+72000,'/',$domain);

			$user = users_update($user['id'],array('userIP'=>$_SERVER['REMOTE_ADDR'],'userLastLogin'=>date('Y-m-d H:i:s')),$db,true);
			$GLOBALS['userLogged'] = $user;
			if($shouldClose){$db->close();}
			if($noencode){return $user;}
			return json_encode(array('errorCode'=>0,'data'=>$user));
		}
		if($shouldClose){$db->close();}
		if($noencode){return false;}
		return json_encode(array('errorCode'=>1,'errorDescription'=>'WRONG_USER_OR_PASS','file'=>__FILE__,'line'=>__LINE__));
	}
	function users_logout(){
		$domain = '.'.$_SERVER['SERVER_NAME'];
		if($_SERVER['SERVER_NAME'] == 'localhost'){$domain = false;}
		setcookie('user','',-1,'/',$domain);
		setcookie('pass','',-1,'/',$domain);
		session_destroy();
	}

	function users_isLogged($db = false){
		if(isset($GLOBALS['userLogged']) && is_array($GLOBALS['userLogged'])){return true;}
		if(isset($_SESSION['user']) && $_SESSION['user'] == 'Guest' && isset($_SESSION['pass']) && $_SESSION['pass'] == 'Guest' && isset($_SESSION['userMail'])){users_guestSession($_SESSION['userMail']);return true;}
		if(!isset($_COOKIE['user']) || !isset($_COOKIE['pass'])){return false;}
		$user = $_COOKIE['user'];
		$pass = $_COOKIE['pass'];

		if(!file_exists($GLOBALS['db']['users'])){return false;}
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['users'],SQLITE3_OPEN_READONLY);$shouldClose = true;}
		$r = $db->query('SELECT '.$GLOBALS['API_users']['selectString'].' FROM users WHERE userMail = "'.$db->escapeString($user).'" AND userPass = "'.$db->escapeString($pass).'";');
		if($r && ($GLOBALS['userLogged'] = $r->fetchArray(SQLITE3_ASSOC))){if($shouldClose){$db->close();}return true;}
		//FIXME: expire cookies
		unset($_COOKIE['user'],$_COOKIE['pass']);
		if($shouldClose){$db->close();}
		return false;
	}

	function users_isAdmin(){
		if(!isset($GLOBALS['userLogged'])){return false;}
		return (strpos($GLOBALS['userLogged']['userClass'],',admin,') !== false);
	}

	function users_resolveIds($ids,$db = false,$noencode = false,$indexBy = 'id'){
		if(is_array($ids)){$ids = implode(',',$ids);}$ids = preg_replace('/[^0-9,]*/','',$ids);
		if($ids === ''){return false;}
		$ids = explode(',',$ids);
		$ids = array_diff($ids,array(''));

		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['users']);$shouldClose = true;}
		$whereClause = 'WHERE ';foreach($ids as $id){$whereClause .= '(id = '.$id.') OR ';}$whereClause = substr($whereClause,0,-4);
		$r = $db->query('SELECT '.$GLOBALS['API_users']['selectString'].' FROM users '.$whereClause);
		$rows = array();if($r){while($row =  $r->fetchArray(SQLITE3_ASSOC)){$rows[$row[$indexBy]] = $row;}}
		if(in_array(0,$ids)){$row = users_getGuest();$rows[$row[$indexBy]] = $row;}
		if($noencode){return $rows;}
		if($shouldClose){$db->close();}
		return json_encode(array('errorCode'=>0,'data'=>$rows));
	}

	function users_getByMail($mail,$db = false,$noencode = false){
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['users'],SQLITE3_OPEN_READONLY);$shouldClose = true;}
		$row = $db->querySingle('SELECT '.$GLOBALS['API_users']['selectString'].' FROM users WHERE userMail = \''.$db->escapeString($mail).'\'',1);
		if($shouldClose){$db->close();}
		if($noencode && ($row === false || count($row) == 0)){return false;}
		if($noencode){return $row;}
		return json_encode(array('errorCode'=>(int)0,'data'=>$row));
	}

	function users_getByFacebookID($fID,$db = false,$noencode = false){
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['users'],SQLITE3_OPEN_READONLY);$shouldClose = true;}
		$row = $db->querySingle('SELECT '.$GLOBALS['API_users']['selectString'].' FROM users WHERE userFacebookID = \''.$db->escapeString($fID).'\'',1);
		if($shouldClose){$db->close();}
		if($noencode && ($row === false || count($row) == 0)){return false;}
		if($noencode){return $row;}
		return json_encode(array('errorCode'=>(int)0,'data'=>$row));
	}

	function users_getUsers($db = false,$noencode = false,$limit = false,$whereClause = false,$order = 'id DESC'){
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['users']);$shouldClose = true;}
		$query = 'SELECT * FROM users '.(($whereClause !== false) ? 'WHERE '.$whereClause : '').' ORDER BY '.$db->escapeString($order);
		if($limit !== false){$limit = preg_replace('/[^0-9,]*/','',$limit);$query .= ' LIMIT '.$limit;}
		//echo $query;
		$r = $db->query($query);
		$rows = array();if($r){while($row = $r->fetchArray(SQLITE3_ASSOC)){$rows[] = $row;}}
		if($shouldClose){$db->close();}
		if($noencode){return $rows;}
		return json_encode(array('errorCode'=>(int)0,'data'=>$rows));
	}

	function users_guestSession($userMail,$noencode = false){
		if(!preg_match('/^[a-z0-9\._\+\-]+@[a-z0-9\.\-]+\.[a-z]{2,6}$/',$userMail)){$a = array('errorCode'=>1,'errorDescription'=>'EMAIL_ERROR','file'=>__FILE__,'line'=>__LINE__);return $noencode ? $a : json_encode($a);}
		$_SESSION = array_merge($_SESSION,array('user'=>'Guest','pass'=>'Guest','userMail'=>$userMail,'userClass'=>',regular,'));
		$GLOBALS['userLogged'] = array('id'=>'0','userName'=>'Guest','userMail'=>$userMail,'userClass'=>',regular,');
		return $noencode ? true : '{"errorCode":"0"}';
	}
	function users_getGuest(){
		return array('id'=>'0','userName'=>'Guest','userMail'=>'empty');
	}
	/* MANAGEMENT */

	function users_changeClass($userID,$userClass,$db = false,$noencode = false){
		//FIXME: validar que el usuario conectado es admin
		$userID = preg_replace('/[^0-9]*/','',$userID);
		$userClass = preg_replace('/[^a-zA-Z0-9,]*/','',$userClass);
		$userClass = explode(',',$userClass);
		$userClass = array_diff($userClass,array(''));
		$userClass = ','.implode(',',$userClass).',';

		//FIXME: comprobar que la clase existe dentro de la configuración del cliente

		$r = users_update($userID,array('userClass'=>$userClass),$db,$noencode);
		return $r;
	}
	function users_changePass($id,$newPass,$db = false,$noencode = false){
		/* El propio users_update genera el sha1 del campo userPass */
		$r = users_update($id,array('userPass'=>$newPass,'userCode'=>''),$db,$noencode);
	}
?>
