<?php
	echo '<ul class=\'assisOptions\'>',
		'<li><a href=\''.$GLOBALS['baseURL_currentASSIS'].'\'>General</a></li>',
		'<li><a href=\''.$GLOBALS['baseURL_currentASSIS'].'changeLogo\'>Cambiar logo</a></li>',
		'<li><a href=\''.$GLOBALS['baseURL_currentASSIS'].'changeTitle\'>Cambiar título</a></li>',
		'<li><a href=\''.$GLOBALS['baseURL_currentASSIS'].'validPayments\'>Cambiar métodos de pago</a></li>',
		'<li><a href=\''.$GLOBALS['baseURL_currentASSIS'].'metaTags\'>Tags META</a></li>',
		'<li><a href=\''.$GLOBALS['baseURL_currentASSIS'].'metaTagsByUri\'>Tags META (URI)</a></li>',
	'</ul>';

	function main(){
		/* Vamos a hacer una comprobación previa de las variables que nos interesen */
		//print_r($GLOBALS['blueCommerce']);
		echo T,T,T,'<div class=\'block\'><h2>Comprobación de configuración de la tienda</h2>',N,
		T,T,T,T,'<p>La tienda puede carecer de algunos datos de configuración básicos, para evitar problemas es necesario que la configuración esté realizada correctamente.</p>',N,
		T,T,T,T,'<table><tbody>',N,
		T,T,T,T,'<tr><td></td><td style=\'width:1px;\'></td></tr>',N;
		if(!isset($GLOBALS['blueCommerce']['HTML_defaultTitle']) || !is_array($GLOBALS['blueCommerce']['HTML_defaultTitle']) || count($GLOBALS['blueCommerce']['HTML_defaultTitle']) < 1){
			echo '<tr><td>No ha sido declarado el título HTML base de la tienda</td><td><a href=\'',$GLOBALS['baseURL_currentASSIS'],'changeTitle\'>Solucionar</a></td></tr>';
		}
		if(!isset($GLOBALS['blueCommerce']['validPaymentsMethods']) || !is_array($GLOBALS['blueCommerce']['validPaymentsMethods']) || count($GLOBALS['blueCommerce']['validPaymentsMethods']) < 1){
			echo '<tr><td>No han sido declarados métodos de pago válidos</td><td><a href=\'',$GLOBALS['baseURL_currentASSIS'],'validPayments\'>Solucionar</a></td></tr>';
		}
		echo T,T,T,T,'</tbody></table>',N,
		T,T,T,'</div>',N;
	}

	function integrityCheck(){
		$r = common_file_createBackup('db/config22.php');
		//$r = common_file_lintCheck('db/config.php');
var_dump($r);
	}

	function changeLogo(){
		echo T,T,T,'<div class=\'block\'><h2>Logo de la tienda</h2>',N,
		T,T,T,T,'<p>El logo se utilizará como distintivo de su tienda.</p>',N,
		T,T,T,T,'<img src=\'',$GLOBALS['clientURL'],'r/images/logo.png\'/>',N;
	}

	function changeTitle(){
		if(count($_POST) > 0){
			$vars = array('HTML_defaultTitle'=>array());
			$pool = &$vars['HTML_defaultTitle'];
			foreach($_POST as $k=>$v){
				$pos = strpos($k,'_');if($pos === false){continue;}$h = substr($k,0,$pos);if($h !== 'defaultTitle'){continue;}
				$lang = substr($k,$pos+1);
				$pool[$lang] = $v;
			}
			$r = common_config_save($vars);
if($r){
	echo "el fichero de config se ha salvado correctamente.";
}
			return false;
		}

		echo T,T,T,'<div class=\'block\'><h2>Configuración del título HTML de la tienda</h2>',N,
		T,T,T,T,'<p>Es necesario establecer un título HTML para el proyecto, este título se usará por defecto cuando no haya otro recurso disponible, es decir, si no hay ningún título declarado para una página determinada, se usará este.</p>',N,
		T,T,T,T,'<form action=\'',$GLOBALS['baseURL_currentASSIS'],'changeTitle\' method=\'post\'>',N,
		T,T,T,T,T,T,T,'<table class=\'middle\'><tbody>',N;
		$langs = array_merge(array('default'),$GLOBALS['blueCommerce']['langAllowed']);
		foreach($langs as $lang){echo T,T,T,T,T,T,T,T,'<tr><td style=\'width:1px;\'>',$lang,'</td><td><div class=\'inputTextSimple\'><input type=\'text\' name=\'defaultTitle_',$lang,'\' value=\'',(isset($GLOBALS['blueCommerce']['HTML_defaultTitle'][$lang]) ? $GLOBALS['blueCommerce']['HTML_defaultTitle'][$lang] : ''),'\'/></div></td></tr>',N;}
		echo T,T,T,T,T,'<tr><td class=\'submit\' colspan=\'2\'><input type=\'submit\' value=\'Guardar datos\'/></td></tr>',N,
		T,T,T,T,T,T,T,'</tbody></table>',N,
		T,T,T,T,'</form>',N,
		T,T,T,'</div>',N;
	}


	function metaTags($lang = 'default'){
		if(!in_array($lang,$GLOBALS['blueCommerce']['langAllowed'])){
//FIXME: hacer una solución más limpia
$lang = $GLOBALS['blueCommerce']['langFallback'];
//$lang = 'default';
		}

		$HTML_PATH = '../clients/'.$GLOBALS['CLIENT'].'/db/html/';
		$VIEWS_PATH = '../clients/'.$GLOBALS['CLIENT'].'/views/';
		$metaFile = $HTML_PATH.'meta_'.$lang.'.php';
		if(file_exists($metaFile)){include($metaFile);}
		$files = array();if($handle = opendir($VIEWS_PATH)){while(false !== ($file = readdir($handle))){if($file[0]!='.' && $file != 'base.php'){$files[] = $file;}}closedir($handle);}
		echo T,T,T,'<div class=\'block\'><h2>Plantillas presentes dentro de la plataforma</h2>',N,
		T,T,T,T,'<p>Estas son las vistas a las que podemos aplicar la información de las etiquetas META</p>',N,
		T,T,T,T,T,T,T,'<table class=\'middle\'><tbody>',N,
		T,T,T,T,T,T,T,T,'<tr><td style=\'width:1px;\'></td><td style=\'width:1px;\'></td><td>plantilla</td></tr>',N;
		foreach($files as $k=>$view){
			$q = array('id'=>$view,'HTML_TITLE'=>'','HTML_DESCRIPTION'=>'');
			if(isset($GLOBALS['META'][$view])){$q = array_merge($q,$GLOBALS['META'][$view]);}
			common_array_register('templates',$q);

			echo T,T,T,T,T,T,T,T,'<tr>',
			'<td><input type=\'checkbox\' class=\'checkbox\' value=\'',$view,'\' onclick=\'assis.helper_checkThis(this);\' autocomplete="off"/></td>',
			'<td>',$k,'</td><td>',$view,'</td>',N;
		}
		echo T,T,T,T,T,T,T,'</tbody></table>',N,
		T,T,T,'<div class=\'tableOptions\'>',
			'<div><a href=\'javascript:\' onclick=\'assis.html_changeMetaInformation(this.parentNode,"templates");\'>Cambiar META información</a></div>',
		T,T,T,'</div>',N,
		T,T,T,'</div>',N;





return;
		$CONTROLLERS_PATH = '../clients/'.$GLOBALS['CLIENT'].'/controllers/';
		$files = array();if($handle = opendir($CONTROLLERS_PATH)){while(false !== ($file = readdir($handle))){if($file[0]!='.'){$files[] = $file;}}closedir($handle);}
		$controllers = array();
		foreach($files as $controller){
			$blob = file_get_contents($CONTROLLERS_PATH.$controller);
			$r = preg_match_all('/function ([a-zA-Z0-9_]+)\([^\)]+\)\{/sm',$blob,$m);

			$functions = array();
			foreach($m[0] as $k=>$function){
				$pos = strpos($blob,$function)+strlen($function);
				$functionEnd = 0;$start = $pos;$end = strlen($blob);$count = 1;while($start < $end){if($blob[$start] == '{'){$count++;}if($blob[$start] == '}'){$count--;}if($count < 1){$functionEnd = $start;break;}$start++;}
				$functionName = $m[1][$k];
				$functionCode = substr($blob,$pos,$functionEnd-$pos);
				$r = preg_match_all('/\$GLOBALS\[\'TEMPLATE\'\]\[([^\]]+)\]/',$functionCode,$u);
				$functionResources = implode(',',$u[1]);
				$r = preg_match_all('/common_renderTemplate\(([^\)]+)\);/',$functionCode,$u);
				$functionTemplates = implode(',',$u[1]);
				$functions[$functionName] = array('functionCode'=>$functionCode,'functionResources'=>$functionResources,'functionTemplates'=>$functionTemplates);
//print_r($u);
//echo '<code>'.$functionCode.'</code>';
				$controllers[$controller] = array('functions'=>$functions);
			}
		}

		echo T,T,T,'<div class=\'block\'><h2>Páginas de la tienda</h2>',N,
		T,T,T,T,'<p>asd.</p>',N,
		T,T,T,'</div>',N;
		//print_r($controllers);
	}

	function metaTagsByUri($lang = false){
		if($lang == false){$lang = $GLOBALS['blueCommerce']['langFallback'];}
if(!isset($GLOBALS['blueCommerce']['HTML_uriRules'])){return false;}
		echo T,T,T,'<div class=\'block\'><h2>Reglas de presentación de Información basadas en la URI</h2>',N,
		T,T,T,T,'<p>Las reglas que describen las URLs están definidas mediante expresiones regulares</p>',N,
		T,T,T,T,T,T,T,'<table class=\'middle\'><tbody>',N,
		T,T,T,T,T,T,T,T,'<tr><td style=\'width:1px;\'></td><td>regla</td><td style=\'width:1px;\'></td><td style=\'width:1px;\'></td></tr>',N;
		foreach($GLOBALS['blueCommerce']['HTML_uriRules'][$lang] as $k=>$data){echo T,T,T,T,T,T,T,T,'<tr><td>',$k,'</td><td>',json_encode($data),'</td>',N,
			'<td><a href=\''.$GLOBALS['baseURL_currentASSIS'].'metaTagsByUriEdit/',$k,'/',$lang,'\'>editar</a></td>',N,
			'<td><a href=\''.$GLOBALS['baseURL_currentASSIS'].'metaTagsByUriRemove/',$k,'/',$lang,'\'>eliminar</a></td></tr>',N;
		}
		echo T,T,T,T,T,T,T,'</tbody></table>',N,
		T,T,T,'</div>',N;

		echo T,T,T,'<div class=\'block\'><h2>Añadir nueva regla</h2>',N,
		T,T,T,T,'<p>Info</p>',N,
		T,T,T,T,'<form action=\'',$GLOBALS['baseURL_currentASSIS'],'metaTagsByUriEdit\' method=\'post\'>',N,
		T,T,T,T,T,T,T,'<table class=\'middle\'><tbody>',N,
		T,T,T,T,T,T,T,T,'<tr><td>Regla</td><td><div class=\'inputText\'><input type=\'text\' name=\'URI_RULE\' value=\'\'/></div></td></tr>',N,
		T,T,T,T,T,T,T,T,'<tr><td>Título de la página</td><td><div class=\'inputText\'><input type=\'text\' name=\'HTML_TITLE\' value=\'\'/></div></td></tr>',N,
		T,T,T,T,T,T,T,T,'<tr><td>Descripción de la página</td><td><div class=\'inputText\'><input type=\'text\' name=\'HTML_DESCRIPTION\' value=\'\'/></div></td></tr>',N;
		echo T,T,T,T,T,T,T,'</tbody></table>',N,
		T,T,T,T,'<div><input type=\'submit\' value=\'Guardar\'/></div>',N,
		T,T,T,T,'</form>',N,
		T,T,T,'</div>',N;
	}

	function metaTagsByUriEdit($id = false,$lang = false){
		if($lang == false){$lang = $GLOBALS['blueCommerce']['langFallback'];}
if(!isset($GLOBALS['blueCommerce']['HTML_uriRules'])){return false;}

		if(count($_POST) > 0){
			if(!isset($_POST['id']) || intval($_POST['id']) < 1){$_POST['id'] = count($GLOBALS['blueCommerce']['HTML_uriRules'][$lang])+1;}
			$newItem = array('URI_RULE'=>$_POST['URI_RULE'],'HTML_TITLE'=>$_POST['HTML_TITLE'],'HTML_DESCRIPTION'=>$_POST['HTML_DESCRIPTION']);
			$uriRules = $GLOBALS['blueCommerce']['HTML_uriRules'];$uriRules[$lang][$_POST['id']] = $newItem;$uriRules[$lang] = array_values($uriRules[$lang]);
			$vars['HTML_uriRules'] = $uriRules;
$r = common_config_save($vars);
var_dump($r);
print_r($vars);
return;
		}

		echo T,T,T,'<div class=\'block\'><h2>Edición de Reglas de presentación</h2>',N,
		T,T,T,T,'<p>Info</p>',N,
		T,T,T,T,'<form action=\'',$GLOBALS['baseURL_currentASSIS'],'metaTagsByUriEdit\' method=\'post\'>',N,
		T,T,T,T,T,'<input type=\'hidden\' name=\'id\' value=\'',$id,'\'/>',N,
		T,T,T,T,T,T,T,'<table class=\'middle\'><tbody>',N,
		T,T,T,T,T,T,T,T,'<tr><td style=\'width:1px;\'></td><td>regla</td><td style=\'width:1px;\'></td></tr>',N;
		foreach($GLOBALS['blueCommerce']['HTML_uriRules'][$lang][$id] as $k=>$data){echo T,T,T,T,T,T,T,T,'<tr><td>',$k,'</td><td><div class=\'inputText\'><input type=\'text\' name=\'',$k,'\' value=\'',$data,'\'/></div></td></tr>',N;}
		echo T,T,T,T,T,T,T,'</tbody></table>',N,
		T,T,T,T,'<div><input type=\'submit\' value=\'Guardar\'/></div>',N,
		T,T,T,T,'</form>',N,
		T,T,T,'</div>',N;
	}

	function metaTagsByUriRemove($id = false,$lang = false){
		if($lang == false){$lang = $GLOBALS['blueCommerce']['langFallback'];}
		$vars['HTML_uriRules'] = $GLOBALS['blueCommerce']['HTML_uriRules'];
		unset($vars['HTML_uriRules'][$lang][$id]);
		$vars['HTML_uriRules'][$lang] = array_values($vars['HTML_uriRules'][$lang]);
		$r = common_config_save($vars);
		var_dump($r);
	}

	function validPayments(){
		if(count($_POST) > 0){
			$_POST = array_intersect_key($_POST,array('paypal'=>0,'creditCard'=>0));
			$vars['validPaymentsMethods'] = array_keys($_POST);
			$r = common_config_save($vars);
if($r){
	echo "el fichero de config se ha salvado correctamente.";
}
			return false;
		}

		echo T,T,T,'<div class=\'block\'><h2>Configuración de los métodos de pago de la tienda</h2>',N,
		T,T,T,T,'<p>Activar o desactivar alguno de los siguientes procedimientos afectará a las opciones visibles que aparecerán en la tienda. Selecciona los diferentes métodos a través de los cuales los usuarios de tu tienda podrán pasar por caja.</p>',N,
		T,T,T,T,'<form action=\'',$GLOBALS['baseURL_currentASSIS'],'validPayments\' method=\'post\'>',N,
		T,T,T,T,T,'<table class=\'middle\'><tbody>',N,
		T,T,T,T,T,T,'<tr><td><div><input type=\'checkbox\' class=\'checkbox\' name=\'paypal\' ',(isset($GLOBALS['blueCommerce']['validPaymentsMethods']) && in_array('paypal',$GLOBALS['blueCommerce']['validPaymentsMethods']) ? 'checked="checked"' : ''),'/> Paypal</div></td></tr>',N,
		T,T,T,T,T,T,'<tr><td><div><input type=\'checkbox\' class=\'checkbox\' name=\'creditCard\' ',(isset($GLOBALS['blueCommerce']['validPaymentsMethods']) && in_array('creditCard',$GLOBALS['blueCommerce']['validPaymentsMethods']) ? 'checked="checked"' : ''),'/> Tarjeta de crédito</div></td></tr>',N,
		T,T,T,T,T,T,'<tr><td class=\'submit\' colspan=\'2\'><input type=\'submit\' value=\'Guardar datos\'/></td></tr>',N,
		T,T,T,T,T,'</tbody></table>',N,
		T,T,T,T,'</form>',N,
		T,T,T,'</div>',N;
	}
?>
