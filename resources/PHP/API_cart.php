<?php
	if(!isset($GLOBALS['CLIENT'])){
		if($_SERVER['SERVER_NAME'] !== 'localhost'){$GLOBALS['CLIENT'] = $_SERVER['SERVER_NAME'];}
		else{$r = preg_match('/([a-zA-Z0-9\.]+)\/g\/PHP\/([a-zA-Z0-9_]+)\.php(\?.*?)$/',$_SERVER['REQUEST_URI'],$m);
		if(!$r){exit;}$GLOBALS['CLIENT'] = $m[1];}
	}

	$GLOBALS['db']['cart'] = '../clients/'.$GLOBALS['CLIENT'].'/db/blueCommerce.db';
	$GLOBALS['tables']['cartList'] = array('_cartHash_'=>'TEXT NOT NULL','cartFile'=>'TEXT','cartUserID'=>'TEXT','cartDate'=>'TEXT','cartProducts'=>'TEXT','cartTotalPrice'=>'TEXT','cartCurrency'=>'TEXT','cartStatus'=>'INTEGER','cartTrack'=>'TEXT');
	$GLOBALS['tables']['usersShippingInfo'] = array('_id_'=>'INTEGER AUTOINCREMENT','shippingUserID'=>'INTEGER','shippingUserName'=>'TEXT','shippingUserLastName'=>'TEXT','shippingUserPhone'=>'TEXT','shippingAddress'=>'TEXT',
		'shippingCountry'=>'TEXT','shippingCountryEng'=>'TEXT','shippingCountryCode'=>'TEXT','shippingPostalCode'=>'TEXT','shippingLocation'=>'TEXT','shippingInfoActive'=>'INTEGER','shippingStatus'=>'INTEGER DEFAULT 0');
	/* shippingStatus:
	/* -> 1 : dirección por defecto
	/* -> 2 : facturación
	/*/
	$GLOBALS['tables']['shippingList'] = array(
		'_cartHash_'=>'TEXT NOT NULL','shippingUserID'=>'INTEGER','shippingInfoID'=>'INTEGER',
		'shippingCartName'=>'TEXT','shippingProducts'=>'TEXT','shippingPaymentMethod'=>'INTEGER','shippingComments'=>'TEXT','shippingMail'=>'TEXT',
		'shippingLang'=>'TEXT','shippingStatus'=>'INTEGER','shippingTrack'=>'TEXT','shippingOrderDate'=>'TEXT','shippingOrderTime'=>'TEXT','shippingInvoice'=>'INTEGER');

	$GLOBALS['CURRENCYCODE'] = array('EUR'=>'978','LIB'=>'826','USA'=>'840');

	if(isset($_POST['command'])){
		$command = $_POST['command'];unset($_POST['command']);
		include_once('API_users.php');users_isLogged();if($GLOBALS['userLogged'] === false){return false;}
		switch($command){
			case 'shipping_update':echo cart_shipping_update((isset($_POST['cartHash']) ? $_POST['cartHash'] : false),array_diff_key($_POST,array('cartHash'=>'')));break;
			case 'shipping_getInfo':echo cart_shipping_getInfo((isset($_POST['cartHash']) ? $_POST['cartHash'] : false));break;
		}
		exit;
	}

	//cart_updateSchema();exit;
	function cart_updateSchema($db = false){
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['cart']);$shouldClose = true;}
		include_once('inc_databaseSqlite3.php');
		$r = sqlite3_updateTableSchema('usersShippingInfo',$db);
		print_r($r);
		if($shouldClose){$db->close();}
	}
	function cart_updateSchemaByTable($table = false,$db = false){
//FIXME: habría que usar la API para backup de common
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['cart']);$shouldClose = true;}
		include_once('inc_databaseSqlite3.php');
		$r = sqlite3_updateTableSchema($table,$db);
		print_r($r);
		if($shouldClose){$db->close();}
	}

	/* FIXME: DEPRECATED */
	function cart_getCarts($db = false,$noencode = false,$limit = false,$whereClause = false,$order = 'cartFile DESC'){
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['cart'],SQLITE3_OPEN_READONLY);$shouldClose = true;}
		$query = 'SELECT * FROM cartList '.(($whereClause !== false) ? 'WHERE '.$whereClause : '').' ORDER BY '.$db->escapeString($order);
		if($limit !== false){$limit = preg_replace('/[^0-9,]*/','',$limit);$query .= ' LIMIT '.$limit;}
		//echo $query;
		$r = @$db->query($query);
		$rows = array();if($r){while($row = $r->fetchArray(SQLITE3_ASSOC)){$rows[] = $row;}}
		if($shouldClose){$db->close();}
		if($noencode){return $rows;}
		return json_encode(array('errorCode'=>(int)0,'data'=>$rows));
	}
	
	function cart_getCarts2($db = false,$noencode = false,$limit = false,$whereClause = false,$order = 'shippingCartName DESC'){
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['cart'],SQLITE3_OPEN_READONLY);$shouldClose = true;}
		$query = 'SELECT * FROM shippingList '.(($whereClause !== false) ? 'WHERE '.$whereClause : '').' ORDER BY '.$db->escapeString($order);
		if($limit !== false){$limit = preg_replace('/[^0-9,]*/','',$limit);$query .= ' LIMIT '.$limit;}
		//echo $query;
		$r = @$db->query($query);
		$rows = array();if($r){while($row = $r->fetchArray(SQLITE3_ASSOC)){$rows[] = $row;}}
		if($shouldClose){$db->close();}
		if($noencode){return $rows;}
		return json_encode(array('errorCode'=>(int)0,'data'=>$rows));
	}

	function cart_getByHash($hash,$db = false,$noencode = false){
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['cart'],SQLITE3_OPEN_READONLY);$shouldClose = true;}
		$row = @$db->querySingle('SELECT * FROM cartList WHERE cartHash = \''.$db->escapeString($hash).'\'',1);
		if($shouldClose){$db->close();}
		if($noencode && ($row === false || count($row) == 0)){return false;}
		if($noencode){return $row;}
		return json_encode(array('errorCode'=>(int)0,'data'=>$row));
	}

	function cart_update($cartHash,$data = false,$db = false,$noencode = true){
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['cart']);$shouldClose = true;}
		$data['_cartHash_'] = $cartHash;
		include_once('inc_databaseSqlite3.php');
		$r = sqlite3_insertIntoTable('cartList',$data,$db);
		if($r['OK'] === false){if($shouldClose){$db->close();}$a = array('errorCode'=>$r['errno'],'errorDescription'=>$r['error'],'file'=>__FILE__,'line'=>__LINE__);return ($noencode) ? $a : json_encode($a);}
		if($shouldClose){$db->close();}
		if($noencode){return true;}
		return '{"errorCode":"0"}';
	}

	function cart_acquireCart(){
		$ids = array();foreach($_COOKIE as $k=>$v){
			if(substr($k,0,9) != 'cartItem_'){continue;}
			$id = substr($k,9);if(!is_numeric($id)){setcookie('cartItem_'.$id,'',-1,'/');continue;}
			$ids[] = $id;
		}

		include_once('API_product.php');
		$pList = product_resolveIds($ids,false,true);
		foreach($ids as $id){
			if(!isset($pList[$id])){setcookie('cartItem_'.$id,'',-1,'/');continue;}
			$cookieDecoded = json_decode(utf8_encode($_COOKIE['cartItem_'.$id]),1);
			$count = intval($cookieDecoded['productCount']);
			if($count < 1){unset($pList[$id]);setcookie('cartItem_'.$id,'',-1,'/');continue;}
			$pList[$id]['productCount'] = $count;
		}
		return $pList;
	}

	function cart_save($userID,$db = false,$noencode = false,$shippingCost = false){
//FIXME: userID no se usa actualmente
//FIXME: detectar si el carro existe y actualizar, debería poder actualizar siempre y cuando el status sea cero
		if(!users_isLogged()){$a = array('errorCode'=>99,'errorDescription'=>'USER_NOT_LOGGED','file'=>__FILE__,'line'=>__LINE__);return ($noencode) ? $a : json_encode($a);}
		$userPool = '../clients/'.$GLOBALS['CLIENT'].'/db/users/';
		$userPath = $userPool.$GLOBALS['userLogged']['id'].'/';
		if(!is_writable($userPool)){$a = array('errorCode'=>1,'errorDescription'=>'USER_POOL_NOT_WRITABLE','file'=>__FILE__,'line'=>__LINE__);return ($noencode) ? $a : json_encode($a);}
		if(!file_exists($userPath)){mkdir($userPath,0777,1);}
		$cartDatabasePath = $userPath.'db/carts/';if(!file_exists($cartDatabasePath)){mkdir($cartDatabasePath,0777,1);}

		$cartFile = date('\c\a\r\t_Y-m-d-H-i-s.\d\b');
		$cartPath = $cartDatabasePath.$cartFile;

		/* Si el carrito de la compra ya existe, y no queremos sobreescribir el fichero porque no estamos 
		 * actualizando productos, necesitaríamos generar un nuevo nombre del carro que sea único */
		while(file_exists($cartPath)){sleep(1);$cartFile = date('\c\a\r\t_Y-m-d-H-i-s.\d\b');$cartPath = $cartDatabasePath.$cartFile;}

		$pList = $uList = cart_acquireCart();
		/* Es de vital importancia que los productos se inserten dentro de la base de datos en un orden determinado,
		 * se ordenarán en base al identificador del producto, que es además la key de cada nodo del array, se usará ksort */
		ksort($pList);

		if(!is_writable($cartDatabasePath)){$a = array('errorCode'=>1,'errorDescription'=>'USER_CARTSFOLDER_NOT_WRITABLE','file'=>__FILE__,'line'=>__LINE__);return ($noencode) ? $a : json_encode($a);}
		$uc = new sqlite3($cartPath);
		include_once('inc_databaseSqlite3.php');
		$productIDs = ',';
		$cartTotalPrice = 0;$cartTotalWeight = 0;
		/* Parseamos los productos para poder obetener el coste global, hay que darse cuenta que en el fichero de carrito que
		 * se está generando se inserta un elemento de $uList en vez de $pList que serían los traducidos, por lo que no se pierde 
		 * información sobre el elemento original en ningún caso */
		$pList = product_helper_parseProductsByLang($pList,$GLOBALS['LANGCODE']);
		foreach($pList as $k=>$p){
			if(!isset($p['productCount']) || empty($p['productCount'])){$p['productCount'] = 1;}
			$productIDs .= $p['id'].':'.$p['productCount'].',';
			$cartTotalPrice += $p['productPriceTotal'];
			$cartTotalWeight += $p['productWeightTotal'];
			$r = sqlite3_insertIntoTable('products',$uList[$k],$uc);
		}
		
		/* Gastos de envío por peso en kg */
		//FIXME: DEPRECATED
		if(isset($GLOBALS['blueCommerce']['STORE_shippingType']) && $GLOBALS['blueCommerce']['STORE_shippingType'] == 'byWeightKilo'){
			$cartTotalWeight = ceil($cartTotalWeight/1000);
			if($cartTotalWeight != 0){
				$rang = $GLOBALS['blueCommerce']['STORE_shippingPrices'][$GLOBALS['LANGCODE']];		
				preg_match_all('/\(([0-9\-]+)\)([0-9]+)/', $rang, $r);
				
				$rang = 0;
				while(isset($r[1][$rang])) {$val = explode('-', $r[1][$rang]);if($cartTotalWeight > $val[0] && $cartTotalWeight <= $val[1]){break;}$rang++;}
				//Ya sabemos donde está
				$shippingCosts = $r[2][$rang];
				$cartTotalPrice += $shippingCosts;
				
				//Crear nuevo producto con id -1 para gastos de envío
				$sc = array();
				//FIXME:FIXME:FIXME: SOPORTE DE IDIOMAS
				$sc['id'] = -1;
				$sc['productPrice'] = '<div lang=\'default\'>'.$shippingCosts.'</div><div lang=\'es-es\'>'.$shippingCosts.'</div><div lang=\'en-us\'>'.$shippingCosts.'</div>';
				$sc['productTitle'] = '<div lang=\'default\'>Gastos de enví­o</div><div lang=\'es-es\'>Gastos de envío</div><div lang=\'en-us\'>Gastos de enví­o</div>';
				$sc['productCount'] = 1;
				
				$r = sqlite3_insertIntoTable('products',$sc,$uc);
			}
		}

		if($shippingCost != false){
			$langFile = '../clients/'.$GLOBALS['CLIENT'].'/db/lang/'.$GLOBALS['LANGCODE'].'.php';
			if(file_exists($langFile)){include($langFile);}
			//FIXME: llenar la descripción con los detalles del carrier
			if(!isset($LANG['shippingCostTitle'])){$LANG['shippingCostTitle'] = '{%LANG_shippingCost%}';}
			$sc = array('id'=>-1,'productTitle'=>'<div lang=\'default\'>'.$LANG['shippingCostTitle'].'</div>','productPrice'=>'<div lang=\'default\'>'.$shippingCost['cost'].'</div>','productCurrency'=>'<div lang=\'default\'>'.$shippingCost['currency'].'</div>','productCount'=>1);
			$r = sqlite3_insertIntoTable('products',$sc,$uc);
		}
				
		$uc->close();

//FIXME: hardCoded. sacar el currency gracias a LANGCODE
$cartCurrency = 'EUR';
		//FIXME: lo vamos a hacer aquí de momento, pero los carros se deben poder guardar para después
		$cartHash = sha1($GLOBALS['userLogged']['id'].$cartFile);
		
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['cart']);$shouldClose = true;}
		$row = array('cartHash'=>$cartHash,'cartFile'=>$cartFile,'cartUserID'=>$GLOBALS['userLogged']['id'],'cartDate'=>date('Y-m-d H:i:s'),'cartProducts'=>$productIDs,'cartTotalPrice'=>$cartTotalPrice,'cartCurrency'=>$cartCurrency,'cartStatus'=>0);
				
		$r = sqlite3_insertIntoTable('cartList',$row,$db);
		
		if($r['OK'] === false){if($shouldClose){$db->close();}$a = array('errorCode'=>$r['errno'],'errorDescription'=>$r['error'],'file'=>__FILE__,'line'=>__LINE__);return ($noencode) ? $a : json_encode($a);}

		$carts = cart_getCarts($db,true,false,'(cartHash = \''.$cartHash.'\')');
		
		if($shouldClose){$db->close();}
		if(count($carts) < 1){
//FIXME:
			echo 'error';
			exit;
		}
		$row = array_shift($carts);

		//FIXME: obtener $row por API
		$a = array('errorCode'=>(int)0,'data'=>$row);
		return ($noencode) ? $a : json_encode($a);
	}

	function cart_load($userID,$cartFile,$db = false,$noencode = false){
//FIXME: si entra db por parámetros, usar esa conexión y ahorrar comprobaciones
		$userPool = '../clients/'.$GLOBALS['CLIENT'].'/db/users/';
		$userPath = $userPool.$userID.'/';
		if(!file_exists($userPath)){$a = array('errorCode'=>1,'errorDescription'=>'USER_NOT_EXISTS','file'=>__FILE__,'line'=>__LINE__);return ($noencode) ? $a : json_encode($a);}
		$cartPath = $userPath.'db/carts/';if(!file_exists($cartPath)){$a = array('errorCode'=>1,'errorDescription'=>'USER_HAS_NO_CARTS','file'=>__FILE__,'line'=>__LINE__);return ($noencode) ? $a : json_encode($a);}
		$cartPath .= $cartFile;if(!file_exists($cartPath)){$a = array('errorCode'=>1,'errorDescription'=>'CART_NOT_EXISTS','file'=>__FILE__,'line'=>__LINE__);return ($noencode) ? $a : json_encode($a);}

		$db = new sqlite3($cartPath);
		unset($_POST['command']);
		include_once('API_product.php');
		$rows = product_getProducts($db,true);
		$db->close();

		$a = array('errorCode'=>(int)0,'data'=>$rows);
		return ($noencode) ? $a : json_encode($a);
	}

	function cart_getCartsFromUser($userID,$db = false,$noencode = false,$userIsValidated = false){
		if($userIsValidated === false){
			include_once('API_users.php');$user = users_resolveIds(array($userID),false,true);$user = array_shift($user);
			if(count($user) < 1){$a = array('errorCode'=>99,'errorDescription'=>'USER_NOT_EXISTS','file'=>__FILE__,'line'=>__LINE__);return ($noencode) ? $a : json_encode($a);}
			$userID = $user['id'];
		}

		$userPath = '../clients/'.$GLOBALS['CLIENT'].'/db/users/'.$userID.'/';
		if(!file_exists($userPath)){return;}
		$cartPath = $userPath.'db/carts/';
		if(!file_exists($cartPath)){return;}

		$carts = array();
		if($handle = opendir($cartPath)){while(false !== ($file = readdir($handle))){if(($file[0]!='.')){$carts[$file] = array('cartFile'=>$file);}}closedir($handle);}
		rsort($carts);
		if($noencode){return $carts;}
	}

	function cart_shipping_save($userID,$row,$db = false,$noencode = false,$userIsValidated = false){
//FIXME: $userID debe desaparecer, debería formar parte de $row
		if($userIsValidated === false){
			include_once('API_users.php');$user = users_resolveIds(array($userID),false,true);$user = array_shift($user);
			if(count($user) < 1){$a = array('errorCode'=>99,'errorDescription'=>'USER_NOT_EXISTS','file'=>__FILE__,'line'=>__LINE__);return ($noencode) ? $a : json_encode($a);}
			$userID = $user['id'];
		}

		$valid = array_fill_keys(array_keys($GLOBALS['tables']['shippingList']),0);
		unset($valid['_id_']);
		include_once('inc_strings.php');
		foreach($row as $k=>$v){if(!isset($valid[$k])){unset($row[$k]);continue;}$row[$k] = strings_UTF8Encode($row[$k]);}
		$row['shippingUserID'] = $userID;
		$row['shippingStatus'] = 0;
		$row['shippingOrderDate'] = date('Y-m-d');
		$row['shippingOrderTime'] = date('H:i:s');
		if(!isset($row['shippingMail'])){$row['shippingMail'] = '';}

		//FIXME: algunos parámetros no pueden llegar vacíos

		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['cart']);$shouldClose = true;}
		/* Validamos la información de envío */
		$sInfo = cart_shippingInfo_getByID($row['shippingInfoID'],$db,true);


		if($sInfo === false){$a = array('errorCode'=>3,'errorDescription'=>'INVALID_SHIPPING_INFO','file'=>__FILE__,'line'=>__LINE__);return ($noencode) ? $a : json_encode($a);}
		if($sInfo['shippingUserID'] != $userID){$a = array('errorCode'=>4,'errorDescription'=>'INVALID_SHIPPING_INFO','file'=>__FILE__,'line'=>__LINE__);return ($noencode) ? $a : json_encode($a);}


		/* Debemos validar el shippingCartName, y decrementar el número del stock, si se tiene que quedar a números negativos no
		 * pasa nada, algunas tiendas funcionarán de esta manera, también hay que aumentar el volatile */
		$carts = cart_getCarts($db,true,1,'(cartUserID = '.$row['shippingUserID'].' AND cartFile = \''.$row['shippingCartName'].'\')');
		if(count($carts) < 1){if($shouldClose){$db->close();}$a = array('errorCode'=>1,'errorDescription'=>'INVALID_CART','file'=>__FILE__,'line'=>__LINE__);return ($noencode) ? $a : json_encode($a);}
		$cart = array_shift($carts);unset($carts);

		//FIXME: con el tiempo cartList dejará de tener sentido como tal, muchos de sus campos encajarían bien en el shippingList
		$row['shippingProducts'] = $cart['cartProducts'];
		$row['_cartHash_'] = $cart['cartHash'];

		//FIXME, nos interesa principalmente el campo cartProducts
		$r = preg_match_all('/([0-9]+):([0-9]+)/',$cart['cartProducts'],$m);
		if(!$r){if($shouldClose){$db->close();}$a = array('errorCode'=>1,'errorDescription'=>'NO_PRODUCTS_MATCH','file'=>__FILE__,'line'=>__LINE__);return ($noencode) ? $a : json_encode($a);}
		$cartProducts = array();foreach($m[0] as $k=>$v){$cartProducts[$m[1][$k]] = ($m[2][$k])*-1;}

		/* Ahora tenemos el array $cartProducts con el formato [1] => 1 , es el mismo que que acepta la funcion 
		 * product_stock_modifyFromArrayIndexed de la librería API_product.php, la usamos para actualizar el stock */
		include_once('API_product.php');
		$r = product_stock_modifyFromArrayIndexed($cartProducts,$db,true);
		if(count($r['data']) > 0){
			echo 'UNHANDLED ERROR';exit;
		}

		include_once('inc_databaseSqlite3.php');
		$r = sqlite3_insertIntoTable('shippingList',$row,$db);
		if($r['OK'] === false){if($shouldClose){$db->close();}$a = array('errorCode'=>$r['errno'],'errorDescription'=>$r['error'],'file'=>__FILE__,'line'=>__LINE__);return ($noencode) ? $a : json_encode($a);}

		//FIXME quizá obtener el shipping y enviarlo
		if($shouldClose){$db->close();}
		$a = array('errorCode'=>(int)0,'data'=>$row);
		return ($noencode) ? $a : json_encode($a);
	}

	//$r = cart_shipping_update('f3c7b00bb6c12c590e0ee71b965a8b997bfcf75a',array('shippingStatus'=>1,'shippingTrack'=>'paypal:aabbcc'),false,true);
	function cart_shipping_update($cartHash,$data = false,$db = false,$noencode = false){
		/* EL identificador $cartHash pueden ser varios separados por comas, se aplicarán los mismos valores
		 * de data para todos los identificadores, esta función sirve para hacer actualizaciones masivas, si se
		 * quisiera aplicar un valor de data individual a cada $cartHash habría que hacer varias llamadas individuales 
		 * a esta función */
		include_once('inc_databaseSqlite3.php');
		$cartHashes = explode(',',$cartHash);
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['cart']);$shouldClose = true;}
		foreach($cartHashes as $cartHash){
			$data['_cartHash_'] = $cartHash;
			$r = sqlite3_insertIntoTable('shippingList',$data,$db);
			if($r['OK'] === false){if($shouldClose){$db->close();}$a = array('errorCode'=>$r['errno'],'errorDescription'=>$r['error'],'file'=>__FILE__,'line'=>__LINE__);return ($noencode) ? $a : json_encode($a);}
		}
		if($shouldClose){$db->close();}
		if($noencode){return true;}
		return '{"errorCode":"0"}';
	}

	function cart_shipping_get($db = false,$noencode = false,$limit = false,$whereClause = false,$order = 'shippingCartName DESC'){
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['cart'],SQLITE3_OPEN_READONLY);$shouldClose = true;}
		$query = 'SELECT * FROM shippingList '.(($whereClause !== false) ? 'WHERE '.$whereClause : '').' ORDER BY '.$db->escapeString($order);
		if($limit !== false){$limit = preg_replace('/[^0-9,]*/','',$limit);$query .= ' LIMIT '.$limit;}
		//echo $query;
		$r = @$db->query($query);
		$rows = array();if($r){while($row = $r->fetchArray(SQLITE3_ASSOC)){$rows[] = $row;}}
		if($shouldClose){$db->close();}
		if($noencode){return $rows;}
		return json_encode(array('errorCode'=>(int)0,'data'=>$rows));
	}

	function cart_shipping_getByHash($hash,$db = false,$noencode = false){
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['cart'],SQLITE3_OPEN_READONLY);$shouldClose = true;}
		$row = $db->querySingle('SELECT * FROM shippingList WHERE cartHash = \''.$hash.'\'',1);
		if($shouldClose){$db->close();}
		if($noencode && ($row === false || count($row) == 0)){return false;}
		if($noencode){return $row;}
		return json_encode(array('errorCode'=>(int)0,'data'=>$row));
	}

	function cart_shipping_getByID($db = false,$noencode = false,$limit = false,$whereClause = false,$order = 'shippingCartName DESC'){
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['cart'],SQLITE3_OPEN_READONLY);$shouldClose = true;}
		$query = 'SELECT * FROM shippingList '.(($whereClause !== false) ? 'WHERE '.$whereClause : '').' ORDER BY '.$db->escapeString($order);
		if($limit !== false){$limit = preg_replace('/[^0-9,]*/','',$limit);$query .= ' LIMIT '.$limit;}
		//echo $query;
		$r = @$db->query($query);
		$rows = array();if($r){while($row = $r->fetchArray(SQLITE3_ASSOC)){$rows[] = $row;}}
		if($shouldClose){$db->close();}
		if($noencode){return $rows;}
		return json_encode(array('errorCode'=>(int)0,'data'=>$rows));
	}

	function cart_shipping_getInfo($cartHash,$db = false,$noencode = false){
		/* Le pasamos una serie de $cartHash separados por comas y esta función te devuelve los infos 
		 * asociados a esos cartHashs */
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['cart'],SQLITE3_OPEN_READONLY);$shouldClose = true;}
		$cartHashes = explode(',',$cartHash);
		$whereClause = '';foreach($cartHashes as $cartHash){$whereClause .= '(cartHash = \''.$cartHash.'\') OR ';}
		$whereClause = substr($whereClause,0,-4);
		$shippings = cart_shipping_get($db,true,count($cartHashes),$whereClause);
		$shippingInfoIDs = array();foreach($shippings as $s){$shippingInfoIDs[] = $s['shippingInfoID'];}
		$shippingInfoIDs = array_unique($shippingInfoIDs);
		$whereClause = '';foreach($shippingInfoIDs as $shippingInfoID){$whereClause .= '(id = '.$shippingInfoID.') OR ';}
		$whereClause = substr($whereClause,0,-4);
		$shippingInfos = cart_shippingInfo_get($db,true,count($shippingInfoIDs),$whereClause);
		$data = array();foreach($shippings as $s){$data[$s['cartHash']] = $shippingInfos[$s['shippingInfoID']];}
		if($noencode){return $shippingInfos;}
		return json_encode(array('errorCode'=>(int)0,'data'=>$data));
	}

	function cart_shippingInfo_get($db = false,$noencode = false,$limit = false,$whereClause = false,$orderBy = 'id DESC',$indexBy = 'id'){
		if($orderBy === false){$orderBy = 'id DESC';}if($indexBy === false){$indexBy = 'id';}
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['cart'],SQLITE3_OPEN_READONLY);$shouldClose = true;}
		$query = 'SELECT * FROM usersShippingInfo '.(($whereClause !== false) ? 'WHERE '.$whereClause : '').' ORDER BY '.$db->escapeString($orderBy);
		if($limit !== false){$limit = preg_replace('/[^0-9,]*/','',$limit);$query .= ' LIMIT '.$limit;}
		//echo $query;
		$r = @$db->query($query);
		$rows = array();if($r){while($row = $r->fetchArray(SQLITE3_ASSOC)){$rows[$row[$indexBy]] = $row;}}
		if($shouldClose){$db->close();}
		if($noencode){return $rows;}
		return json_encode(array('errorCode'=>(int)0,'data'=>$rows));
	}

	function cart_shippingInfo_markAsDefault($sID,$db = false,$noencode = false){
		/* Primero debemos comprobar que ese shipping existe */
		$shipping = cart_shippingInfo_getByID($sID,$db,true);
		if($shipping === false){
			$a = array('errorCode'=>1,'errorDescription'=>'SHIPPINGINFO_NOT_EXISTS','file'=>__FILE__,'line'=>__LINE__);
			return $noencode ? $a : json_encode($a);
		}
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['cart']);$shouldClose = true;}
		/* Eliminamos el anterior default */
		$db->exec('UPDATE usersShippingInfo SET shippingStatus = 0 WHERE shippingUserID = '.$shipping['shippingUserID'].' AND shippingStatus = 1;');
		$db->exec('UPDATE usersShippingInfo SET shippingStatus = 1 WHERE id = '.$shipping['id'].';');
		if($shouldClose){$db->close();}
		if($noencode){return true;}
		return '{"errorCode":0}';
	}

	function cart_shippingInfo_changeStatus($id,$status,$db = false,$noencode = false){
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['cart']);$shouldClose = true;}
		
		$data = array();
		$data['_id_'] = $id;
		$data['shippingInfoActive'] = $status;
		include_once('inc_databaseSqlite3.php');
		$r = sqlite3_insertIntoTable('usersShippingInfo',$data,$db);
		if($r['OK'] === false){if($shouldClose){$db->close();}$a = array('errorCode'=>$r['errno'],'errorDescription'=>$r['error'],'file'=>__FILE__,'line'=>__LINE__);return false;}
		
		if($shouldClose){$db->close();}
		return true;
	}

	function cart_shippingInfo_save($userID,$row,$db = false,$noencode = false,$userIsValidated = false){
		if($userIsValidated === false){
			include_once('API_users.php');$user = users_resolveIds(array($userID),false,true);$user = array_shift($user);
			if(count($user) < 1){$a = array('errorCode'=>99,'errorDescription'=>'USER_NOT_EXISTS','file'=>__FILE__,'line'=>__LINE__);return ($noencode) ? $a : json_encode($a);}
			$userID = $user['id'];
		}

		$valid = array_fill_keys(array_keys($GLOBALS['tables']['usersShippingInfo']),0);
		unset($valid['_id_']);
		include_once('inc_strings.php');
		foreach($row as $k=>$v){if(!isset($valid[$k])){unset($row[$k]);continue;}$row[$k] = strings_UTF8Encode($row[$k]);}
		$row['shippingUserID'] = $userID;
		$row['shippingInfoActive'] = 1;

		//FIXME: agregar el resto de traducciones de paises
		$lower = strtolower($row['shippingCountry']);
		$row['shippingCountryEng'] = str_replace(array('españa','francia'),array('spain','france'),$lower);
		$row['shippingCountryCode'] = str_replace(array('spain','france'),array('es','fr'),$row['shippingCountryEng']);

		//FIXME: comprobaciones
		//FIXME: algunos valores no pueden ir vacíos NUNCA

		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['cart']);$shouldClose = true;}
		include_once('inc_databaseSqlite3.php');
		$r = sqlite3_insertIntoTable('usersShippingInfo',$row,$db);
		if($r['OK'] === false){if($shouldClose){$db->close();}$a = array('errorCode'=>$r['errno'],'errorDescription'=>$r['error'],'file'=>__FILE__,'line'=>__LINE__);return ($noencode) ? $a : json_encode($a);}

		$row = cart_shippingInfo_getLastByUserID($userID,$db,true);

		if($shouldClose){$db->close();}
		return ($noencode) ? $row : json_encode(array('errorCode'=>(int)0,'data'=>$row));
	}


	function cart_shippingInfo_getByID($sID,$db = false,$noencode = false){
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['cart'],SQLITE3_OPEN_READONLY);$shouldClose = true;}
		$row = $db->querySingle('SELECT * FROM usersShippingInfo WHERE id = \''.$sID.'\' and shippingInfoActive = 1',1);
		if($shouldClose){$db->close();}
		if($noencode && ($row === false || count($row) == 0)){return false;}
		if($noencode){return $row;}
		return json_encode(array('errorCode'=>(int)0,'data'=>$row));
	}

	function cart_shippingInfo_getLastByUserID($userID,$db = false,$noencode = false,$orderBy = 'id DESC'){
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['cart'],SQLITE3_OPEN_READONLY);$shouldClose = true;}
		$row = @$db->querySingle('SELECT * from usersShippingInfo WHERE shippingUserID = \''.$userID.'\' ORDER BY '.$db->escapeString($orderBy),1);
		if($shouldClose){$db->close();}
		if($noencode && ($row === false || count($row) == 0)){return false;}
		if($noencode){return $row;}
		return json_encode(array('errorCode'=>(int)0,'data'=>$row));
	}

	function cart_shippingInfo_remove($userID,$shippingInfoID,$db = false,$noencode = false){
		/* No podemos eliminar algunas informaciónes de envío porque son necesarias para mostrar la información de 
		 * envío de algún pedido en un modo de vista de histórico. Sin embargo, si la información de envío no depende 
		 * de ningún producto concreto entonces la podemos eliminar directamente. Para averiguarlo buscaremos dentro 
		 * de la tabla shippingList, que contiene la informaciones de envío de pedidos realizados */
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['cart']);$shouldClose = true;}
		
