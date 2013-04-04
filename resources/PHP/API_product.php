<?php
	if(!isset($GLOBALS['CLIENT'])){
		if($_SERVER['SERVER_NAME'] !== 'localhost'){$GLOBALS['CLIENT'] = $_SERVER['SERVER_NAME'];}
		else{$r = preg_match('/([a-zA-Z0-9\.]+)\/g\/PHP\/([a-zA-Z0-9_]+)\.php(\?.*?|)$/',$_SERVER['REQUEST_URI'],$m);
		if(!$r){exit;}$GLOBALS['CLIENT'] = $m[1];}
	}

	$GLOBALS['db']['product'] = '../clients/'.$GLOBALS['CLIENT'].'/db/blueCommerce.db';
	$GLOBALS['CURRENCY'] = array('EUR'=>'€','USD'=>'$','LIB'=>'L','DOL'=>'$');
	$GLOBALS['LANGUAGES'] = array('es-es','en-en');
	$GLOBALS['LANGNAME'] = array('ES'=>'Español','FR'=>'Français','EN'=>'English');
	$GLOBALS['COUNTRIES'] = array('ES'=>'ES','EN'=>'EN','PT'=>'PT','AR'=>'ARG','USA'=>'USA','EU'=>'EU'); // COUNTRIES and regions
	$GLOBALS['CURRENCYCODE'] = array('EUR'=>'978','LIB'=>'826','USA'=>'840');

	//FIXME: quizá sería necesario un campo "productHasChilds"
	$GLOBALS['tables']['products'] = array(
		'_id_'=>'INTEGER AUTOINCREMENT','productParentID'=>'INTEGER DEFAULT 0','productRelativeID'=>'TEXT','productCategories'=>'TEXT',
		'productLanguage'=>'TEXT','productCountry'=>'TEXT','productCurrency'=>'TEXT','productTitle'=>'TEXT',
		'productTitleFixed'=>'TEXT','productStock'=>'INTEGER','productVolatile'=>'INTEGER','productMax'=>'INTEGER',
		'productPrice'=>'TEXT','productOldPrice'=>'TEXT','productUnit'=>'TEXT','productWeight'=>'TEXT','productSize'=>'TEXT',
		'productColor'=>'TEXT','productDimensions'=>'TEXT','productDescription'=>'TEXT','productShortText'=>'TEXT','productTags'=>'TEXT',
		'productVat'=>'TEXT','productVatUnit'=>'TEXT','productStatus'=>'TEXT','productCreationDate'=>'TEXT','productCreationTime'=>'TEXT',
		'productCount'=>'INTEGER'
	);
	$GLOBALS['API_products']['selectString'] = '*';

	if(isset($_POST['command'])){
		$command = $_POST['command'];unset($_POST['command']);
		include_once('API_users.php');users_isLogged();if($GLOBALS['userLogged'] === false){return false;}
		switch($command){
			case 'changeField':echo product_changeField((isset($_POST['ids']) ? $_POST['ids'] : false),(isset($_POST['fieldName']) ? $_POST['fieldName'] : false),(isset($_POST['fieldText']) ? $_POST['fieldText'] : false));break;
			case 'update':echo product_update((isset($_POST['ids']) ? $_POST['ids'] : false),array_diff_key($_POST,array('ids'=>'')));break;
			case 'image_savePortraits':echo product_image_savePortraits($_POST);break;
		}
		exit;
	}

	function product_updateSchemaByTable($table = false,$db = false){
//FIXME: habría que usar la API para backup de common
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['product']);$shouldClose = true;}
		include_once('inc_databaseSqlite3.php');
		$r = sqlite3_updateTableSchema($table,$db);
		print_r($r);
		if($shouldClose){$db->close();}
	}

	function product_helper_parseProductsByLang($products,$language){
		//FIXME: añadir diferentes tags que dependan del lang
		foreach($products as $k=>&$product){
			$d = preg_match('/<div lang=\''.$language.'\'>(.*?)<\/div>/ms',$product['productTitle'],$m);		if(!$d){$d = preg_match('/<div lang=\'default\'>(.*?)<\/div>/ms',$product['productTitle'],$m);}if($d){$products[$k]['productTitle'] = $m[1];}else{$products[$k]['productTitle'] = '';}
			$d = preg_match('/<div lang=\''.$language.'\'>(.*?)<\/div>/ms',$product['productTitleFixed'],$m);	if(!$d){$d = preg_match('/<div lang=\'default\'>(.*?)<\/div>/ms',$product['productTitleFixed'],$m);}if($d){$products[$k]['productTitleFixed'] = $m[1];}else{$products[$k]['productTitleFixed'] = '';}
			$d = preg_match('/<div lang=\''.$language.'\'>(.*?)<\/div>/ms',$product['productDescription'],$m);	if(!$d){$d = preg_match('/<div lang=\'default\'>(.*?)<\/div>/ms',$product['productDescription'],$m);}if($d){$products[$k]['productDescription'] = $m[1];}else{$products[$k]['productDescription'] = '';}
			$d = preg_match('/<div lang=\''.$language.'\'>(.*?)<\/div>/ms',$product['productVat'],$m);		if(!$d){$d = preg_match('/<div lang=\'default\'>(.*?)<\/div>/ms',$product['productVat'],$m);}if($d){$products[$k]['productVat'] = $m[1];}else{$products[$k]['productVat'] = '';}
			$d = preg_match('/<div lang=\''.$language.'\'>(.*?)<\/div>/ms',$product['productPrice'],$m);		if(!$d){$d = preg_match('/<div lang=\'default\'>(.*?)<\/div>/ms',$product['productPrice'],$m);}if($d){$product['productPrice'] = $m[1];}else{$product['productPrice'] = '';}
			$d = preg_match('/<div lang=\''.$language.'\'>(.*?)<\/div>/ms',$product['productCurrency'],$m);	if(!$d){$d = preg_match('/<div lang=\'default\'>(.*?)<\/div>/ms',$product['productCurrency'],$m);}if($d){$products[$k]['productCurrency'] = $m[1];}else{$products[$k]['productCurrency'] = '';}
			$d = preg_match('/<div lang=\''.$language.'\'>(.*?)<\/div>/ms',$product['productVatUnit'],$m);	if(!$d){$d = preg_match('/<div lang=\'default\'>(.*?)<\/div>/ms',$product['productVatUnit'],$m);}if($d){$products[$k]['productVatUnit'] = $m[1];}else{$products[$k]['productVatUnit'] = '';}
			$d = preg_match('/<div lang=\''.$language.'\'>(.*?)<\/div>/ms',$product['productOldPrice'],$m);	if(!$d){$d = preg_match('/<div lang=\'default\'>(.*?)<\/div>/ms',$product['productOldPrice'],$m);}if($d){$products[$k]['productOldPrice'] = $m[1];}else{$products[$k]['productOldPrice'] = '';}
			if(intval($product['productCount']) > 0 && intval($product['productPrice']) > 0){$product['productPriceTotal'] = ($product['productCount']*$product['productPrice']);}else{$product['productPriceTotal'] = $product['productPrice'];}
			if(intval($product['productCount']) > 0 && intval($product['productWeight']) > 0){$product['productWeightTotal'] = ($product['productCount']*$product['productWeight']);}else{$product['productWeightTotal'] = $product['productWeight'];}
			if(isset($product['productCurrency']) && !empty($product['productCurrency'])){$product['productCurrencySymbol'] = $GLOBALS['CURRENCY'][$product['productCurrency']];}
			if(isset($GLOBALS['blueCommerce']['implicitVat']) && !$GLOBALS['blueCommerce']['implicitVat']){
				if($product['productVatUnit'] == 'percentage'){$product['productPrice'] += $product['productPrice']*($product['productVat']/100);}
				//FIXME: otros tipos de iva
			}
		}
		return $products;
	}

	$GLOBALS['PRODUCT_FLAG_RELATIVEIDMODE'] = false;
	function product_save($params,$db = false,$noencode = true){
		//FIXME: esto es una historia bastante caótica. Lo ideal sería que pudieran entrar
		// fragmentos de otros idiomas y se añadieran a los que ya existe sin sobreescribirlos
		$keys = ','.implode(',',array_keys($params)).',';
		$valid = array();
		$r = preg_match_all('/(productTitle|productPrice|productCurrency|productVat|productVatUnit|productDescription|productOldPrice|productColor)_(default|[a-z]{2}\-[a-z]{2})/',$keys,$validAdd);
		if($r){$valid = array_merge($valid,$validAdd[0]);}
		$r = preg_match_all('/(productCategory)_([0-9]+)/',$keys,$validAdd);
		if($r){$valid = array_merge($valid,$validAdd[0]);}
		/* Insertamos algunos válidos manualmente */
		$valid = array_merge($valid,array('productParentID','productRelativeID','productCategories','productWeight','productStatus','productStock'));
		$valid = array_fill_keys($valid,'');

		include_once('inc_databaseSqlite3.php');
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['product']);$shouldClose = true;}

		/* Debemos comprobar si se está actualizando o no un producto */
		$productUpdate = false;
		if(isset($params['id']) && intval($params['id']) > 0){
			$params['id'] = preg_replace('/[^0-9]*/','',$params['id']);
			$oldProduct = product_getByID($params['id'],$db,true);
			if($oldProduct === false){unset($params['id']);}
			else{$oldProduct['_id_'] = $oldProduct['id'];unset($params['id'],$oldProduct['id']);$productUpdate = true;}
		}

		/* RELATIVEIDMODE */
		if(isset($params['productRelativeID']) && $GLOBALS['PRODUCT_FLAG_RELATIVEIDMODE'] != 'ignore'){
			$oldProduct = product_getByRelativeID($params['productRelativeID'],$db,true);
			if($oldProduct !== false && $GLOBALS['PRODUCT_FLAG_RELATIVEIDMODE'] == 'skip'){$a = array('errorCode'=>(int)0,'data'=>$oldProduct);return ($noencode) ? $a : json_encode($a);}
		}

		foreach($params as $k=>$v){if(!isset($valid[$k])){unset($params[$k]);}}
		
		$product = array('productParentID'=>'','productRelativeID'=>'','productTitle'=>'','productTitleFixed'=>'','productCategories'=>'','productPrice'=>'','productCurrency'=>'','productVat'=>'','productVatUnit'=>'','productDescription'=>'','productShortText'=>'','productWeight'=>'','productColor'=>'','productDimensions'=>'','productStatus'=>'','productStock'=>'','productOldPrice'=>'');
		$fieldsThatCanBeEmpty = array('productParentID'=>'','productCategories'=>'','productDescription'=>'','productOldPrice'=>'');

		include_once('inc_strings.php');

		//FIXME: queda mucho trabajo que hacer con productParentID, primero debemos comprobar que existe,
		// luego que no es el mismo producto ... etc

		/* Agrupamos las categorías */
		$productCategories = ',';foreach($params as $k=>$v){if(substr($k,0,15) == 'productCategory'){$productCategories .= substr($k,16).',';unset($params[$k]);}}
		if($productCategories != ','){$params['productCategories'] = $productCategories;}

		/* Vamos a recopilar el número de keys válidas de producto que han entrado despues de la agrupación */
		$productKeys = array();
		/* Agrupamos los títulos */
		foreach($params as $k=>$v){
			$pos = strpos($k,'_');
			/* Si $k no tiene el separador '_' es una key directamente */
			if($pos === false){if(isset($product[$k])){$productKeys[] = $k;$product[$k] = $v;}continue;}

			$keyHeader = substr($k,0,$pos+1);
			$lang = substr($k,$pos+1);

			$pKey = substr($keyHeader,0,-1);
			$productKeys[] = $pKey;
			if(!isset($product[$pKey]) || $v == ''){continue;}

			$product[$pKey] .= '<div lang=\''.$lang.'\'>'.$v.'</div>';
			/* QUIRKS */
			switch($pKey){
				case 'productTitle':$product['productTitleFixed'] .= '<div lang=\''.$lang.'\'>'.strings_stringToURL($v).'</div>';
			}
		}
		
		$productKeys = array_fill_keys(array_unique($productKeys),'');
		/* Existen algunos campos que podemos dejar vacíos, el problema viene que normalmente, como se pueden 
		 * actualizar los productos por partes normalmente eliminamos todo lo que sobre para que no interfiera
		 * con el resto de campos, por lo que debemos tener muy controlados los campos que pueden estar vacíos
		 * y los que no, esta variable sirve para evitar el unset de una key que puede ir vacía dentro de un 
		 * producto */
		$fieldsThatCanBeEmpty = array_intersect_key($fieldsThatCanBeEmpty,$productKeys);

		if($productUpdate){
			/* Limpiamos los valores vacíos por si hay que hacer mezcla */
			foreach($product as $k=>$v){if(!isset($fieldsThatCanBeEmpty[$k]) && $v === ''){unset($product[$k]);}}
			$product = array_merge($oldProduct,$product);
		}
