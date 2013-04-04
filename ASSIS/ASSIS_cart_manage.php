<?php
	echo '<ul class=\'assisOptions\'>',
		'<li><a href=\''.$GLOBALS['baseURL_currentASSIS'].'\'>General</a></li>',
		'<li><a href=\''.$GLOBALS['baseURL_currentASSIS'].'paypalData\'>Configuración de la cuenta de Paypal</a></li>',
		'<li><a href=\''.$GLOBALS['baseURL_currentASSIS'].'updateSchema\'>Update Schema (desarrolladores)</a></li>',
	'</ul>';

	include_once('API_cart.php');

	function updateSchema($table = false){
		switch($table){
			case 'shippingList':break;
			default:exit;
		}
		$r = cart_updateSchemaByTable($table);
	}

	function main(){
		include_once('API_users.php');

		$rows = cart_shipping_get(false,true,20,'(shippingStatus > 0)');
		$userIDs = '';foreach($rows as $row){$userIDs .= $row['shippingUserID'].',';}
		$users = users_resolveIds($userIDs,false,true);
		echo T,T,T,'<div class=\'block\'><h2>Últimas peticiones de envío</h2>',N,
		T,T,T,'<p>Pedidos de los que se ha recibido el pago y están pendientes de confirmación y envío</p>',N,
		T,T,T,'<table><thead><tr><td style=\'width:1px;\'></td><td style=\'width:1px;\'><span>id</span></td><td style=\'width:1px;\'>lang</td><td style=\'width:1px;\'>user</td><td style=\'width:1px;\'>cartName</td><td style=\'width:1px;\'>pago</td><td style=\'width:1px;\'>status</td><td>cartTrack</td><td>comments</td></tr></thead><tbody>',N;
		foreach($rows as $row){
			common_array_register('shippings',$row,'cartHash');
			echo T,T,T,T,'<tr><td><input type=\'checkbox\' class=\'checkbox\' value=\'',$row['cartHash'],'\' onclick=\'assis.helper_checkThis(this);\' autocomplete="off"/></td>',
			T,T,T,T,'<td>',substr($row['cartHash'],0,8),'</td><td>',$row['shippingLang'],'</td>',
			T,T,T,T,'<td class=\'nowrap\'>from <a href=\'',$GLOBALS['baseURL_currentASSIS'],'viewCartsByUserID/',$row['shippingUserID'],'/\'>',$users[$row['shippingUserID']]['userName'],'</a></td>',
			T,T,T,T,'<td class=\'nowrap\'><a href=\'',$GLOBALS['baseURL_currentASSIS'],'viewCartFromUserID/',$row['shippingUserID'],'/',$row['shippingCartName'],'\'>',$row['shippingCartName'],'</a></td>',
			T,T,T,T,'<td>',$row['shippingPaymentMethod'],'</td>',N,
			T,T,T,T,'<td>',$row['shippingStatus'],'</td>',N,
			T,T,T,T,'<td>',$row['shippingTrack'],'</td>',N,
			T,T,T,T,'<td>',$row['shippingComments'],'</td>',N,
			T,T,T,T,'</tr>',N;
		}
		echo T,T,T,'</tbody></table>',N,
		T,T,T,'<div class=\'tableOptions\'>',
			'<div><a href=\'javascript:\' onclick=\'assis.shipping_changeStatus(this.parentNode,"shippings");\'>Cambiar status</a></div>',
			'<div><a href=\'javascript:\' onclick=\'assis.shipping_viewShippingAddress(this.parentNode,"shippings");\'>Ver dirección de envío</a></div>',
			'<div><a href=\'javascript:\' onclick=\'assis.shipping_viewShippingAddress(this.parentNode,"shippings");\'>Volver a enviar mail de recepción</a></div>',
		T,T,T,'</div>',N,
		T,T,T,'<ul class=\'pager\'><li><a href=\'',$GLOBALS['baseURL_currentASSIS'],'last/page/',($GLOBALS['currentPage']-1),'/\'>prev</a></li><li>',$GLOBALS['currentPage'],'</li><li><a href=\'',$GLOBALS['baseURL_currentASSIS'],'last/page/',($GLOBALS['currentPage']+1),'/\'>next</a></li></ul>',N,
		T,T,T,'</div>',N;

		/* -- */

		$rows = cart_shipping_get(false,true,false,1);
		$userIDs = '';foreach($rows as $row){$userIDs .= $row['shippingUserID'].',';}
		include_once('API_users.php');
		$users = users_resolveIds($userIDs,false,true);

		echo T,T,T,'<div class=\'block\'><h2>Last Carts processed</h2>',N,
		T,T,T,'<p>Here is a list of carts.</p>',N,
		T,T,T,'<table><thead><tr><td style=\'width:1px;\'></td><td><span>id</span></td><td></td><td></td></tr></thead><tbody>',N;
		foreach($rows as $row){
			common_array_register('shippings',$row,'cartHash');
			echo T,T,T,T,'<tr><td><input type=\'checkbox\' class=\'checkbox\' value=\'',$row['cartHash'],'\' onclick=\'assis.helper_checkThis(this);\' autocomplete="off"/></td>',
			'<td>',$row['shippingCartName'],'</td><td>from <a href=\'',$GLOBALS['baseURL_currentASSIS'],'viewCartsByUserID/',$row['shippingUserID'],'/\'>',$users[$row['shippingUserID']]['userName'],'</a></td><td><a href=\'',$GLOBALS['baseURL_currentASSIS'],'viewCartFromUserID/',$row['shippingUserID'],'/',$row['shippingCartName'],'\'>view content</a></td></tr>',N;
		}
		echo T,T,T,'</tbody></table>',N,
		T,T,T,'<div class=\'tableOptions\'>',
			'<div><a href=\'javascript:\' onclick=\'assis.shipping_addTrack(this.parentNode,"shippings");\'>Añadir track</a></div>',
		T,T,T,'</div>',N,
		T,T,T,'<ul class=\'pager\'><li><a href=\'',$GLOBALS['baseURL_currentASSIS'],'last/page/',($GLOBALS['currentPage']-1),'/\'>prev</a></li><li>',$GLOBALS['currentPage'],'</li><li><a href=\'',$GLOBALS['baseURL_currentASSIS'],'last/page/',($GLOBALS['currentPage']+1),'/\'>next</a></li></ul>',
		T,T,T,'</div>',N;
	}

	function paypalData(){
		if(count($_POST) > 0){
			$vars = array('paypalTesting'=>array(),'paypalProduction'=>array());
			$pool = &$vars;
			foreach($_POST as $k=>$v){
				$pos = strpos($k,'_');if($pos === false){continue;}
				$h = substr($k,0,$pos);
				$val = substr($k,$pos+1);
				$pool[$h][$val] = $v;
			}
			if(isset($_POST['paypalMode']) && in_array($_POST['paypalMode'],array('paypalTesting','paypalProduction'))){$vars['paypalMode'] = $_POST['paypalMode'];}
			$r = common_config_save($vars);
			if($r){
				echo "el fichero de config se ha salvado correctamente.";
			}
			return;
		}

		echo T,T,T,'<div class=\'block\'><h2>Configuración de la cuenta de paypal</h2>',N,
		T,T,T,T,'<p>Datos de configuración de las cuentas de paypal del portal.</p>',N,
		T,T,T,T,'<form action=\'',$GLOBALS['baseURL_currentASSIS'],'paypalData\' method=\'post\'>',N,
		T,T,T,T,'<input type=\'hidden\' name=\'option\' value=\'paypalBase\'/>',N,
		T,T,T,T,T,T,T,'<table class=\'middle\'><tbody>',N,
		T,T,T,T,T,T,T,T,'<tr><td colspan=\'2\'><input class=\'radio\' type=\'radio\' name=\'paypalMode\' value=\'paypalTesting\' ',((isset($GLOBALS['blueCommerce']['paypalMode']) && $GLOBALS['blueCommerce']['paypalMode'] == 'paypalTesting') ? 'checked="checked"' : ''),'/> Testing</td></tr>',N,
		T,T,T,T,T,T,T,T,'<tr><td style=\'width:1px;\'>appAccount</td><td><div class=\'inputTextSimple\'><input type=\'text\' name=\'paypalTesting_appAccount\' value=\'',(isset($GLOBALS['blueCommerce']['paypalTesting']) ? $GLOBALS['blueCommerce']['paypalTesting']['appAccount'] : ''),'\'/></div></td></tr>',N,
		T,T,T,T,T,T,T,T,'<tr><td style=\'width:1px;\'>appAction</td><td><div class=\'inputTextSimple\'><input type=\'text\' name=\'paypalTesting_appAction\' value=\'',(isset($GLOBALS['blueCommerce']['paypalTesting']) ? $GLOBALS['blueCommerce']['paypalTesting']['appAction'] : ''),'\'/></div></td></tr>',N,
		T,T,T,T,T,T,T,T,'<tr><td style=\'width:1px;\'>appVerification</td><td><div class=\'inputTextSimple\'><input type=\'text\' name=\'paypalTesting_appVerification\' value=\'',(isset($GLOBALS['blueCommerce']['paypalTesting']) ? $GLOBALS['blueCommerce']['paypalTesting']['appVerification'] : ''),'\'/></div></td></tr>',N,
		T,T,T,T,T,T,T,'</tbody></table>',N,
		T,T,T,T,T,T,T,'<table class=\'middle\'><tbody>',N,
		T,T,T,T,T,T,T,T,'<tr><td colspan=\'2\'><input class=\'radio\' type=\'radio\' name=\'paypalMode\' value=\'paypalProduction\' ',((isset($GLOBALS['blueCommerce']['paypalMode']) && $GLOBALS['blueCommerce']['paypalMode'] == 'paypalProduction') ? 'checked="checked"' : ''),'/> Producción</td></tr>',N,
		T,T,T,T,T,T,T,T,'<tr><td style=\'width:1px;\'>appAccount</td><td><div class=\'inputTextSimple\'><input type=\'text\' name=\'paypalProduction_appAccount\' value=\'',(isset($GLOBALS['blueCommerce']['paypalProduction']) ? $GLOBALS['blueCommerce']['paypalProduction']['appAccount'] : ''),'\'/></div></td></tr>',N,
		T,T,T,T,T,T,T,T,'<tr><td style=\'width:1px;\'>appAction</td><td><div class=\'inputTextSimple\'><input type=\'text\' name=\'paypalProduction_appAction\' value=\'',(isset($GLOBALS['blueCommerce']['paypalProduction']) ? $GLOBALS['blueCommerce']['paypalProduction']['appAction'] : ''),'\'/></div></td></tr>',N,
		T,T,T,T,T,T,T,T,'<tr><td style=\'width:1px;\'>appVerification</td><td><div class=\'inputTextSimple\'><input type=\'text\' name=\'paypalProduction_appVerification\' value=\'',(isset($GLOBALS['blueCommerce']['paypalProduction']) ? $GLOBALS['blueCommerce']['paypalProduction']['appVerification'] : ''),'\'/></div></td></tr>',N,
		T,T,T,T,T,T,T,'</tbody></table>',N,
		T,T,T,T,'<div><input type=\'submit\' value=\'Guardar\'/></div>',N,
		T,T,T,T,'</form>',N,
		T,T,T,'</div>',N;

		//FIXME: todo quirks mode para distintos probadores de paypal

		echo T,T,T,'<div class=\'block\'><h2>Configuración personalizada para usuarios</h2>',N,
		T,T,T,'</div>',N;
	}

	function viewShipping($cartHash){
		
	}

	function viewCartsByUserID($userID){
		include_once('API_users.php');
		include_once('API_product.php');
		$user = users_resolveIds(array($userID),false,true);
		$user = array_shift($user);
		if(count($user) < 1){
			echo "USER_NOT_EXISTS";return;
		}
		
		$rows = cart_getCartsFromUser($userID,false,true,true);
		$rows = product_helper_parseProductsByLang($rows,$GLOBALS['LANGCODE']);
		echo T,T,T,'<h2>Carts of ',$user['userName'],'</h2>',N,
		T,T,T,'<p>Here is a list of carts.</p>',N,
		T,T,T,'<table><thead><tr><td><span>id</span></td><td></td></tr></thead><tbody>',N;
		foreach($rows as $row){
			echo T,T,T,T,'<tr><td><a href=\'#\'>',$row['cartFile'],'</a></td><td><a href=\'',$GLOBALS['baseURL_currentASSIS'],'viewCartFromUserID/',$userID,'/',$row['cartFile'],'\'>view content</a></td></tr>',N;
		}
		echo T,T,T,'</tbody></table>',N;
		echo '<ul class=\'pager\'><li><a href=\'',$GLOBALS['baseURL_currentASSIS'],'last/page/',($GLOBALS['currentPage']-1),'/\'>prev</a></li><li>',$GLOBALS['currentPage'],'</li><li><a href=\'',$GLOBALS['baseURL_currentASSIS'],'last/page/',($GLOBALS['currentPage']+1),'/\'>next</a></li></ul>';
	}

	function viewShippingInfoByUserID($userID){
		include_once('API_users.php');
		$user = users_resolveIds(array($userID),false,true);
		$user = array_shift($user);
		if(count($user) < 1){
			echo "USER_NOT_EXISTS";return;
		}

		$rows = cart_getShippingInfoByUserID($user['id'],false,true);
		echo T,T,T,'<h2>Shipping Information of ',$user['userName'],'</h2>',N,
		T,T,T,'<p>Info.</p>',N,
		T,T,T,'<table><thead><tr><td><span>id</span></td><td>user info</td><td>location info</td></tr></thead><tbody>',N;
		foreach($rows as $row){
			echo T,T,T,T,'<tr><td><a href=\'#\'>',$row['id'],'</a></td><td>',$row['shippingUserName'],' ',$row['shippingUserLastName'],' (',$row['shippingUserPhone'],')</td><td>',$row['shippingAddress'],' ',$row['shippingLocation'],' (',$row['shippingPostalCode'],') ',$row['shippingCountry'],'</td></tr>',N;
		}
		echo T,T,T,'</tbody></table>',N;
		echo '<ul class=\'pager\'><li><a href=\'',$GLOBALS['baseURL_currentASSIS'],'last/page/',($GLOBALS['currentPage']-1),'/\'>prev</a></li><li>',$GLOBALS['currentPage'],'</li><li><a href=\'',$GLOBALS['baseURL_currentASSIS'],'last/page/',($GLOBALS['currentPage']+1),'/\'>next</a></li></ul>';
	}

	function viewCartFromUserID($userID,$cartFile){
		include_once('API_product.php');
		include_once('API_users.php');
		$user = users_resolveIds(array($userID),false,true);
		$user = array_shift($user);
		if(count($user) < 1){
			echo "USER_NOT_EXISTS";return;
		}

		//FIXME: parse cartFile
		$userPath = '../clients/'.$GLOBALS['CLIENT'].'/db/users/'.$userID.'/';
		if(!file_exists($userPath)){return;}
		$cartPath = $userPath.'db/carts/'.$cartFile;
		if(!file_exists($cartPath)){return;}
		$db = new sqlite3($cartPath);
		$rows = product_getProducts($db,true);
		$rows = product_helper_parseProductsByLang($rows,$GLOBALS['LANGCODE']);
		$db->close();

		//Obtener shippinginfo
		viewShippingInfoByUserID($userID);

		echo T,T,T,'<div class=\'block\'><h2>Productos del carrito</h2>',N,
		T,T,T,'<p>Listado de productos que se almacenaron dentro de este carrito</p>',N,
		T,T,T,'<table><thead><tr><td><span>id</span></td><td>productRelativeID</td><td>productTitle</td><td>Product quantity</td><td>productStatus</td><td></td></tr></thead><tbody>',N;
		foreach($rows as $row){
			echo T,T,T,T,'<tr><td><a href=\'#\'>',$row['id'],'</a></td><td><a href=\'',$GLOBALS['baseURL_ASSIS'],'products_manage/getByRelativeID/',$row['productRelativeID'],'\'>',$row['productRelativeID'],'</a></td>',
			'<td>',$row['productTitle'],'</td><td>',$row['productCount'],'</td><td>',$row['productStatus'],'</td>',
			'<td><a href=\'',$GLOBALS['baseURL_currentASSIS'],'show/',$row['id'],'\'>edit</a></td></tr>',N;
		}
		echo T,T,T,'</tbody></table>',N,
		T,T,T,'<ul class=\'pager\'><li><a href=\'',$GLOBALS['baseURL_currentASSIS'],'last/page/',($GLOBALS['currentPage']-1),'/\'>prev</a></li><li>',$GLOBALS['currentPage'],'</li><li><a href=\'',$GLOBALS['baseURL_currentASSIS'],'last/page/',($GLOBALS['currentPage']+1),'/\'>next</a></li></ul>',
		T,T,T,'</div>',N;
	}
?>