//$row['shippingInfoActive'] = 1;
		//FIXME: TODO

		if($shouldClose){$db->close();}
		if($noencode){return true;}
		return '{"errorCode":0}';
	}

	//FIXME: pasarlo a un get tradicional
	function cart_getShippingInfoByUserID($userID,$db = false,$noencode = false,$orderBy = 'id DESC'){
/*
$shippingInfos = cart_shippingInfo_get(false,true,false,'shippingUserID = \''.$GLOBALS['userLogged']['id'].'\'');
		$GLOBALS['TEMPLATE']['shippingInfos'] = $shippingInfos;
*/
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['cart'],SQLITE3_OPEN_READONLY);$shouldClose = true;}
		/* AÑADIDO ORDER BY */
		$query = 'SELECT * FROM usersShippingInfo WHERE shippingUserID = \''.$userID.'\' and shippingInfoActive = 1 ORDER BY '.$orderBy;
		$r = @$db->query($query);
		$rows = array();if($r){while($row = $r->fetchArray(SQLITE3_ASSOC)){$rows[] = $row;}}
		if($shouldClose){$db->close();}
		$a = array('errorCode'=>(int)0,'data'=>$rows);
		return ($noencode) ? $rows : json_encode($a);
	}

	//cart_invoice_create($cartHash);
	function cart_invoice_create($cartHash,$db = false,$noencode = true){
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['cart'],SQLITE3_OPEN_READONLY);$shouldClose = true;}
		$r = cart_shipping_getByHash($cartHash,$db,true);
		print_r($r);
		
	}
?>
