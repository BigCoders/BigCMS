<?php
	include_once('API_category.php');
	echo '<ul class=\'assisOptions\'>',
		'<li><a href=\''.$GLOBALS['baseURL_currentASSIS'].'\'><img class=\'block icon16 gohome\' src=\'g/images/t.gif\'/></a></li>',
		'<li><a href=\''.$GLOBALS['baseURL_currentASSIS'].'last\'>Ver últimos</a></li>',
		'<li><a href=\''.$GLOBALS['baseURL_currentASSIS'].'add\'>Añadir</a></li>',
	'</ul>';

	function updateSchema($table = false){
		switch($table){
			case 'categories':break;
			default:exit;
		}
		$r = category_updateSchemaByTable($table);
	}

	function helper_paintBaseCategoryOptions(){
		echo '<div><a href=\'javascript:\' onclick=\'\'>Añadir productos</a></div>',
		'';
	}

	function main(){
		last();
	}

	function add($id = false){
		$category = array();
		if($id !== false){
			$category = category_getByID($id,false,true);
			if($category === false){echo "error";exit;}
			common_indexByLang($category['categoryTitle']);
		}

		$parents = category_getWhere('(categoryParentID = \'0\')',false,true);
		$parents = category_helper_parseCategoriesByLang($parents,$GLOBALS['LANGCODE']);
		$parentsSelect = '<select name=\'categoryParentID\' class=\'parentSelect\'><option value=\'0\'></option>';foreach($parents as $parent){$parentsSelect .= '<option value=\''.$parent['id'].'\'>'.$parent['categoryTitle'].'</option>';}$parentsSelect .= '</select>';
		if(isset($category['categoryParentID'])){$parentsSelect = str_replace('<option value=\''.$category['categoryParentID'].'\'>','<option value=\''.$category['categoryParentID'].'\' selected="selected">',$parentsSelect);}


		echo T,T,T,'<div class=\'block\'><h2>Añadir nueva categoría</h2>',N,
		T,T,T,'<p>Las categorías son formas de agrupar listados de productos y de añadirles nuevas propiedades a los mismos.</p>',N,
		T,T,T,'<form action=\'',$GLOBALS['baseURL_currentASSIS'],'save\'  method=\'post\'>',N,
		T,T,T,T,'<input type=\'hidden\' name=\'id\' value=\'',(isset($category['id']) ? $category['id'] : ''),'\'>',N,

		T,T,T,T,'<table class=\'formTable\'><tbody>',N,
		T,T,T,T,T,'<tr><td><h6>Nombre de la categoría</h6><div class=\'blueInfo\'>El nombre de la categoría puede representarse con diferentes idiomas.</div></td>',N,
		T,T,T,T,T,T,'<td>',N,
		T,T,T,T,T,T,T,'<table class=\'middle\'><tbody>',N;
		$langs = array_merge(array('default'),$GLOBALS['blueCommerce']['langAllowed']);
		foreach($langs as $lang){echo T,T,T,T,T,T,T,T,'<tr><td style=\'width:1px;\'>',$lang,'</td><td><div class=\'inputTextSimple\'><input type=\'text\' name=\'categoryTitle_',$lang,'\' value=\'',(isset($category['categoryTitle'][$lang]) ? $category['categoryTitle'][$lang] : ''),'\'/></div></td></tr>',N;}
		echo T,T,T,T,T,T,T,T,'<tr><td colspan=\'2\'><ul class=\'assisOptions\'><li><a href=\'#\'>Añadir nuevo lenguaje al listado</a></li></ul></td>',N,
		T,T,T,T,T,T,T,'</tbody></table>',N,
		T,T,T,T,T,T,'</td>',N,

		T,T,T,T,T,'<tr><td><h6>Nombre de la categoría</h6><div class=\'blueInfo\'>El nombre de la categoría puede representarse con diferentes idiomas.</div></td>',N,
		T,T,T,T,T,T,'<td>',N,
		T,T,T,T,T,T,T,'<table class=\'middle\'><tbody>',N,
		T,T,T,T,T,T,T,T,'<tr><td style=\'width:1px;\'>Producto</td><td>',$parentsSelect,'</div></td></tr>',N,
		T,T,T,T,T,T,T,'</tbody></table>',N,
		T,T,T,T,T,T,'</td>',N,


		T,T,T,T,T,'<tr><td class=\'submit\' colspan=\'2\'><input type=\'submit\' value=\'Guardar categoría\'/></td></tr>',N,

		T,T,T,T,T,T,'</td>',N,
		T,T,T,T,T,'</tr>',N,
		T,T,T,T,'</tbody></table>',N,
		T,T,T,'</form></div>';
	}

	function edit($id){
		

		echo T,T,T,'<div class=\'block\'><h2>Editar categoría</h2>',N,
		T,T,T,'<p>Las categorías son formas de agrupar listados de productos y de añadirles nuevas propiedades a los mismos.</p>',N,
		T,T,T,'<form action=\'',$GLOBALS['baseURL_currentASSIS'],'save\'  method=\'post\'>',N,
		T,T,T,T,'<input type=\'hidden\' name=\'id\' value=\'',$id,'\'>',N,

		T,T,T,T,'<table class=\'formTable\'><tbody>',N,
		T,T,T,T,T,'<tr><td><h6>Nombre de la categoría</h6><div class=\'blueInfo\'>El nombre de la categoría puede representarse con diferentes idiomas.</div></td>',N,
		T,T,T,T,T,T,'<td>',N,
		T,T,T,T,T,T,T,'<table class=\'middle\'><tbody>',N;
		$langs = array_merge(array('default'),$GLOBALS['blueCommerce']['langAllowed']);
		foreach($langs as $lang){echo T,T,T,T,T,T,T,T,'<tr><td style=\'width:1px;\'>',$lang,'</td><td><div class=\'inputTextSimple\'><input type=\'text\' name=\'categoryTitle_',$lang,'\' value=\'',(isset($category['categoryTitle'][$lang]) ? $category['categoryTitle'][$lang] : ''),'\'/></div></td></tr>',N;}
		echo T,T,T,T,T,T,T,T,'<tr><td colspan=\'2\'><ul class=\'assisOptions\'><li><a href=\'#\'>Añadir nuevo lenguaje al listado</a></li></ul></td>',N,
		T,T,T,T,T,T,T,'</tbody></table>',N,

		T,T,T,T,T,'<tr><td class=\'submit\' colspan=\'2\'><input type=\'submit\' value=\'Guardar categoría\'/></td></tr>',N,

		T,T,T,T,T,T,'</td>',N,
		T,T,T,T,T,'</tr>',N,
		T,T,T,T,'</tbody></table>',N,
		T,T,T,'</form></div>';
	}

	function last(){
		//FIXME: para el idioma por params
		$limit = $GLOBALS['currentPage'] * 20 - 20;
		$rows = category_getCategories(false,true,$limit.',20');
		$rows = category_helper_parseCategoriesByLang($rows,$GLOBALS['LANGCODE']);
		echo T,T,T,'<div class=\'block\'><h2>Últimas categorías</h2>',N,
		T,T,T,'<p>Este listado incluye algunas de las últimas categorías añadidas al gestor.</p>',N,
		T,T,T,'<table><thead><tr><td style=\'width:1px;\'></td><td style=\'width:1px;\'><span>id</span></td><td>Título de la categoría</td><td style=\'width:100px;\'></td><td style=\'width:1px;\'></td><td style=\'width:1px;\'></td></tr></thead><tbody>',N;
		foreach($rows as $row){
			$row['clientURL'] = $GLOBALS['baseURL'];
			echo T,T,T,T,'<tr id=\'row_',$row['id'],'\'>',
			'<td><input type=\'checkbox\' class=\'checkbox\' value=\'',$row['id'],'\' onclick=\'assis.helper_checkThis(this);\' autocomplete="off"/></td>',
			'<td><a href=\'#\'>',$row['id'],'</a></td><td><a href=\'',common_replaceInTemplate($GLOBALS['blueCommerce']['categoryLink'],$row),'\'>',$row['categoryTitle'],'</a></td>',
			'<td><a href=\'',$GLOBALS['baseURL_ASSIS'],'products_manage/searchByCategory/',$row['categoryTitleFixed'],'\'>ver productos</a></td>',N,
			'<td><a href=\'',$GLOBALS['baseURL_currentASSIS'],'add/',$row['id'],'\'>editar</a></td>',
			'<td><a href=\'',$GLOBALS['baseURL_currentASSIS'],'remove/',$row['id'],'\' onclick=\'link.confirm(this,event);\'>eliminar</a></td>',
			'</tr>',N;
		}
		echo T,T,T,'</tbody></table>',N,
		T,T,T,'<div class=\'tableOptions\'>';
		helper_paintBaseCategoryOptions();
		echo T,T,T,'</div>',N,
		'<ul class=\'pager\'><li><a href=\'',$GLOBALS['baseURL_currentASSIS'],'last/page/',($GLOBALS['currentPage']-1),'/\'>prev</a></li><li>',$GLOBALS['currentPage'],'</li><li><a href=\'',$GLOBALS['baseURL_currentASSIS'],'last/page/',($GLOBALS['currentPage']+1),'/\'>next</a></li></ul>',
		T,T,T,'</div>',N;
	}

	function save(){
		if(count($_POST) > 0){
			include_once('API_category.php');
			$r = category_save($_POST,false,true);
			//print_r($r);
			header('Location: '.$GLOBALS['baseURL_currentASSIS'],'last');exit;
		}
	}

	function remove($id){
		$r = category_remove($id,false,true);
		if($r === true){common_message_push('La categoría se eliminó correctamente');}
		if(isset($r['errorDescription'])){common_message_push('Se produjo un error al eliminar '.$r['errorDescription']);}
		header('Location: '.$_SERVER['HTTP_REFERER']);exit;
	}

	function sidebar(){
return;
		echo '<ul class=\'aBodySide\'>',N;

		echo T,T,'<li>',N,'<h3>Category Controller</h3>',N,
		T,T,'<p>Normally you will need a controller to receive and display products under a defined category. This is the URL needed to display that data. Please, remember that the URL must start with <i>"%%clientURL%%"</i> in order to show your client base domain.</p>',N,
		T,T,'<div class=\'important\'>',$GLOBALS['blueCommerce']['categoryLink'],'</div>',N,
		T,T,'<i class=\'clear\'></i>',N,
		T,T,'</li>',N;

		$categories = category_getCategories(false,true);
		$categories = category_helper_parseCategoriesByLang($categories,'es-es');
		echo T,T,'<li>',N,'<h3>Categories availables</h3>',N,
		T,T,'<p>This is the list of the categories stored in the database for this languaje, you can add a new by clicking in this <a href=\'',$GLOBALS['baseURL_ASSIS'],'category_manage/add\'>link</a>.</p>',N,
		T,T,'<div class=\'tagList\'>',N;
		foreach($categories as $category){echo T,T,T,'<span class=\'tag category_',$category['id'],'\' onclick=\'assis.addCategory(this);\'><img src=\'g/images/assis/tag_eye.png\'/>',$category['categoryTitle'],'</span>',N;}
		echo T,T,'</div>',N,
		T,T,'<i class=\'clear\'></i>',N,
		T,T,'</li>',N;

		echo '</ul>',N;
	}
?>
