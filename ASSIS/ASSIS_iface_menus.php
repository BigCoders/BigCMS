<?php
	include_once('API_menus.php');
	if(!file_exists($GLOBALS['db']['menus'])){@mkdir($GLOBALS['db']['menus']);if(!file_exists($GLOBALS['db']['menus'])){$GLOBALS['warn'] = 'Unable to create the database folder where the diferent menus are stored.';}else{chmod($GLOBALS['db']['menus'],0777);}}
	if(!is_writable($GLOBALS['db']['menus'])){$GLOBALS['warn'] = 'Unable to white the database folder where the diferent menus are stored.';}
	echo '<ul class=\'assisOptions\'>',
		'<li><a href=\''.$GLOBALS['baseURL_currentASSIS'].'\'>General</a></li>',
	'</ul>';

	function main(){
		if(!file_exists($GLOBALS['db']['menus'].'_index.php')){menus_createIndex(true);}
		include($GLOBALS['db']['menus'].'_index.php');
		ksort($menus);

		echo T,T,T,'<div class=\'block\'><h2>Listado de menus</h2>',N,
		T,T,T,'<p>A través de esta interfaz puedes configurar los diferentes menus de la tienda. Añadir o eliminar elementos del menu, establecer campañas o accesos directos.</p>',N,
		T,T,T,'<table><thead><tr><td><span>Nombre del menu</span></td><td style=\'width:1px;\'>elementos</td><td style=\'width:1px;\'></td><td style=\'width:1px;\'></td></tr></thead><tbody>',N;
		foreach($menus as $row){
			echo T,T,T,T,'<tr><td><span class="mono">',$row['menuName'],'</span></td>',
			'<td>',$row['menuItems'],'</td>',
			'<td><a href=\'',$GLOBALS['baseURL_currentASSIS'],'remove/',$row['menuName'],'\' onclick=\'return link.confirm(this,event);\'>eliminar</a></td>',
			'<td><a href=\'',$GLOBALS['baseURL_currentASSIS'],'edit/',$row['menuName'],'\'>editar</a></td></tr>',N;
		}
		echo T,T,T,'</tbody></table>',N,
		'<ul class=\'pager\'><li><a href=\'',$GLOBALS['baseURL_currentASSIS'],'last/page/',($GLOBALS['currentPage']-1),'/\'>prev</a></li><li>',$GLOBALS['currentPage'],'</li><li><a href=\'',$GLOBALS['baseURL_currentASSIS'],'last/page/',($GLOBALS['currentPage']+1),'/\'>next</a></li></ul>',N,
		T,T,T,'</div>',N;

		echo T,T,T,'<div class=\'block\'><h2>Crear nuevo menu</h2>',N,
		T,T,T,'<p>Añadir un nuevo fichero de menus a esta tienda. Una vez creado aparecerá en la lista superior y podrá aceptar nuevos elementos.</p>',N,
		T,T,T,'<form action=\'',$GLOBALS['baseURL_currentASSIS'],'addMenu/\' method=\'post\'>',N,
		T,T,T,'<table><tbody>',N,
		T,T,T,T,'<tr><td>MenuName</td><td><input type=\'text\' name=\'menuTitle\'/></td></tr>',N,
		T,T,T,'</tbody></table>',N,
		T,T,T,'<input type=\'submit\' value=\'send\'/>',N,
		T,T,T,'</form></div>',N;
	}

	function edit($menuName){
		$menu = menus_getMenu($menuName,true);
		if($menu === false){
			/* Comprobamos integridad */
			return false;
		}

		echo T,T,T,'<div class=\'block\'><h2>Elementos del menú</h2>',N,
		T,T,T,'<p>Estos son los elementos que pertenecen a este menú. Puedes añadir nuevos o editar los actuales, cada cambio que hagas repercutirá sobre la interface que esté usando el menú.</p>',N,
		T,T,T,'<table><thead><tr><td style=\'width:1px;\'>índice</td><td><span>título</span></td><td>clase</td><td>URL</td><td style=\'width:1px;\'></td><td style=\'width:1px;\'><td style=\'width:1px;\'></td><td style=\'width:1px;\'></td></tr></thead><tbody>',N;
		foreach($menu as $item){
			echo T,T,T,T,'<tr><td>',$item['index'],'</td>',N,
			T,T,T,T,'<td>',$item['title'],'</td>',N,
			T,T,T,T,'<td>',$item['class'],'</td>',N,
			T,T,T,T,'<td><a href=\'#\'>',$item['href'],'</a></td>',N,
			T,T,T,T,'<td><a href=\'',$GLOBALS['baseURL_currentASSIS'],'itemMove/',$menuName,'/',$item['index'],'/',((($item['index']-1) > -1) ? ($item['index']-1) : 0),'\'>Subir</a></td>',N,
			T,T,T,T,'<td><a href=\'',$GLOBALS['baseURL_currentASSIS'],'itemMove/',$menuName,'/',$item['index'],'/',($item['index']+1),'\'>Bajar</a></td>',N,
			T,T,T,T,'<td><a href=\'',$GLOBALS['baseURL_currentASSIS'],'itemEdit/',$menuName,'/',$item['index'],'\'>Editar</a></td>',N,
			T,T,T,T,'<td><a onclick=\'link.confirm(this,event);\' href=\'',$GLOBALS['baseURL_currentASSIS'],'itemRemove/',$menuName,'/',$item['index'],'\'>eliminar</a></td>',N,
			T,T,T,'</tr>',N;
		}
		echo T,T,T,'</tbody></table></div>',N;

		include_once('API_category.php');
		$c = category_helper_parseCategoriesByLang(category_getCategories(false,true,false,false,'categoryTitle ASC'),$GLOBALS['LANGCODE']);
		$select = '<select onchange="document.getElementById(\'menuHref\').value = this.value">'.N;
		$select .= T.T.'<option value="">Seleccionar categoría</option>'.N;
		foreach($c as $k=>$v){$select .= T.T.'<option value="{%clientURL%}c/'.$v['categoryTitleFixed'].'">'.$v['categoryTitle'].'</option>'.N;}
		$select .= T.'</select>';

		echo T,T,T,'<div class=\'block\'><h2>Añadir nuevo elemento al menú</h2>',N,
		T,T,T,'<p>Añade nuevos elementos al menú actual, ten en cuenta que al agregar nuevos elementos estos aparecerán de forma automática en cualquier maqueta que esté usando este menú.</p>',N,
		T,T,T,'<form action=\'',$GLOBALS['baseURL_currentASSIS'],'appendItem/',$menuName,'\' method=\'post\'>',N,
		T,T,T,'<table><tbody>',N,
		T,T,T,T,'<tr><td>Título del elemento</td><td><div class=\'inputTextSimple\'><input type=\'text\' name=\'title\'/></div></td></tr>',N,
		T,T,T,T,'<tr><td>Clase del elemento</td><td><div class=\'inputTextSimple\'><input type=\'text\' name=\'class\'/></div></td></tr>',N,
		T,T,T,T,'<tr><td>Enlace (si tuviera)</td><td><div class=\'inputTextSimple\'><input type=\'text\' name=\'href\' id=\'menuHref\'/>',$select,'</div></td></tr>',N,
		T,T,T,'</tbody></table>',N,
		T,T,T,'<input type=\'submit\' value=\'send\'/>',N,
		T,T,T,'</form></div>',N;
	}

	function remove($menuName){
		$r = menus_menuRemove($menuName,true);
		if(!isset($r['errorCode'])){header('Location: '.$GLOBALS['baseURL_currentASSIS']);exit;}
		print_r($r);
	}

	function addMenu(){
		if(count($_POST) < 1){echo 'error';return;}
		$r = menus_createNew($_POST['menuTitle'],true);
		if(!isset($r['errorCode'])){header('Location: '.$GLOBALS['baseURL_currentASSIS'].'edit/'.$r['menuName']);exit;}
		print_r($r);
	}

	function itemMove($menuName,$index,$newIndex){
		$r = menus_itemChangeIndex($menuName,$index,$newIndex,true);
		if(!isset($r['errorCode'])){header('Location: '.$GLOBALS['baseURL_currentASSIS'].'edit/'.$menuName);exit;}
		print_r($r);
	}

	function appendItem($menuName,$index = false){
		if(count($_POST) < 1){echo 'error';return;}
//FIXME: intval
		if($index !== false){$r = menus_itemSave($menuName,array_merge($_POST,array('index'=>$index)),true);}
		else{$r = menus_appendItem($menuName,$_POST,true);}
		if($r === true){header('Location: '.$GLOBALS['baseURL_currentASSIS'].'edit/'.$menuName);exit;}
		echo 'error';
	}

	function itemRemove($menuName,$index){
		$r = menus_itemRemove($menuName,$index,true);
		if($r === true){header('Location: '.$GLOBALS['baseURL_currentASSIS'].'edit/'.$menuName);exit;}
		echo 'error';
	}

	function itemEdit($menuName,$index){
		$menu = menus_getMenu($menuName,true);
		if($menu === false){echo 'error';exit;}
		$item = $menu[$index];

		echo T,T,T,'<div class=\'block\'><h2>Añadir nuevo elemento al menú</h2>',N,
		T,T,T,'<p>Añade nuevos elementos al menú actual, ten en cuenta que al agregar nuevos elementos estos aparecerán de forma automática en cualquier maqueta que esté usando este menú.</p>',N,
		T,T,T,'<form action=\'',$GLOBALS['baseURL_currentASSIS'],'appendItem/',$menuName,'/',$index,'\' method=\'post\'>',N,
		T,T,T,'<table><tbody>',N,
		T,T,T,T,'<tr><td>Título del elemento</td><td><div class=\'inputTextSimple\'><input type=\'text\' name=\'title\' value=\''.$item['title'].'\'/></div></td></tr>',N,
		T,T,T,T,'<tr><td>Clase del elemento</td><td><div class=\'inputTextSimple\'><input type=\'text\' name=\'class\' value=\''.$item['class'].'\'/></div></td></tr>',N,
		T,T,T,T,'<tr><td>Enlace (si tuviera)</td><td><div class=\'inputTextSimple\'><input type=\'text\' name=\'href\' value=\''.$item['href'].'\'/></div></td></tr>',N,
		T,T,T,'</tbody></table>',N,
		T,T,T,'<input type=\'submit\' value=\'send\'/>',N,
		T,T,T,'</form></div>',N;
	}
?>
