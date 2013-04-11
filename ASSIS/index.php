<?php
	if(!strpos(getcwd(),'resources/PHP')){chdir('../resources/PHP');}
	error_reporting(E_ALL);
	$a = session_id();if(empty($a)){session_start();}


	/* Mensajes del sistema */
	//FIXME: deprecated
	$GLOBALS['warn'] = false;
	$GLOBALS['file'] = false;

	include_once('inc_common.php');
	/* Necesitamos averiguar el cliente */
	$GLOBALS['CLIENT'] = common_getClient();

	include_once('API_users.php');
	$u = users_isLogged();
	if($u === false){include('../../ASSIS/login.php');exit;}
	if(!users_isAdmin()){echo 'user is not admin';exit;}

	$GLOBALS['baseURL_ASSIS'] = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
	$GLOBALS['baseURL'] = substr($GLOBALS['baseURL_ASSIS'],0,strpos($GLOBALS['baseURL_ASSIS'],'ASSIS/'));
	$GLOBALS['baseURL_ASSIS'] = $GLOBALS['baseURL'].'ASSIS/';
	$GLOBALS['baseURL_currentASSIS'] = false;
	$GLOBALS['baseURL_currentURI'] = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
	if(substr($GLOBALS['baseURL_currentURI'],-1) != '/'){$GLOBALS['baseURL_currentURI'].='/';}
	$GLOBALS['currentPage'] = 1;
	common_setClient($GLOBALS['CLIENT']);
	$pwd = dirname(__FILE__);

	/* Incluimos la configuración del cliente */
	include('../clients/'.$GLOBALS['CLIENT'].'/db/config.php');

	/* Obtenemos todos los asistentes */
	$files = array();
	if($handle = opendir('../../ASSIS/')){while(false !== ($file = readdir($handle))){if(($file[0]!='.')){if(substr($file,0,5) == 'ASSIS'){$assists[] = $file;}}}closedir($handle);}

	sort($assists);
	$ordered_assists = array();$fam = false;$fpos = 0;
	foreach($assists as $a){
		if($fam !== substr($a,6,$fpos)){$fpos = strpos(substr($a,6),'_');$fam = substr($a,6,$fpos);}
		if($fam === ''){$fam = 'unsorted';}
		$ordered_assists[$fam][] = array('assis'=>$a,'link'=>substr($a,6,-4),'name'=>substr($a,5+$fpos+1,-4));
	}

	if(!defined('T')){define('T',"\t");}
	if(!defined('N')){define('N',"\n");}

	$code = '';
	foreach($ordered_assists as $fam=>$v){
		if(in_array($fam,array('users','cart','category','lang'))){continue;}
		$code .= T.T.'<ul>'.N.
		T.T.T.'<li class=\'familyHeader family_'.$fam.'\'><h4>'.$fam.'</h4></li>'.N;
		foreach($v as $a){
			$code .= T.T.T.'<li><a href=\''.$GLOBALS['baseURL_ASSIS'].$a['link'].'/\'>'.str_ireplace('_', ' ', $a['name']).'</a></li>'.N;
		}
		$code .= T.T.'</ul>'.N;
	}

	$params = array();
	if(isset($_GET['params'])){
		/* Obtenemos la paginación */
		if(preg_match('/page\/([0-9]+)\/$/',$_GET['params'],$m)){
			$_GET['params'] = substr($_GET['params'],0,-strlen($m[0]));$GLOBALS['currentPage'] = $m[1];
			$GLOBALS['baseURL_currentURI'] = substr($GLOBALS['baseURL_currentURI'],0,-strlen($m[0]));
			if($GLOBALS['currentPage'] < 1){header('Location: '.$GLOBALS['baseURL_currentURI'].'page/1/');exit;}
		}
		$params = explode('/',$_GET['params']);
		foreach($params as $k=>$v){if($v === ''){unset($params[$k]);}}
		$subCommand = array_shift($params);
	}

	$content = '';
	$HTMLside = true;
	if(!isset($_GET['command'])){$_GET['command'] = 'dashboard';}
	$_GET['command'] = preg_replace('/[^a-zA-Z0-9_]*/','',$_GET['command']);
	$assisName = $pwd.'/ASSIS_'.$_GET['command'].'.php';
	if(file_exists($assisName)){
		$GLOBALS['baseURL_currentASSIS'] = $GLOBALS['baseURL_ASSIS'].$_GET['command'].'/';
		ob_start();
		include_once($assisName);
		if(isset($subCommand) && function_exists($subCommand)){$r = call_user_func_array($subCommand,$params);}
		else if(function_exists('main')){$r = call_user_func_array('main',$params);}
		$content = ob_get_contents();
		if(isset($GLOBALS['message'])){$content = '<div class=\'message\'>'.$GLOBALS['message'].'</div>'.N.$content;}
		ob_end_clean();

		/* Si el assis lleva su propio side le damos prioridad */
		if(preg_match('/<ul class=\'aBodySide\'>/',$content,$m)){$HTMLside = false;}
	}
