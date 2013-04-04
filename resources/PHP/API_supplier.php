<?php
	if(!isset($GLOBALS['CLIENT'])){
		if($_SERVER['SERVER_NAME'] !== 'localhost'){$GLOBALS['CLIENT'] = $_SERVER['SERVER_NAME'];}
		else{$r = preg_match('/([a-zA-Z0-9\.]+)\/g\/PHP\/([a-zA-Z0-9_]+)\.php$/',$_SERVER['REQUEST_URI'],$m);if(!$r){exit;}$GLOBALS['CLIENT'] = $m[1];}
	}

	//declare the database name 
	$GLOBALS['db']['product'] = '../clients/'.$GLOBALS['CLIENT'].'/db/blueCommerce.db';
	$GLOBALS['COUNTRIES'] = array('ES'=>'ES','EN'=>'EN','PT'=>'PT','AR'=>'ARG','USA'=>'USA','EU'=>'EU'); // COUNTRIES and regions

	//declare the table structure
	$GLOBALS['tables']['suppliers'] = array(
					'_id_'=>'INTEGER AUTOINCREMENT',
					'supplierName'=>'TEXT',
					'supplierContact'=>'TEXT',
					'supplierPhone'=>'TEXT',
					'supplierEmail'=>'TEXT',
					'supplierAdress'=>'TEXT',
					'supplierCity'=>'TEXT',
					'supplierProvince'=>'TEXT',
					'supplierCountry'=>'TEXT',
					'supplierZip'=>'TEXT',
					'supplierFiscalAdress'=>'TEXT',
					'supplierFiscalCity'=>'TEXT',
					'supplierFiscalProvince'=>'TEXT',
					'supplierFiscalCountry'=>'TEXT',
					'supplierFiscalZip'=>'TEXT',
					'supplierTaxIdentificationCode'=>'TEXT',
					'supplierStatus'=>'TEXT'
				);
	
	/*****************END OFF CONFIGURATION**************************/

	//values ​​inserted/update if the table does not exist, create it
	function supplier_insert($supplier,$db = false){
		$valid = array('id'=>0,'supplierName'=>0,'supplierContact'=>0,'supplierPhone'=>0,'supplierEmail'=>0,'supplierAdress'=>0,'supplierCity'=>0,'supplierProvince'=>0,'supplierCountry'=>0,'supplierZip'=>0,'supplierFiscalAdress'=>0,'supplierFiscalCity'=>0,'supplierFiscalProvince'=>0,'supplierFiscalCountry'=>0,'supplierFiscalZip'=>0,'supplierTaxIdentificationCode'=>0,'supplierStatus'=>0);
		foreach($supplier as $k=>$v){if(!isset($valid[$k])){unset($supplier[$k]);}}

		include_once('inc_strings.php');
		//

		if(isset($supplier['id'])){
			$supplier['id'] = preg_replace('/[^0-9]*/','',$supplier['id']);
			if(!empty($supplier['id'])){$supplier['_id_'] = $supplier['id'];unset($supplier['id']);}
			else{unset($supplier['id']);}
		}

		if(isset($supplier['supplierName'])){
			$supplier['supplierName'] = preg_replace('/[^a-zA-Z0-9 áéíóúñÁÉÍÓÚÑ]*/','',$supplier['supplierName']);
			if(empty($supplier['supplierName'])){unset($supplier['supplierName']);}
			$supplier['supplierName'] = strings_stringToURL($supplier['supplierName']);
		}

		if(isset($supplier['supplierContact'])){
			//$supplier['supplierContact'] = preg_replace('/[^0-9]*/','',$supplier['supplierContact']);
			if($supplier['supplierContact'] === ''){unset($supplier['supplierContact']);}
		}

		if(isset($supplier['supplierEmail'])){
			//$supplier['supplierEmail'] = preg_replace('/[^a-zA-Z0-9 áéíóúñÁÉÍÓÚÑ]*/','',$supplier['supplierEmail']);
			if(empty($supplier['supplierEmail'])){unset($supplier['supplierEmail']);}
			//$supplier['supplierEmail'] = strings_stringToURL($supplier['supplierEmail']);
		}

		if(isset($supplier['supplierPhone'])){
			$supplier['supplierPhone'] = preg_replace('/[^a-zA-Z0-9 áéíóúñÁÉÍÓÚÑ]*/','',$supplier['supplierPhone']);
			if(empty($supplier['supplierPhone'])){unset($supplier['supplierPhone']);}
			$supplier['supplierPhone'] = strings_stringToURL($supplier['supplierPhone']);
		}

		if(isset($supplier['supplierAdress'])){
			if(empty($supplier['supplierAdress'])){unset($supplier['supplierAdress']);}
		}

		if(isset($supplier['supplierCity'])){
			if(empty($supplier['supplierCity'])){unset($supplier['supplierCity']);}
		}

		if(isset($supplier['supplierProvince'])){
			if(empty($supplier['supplierProvince'])){unset($supplier['supplierProvince']);}
		}

		if(isset($supplier['supplierCountry'])){
			if(!in_array($supplier['supplierCountry'],$GLOBALS['COUNTRIES'])){unset($supplier['supplierCountry']);}
		}

		if(isset($supplier['supplierZip'])){
			if(empty($supplier['supplierZip'])){unset($supplier['supplierZip']);}
		}

		if(isset($supplier['supplierFiscalAdress'])){
			if(empty($supplier['supplierFiscalAdress'])){unset($supplier['supplierFiscalAdress']);}
		}
		
		if(isset($supplier['supplierFiscalCity'])){
			if(empty($supplier['supplierFiscalCity'])){unset($supplier['supplierFiscalCity']);}
		}

		if(isset($supplier['supplierFiscalProvince'])){
			if(empty($supplier['supplierFiscalProvince'])){unset($supplier['supplierFiscalProvince']);}
		}

		if(isset($supplier['supplierFiscalCountry'])){
			if(!in_array($supplier['supplierFiscalCountry'],$GLOBALS['COUNTRIES'])){unset($supplier['supplierFiscalCountry']);}
		}

		if(isset($supplier['supplierFiscalZip'])){
			if(empty($supplier['supplierFiscalZip'])){unset($supplier['supplierFiscalZip']);}
		}

		if(isset($supplier['supplierTaxIdentificationCode'])){
			if(empty($supplier['supplierTaxIdentificationCode'])){unset($supplier['supplierTaxIdentificationCode']);}
		}

		if(isset($supplier['supplierStatus'])){
			$supplier['supplierStatus'] = preg_replace('/[^0-9]*/','',$supplier['supplierStatus']);
			if(empty($supplier['supplierStatus'])){unset($supplier['supplierStatus']);}
		}

		include_once('inc_databaseSqlite3.php');
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['product']);$shouldClose = true;}
		
		$r = sqlite3_insertIntoTable('suppliers',$supplier,$db);
		if($r['OK'] === false){return json_encode(array('errorCode'=>$r['errno'],'errorDescription'=>$r['error'],'file'=>__FILE__,'line'=>__LINE__));}

		$row = json_decode(supplier_getById($r['id'],$db),1);
		$row = $row['data'];

		if($shouldClose){$db->close();}
		return json_encode(array('errorCode'=>(int)0,'data'=>$row));
	}
	//updated by supplier by Product ID
	function supplier_updateStatusdById($sID,$supplierStatus = 2,$db = false){
		$sID = preg_replace('/[^0-9]*/','',$sID);
		if($sID === ''){return json_encode(array('errorCode'=>1,'errorDescription'=>'PRODUCTID_INVALID','file'=>__FILE__,'line'=>__LINE__));}
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['product']);$shouldClose = true;}
		$query = 'UPDATE suppliers SET supplierStatus=\''.$supplierStatus.'\' WHERE id = \''.$sID.'\'';
		$r = $db->query($query);
		if(!$r){return json_encode(array('errorCode'=>$db->lastErrorCode(),'errorDescripcion'=>$db->lastErrorMsg(),'file'=>__FILE__,'line'=>__LINE__));}else{return json_encode(array('errorCode'=>(int)0));}
		if($shouldClose){$db->close();}
	}

	//search by supplier ID (FALLA NO RETORNA VALOR!!)
	function supplier_getById($sID,$db = false){return supplier_resolveIds(array($sID),$db);}

	//search by supplier IDs
	function supplier_resolveIds($sIDs,$db = false){

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

		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['product']);$shouldClose = true;}

		$query = 'SELECT * FROM suppliers WHERE '.$whereClause;

		$r = $db->query($query);
		$rows = array();
		if($r){while($row = $r->fetchArray(SQLITE3_ASSOC)){$rows[] = $row;}}

		if($shouldClose){$db->close();}
		return json_encode(array('errorCode'=>(int)0,'data'=>$rows));
	}

	//
	function supplier_list($db = false){
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['product']);$shouldClose = true;}

		$query = 'SELECT supplierName,id,supplierStatus FROM suppliers ORDER BY supplierName LIMIT 0,10';

		$r = $db->query($query);
		$rows = array();
		if($r){while($row = $r->fetchArray(SQLITE3_ASSOC)){$rows[] = $row;}}

		if($shouldClose){$db->close();}
		return json_encode(array('errorCode'=>(int)0,'data'=>$rows));
	}

	/*/
	function clear_supplier(){
		//foreach($GLOBALS['tables']['products'] AS $key=>$value){$clear[$$key]='';}
		//$clear[$id]=''
		//
		//$status=json_decode(status_list());
		//
		//$cmbStatus='<select tabindex="12" name="productStatus" id="productStatus">';
		//foreach($status->data AS $values){$cmbStatus.='<option value="'.$values->id.'">'.$values->status.'</option>';}
		//$cmbStatus.='</select>';

		//$cmbLanguages='<select tabindex="3" name="productLanguage" id="productLanguage">';
		//foreach($GLOBALS['LANGUAGES'] AS $language){$cmbLanguages.='<option value="'.$language.'">'.$language.'</option>';}
		//$cmbLanguages.='</select>';
		//
		//$cmbCurrency='<select tabindex="9" name="productCurrency" id="productCurrency">';
		//foreach($GLOBALS['CURRENCY'] AS $name=>$simbol){$cmbCurrency.='<option value="'.$name.'">'.$name.'</option>';}
		//$cmbCurrency.='</select>';
		//
		//$cmbCountries='<select tabindex="9" name="productCountry" id="productCountry">';
		//foreach($GLOBALS['COUNTRIES'] AS $cod=>$name){$cmbCountries.='<option value="'.$cod.'">'.$name.'</option>';}
		//$cmbCountries.='</select>';
		//return $clear;
	}*/
?>
