<?php
@session_start();
error_reporting(E_ALL);
//error_reporting(0);

chdir('resources/PHP/');
define('T',"\t");
define('N',"\n");

/* Si entra por ip, las condiciones cambian */
if($_SERVER['SERVER_NAME'] == $_SERVER['HTTP_HOST'] && isset($_GET['params']) && substr($_GET['params'],0,13) == 'BigCMS/'){$_GET['params'] = substr($_GET['params'],13);}

//FIXME: esto luego
$GLOBALS['currentURL'] = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];


$GLOBALS['CLIENT'] = preg_replace('/^www\./','',$_SERVER['SERVER_NAME']);
$clientExists = file_exists('../clients/'.$GLOBALS['CLIENT']);

$GLOBALS['TEMPDATA'] = array('header2'=>'');
$GLOBALS['LANGCODE'] = false;
$GLOBALS['OUTPUT'] = false;
$params = false;

if(isset($_GET['params'])){
	/* Obtenemos la paginación */
	if(preg_match('/page\/([0-9]+)\/$/',$_GET['params'],$m)){
		$_GET['params'] = substr($_GET['params'],0,-strlen($m[0]));$GLOBALS['currentPage'] = $m[1];
		$GLOBALS['currentURL'] = substr($GLOBALS['currentURL'],0,-strlen($m[0]));
		if($GLOBALS['currentURL'] < 1){header('Location: '.$GLOBALS['currentURL'].'page/1/');exit;}
	}
	$params = explode('/',$_GET['params']);
	foreach($params as $k=>$v){if($v === ''){unset($params[$k]);}}
}

$pos = strpos($_SERVER['REQUEST_URI'], '?');
if($pos>0){$response = substr($_SERVER['REQUEST_URI'],$pos+1);parse_str($response,$m);if(count($m)>0){$_GET = array_merge($_GET,$m);}}

if(!$clientExists && $params !== false && is_array($params)){
	$GLOBALS['CLIENT'] = array_shift($params);if($GLOBALS['CLIENT'] === NULL){echo 'No existe el cliente seleccionado';exit;}
	$GLOBALS['CLIENT'] = preg_replace('/[^a-zA-Z0-9_\.]*/','',$GLOBALS['CLIENT']);
	if(empty($GLOBALS['CLIENT']) || !file_exists('../clients/'.$GLOBALS['CLIENT'])){echo 'No existe el cliente seleccionado';exit;}
	$clientExists = true;
}

include_once('inc_common.php');
common_setClient($GLOBALS['CLIENT']);
include_once('API_users.php');
include_once('API_menus.php');
$GLOBALS['userLogged'] = false;
users_isLogged();

$controller = 'index';
//FIXME: $params is false
if($params !== false && count($params) > 0){
	$controller = array_shift($params);
	$command = $unshift = array_shift($params);
	if($command !== NULL){$command = $controller.'_'.$command;}
	if($controller == NULL){$controller = 'index';}
	/* Regla especial para ASSIS */
	if($controller == 'ASSIS'){include('../../ASSIS/index.php');exit;}
}
$controllerPath = '../clients/'.$GLOBALS['CLIENT'].'/controllers/'.$controller.'.php';
if(file_exists($controllerPath)){
	include_once($controllerPath);
	/* La funcion call_user_func_array necesita que el segundo parámetro sea un array */
	if($params == false){$params = array();}
	if(isset($command) && function_exists($command)){$r = call_user_func_array($command,$params);}
	else if(function_exists('main')){if(isset($unshift)){array_unshift($params,$unshift);}$r = call_user_func_array('main',$params);}
}


/* Control de usuarios activos */
//FIXME: si $GLOBALS['userLogged'] lo almacenamos dentro del fichero, aquí tambien pueden ir otro tipo de ficheros que no sean ips
//para indicar usuarios logueados
$sessionID = session_id();
$ipPath = '../clients/'.$GLOBALS['CLIENT'].'/db/tracking/sessions/';
if(is_writable($ipPath)){
	$ipPath .= $_SERVER['REMOTE_ADDR'].'_'.$sessionID;
	if(file_exists($ipPath)){$mtime = stat($ipPath);$mtime = date('Y-m-d H:i',$mtime['mtime']);if($mtime != date('Y-m-d H:i')){$ar = @fopen($ipPath,'w');@fclose($ar);}}
	else{$ar = fopen($ipPath,'w');fclose($ar);}
}

if(!$clientExists){echo 'No existe el cliente seleccionado';exit;}
include_once('API_tracking.php');
$a = tracking_touch();
if($GLOBALS['OUTPUT'] === false){common_renderTemplate('index');}
echo $GLOBALS['OUTPUT'];
?>