?><!doctype html>
<html>
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8">
	<title>BigCMS Admin</title>
	<link href='http://fonts.googleapis.com/css?family=Droid+Sans+Mono' rel='stylesheet' type='text/css'>
	<link rel="stylesheet" type="text/css" media="screen" href="g/css/assis.css"/>
	<script type='text/javascript'>
		var VAR_clientURL = '<?php echo $GLOBALS['clientURL']; ?>';
		var VAR_baseURL = '<?php echo $GLOBALS['baseURL_currentASSIS']; ?>';
		var VAR_langCode = '<?php echo $GLOBALS['LANGCODE']; ?>';
		var VAR_allowedLangs = <?php echo json_encode($GLOBALS['bigCMS']['langAllowed']);?>;
		var API_product = VAR_clientURL+'g/PHP/API_product.php';
		<?php foreach($GLOBALS['ARRAYS'] as $k=>$v){echo common_array_toJS($k);} ?>
	</script>
	<script src='g/js/coreJS.402.js' type='text/javascript'></script>
	<script src='g/js/assis.js' type='text/javascript'></script>
</head>
<body>
	<div class='aHeader'><h1>BigCMS Admin</h1></div>
	<div class='aMenu'>
		<a <?php echo ($_GET['command'] == 'dashboard') ? 'class=\'selected\'' : ''; ?> href='<?php echo $GLOBALS['baseURL_ASSIS']; ?>'><span>Dashboard</span></a>
		<a <?php echo ($_GET['command'] == 'products_manage') ? 'class=\'selected\'' : ''; ?> href='<?php echo $GLOBALS['baseURL_ASSIS']; ?>products_manage/'><span>Productos</span></a>
		<a <?php echo ($_GET['command'] == 'category') ? 'class=\'selected\'' : ''; ?> href='<?php echo $GLOBALS['baseURL_ASSIS']; ?>category/'><span>Categorias</span></a>
		<a <?php echo ($_GET['command'] == 'users_manage') ? 'class=\'selected\'' : ''; ?> href='<?php echo $GLOBALS['baseURL_ASSIS']; ?>users_manage/'><span>Users</span></a>
		<a <?php echo ($_GET['command'] == 'cart_manage') ? 'class=\'selected\'' : ''; ?> href='<?php echo $GLOBALS['baseURL_ASSIS']; ?>cart_manage/'><span>Pedidos</span></a>
		<a <?php echo ($_GET['command'] == 'notifications') ? 'class=\'selected\'' : ''; ?> href='<?php echo $GLOBALS['baseURL_ASSIS']; ?>notifications/'><span>Notifications</span></a>
		<a <?php echo ($_GET['command'] == 'lang_manage') ? 'class=\'selected\'' : ''; ?> href='<?php echo $GLOBALS['baseURL_ASSIS']; ?>lang_manage/'><span>Idioma</span></a>
		<a <?php echo ($_GET['command'] == 'page_manage') ? 'class=\'selected\'' : ''; ?> href='<?php echo $GLOBALS['baseURL_ASSIS']; ?>page_manage/'><span>Pages</span></a>
		<a <?php echo ($_GET['command'] == 'blog_manage') ? 'class=\'selected\'' : ''; ?> href='<?php echo $GLOBALS['baseURL_ASSIS']; ?>blog_manage/'><span>Blog</span></a>
	</div>
	<div class='aBody'>
		<ul class='aBodySide'>
			<li>
				<ul class='assisOptions'><li><a href='javascript:' onclick='assis.changeLang(this);'><?php echo $GLOBALS['LANGCODE']; ?></a></li></ul>
			</li>
<?php if($HTMLside === true){ ?>
			<h3>Hola, <?php echo $GLOBALS['userLogged']['userName']; ?></h3>
			<p>Bienvenido al panel de control de BigCMS</p>
			<h3>Asistentes</h3>
			<?php echo N,$code,T,T; ?>
<?php } ?>
		</ul>
		<div class='aBodyContent'><?php
			while($m = common_message_shift()){echo '<p class=\'message\'>'.$m.'</p>';}
			if($GLOBALS['warn'] !== false){echo '<p class=\'warn\'>',$GLOBALS['warn'],'</p>';}
			echo N,$content,N,N;
		?></div>
	</div>
	<div class='aFooter'>
		
	</div>
</body>
</html>	
