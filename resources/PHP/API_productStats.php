<?php
	$GLOBALS['db']['productStats'] = '../clients/'.$GLOBALS['CLIENT'].'/db/productStats.db';

	$GLOBALS['tables']['productsRelations'] = array('_lowid_'=>'INTEGER','_highid_'=>'INTEGER','collitions'=>'INTEGER');
	$GLOBALS['tables']['productsSellsTotal'] = array('_id_'=>'INTEGER','total'=>'INTEGER');
	$GLOBALS['tables']['productsSells'] = array('_id_'=>'INTEGER AUTOINCREMENT','productID'=>'INTEGER','count'=>'INTEGER','date'=>'TEXT','time'=>'TEXT');

/*$q = new sqlite3('../clients/'.$GLOBALS['CLIENT'].'/db/blueCommerce.db');
$cart = $q->querySingle('SELECT * FROM cartList',1);
$cart['cartProducts'] .= '2:1,';
productStats_processCart($cart);*/
	function productStats_processCart($cart,$db = false){
		$products = $cart['shippingProducts'];
		$r = preg_match_all('/([0-9]+):([0-9]+)/',$products,$m);
		$products = array();foreach($m[0] as $k=>$v){$products[$m[1][$k]] = $m[2][$k];}

		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['productStats']);$shouldClose = true;}
		include_once('inc_databaseSqlite3.php');

		$date = $cart['shippingOrderDate'];
		$time = $cart['shippingOrderTime'];

		$done = array();
		foreach($products as $k=>$c){
			$totalSells = productStats_getTotalSells($k,$db,true);
			$totalSells = ($totalSells) ? $totalSells['total'] : 0;
			$totalSells += $c;

			$row = array('productID'=>$k,'count'=>$c,'date'=>$date,'time'=>$time);
			$r = sqlite3_insertIntoTable('productsSells',$row,$db);
			if($r['OK'] === false){$a = array('errorCode'=>$r['errno'],'errorDescription'=>$r['error'],'file'=>__FILE__,'line'=>__LINE__);return ($noencode) ? $a : json_encode($a);}

			$row = array('_id_'=>$k,'total'=>$totalSells);
			$r = sqlite3_insertIntoTable('productsSellsTotal',$row,$db);
			if($r['OK'] === false){$a = array('errorCode'=>$r['errno'],'errorDescription'=>$r['error'],'file'=>__FILE__,'line'=>__LINE__);return ($noencode) ? $a : json_encode($a);}

			foreach($products as $l=>$m){
				if($l == $k){continue;}
				$ids = array($l,$k);sort($ids);list($lowID,$highID) = $ids;
				if(isset($done[$lowID.'_'.$highID])){continue;}

				$rel = productStats_getRelation($lowID,$highID,$db,true);
				$collitions = ($rel) ? $rel['collitions']+1 : 1;

				$row = array('_lowid_'=>$lowID,'_highID_'=>$highID,'collitions'=>$collitions);
				$r = sqlite3_insertIntoTable('productsRelations',$row,$db);
				if($r['OK'] === false){$a = array('errorCode'=>$r['errno'],'errorDescription'=>$r['error'],'file'=>__FILE__,'line'=>__LINE__);return ($noencode) ? $a : json_encode($a);}
				$done[$lowID.'_'.$highID] = true;
			}
		}

		if($shouldClose){$db->close();}
		return '{"errorCode":0}';
	}
	
	function productStats_getTopSellers($db = false,$noencode = false,$whereClause = false,$limit = false,$order = 'id DESC'){
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['productStats'],SQLITE3_OPEN_READONLY);$shouldClose = true;}
		$query = 'SELECT * FROM productsSellsTotal '.(($whereClause !== false) ? 'WHERE '.$whereClause : '').' ORDER BY '.$db->escapeString($order);
		if($limit !== false){$limit = preg_replace('/[^0-9,]*/','',$limit);$query .= ' LIMIT '.$limit;}
		//echo $query.' ';
		$r = $db->query($query);
		$parentIDs = array();
		$rows = array();if($r){while($row = $r->fetchArray(SQLITE3_ASSOC)){$rows[$row['id']] = $row;}}

		if($shouldClose){$db->close();}
		if($noencode){return $rows;}
		return json_encode(array('errorCode'=>(int)0,'data'=>$rows));
	}

	function productStats_getTotalSells($pID,$db = false,$noencode = false){
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['productStats'],SQLITE3_OPEN_READONLY);$shouldClose = true;}
		$row = @$db->querySingle('SELECT * FROM productsSellsTotal WHERE id = \''.$pID.'\'',1);
		if($shouldClose){$db->close();}
		if($noencode && ($row === false || count($row) == 0)){return false;}
		if($noencode){return $row;}
		return json_encode(array('errorCode'=>(int)0,'data'=>$row));
	}

	function productStats_getRelation($lowID,$highID,$db = false,$noencode = false){
		$ids = array($lowID,$highID);sort($ids);list($lowID,$highID) = $ids;
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['productStats'],SQLITE3_OPEN_READONLY);$shouldClose = true;}
		$row = @$db->querySingle('SELECT * FROM productsRelations WHERE lowID = \''.$lowID.'\' AND highID = \''.$highID.'\'',1);
		if($shouldClose){$db->close();}
		if($noencode && ($row === false || count($row) == 0)){return false;}
		if($noencode){return $row;}
		return json_encode(array('errorCode'=>(int)0,'data'=>$row));
	}

	//$rows = productStats_getSellsByTimeStamp(time(),$k,$db,true);
	function productStats_getSellsByTimeStamp($stamp,$pID,$db = false,$noencode = false){
		$date = date('Y-m-d',$stamp);$time = date('H:i:s',$stamp);
		//FIXME: $pID puede ser false
		$shouldClose = false;if($db == false){$db = new sqlite3($GLOBALS['db']['productStats'],SQLITE3_OPEN_READONLY);$shouldClose = true;}
		$r = @$db->query('SELECT * FROM productsSells WHERE (date <= \''.$date.'\' OR (date = \''.$date.'\' AND time <= \''.$time.'\')) AND productID = \''.$pID.'\'');
		$rows = array();if($r){while($row = $r->fetchArray(SQLITE3_ASSOC)){$rows[] = $row;}}
		if($noencode){return $rows;}
		return json_encode(array('errorCode'=>(int)0,'data'=>$rows));
	}
?>