//print_r($params);
//print_r($product);
//exit;
		/* el parent id no puede ir vacío, como mínimo puede ir a cero */
		if(empty($product['productParentID'])){$product['productParentID'] = 0;}

		/* Si no se trata de una actualización de producto, inicializamos algunos campos necesarios,
		 * además hay una serie de campos mínimos que no pueden estár vacíos si es una nueva inserción */
		 
		if(!$productUpdate){
			$product = array_merge($product,array('productCreationDate'=>date('Y-m-d'),'productCreationTime'=>date('H:i:s'),'productVolatile'=>0,'productStock'=>((isset($product['productStock']) && !empty($product['productStock'])) ? $product['productStock'] : 0),'productStatus'=>((isset($product['productStatus']) && !empty($product['productStatus'])) ? $product['productStatus'] : 0),'productCount'=>((isset($product['productCount']) && !empty($product['productCount'])) ? $product['productCount'] : 0)));
			if(empty($product['productTitle'])){$a = array('errorCode'=>3,'errorDescription'=>'EMPTY_TITLE','file'=>__FILE__,'line'=>__LINE__);return ($noencode) ? $a : json_encode($a);}
			//FIXME: Si no se trata de un update hay
		}
		$r = sqlite3_insertIntoTable('products',$product,$db);
		if($r['OK'] === false){$a = array('errorCode'=>$r['errno'],'errorDescription'=>$r['error'],'file'=>__FILE__,'line'=>__LINE__);return ($noencode) ? $a : json_encode($a);}
		$row = product_getById($r['id'],$db,true);

		if($shouldClose){$db->close();}
		$a = array('errorCode'=>(int)0,'data'=>$row);
		return ($noencode) ? $a : json_encode($a);
	}

	//echo product_changeField('5,6','productStock',2);exit;
	function product_changeField($pID,$fieldName,$fieldText,$db = false,$noencode = false){
		if($pID === false || $fieldName === false || $fieldText === false){$a = array('errorCode'=>101,'errorDescription'=>'INVALID_DATA','file'=>__FILE__,'line'=>__LINE__);return ($noencode) ? $a : json_encode($a);}
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['product']);$shouldClose = true;}
		/* Comprobamos que el producto exista previamente */

		$products = product_getByIDs($pID,$db,true);
		if($products === false){if($shouldClose){$db->close();}$a = array('errorCode'=>1,'errorDescription'=>'PRODUCT_NOT_EXISTS','file'=>__FILE__,'line'=>__LINE__);return ($noencode) ? $a : json_encode($a);}
		if(!isset($products[key($products)][$fieldName])){if($shouldClose){$db->close();}$a = array('errorCode'=>2,'errorDescription'=>'FIELD_NOT_EXISTS','file'=>__FILE__,'line'=>__LINE__);return ($noencode) ? $a : json_encode($a);}

		$results = array();
		foreach($products as $k=>$product){
			$product = array('id'=>$product['id'],$fieldName=>$fieldText);
			$r = product_save($product,$db,true);
			if(isset($r['errorDescription'])){if($shouldClose){$db->close();}$a = array('errorCode'=>$r['errorCode'],'errorDescription'=>$r['errorDescription'],'file'=>__FILE__,'line'=>__LINE__);return ($noencode) ? $a : json_encode($a);}
			$results[] = $product['id'];
		}

		$products = product_getByIDs($results,$db,true);
		if($shouldClose){$db->close();}
		$a = array('errorCode'=>(int)0,'data'=>$products);
		return ($noencode) ? $product : json_encode($a);
	}

	//product_update('2',array('productPrice_es-es_2'=>1),false,true);
	function product_update($pIDs,$params,$db = false,$noencode = false){
		//FIXME: hacer un filtro de params como en product_save
		/* campos soportados -> productPrice */
		$pIDs = preg_replace('/[^0-9,]*/','',$pIDs);
		//FIXME: comprobar count
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['product']);$shouldClose = true;}

		//FIXME: debe soportar solo los idiomas de config
		$products = array_fill_keys(explode(',',$pIDs),array());
		foreach($params as $param=>$value){
			$param = explode('_',$param);
			if(count($param) < 3){list($field,$id) = $param;$products[$id][$field] = $value;continue;}
			list($field,$lang,$id) = $param;
			$products[$id][$field][$lang] = $value;
		}
		
		$langFields = array('productTitle','productDescription','productPrice');
		$langFields = array_fill_keys($langFields,'');

		include_once('inc_databaseSqlite3.php');
		$oldProducts = product_resolveIds($pIDs,$db,true);
		foreach($products as $id=>$product){
			$products[$id]['id'] = $id;
			if(!isset($oldProducts[$id])){continue;}
			foreach($product as $propertieName=>$v){
				if(!isset($langFields[$propertieName])){continue;}
				
				$tmp = product_helper_indexByLang($oldProducts[$id][$propertieName]);
				if(!isset($tmp['default'])){$tmp['default'] = current($tmp);}
				$tmp = array_merge($tmp,$v);
				unset($products[$id][$propertieName]);
				
				foreach($tmp as $lang=>$l){
					$products[$id][$propertieName.'_'.$lang] = $l;
				}
			}
		}

		foreach($products as $id=>$product){
			$r = product_save($product,$db,true);
			if(isset($r['errorDescription'])){
				//FIXME:
				print_r($r);
				exit;
			}
			//print_r($r);
		}

		if($shouldClose){$db->close();}
		return '{"errorCode":0}';
	}

	function product_helper_indexByLang($elem){
		$d = preg_match_all('/<div lang=\'([a-z]{2}\-[a-z]{2}|default)\'>(.*?)<\/div>/ms',$elem,$m);
		$elem = array();if($d){foreach($m[0] as $k=>$v){$elem[$m[1][$k]] = $m[2][$k];}}
		return $elem;
	}

	/* DEPRECATED */
	function product_resolveIds($pIDs,$db = false,$noencode = false,$indexBy = 'id'){
		if(!is_array($pIDs)){$pIDs = preg_replace('/[^0-9,]*/','',$pIDs);$pIDs = explode(',',$pIDs);}
		$pIDs = array_unique($pIDs);
		if(count($pIDs) < 1){return ($noencode) ? array() : '{"errorCode":0,"data":[]}';}
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['product'],SQLITE3_OPEN_READONLY);$shouldClose = true;}

		$whereClause = '(';foreach($pIDs as $i){$whereClause .= ' id = \''.$i.'\' OR ';}$whereClause = substr($whereClause,0,-4).')';
		$rows = product_getProducts($db,$noencode,false,$whereClause);

		if($shouldClose){$db->close();}
		if($noencode){return $rows;}
		return json_encode(array('errorCode'=>(int)0,'data'=>$rows));
	}

	//updates the maximum amount of sales by product ID for a user (FALLA NO RETORNA VALOR!!)
	function product_updateproductMaxById($pID,$productMax,$db = false){
		return product_updateproductMaxdByIds($pIDs,$productMax,$db);
	}

	//updates the maximum amount of sales by product IDs for a user 
	function product_updateproductMaxByIds($pIDs,$productMax,$db = false){
		if(!is_array($pID)){
			$pIDs = preg_replace('/[^0-9,]*/','',$pIDs);
			$pIDs = explode(',',$pIDs);
		}
		$pIDs = array_unique($pIDs);

		if($pIDs === ''){return json_encode(array('errorCode'=>1,'errorDescription'=>'PRODUCTID_INVALID','file'=>__FILE__,'line'=>__LINE__));}
	
		$whereClause = '(';
		foreach($pIDs as $i){
			$whereClause .= ' id = \''.$i.'\' OR ';
		}
		$whereClause = substr($whereClause,0,-4).')';

		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['product']);$shouldClose = true;}

		$query = 'UPDATE products SET productMAx=\''.$productMax.'\'  WHERE '.$whereClause;
		$r = $db->query($query);

		if(!$r){return json_encode(array('errorCode'=>$db->lastErrorCode(),'errorDescripcion'=>$db->lastErrorMsg(),'file'=>__FILE__,'line'=>__LINE__));}else{return json_encode(array('errorCode'=>(int)0));}
		if($shouldClose){$db->close();}

	}

	function product_getByID($pID,$db = false,$noencode = false){
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['product'],SQLITE3_OPEN_READONLY);$shouldClose = true;}
		$row = $db->querySingle('SELECT * FROM products WHERE id = \''.$pID.'\'',1);
		if($shouldClose){$db->close();}
		if($noencode && ($row === false || count($row) == 0)){return false;}
		if($noencode){return $row;}
		return json_encode(array('errorCode'=>(int)0,'data'=>$row));
	}
	function product_getByIDs($pID,$db = false,$noencode = false){
		/* Esta función obtiene productos por id sin renunciar a la velocidad del querySingle cuando sea posible */
		if(!is_array($pID)){$pID = explode(',',$pID);}
		if(count($pID) == 1){
			$pID = array_shift($pID);
			$row = product_getByID($pID,$db,$noencode);
			if($noencode && $row === false){return false;}
			if($noencode){return array($row['id']=>$row);}
			return json_encode(array('errorCode'=>(int)0,'data'=>array($row['id']=>$row)));
		}

		$whereClause = '';foreach($pID as $id){$whereClause .= '(id = \''.$id.'\') OR ';}
		if(strlen($whereClause)){$whereClause = substr($whereClause,0,-4);}
		$rows = product_getProducts($db,$noencode,false,$whereClause);
		if($noencode && !count($rows)){return false;}
		if($noencode){return $rows;}
		return json_encode(array('errorCode'=>(int)0,'data'=>$rows));
	}

	$GLOBALS['PRODUCT_FLAG_RESOLVEPARENTS'] = true;
	function product_getProducts($db = false,$noencode = false,$limit = false,$whereClause = false,$order = 'id DESC'){
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['product'],SQLITE3_OPEN_READONLY);$shouldClose = true;}
		$query = 'SELECT '.$GLOBALS['API_products']['selectString'].' FROM products '.(($whereClause !== false) ? 'WHERE '.$whereClause : '').' ORDER BY '.$db->escapeString($order);
		if($limit !== false){$limit = preg_replace('/[^0-9,]*/','',$limit);$query .= ' LIMIT '.$limit;}
		//echo $query.' ';
		$r = $db->query($query);
		$parentIDs = array();
		$rows = array();if($r){while($row = $r->fetchArray(SQLITE3_ASSOC)){$rows[$row['id']] = $row;if(isset($row['productParentID'])){$parentIDs[] = $row['productParentID'];}}}

		/* Rellenamos los campos con los datos de los padres */
		if(count($rows) > 0 && count($parentIDs) > 0 && isset($GLOBALS['PRODUCT_FLAG_RESOLVEPARENTS']) && $GLOBALS['PRODUCT_FLAG_RESOLVEPARENTS']){
			$parentIDs = array_diff(array_unique($parentIDs),array(0));
			$parentClause = '(id = \''.implode('\') OR (id = \'',$parentIDs).'\')';
			$oldResolveParents = $GLOBALS['PRODUCT_FLAG_RESOLVEPARENTS'];
			$GLOBALS['PRODUCT_FLAG_RESOLVEPARENTS'] = false;
			$parents = product_getProducts($db,true,false,$parentClause);
			$GLOBALS['PRODUCT_FLAG_RESOLVEPARENTS'] = $oldResolveParents;
			$fields = array_fill_keys(array_keys($rows[key($rows)]),'');

			if($parents && count($parents) > 0){foreach($rows as $id=>&$row){
				if(!isset($parents[$row['productParentID']])){continue;}
				$row = array_merge($parents[$row['productParentID']],array_diff_assoc($row,$fields));
			}}
		}


		if($shouldClose){$db->close();}
		if($noencode){return $rows;}
		return json_encode(array('errorCode'=>(int)0,'data'=>$rows));
	}

	function product_getByRelativeID($relID,$db = false,$noencode = false){
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['product']);$shouldClose = true;}
		$row = @$db->querySingle('SELECT * FROM products WHERE productRelativeID = \''.$db->escapeString($relID).'\';',1);
		if($shouldClose){$db->close();}
		if($noencode && ($row === false || count($row) == 0)){return false;}
		if($noencode){return $row;}
		return json_encode(array('errorCode'=>(int)0,'data'=>$row));
	}

	function product_getByCategoryIDs($cIDs,$db = false,$noencode = false,$limit = false){
		if(is_array($cIDs)){$cIDs = implode(',', $cIDs);}
		$cIDs = preg_replace('/[^0-9,]*/','',$cIDs);
		$cIDs = array_diff(explode(',',$cIDs),array(''));
		$whereClause = '(';foreach($cIDs as $cID){$whereClause .= 'productCategories LIKE \'%,'.$cID.',%\' OR ';}$whereClause = substr($whereClause,0,-4).')';
		return product_getProducts($db,$noencode,$limit,$whereClause,'id DESC');
	}

	function product_getByCategoryIDsNew($cIDs,$db = false,$noencode = false,$limit = false,$whereClause = false){
		if(is_array($cIDs)){$cIDs = implode(',', $cIDs);}
		$cIDs = preg_replace('/[^0-9,]*/','',$cIDs);
		$cIDs = array_diff(explode(',',$cIDs),array(''));
		if($whereClause !== false){$whereClause .= ' and ';}else{$whereClause = '';}
		$whereClause .= '(';foreach($cIDs as $cID){$whereClause .= 'productCategories LIKE \'%,'.$cID.',%\' OR ';}$whereClause = substr($whereClause,0,-4);$whereClause .= ')';
		return product_getProducts($db,$noencode,$limit,$whereClause,'id DESC');
	}

	function product_getSiblings($id,$db = false,$noencode = false){
//FIXME: comprobar que tenga productParentID una vez obtenido el elemento
		if(!isset($id['productParentID'])){$id = product_getById($id,$db,$noencode);}
		return product_getChildsFromParentID($id['productParentID'],$db,$noencode);
	}
	function product_getChildsFromParentID($id,$db = false,$noencode = false){return product_getProducts($db,$noencode,false,'(productParentID = '.$id.')','productWeight ASC,id DESC');}

	function product_stock_modifyFromArrayIndexed($pIDs = array(),$db = false,$noencode = false){
		$strangeIDs = array();
		foreach($pIDs as $id=>$stock){
			if(!is_string($stock)){$stock = (($stock > -1) ? '+' : '').$stock;}elseif($stock[0] != '+' && $stock[0] != '-'){$stock = '+'.$stock;}
			$volatile = (($stock[0] == '+') ? '-' : '+').substr($stock,1);
			$query = 'UPDATE products SET productStock = productStock'.$stock.',productVolatile = productVolatile'.$volatile.' WHERE id = \''.$id.'\';';
			$db->exec($query);
			if($db->changes() < 1){$strangeIDs[] = $id;}
		}

		$a = array('errorCode'=>(int)0,'data'=>$strangeIDs);
		return ($noencode) ? $a : json_encode($a);
	}

	function product_image_append($pID,$base64Image,$noencode = false){
		//FIXME: variables de optimización como $novalidate y evitamos la validación del producto
		$product = product_getById($pID,false,true);
		if($product === false){$a = array('errorCode'=>1,'errorDescription'=>'PRODUCT_NOT_EXISTS','file'=>__FILE__,'line'=>__LINE__);return $noencode ? $a : json_encode($a);}

		//FIXME: quizá esto (el orig) va a la librería de thumbs
		$productGalleryPath = '../clients/'.$GLOBALS['CLIENT'].'/db/products/'.$product['id'].'/gallery/orig/';
		if(!file_exists($productGalleryPath)){mkdir($productGalleryPath,0777,1);if(!file_exists($productGalleryPath)){$a = array('errorCode'=>1,'errorDescription'=>'PERMISSION_DENIED','file'=>__FILE__,'line'=>__LINE__);return $noencode ? $a : json_encode($a);}}
		if(substr($base64Image,0,10) == 'data:image'){
			$comma = strpos($base64Image,',');
			if($comma !== false){
				$imgData = substr($base64Image,$comma);
				$imgType = substr($base64Image,11,$comma-7-11);
				$tmpName = '/tmp/'.microtime(1).'.'.$imgType;
				$fp = fopen($tmpName,'w');fwrite($fp,base64_decode(str_replace(' ','+',$imgData)));fclose($fp);
				chmod($tmpName,0755);

				$sum = md5_file($tmpName);
				$imgProp = getimagesize($tmpName);
				if($imgProp === false){unlink($tmpName);return json_encode(array('errorCode'=>1,'errorDescription'=>'NOT_AN_IMAGE','file'=>__FILE__,'line'=>__LINE__));}
				$imgName = $sum.'.'.$imgType;
				rename($tmpName,$productGalleryPath.$imgName);
				product_image_thumbs($pID,$imgName,true);
			}
		}
		if($noencode){return $imgName;}
		return '{"errorCode":0,"data":"'.$imgName.'"}';
	}
	
	function product_image_appendFromUrl($pID,$url,$noencode = false){
		$product = product_getById($pID,false,true);
		//if($product === false){$a = array('errorCode'=>1,'errorDescription'=>'PRODUCT_NOT_EXISTS','file'=>__FILE__,'line'=>__LINE__);return $noencode ? $a : json_encode($a);}
		
		$productGalleryPath = '../clients/'.$GLOBALS['CLIENT'].'/db/products/'.$product['id'].'/gallery/orig/';
		if(!file_exists($productGalleryPath)){mkdir($productGalleryPath,0777,1);}
		
		//get file type, creamos el fichero después de comprobar imagen
		if($im = @getimagesize($url)){
			switch($im['mime']){
				case 'image/gif':$imgType = 'gif';break;
				case 'image/jpeg':$imgType = 'jpg';break;
				case 'image/png':$imgType = 'png';break;
				default: return false;
			}
			
			$tmpName = '/tmp/'.microtime(1).'.'.$imgType;
			file_put_contents($tmpName,file_get_contents($url));
			chmod($tmpName,0755);
			
			$sum = md5_file($tmpName);
			
			$imgName = $sum.'.'.$imgType;
			rename($tmpName,$productGalleryPath.$imgName);
			product_image_thumbs($pID,$imgName,true);
			if($noencode){return $imgName;}
			return '{"errorCode":0,"data":"'.$imgName.'"}';
		}
		return false;
	}

	function product_image_savePortraits($params = array()){
		$products = array();
		foreach($params as $param=>$value){
			$param = explode('_',$param);
			if(count($param) < 2){continue;}
			list($dummy,$id) = $param;
			$products[$id] = $value;
		}

		foreach($products as $id=>$value){
			$imageName = product_image_append($id,$value,true);
			product_image_toPortrait($id,$imageName);
		}

		return '{"errorCode":0}';
	}

	function product_image_toPortrait($pID,$imgName){
		$product = product_getById($pID,false,true);
		if(count($product) < 1){return json_encode(array('errorCode'=>1,'errorDescription'=>'PRODUCT_NOT_EXISTS','file'=>__FILE__,'line'=>__LINE__));}

		$productGalleryPath = '../clients/'.$GLOBALS['CLIENT'].'/db/products/'.$product['id'].'/gallery/orig/';
		$imagePath = $productGalleryPath.$imgName;
		if(!file_exists($imagePath)){return json_encode(array('errorCode'=>2,'errorDescription'=>'IMAGE_NOT_EXISTS','file'=>__FILE__,'line'=>__LINE__));}

		$configFile = '../clients/'.$GLOBALS['CLIENT'].'/db/config.php';
		if(!file_exists($imagePath)){return json_encode(array('errorCode'=>3,'errorDescription'=>'CONFIG_FILE_ERROR','file'=>__FILE__,'line'=>__LINE__));}
		include_once($configFile);

		$productGalleryPathBase = dirname($productGalleryPath).'/';
		$productGalleryPathPortrait = $productGalleryPathBase.'/portrait/';
		if(!file_exists($productGalleryPathPortrait)){@mkdir($productGalleryPathPortrait,0777,1);if(!file_exists($productGalleryPathPortrait)){
			return json_encode(array('errorCode'=>4,'errorDescription'=>'PERMISSION_DENIED','file'=>__FILE__,'line'=>__LINE__));
		}}
		$pngFile = preg_replace('/(\.png|\.gif|\.jpg)$/','.jpeg',$imgName);

		$sizes = array_unique(array_merge($GLOBALS['blueCommerce']['imageSizes'],array('200')));
		foreach($sizes as $size){
			$dest = $productGalleryPathPortrait.$size;
			if(!file_exists($dest)){mkdir($dest,0777,1);if(!file_exists($dest)){continue;}}
			$source = $productGalleryPathBase.$size.'/'.$pngFile;if(!file_exists($source)){continue;}
			copy($source,$dest.'/index.png');
		}

		$dest = $productGalleryPathPortrait.'orig';
		if(!file_exists($dest)){mkdir($dest,0777,1);}
		copy($imagePath,$dest.'/index.png');
		return '{"errorCode":0}';
	}

	function product_image_remove($pID,$imgName){
		$product = product_getById($pID,false,true);
		if($product === false){return json_encode(array('errorCode'=>1,'errorDescription'=>'PRODUCT_NOT_EXISTS','file'=>__FILE__,'line'=>__LINE__));}

		$productGalleryPathBase = '../clients/'.$GLOBALS['CLIENT'].'/db/products/'.$product['id'].'/gallery/';
		$productGalleryPath = '../clients/'.$GLOBALS['CLIENT'].'/db/products/'.$product['id'].'/gallery/orig/';
		$imagePath = $productGalleryPath.$imgName;
		if(!file_exists($imagePath)){return json_encode(array('errorCode'=>2,'errorDescription'=>'IMAGE_NOT_EXISTS','file'=>__FILE__,'line'=>__LINE__));}

		$destName = preg_replace('/(\.png|\.gif|\.jpg)$/','.jpeg',$imgName);
//FIXME: sería mejor recorrer todas las carpetas
		$sizes = array_unique(array_merge($GLOBALS['blueCommerce']['imageSizes'],array('200')));
		foreach($sizes as $size){
			$tmpFile = $productGalleryPathBase.$size.'/'.$destName;
			if(file_exists($tmpFile)){unlink($tmpFile);}
		}

		unlink($imagePath);
		return '{"errorCode":0}';
	}

	//product_image_thumbs(1,'960fc08649369a5401406db4100658ba.png');
	function product_image_thumbs($pID,$imgName,$novalidate = false){
		$product = array('id'=>$pID);
		if(!$novalidate){
			$product = product_getById($pID,false,true);
			if(count($product) < 1){return json_encode(array('errorCode'=>1,'errorDescription'=>'PRODUCT_NOT_EXISTS','file'=>__FILE__,'line'=>__LINE__));}
		}

		$productGalleryPath = '../clients/'.$GLOBALS['CLIENT'].'/db/products/'.$product['id'].'/gallery/orig/';
		$imagePath = $productGalleryPath.$imgName;
		if(!file_exists($imagePath)){return json_encode(array('errorCode'=>2,'errorDescription'=>'IMAGE_NOT_EXISTS','file'=>__FILE__,'line'=>__LINE__));}

		$configFile = '../clients/'.$GLOBALS['CLIENT'].'/db/config.php';
		if(!file_exists($configFile)){return json_encode(array('errorCode'=>3,'errorDescription'=>'CONFIG_FILE_ERROR','file'=>__FILE__,'line'=>__LINE__));}
		include_once($configFile);

		include_once('inc_images.php');
		$destPool = dirname($productGalleryPath).'/';
		$sizes = array_unique(array_merge($GLOBALS['blueCommerce']['imageSizes'],array('200')));
		product_image_helper_createThumbnail($imagePath,$destPool,$sizes);

		return '{"errorCode":0}';
	}

	function product_image_helper_createThumbnail($imagePath,$destPool,$sizes = false,$overWrite = false){
		include_once('inc_images.php');
		$res = image_getResource($imagePath);
		if($res === false){
		//FIXME: TODO
		}

		if(substr($destPool,-1) !== '/'){$destPool .= '/';}
		//FIXME: comprobar file_exists de $destPool
		foreach($sizes as $k=>$size){$a = $destPool.$size;if(!file_exists($a)){mkdir($a,0777,1);chmod($a,0777);}if(!file_exists($a)){return false;}}

		$pathInfo = pathinfo($imagePath);
		$imageName = $pathInfo['filename'].'.jpeg';
		
		foreach($sizes as $k=>$size){
			$size .= '';
			$destPath = $destPool.$size.'/'.$imageName;
			if($overWrite === false && file_exists($destPath)){continue;}
			if(!is_numeric($size[0])){unset($sizes[$k]);continue;}
			if(strpos($size,'x') !== false){product_image_helper_thumb($res,$destPath,$size);continue;}
			$r = product_image_helper_square($res,$destPath,$size);
		}

		imagedestroy($res);
		return true;
	}
	function product_image_helper_square($res,$dPath,$size){$adjust = (isset($GLOBALS['blueCommerce']['imageAdjust'])) ? $GLOBALS['blueCommerce']['imageAdjust'] : 'min';include_once('inc_images.php');if(is_dir($dPath)){return false;}$res = imageResource_resize($res,$size,$size,$adjust);$res = imageResource_crop($res,$size,$size);if(!preg_match('/\.jpeg$/',$dPath)){$dPath = preg_replace('/\.[a-zA-Z]+$/','.jpeg',$dPath);}imagejpeg($res,$dPath,100);imagedestroy($res);return true;}
	function product_image_helper_thumb($res,$dPath,$size){if(is_dir($dPath)){return false;}list($w,$h) = explode('x',$size);$adjust = (isset($GLOBALS['blueCommerce']['imageAdjust'])) ? $GLOBALS['blueCommerce']['imageAdjust'] : 'min';$res = imageResource_resize($res,$w,$h,$adjust);if($w != 0 && $h != 0){$res = imageResource_crop($res,$w,$h);}if(!preg_match('/\.jpeg$/',$dPath)){$dPath = preg_replace('/\.[a-zA-Z]+$/','.jpeg',$dPath);}imagejpeg($res,$dPath,100);imagedestroy($res);return true;}


	//product_image_addSize(72);
	function product_image_addSize($newSize){
		$newSize = preg_replace('/[^a-z0-9]*/','',$newSize);
		$configFile = '../clients/'.$GLOBALS['CLIENT'].'/db/config.php';
		if(!file_exists($configFile)){return json_encode(array('errorCode'=>3,'errorDescription'=>'CONFIG_FILE_ERROR','file'=>__FILE__,'line'=>__LINE__));}
		include_once($configFile);

		$GLOBALS['blueCommerce']['imageSizes'][] = $newSize;
		$raw = file_get_contents($configFile);
		$newLine = "\t".'$GLOBALS[\'blueCommerce\'][\'imageSizes\'] = array('.str_replace(array(':','"'),array('=>','\''),substr(json_encode($GLOBALS['blueCommerce']['imageSizes']),1,-1)).');'."\n";
		$raw = preg_replace('/\t\$GLOBALS\[\'blueCommerce\'\]\[\'imageSizes\'\] = array\([^\)]+\);\n/',$newLine,$raw);
		$fp = fopen($configFile,'w');fwrite($fp,$raw);fclose($fp);

		return '{"errorCode":0}';
	}

	function product_image_getAll($pID,$noencode = false){
		$productGalleryPath = '../clients/'.$GLOBALS['CLIENT'].'/db/products/'.$pID.'/gallery/orig/';
		$files = array();if(file_exists($productGalleryPath)){if($handle = opendir($productGalleryPath)){while(false !== ($file = readdir($handle))){if(($file[0]!='.')){$files[] = array('imageFile'=>$file);}}closedir($handle);}}
		if($noencode){return $files;}
		return json_encode(array('errorCode'=>(int)0,'data'=>$files));
	}
	
	function product_search($searchString,$db = false,$noencode = false,$letterLimit = 3,$whereClause = false){
		//FIXME SQL injection 
		$searchString = preg_replace('/[\']*/','',$searchString);
		if($letterLimit == false){$letterLimit = 3;}
		$searchArray = array_unique(explode(' ',$searchString));
		$o = function($a,$b){if ($a['searchRate'] == $b['searchRate']){return 0;}return ($a['searchRate'] > $b['searchRate']) ? -1 : 1;};

		$searchQuery = '(';
		foreach($searchArray as $element){if(strlen($element) > $letterLimit){$searchQuery .= '(productTitle like \'%'.$element.'%\' OR productDescription like \'%'.$element.'%\') OR ';$cleanArray[] = $element;}}
		$totalStars = count($cleanArray);$searchQuery = substr($searchQuery,0,-4);$searchQuery .= ')';
		if($whereClause !== false){$searchQuery .= ' AND ('.$whereClause.')';}

		$searchProd = array();
		$products = product_getProducts($db,true,false,$searchQuery);
		foreach($products as $product){
			$i = $totalStars;
			$product['searchRate'] = 0;
			foreach($cleanArray as $searchItem){
				$ret = strpos(strtolower($product['productTitle']),strtolower($searchItem));if($ret !== false){$product['searchRate'] += $i;}
				$ret = strpos(strtolower($product['productDescription']),strtolower($searchItem));if($ret !== false){$product['searchRate'] += $i;}
				$i--;
			}
			$searchProd[] = $product;
		}

		if(count($searchProd) > 0){usort($searchProd,$o);}

		//FIXME: solo si la cadena es un solo elemento
		if(count($searchArray) == 1){
			$whereClause = '(productRelativeID = \''.$searchString.'\')';
			$relativeProducts = product_getProducts($db,true,false,$whereClause);
			$searchProd = array_merge($relativeProducts,$searchProd);
		}

		//FIXME Tener en cuenta noencode
		return $searchProd;		
	}
?>
