<?php
	echo '<ul class=\'assisOptions\'>',
		'<li><a href=\''.$GLOBALS['baseURL_currentASSIS'].'\'><img class=\'block icon16 gohome\' src=\'g/images/t.gif\'/></a></li>',
		'<li><a href=\'javascript:\' onclick=\'assis.products_listInfo(this);\'>Listar</a></li>',
		'<li><a href=\''.$GLOBALS['baseURL_currentASSIS'].'add\'>Crear nuevo producto</a></li>',
		'<li><a href=\''.$GLOBALS['baseURL_currentASSIS'].'last\'>Ver últimos</a></li>',
		'<li><a href=\''.$GLOBALS['baseURL_currentASSIS'].'search\'>Buscar</a></li>',
		'<li><a href=\''.$GLOBALS['baseURL_currentASSIS'].'searchByCategory\' onclick=\'form.searchAsURL(this,event);\'>Buscar (Categoria)</a></li>',
		'<li><a href=\''.$GLOBALS['baseURL_currentASSIS'].'csv\'>CSV</a></li>',
		'<li><a href=\''.$GLOBALS['baseURL_currentASSIS'].'delete\' onclick="if(!confirm(\'¿Está SEGURO que desea borrar TODOS los productos y TODAS las categorías?\')){return false;}">Borrar todos los productos y categorías</a></li>',
		'<li><a href=\''.$GLOBALS['baseURL_currentASSIS'].'setDefaultPortrait\' >Portrait by default</a></li>',
		
	'</ul>';

	//chdir('../resources/PHP/');
	include('API_product.php');
	include('API_status.php');

	function updateSchema($table = false){
		switch($table){
			case 'products':break;
			default:exit;
		}
		$r = product_updateSchemaByTable($table);
	}

	function helper_paintProductRow($p){
		common_array_register('products',$p);
		$p['clientURL'] = $GLOBALS['baseURL'];
		$icon = ($p['productParentID'] > 0) ? 'synaptic' : 'package-x-generic';
		if($p['productStatus'] == 0){$icon.='50o';}
		echo T,T,T,T,'<tr id=\'row_',$p['id'],'\'>',
		'<td><input type=\'checkbox\' class=\'checkbox\' value=\'',$p['id'],'\' onclick=\'assis.helper_checkThis(this);\' autocomplete="off"/></td>',
		'<td><a href=\'#\'>',$p['id'],'</a></td>',
		'<td><a href=\'#\'>',$p['productRelativeID'],'</a></td>',
		'<td class=\'icon16 ',$icon,'\'><a href=\'',(isset($GLOBALS['blueCommerce']['productLink']) ? common_replaceInTemplate($GLOBALS['blueCommerce']['productLink'],$p) : ''),'\'>',$p['productTitle'],'</a></td>',
		'<td><div>',$p['productParentID'],'</div></td>',
		'<td><div class=\'changeableField ',$p['id'],':productStatus\' ondblclick=\'form.changeField(event,this,API_product);\'>',$p['productStatus'],'</div></td>',
		'<td><div class=\'changeableField ',$p['id'],':productStock\' ondblclick=\'form.changeField(event,this,API_product);\'>',$p['productStock'],'</div></td>',
		'<td><div>',$p['productPrice'],'</div></td>',
		'<td><div>',$p['productCurrency'],'</div></td>',
		'<td><a href=\'',$GLOBALS['baseURL_currentASSIS'],'show/',$p['id'],'\'>editar</a></td></tr>',N;
	}
	
	function helper_tableHeader(){
		echo T,T,T,'<table><thead><tr><td style=\'width:1px;\'></td><td style=\'width:1px;\'><span>id</span></td><td style=\'width:1px;\'><span>SKU</span></td><td>productTitle</td><td style=\'width:1px;\'>parent</td><td style=\'width:1px;\'>status</td><td style=\'width:1px;\'>stock</td><td style=\'width:1px;\'>precio</td><td style=\'width:1px;\'>mon</td><td style=\'width:1px;\'></td></tr></thead><tbody>',N;
	}

	function helper_paintBaseProductOptions(){
		echo '<div><a href=\'javascript:\' onclick=\'assis.products_changeStatus(this.parentNode,"products");\'>Cambiar status</a></div>',
		'<div><a href=\'javascript:\' onclick=\'assis.products_changePriceManual(this.parentNode,"shippings");\'>Actualizar precio (manual)</a></div>';
	}

	function main(){
		$limit = $GLOBALS['currentPage'] * 20 - 20;
		//FIXME: $productStockWarningLimit al fichero de config
//FIXME: obtener los porcentajes de venta del producto para ver la severidad
//número de ventas por día mejor
//también quizá el numero de pageviews
		$rows = product_getProducts(false,true,$limit.',20','productStock < 20');
		$rows = product_helper_parseProductsByLang($rows,$GLOBALS['LANGCODE']);
		echo T,T,T,'<div class=\'block\'><h2>Productos que se están quedado sin stock</h2>',N,
		T,T,T,'<p>Listado de productos que se están quedando sin stock, puedes incrementar el valor del stock de un producto en cualquier momento. Algunos modelos de tienda no dependen del stock, si tu tienda pertenece a este tipo no necesitas prestar atención a esta tabla.</p>',N,
		helper_tableHeader();
		foreach($rows as $row){helper_paintProductRow($row);}
		echo T,T,T,'</tbody></table>',N,
		T,T,T,'<ul class=\'pager\'><li><a href=\'',$GLOBALS['baseURL_currentASSIS'],'last/page/',($GLOBALS['currentPage']-1),'/\'>prev</a></li><li>',$GLOBALS['currentPage'],'</li><li><a href=\'',$GLOBALS['baseURL_currentASSIS'],'last/page/',($GLOBALS['currentPage']+1),'/\'>next</a></li></ul>',
		T,T,T,'</div>',N;
	}

	function last($lang = 'default'){
		$limit = $GLOBALS['currentPage'] * 20 - 20;
		$rows = product_getProducts(false,true,$limit.',20');
		if(!in_array($lang,$GLOBALS['blueCommerce']['langAllowed'])){
//FIXME: hacer una solución más limpia
$lang = $GLOBALS['blueCommerce']['langFallback'];
//$lang = 'default';
		}
		$rows = product_helper_parseProductsByLang($rows,$lang);

		$GLOBALS['LANGCODE'] = $lang;
		echo T,T,T,'<div class=\'block\'><h2>Últimos productos que han sido añadidos</h2>',N,
		T,T,T,'<p>Consulta de forma rápida los ùltimos productos añadidos a la base de datos. Para consultas más complejas usa la opción de <a href=\'#\'>buscar</a>.</p>',N,
		T,T,T,'<p>También puedes ver esta tabla en los siguiente idiomas: <a href=\'',$GLOBALS['baseURL_currentASSIS'],'last/default\'>default</a> , ';
		foreach($GLOBALS['blueCommerce']['langAllowed'] as $langAllowed){echo '<a href=\'',$GLOBALS['baseURL_currentASSIS'],'last/'.$langAllowed.'\'>'.$langAllowed.'</a> , ';}
		echo '</p>',N,
		helper_tableHeader();
		foreach($rows as $row){helper_paintProductRow($row);}
		echo T,T,T,'</tbody></table>',N,
		T,T,T,'<div class=\'tableOptions\'>',
			'<div><a href=\'javascript:\' onclick=\'assis.products_changeStatus(this.parentNode,"products");\'>Cambiar status</a></div>',
			'<div><a href=\'javascript:\' onclick=\'assis.products_changePriceManual(this.parentNode,"shippings");\'>Actualizar precio (manual)</a></div>',
			'<div><a href=\'javascript:\' onclick=\'assis.products_changePhoto(this.parentNode,"products");\'>Imágenes (manual)</a></div>',
		T,T,T,'</div>',N,
		T,T,T,'<ul class=\'pager\'><li><a href=\'',$GLOBALS['baseURL_currentASSIS'],'last/page/',($GLOBALS['currentPage']-1),'/\'>prev</a></li><li>',$GLOBALS['currentPage'],'</li><li><a href=\'',$GLOBALS['baseURL_currentASSIS'],'last/page/',($GLOBALS['currentPage']+1),'/\'>next</a></li></ul>',
		T,T,T,'</div>',N;
	}

	function search($lang = 'default'){
		if(!in_array($lang,$GLOBALS['blueCommerce']['langAllowed'])){$lang = 'default';}
		echo T,T,T,'<div class=\'block\'><h2>Búsqueda de productos en la base de datos</h2>',N,
		T,T,T,'<p>Indica uno o varios criterios para realizar una búsqueda de productos relacionados. Pueden encontrarse en múltiples idiomas.</p>',N,
		T,T,T,T,'<form action=\'',$GLOBALS['baseURL_currentASSIS'],'search\' method=\'POST\'>',N,
		T,T,T,T,T,'<input type=\'text\' value=\'',(isset($_POST['criteria']) ? $_POST['criteria'] : ''),'\' name=\'criteria\'>',N,
		T,T,T,T,T,'<input type=\'submit\' value=\'buscar\'>',N,
		T,T,T,T,'</form>',N,
		T,T,T,'</div>',N;

		if(isset($_POST['criteria']) && !empty($_POST['criteria'])){
			$rows = product_search($_POST['criteria']);
			$rows = product_helper_parseProductsByLang($rows,$lang);

			echo T,T,T,'<div class=\'block\'><h2>Resultados de la búsqueda</h2>',N,
			T,T,T,'<p>Productos que contienen algunos elementos que coinciden con los criterios de búsqueda. Para concretar más la búsqueda añada más criterios dentro del buscador en la parte superior. <b>Puedes editar algunos elementos de esta tabla haciendo doble click sobre ellos.</b></p>',N,
			helper_tableHeader();
			foreach($rows as $row){helper_paintProductRow($row);}
			echo T,T,T,'</tbody></table>',N,
			T,T,T,'</div>',N;
		}

		if(!in_array($lang,$GLOBALS['blueCommerce']['langAllowed'])){$lang = 'default';}
	}

	function searchByCategory($categ){
		include_once('API_category.php');
echo $categ;
		$products = array();$category = category_getByTitleFixed($categ,false,true);
		if($category === false){
return false;
		}
		$categoryID = $category['id'];
		$products = product_getByCategoryIDs($categoryID,false,true);

		$products = product_helper_parseProductsByLang($products,$GLOBALS['LANGCODE']);
		echo T,T,T,'<div class=\'block\'><h2>Últimos productos que han sido añadidos</h2>',N,
		T,T,T,'<p>Consulta de forma rápida los ùltimos productos añadidos a la base de datos. Para consultas más complejas usa la opción de <a href=\'#\'>buscar</a>.</p>',N;
		echo helper_tableHeader();
		foreach($products as $product){helper_paintProductRow($product);}
		echo T,T,T,'</tbody></table>',N,
		T,T,T,'<div class=\'tableOptions\'>',
			'<div><a href=\'javascript:\' onclick=\'assis.products_changeStatus(this.parentNode,"products");\'>Cambiar status</a></div>',
			'<div><a href=\'javascript:\' onclick=\'assis.products_changePriceManual(this.parentNode,"shippings");\'>Actualizar precio (manual)</a></div>',
			'<div><a href=\'javascript:\' onclick=\'assis.products_removeProductFromCategory(this.parentNode,',$categoryID,');\'>Eliminar productos de la categoría</a></div>',
		T,T,T,'</div>',N,
		T,T,T,'<ul class=\'pager\'><li><a href=\'',$GLOBALS['baseURL_currentASSIS'],'last/page/',($GLOBALS['currentPage']-1),'/\'>prev</a></li><li>',$GLOBALS['currentPage'],'</li><li><a href=\'',$GLOBALS['baseURL_currentASSIS'],'last/page/',($GLOBALS['currentPage']+1),'/\'>next</a></li></ul>',
		T,T,T,'</div>',N;
	}

	function sidebar(){
		echo '<ul class=\'aBodySide\'>',N;

		echo T,T,'<li>',N,'<h3>Product Image Sizes</h3>',N,
		T,T,'<p>This are the sizes in availables for each product in the store. If you are a designer and need another sizes, you can add more to this list.</p>',N,
		T,T,'<div class=\'elemSimpleList\'>',N;
		foreach($GLOBALS['blueCommerce']['imageSizes'] as $size){
			echo T,T,T,'<span>',$size,'</span>',N;
		}
		echo T,T,'</div>',N,
		T,T,'<i class=\'clear\'></i>',N,
		T,T,'</li>',N;


		include_once('API_category.php');
		//FIXME: lang hardcoded
		$categories = category_getCategories('es-es',false,true);
		echo T,T,'<li>',N,'<h3>Categories availables</h3>',N,
		T,T,'<p>This is the list of the categories stored in the database for this languaje, you can add a new by clicking in this <a href=\'',$GLOBALS['baseURL_ASSIS'],'category_manage/add\'>link</a>.</p>',N,
		T,T,'<div class=\'tagList\'>',N;
		foreach($categories as $category){echo T,T,T,'<span class=\'tag category_',$category['id'],'\' onclick=\'assis.addCategory(this);\'><img src=\'g/images/assis/tag_eye.png\'/>',$category['categoryTitle'],'</span>',N;}
		echo T,T,'</div>',N,
		T,T,'<i class=\'clear\'></i>',N,
		T,T,'</li>',N;

		echo '</ul>',N;
	}

	function show($id = false,$track = false){
		$product = false;
		if($id !== false){$product = product_getById($id,false,true);}
		/* Generamos un producto dummy para rellenar los campos del formulario evitando los NOTICE */
		if($product === false){$product = array_fill_keys(array_keys($GLOBALS['tables']['products']),'');$product['id'] = '';unset($product['_id_']);}
		common_indexByLang($product['productTitle']);
		common_indexByLang($product['productPrice']);
		common_indexByLang($product['productCurrency']);
		common_indexByLang($product['productVat']);
		common_indexByLang($product['productVatUnit']);
		common_indexByLang($product['productOldPrice']);
//print_r($product);

		$cmbCurrency = '<select>';foreach($GLOBALS['CURRENCY'] as $n=>$s){$cmbCurrency .= '<option value=\''.$n.'\'>'.$n.' ( '.$s.' )'.'</option>';}$cmbCurrency .= '</select>';
		$cmbVat = str_replace('<select>','<select><option value=\'percentage\' selected="selected">%</option>',$cmbCurrency);

		echo T,T,T,'<div class=\'block\'>',N,
		T,T,T,T,'<h2>Editar propiedades del producto.</h2>',N,
		T,T,T,T,'<p>Estos son los dátos básicos necesarios para insertar un producto dentro de la plataforma. <a href=\'',$GLOBALS['baseURL_currentASSIS'],'show/',$id,'\'>Datos básicos</a> - <a href=\'',$GLOBALS['baseURL_currentASSIS'],'show/',$id,'/desc\'>Editar descripciones</a>',
		' - <a href=\'',$GLOBALS['baseURL_currentASSIS'],'show/',$id,'/categ\'>Categorías del producto</a> - <a href=\'',$GLOBALS['baseURL_currentASSIS'],'show/',$id,'/images\'>Imágenes del producto</a> - <a href=\'',$GLOBALS['baseURL_currentASSIS'],'show/',$id,'/other\'>Otros detalles</a></p>',N;
		switch($track){
			default:
				echo T,T,T,T,'<div class=\'productImage\'><img src=\'',$GLOBALS['clientURL'],'pi/',$id,'/200\'/></div>',N,
				T,T,T,T,'<form action=\'',$GLOBALS['baseURL_currentASSIS'],'save\' method=\'post\'>',N,
				T,T,T,T,'<input type=\'hidden\' name=\'id\' value=\'',$product['id'],'\'/>',N,

				T,T,T,T,'<table class=\'productTable\'><tbody>',N,
				T,T,T,T,T,'<tr><td><h6>Título del producto</h6><div class=\'blueInfo\'>El título del producto puede sufrir variaciones dependiendo del idioma en que se encuentre la tienda. Por ello es importante definir un título genérico además de las variantes de cada idioma. El título genérico se usará cuando no se encuentre disponible el título en el idioma solicitado.</div></td>',N,
				T,T,T,T,T,T,'<td>',N,
				T,T,T,T,T,T,T,'<table class=\'middle\'><tbody>',N;
				$langs = array_merge(array('default'),$GLOBALS['blueCommerce']['langAllowed']);
				foreach($langs as $lang){echo T,T,T,T,T,T,T,T,'<tr><td style=\'width:1px;\'>',$lang,'</td><td><div class=\'inputTextSimple\'><input type=\'text\' name=\'productTitle_',$lang,'\' value=\'',(isset($product['productTitle'][$lang]) ? $product['productTitle'][$lang] : ''),'\'/></div></td></tr>',N;}
				echo T,T,T,T,T,T,T,T,'<tr><td colspan=\'2\'><ul class=\'assisOptions\'><li><a href=\'#\'>Añadir nuevo idioma al listado</a></li></ul></td>',N,
				T,T,T,T,T,T,T,'</tbody></table>',N,
				T,T,T,T,T,T,'</td>',N,
				T,T,T,T,T,'</tr>',N,

				T,T,T,T,T,'<tr><td><h6>Precio del producto</h6><div class=\'blueInfo\'>El precio del producto puede variar dependiendo del idioma, por lo que es posible establecer un precio individual para cada región. Si el precio no existe en el idioma solicitado se usará el valor por defecto. <b>En caso de que el precio sea único para todas la regiones solo rellenar el valor por defecto</b>.</div></td>',N,
				T,T,T,T,T,T,'<td>',N,
				T,T,T,T,T,T,T,'<table class=\'middle\'><tbody>',N;
				$langs = array_merge(array('default'),$GLOBALS['blueCommerce']['langAllowed']);
				foreach($langs as $lang){
					$currencySelect = str_replace('<select>','<select name=\'productCurrency_'.$lang.'\'>',$cmbCurrency);
					if(isset($product['productCurrency'][$lang])){$currencySelect = str_replace('<option value=\''.$product['productCurrency'][$lang].'\'>','<option value=\''.$product['productCurrency'][$lang].'\' selected="selected">',$currencySelect);}
					echo T,T,T,T,T,T,T,T,'<tr><td style=\'width:1px;\'>',$lang,'</td><td><div class=\'inputTextSimple\'><input type=\'text\' name=\'productPrice_',$lang,'\' value=\'',(isset($product['productPrice'][$lang]) ? $product['productPrice'][$lang] : ''),'\'/></div></td><td style=\'width:1px;\'>',$currencySelect,'</td></tr>',N;
				}
				echo T,T,T,T,T,T,T,T,'<tr><td colspan=\'3\'><ul class=\'assisOptions\'><li><a href=\'#\'>Añadir nuevo precio al listado</a></li></ul></td>',N,
				T,T,T,T,T,T,T,'</tbody></table>',N,
				T,T,T,T,T,T,'</td>',N,
				T,T,T,T,T,'</tr>',N,

				T,T,T,T,T,'<tr><td><h6>Retenciones del producto</h6><div class=\'blueInfo\'>Las retenciones son los impuestos tributados en cada pais que hay que aplicar al precio de venta del producto.</div></td>',N,
				T,T,T,T,T,T,'<td>',N,
				T,T,T,T,T,T,T,'<table class=\'middle\'><tbody>',N;
				$langs = array_merge(array('default'),$GLOBALS['blueCommerce']['langAllowed']);
				foreach($langs as $lang){
					$vatUnitSelect = str_replace('<select>','<select name=\'productVatUnit_'.$lang.'\'>',$cmbVat);
					if(isset($product['productVatUnit'][$lang])){$vatUnitSelect = str_replace('<option value=\''.$product['productVatUnit'][$lang].'\'>','<option value=\''.$product['productVatUnit'][$lang].'\' selected="selected">',$vatUnitSelect);}
					echo T,T,T,T,T,T,T,T,'<tr><td style=\'width:1px;\'>',$lang,'</td><td><div class=\'inputTextSimple\'><input type=\'text\' name=\'productVat_',$lang,'\' value=\'',(isset($product['productVat'][$lang]) ? $product['productVat'][$lang] : ''),'\'/></div></td><td style=\'width:1px;\'>',$vatUnitSelect,'</td></tr>',N;
				}
				echo T,T,T,T,T,T,T,T,'<tr><td colspan=\'3\'><ul class=\'assisOptions\'><li><a href=\'#\'>Añadir nueva retención al listado</a></li></ul></td>',N,
				T,T,T,T,T,T,T,'</tbody></table>',N,
				T,T,T,T,T,T,'</td>',N,
				T,T,T,T,T,'</tr>',N,
T,T,T,T,T,'<input type=\'hidden\' name=\'next\' id=\'next\' value=\'\' />',N,
T,T,T,T,T,'<tr><td class=\'submit\' colspan=\'2\'><input type=\'submit\' value=\'Guardar producto\'/><input type=\'button\' onclick="$_(\'next\').value = \'desc\';form.submit();" value=\'Siguiente\' /></td></tr>',N,
				T,T,T,T,'</tbody></table>',N,
				T,T,T,T,'</form>',N;
			break;
			case 'categ':
				//print_r($product);
				include_once('API_category.php');
				$categories = category_getCategories(false,true);
				$productCategories = array_diff(explode(',',$product['productCategories']),array(''));
				$productCategories = array_fill_keys($productCategories,'');
				$categories = category_helper_parseCategoriesByLang($categories,$GLOBALS['LANGCODE']);
				echo T,T,T,T,'<form action=\'',$GLOBALS['baseURL_currentASSIS'],'save\' method=\'post\'>',N,
				T,T,T,T,'<input type=\'hidden\' name=\'id\' value=\'',$product['id'],'\'/>',N,
				T,T,T,T,'<input type=\'hidden\' name=\'productCategories\' value=\'\'/>',N,
				T,T,T,T,'<div>',N;
				foreach($categories as $category){
					echo '<div><input class=\'checkbox\' type=\'checkbox\' name=\'productCategory_',$category['id'],'\' autocomplete="off" ',((isset($productCategories[$category['id']])) ? 'checked="checked"' : ''),'/>',$category['categoryTitle'],'</div>';
				}
				echo T,T,T,T,'</div>',N,
'<input type=\'hidden\' name=\'next\' id=\'next\' value=\'\' />',N,
'<input type=\'submit\' value=\'Guardar producto\'/><input type=\'button\' onclick="$_(\'next\').value = \'images\';form.submit();" value=\'Siguiente\' />',N,
				'</form>',N;
				//print_r($categories);
				break;
			case 'desc':
				common_indexByLang($product['productDescription']);
				//print_r($product);
				echo T,T,T,T,'<form action=\'',$GLOBALS['baseURL_currentASSIS'],'save\' method=\'post\'>',N,
				T,T,T,T,'<input type=\'hidden\' name=\'id\' value=\'',$product['id'],'\'/>',N,

				T,T,T,T,'<table class=\'productTable\'><tbody>',N,
				T,T,T,T,T,'<tr><td><h6>Descripciones del Producto</h6><div class=\'blueInfo\'>asd</div></td></tr>',N;
				$langs = array_merge(array('default'),$GLOBALS['blueCommerce']['langAllowed']);
				foreach($langs as $lang){
				//foreach($product['productDescription'] as $lang=>$description){
					echo T,T,T,T,T,'<tr><td><h6>',$lang,'</h6><div class=\'inputTextSimple\'><textarea style=\'height:150px;\' name=\'productDescription_',$lang,'\'>',(isset($product['productDescription'][$lang]) ? $product['productDescription'][$lang] : ''),'</textarea></div></td></tr>',N;
				}

echo T,T,T,T,T,'<input type=\'hidden\' name=\'next\' id=\'next\' value=\'\' />',N,
T,T,T,T,T,'<tr><td class=\'submit\' colspan=\'2\'><input type=\'submit\' value=\'Guardar producto\'/><input type=\'button\' onclick="$_(\'next\').value = \'categ\';form.submit();" value=\'Siguiente\' /></td></tr>',N,
				T,T,T,T,'</tbody></table>',N,
				T,T,T,T,'</form>',N;
			break;
			case 'images':
				echo T,T,T,T,'<form action=\'',$GLOBALS['baseURL_currentASSIS'],'appendImage/',$product['id'],'\' method=\'post\'>',N,
				T,T,T,T,T,'<div style=\'box-shadow:0px 1px 2px #000;min-width:120px;min-height:160px;\' ondragover=\'assis.imgDragOver(event);\' ondrop=\'assis.imgDrop(event,this);\'/></div>',N,
				T,T,T,T,T,'<table><tbody id=\'uploadList\'></tbody></table>',N,
				//T,T,T,T,T,'<input type=\'submit\' value=\'Añadir imagen\'>',N,
				
				T,T,T,T,T,'<input type=\'hidden\' name=\'next\' id=\'next\' value=\'\' />',N,
				T,T,T,T,T,'<tr><td class=\'submit\' colspan=\'2\'><input type=\'submit\' value=\'Añadir imagen\'/><input type=\'button\' onclick="$_(\'next\').value = \'other\';form.submit();" value=\'Siguiente\' /></td></tr>',N,
				
				T,T,T,T,'</form>',N,
				T,T,T,'</div>',N;

				$productGalleryPath = '../clients/'.$GLOBALS['CLIENT'].'/db/products/'.$product['id'].'/gallery/orig/';
				if(file_exists($productGalleryPath)){
					$files = array();if($handle = opendir($productGalleryPath)){while(false !== ($file = readdir($handle))){if(($file[0]!='.')){$files[] = $file;}}closedir($handle);}
					echo T,T,T,'<div class=\'block\'>',N,
					T,T,T,T,'<h2>Imágenes del producto</h2>',N,
					T,T,T,T,'<p>Estas son las diferentes imágenes que existen en la galería del producto.</p>',N,
					T,T,T,T,'<table><thead><tr><td>preview</td><td><span>fileName</span></td><td>portrait</td></tr></thead><tbody>',N;
					foreach($files as $file){
						$jpgfile = preg_replace('/(\.png|\.gif|\.jpg)$/','.jpeg',$file);
						echo T,T,T,T,'<tr><td><div><a href=\'',$GLOBALS['clientURL'],'pi/',$product['id'],'/orig/',$file,'\'><img src=\'',$GLOBALS['baseURL'],'pi/',$product['id'],'/32/',$jpgfile,'\'/></a></div></td>',
						'<td>',$file,'</td>',
						'<td><a href=\'',$GLOBALS['baseURL_currentASSIS'],'imagePortrait/',$product['id'],'/',$file,'\'/>set as portrait</a></td>',
						'<td style=\'width:1px;\'><a href=\'',$GLOBALS['baseURL_currentASSIS'],'imageRemove/',$product['id'],'/',$file,'\'/>eliminar</a></td>',
						'</tr>',N;
					}
					echo T,T,T,T,'</tbody></table>',N,
					T,T,T,'</div>',N;
				}
			break;
			case 'other':
				echo T,T,T,T,'<form action=\'',$GLOBALS['baseURL_currentASSIS'],'save\' method=\'post\'>',N,
				T,T,T,T,'<input type=\'hidden\' name=\'id\' value=\'',$product['id'],'\'/>',N,

				T,T,T,T,'<table class=\'productTable\'><tbody>',N,

				T,T,T,T,T,'<tr><td><h6>Precio Anterior</h6><div class=\'blueInfo\'>Algunos productos muestran un precio tachado indicando que era el precio anterior del producto, esa información se guarda en este campo.</div></td>',N,
				T,T,T,T,T,T,'<td>',N,
				T,T,T,T,T,T,T,'<table class=\'middle\'><tbody>',N;
				$langs = array_merge(array('default'),$GLOBALS['blueCommerce']['langAllowed']);
				foreach($langs as $lang){echo T,T,T,T,T,T,T,T,'<tr><td style=\'width:1px;\'>',$lang,'</td><td><div class=\'inputTextSimple\'><input type=\'text\' name=\'productOldPrice_',$lang,'\' value=\'',(isset($product['productOldPrice'][$lang]) ? $product['productOldPrice'][$lang] : ''),'\'/></div></td></tr>',N;}
				echo T,T,T,T,T,T,T,T,'<tr><td colspan=\'2\'><ul class=\'assisOptions\'><li><a href=\'#\'>Añadir nuevo idioma al listado</a></li></ul></td>',N,
				T,T,T,T,T,T,T,'</tbody></table>',N,
				T,T,T,T,T,T,'</td>',N,
				T,T,T,T,T,'</tr>',N,

				T,T,T,T,T,'<tr><td><h6>Peso del producto</h6><div class=\'blueInfo\'>Es recomentable darle un valor a este campo en todos los productos, aunque no parezca importante, si la tienda cambia su sistema de cálculo de costes de envío a peso, ese coste se basará principalmente en el valor de este campo.</div></td>',N,
				T,T,T,T,T,T,'<td>',N,
				T,T,T,T,T,T,T,'<table class=\'middle\'><tbody>',N;
				echo T,T,T,T,T,T,T,T,'<tr><td style=\'width:1px;\'>Gramos</td><td><div class=\'inputTextSimple\'><input type=\'text\' name=\'productWeight\' value=\'',(isset($product['productWeight']) ? $product['productWeight'] : ''),'\'/></div></td></tr>',N;
				echo T,T,T,T,T,T,T,T,'<tr><td colspan=\'2\'><ul class=\'assisOptions\'><li><a href=\'#\'>Añadir nuevo idioma al listado</a></li></ul></td>',N,
				T,T,T,T,T,T,T,'</tbody></table>',N,
				T,T,T,T,T,T,'</td>',N,
				T,T,T,T,T,'</tr>',N;

				$parents = product_getProducts(false,true,false,'(productParentID = 0)','id DESC');
				$parents = product_helper_parseProductsByLang($parents,$GLOBALS['LANGCODE']);
				$parentsSelect = '<select name=\'productParentID\' class=\'parentSelect\'><option value=\'0\'></option>';foreach($parents as $parent){$parentsSelect .= '<option value=\''.$parent['id'].'\'>'.$parent['productTitle'].'</option>';}$parentsSelect .= '</select>';
				if(isset($product['productParentID'])){$parentsSelect = str_replace('<option value=\''.$product['productParentID'].'\'>','<option value=\''.$product['productParentID'].'\' selected="selected">',$parentsSelect);}
				echo T,T,T,T,T,'<tr><td><h6>Heredar las propiedades de otro producto</h6><div class=\'blueInfo\'>Todas las propiedades que estén vacías serán heredadas del producto padre. <b>Los controladores de la tienda deben soportar esta característica</b></div></td>',N,
				T,T,T,T,T,T,'<td>',N,
				T,T,T,T,T,T,T,'<table class=\'middle\'><tbody>',N,
				T,T,T,T,T,T,T,T,'<tr><td style=\'width:1px;\'>Producto</td><td>',$parentsSelect,'</div></td></tr>',N,
				T,T,T,T,T,T,T,'</tbody></table>',N,
				T,T,T,T,T,T,'</td>',N,
				T,T,T,T,T,'</tr>',N;

				echo T,T,T,T,T,'<tr><td class=\'submit\' colspan=\'2\'><input type=\'submit\' value=\'Guardar producto\'/></td></tr>',N,
				T,T,T,T,'</tbody></table>',N,
				T,T,T,T,'</form>',N;
			break;
		}
		echo T,T,T,'</div>',N;
	}

	function add(){
		$cmbCurrency = '<select>';foreach($GLOBALS['CURRENCY'] as $n=>$s){$cmbCurrency .= '<option value=\''.$n.'\'>'.$n.' ( '.$s.' )'.'</option>';}$cmbCurrency .= '</select>';
		$cmbVat = str_replace('<select>','<select><option value=\'percentage\' selected="selected">%</option>',$cmbCurrency);

		echo T,T,T,'<div class=\'block\'>',N,
		T,T,T,T,'<h2>Crear un nuevo producto dentro de la tienda</h2>',N,
		T,T,T,T,'<p>Estos son los dátos básicos necesarios para insertar un producto dentro de la plataforma.</p>',N,
		T,T,T,T,'<form action=\'',$GLOBALS['baseURL_currentASSIS'],'save\' method=\'post\'>',N,
		T,T,T,T,'<table class=\'productTable\'><tbody>',N,
		T,T,T,T,T,'<tr><td><h6>Título del producto</h6><div class=\'blueInfo\'>El título del producto puede sufrir variaciones dependiendo del idioma en que se encuentre la tienda. Por ello es importante definir un título genérico además de las variantes de cada idioma. El título genérico se usará cuando no se encuentre disponible el título en el idioma solicitado.</div></td>',N,
		T,T,T,T,T,T,'<td>',N,
		T,T,T,T,T,T,T,'<table class=\'middle\'><tbody>',N;
		$langs = array_merge(array('default'),$GLOBALS['blueCommerce']['langAllowed']);
		foreach($langs as $lang){echo T,T,T,T,T,T,T,T,'<tr><td style=\'width:1px;\'>',$lang,'</td><td><div class=\'inputTextSimple\'><input type=\'text\' name=\'productTitle_',$lang,'\' value=\'\'/></div></td></tr>',N;}
		echo T,T,T,T,T,T,T,T,'<tr><td colspan=\'2\'><ul class=\'assisOptions\'><li><a href=\'#\'>Añadir nuevo idioma al listado</a></li></ul></td>',N,
		T,T,T,T,T,T,T,'</tbody></table>',N,
		T,T,T,T,T,T,'</td>',N,
		T,T,T,T,T,'</tr>',N,

		T,T,T,T,T,'<tr><td><h6>Precio del producto</h6><div class=\'blueInfo\'>El precio del producto puede variar dependiendo del idioma, por lo que es posible establecer un precio individual para cada región. Si el precio no existe en el idioma solicitado se usará el valor por defecto. <b>En caso de que el precio sea único para todas la regiones solo rellenar el valor por defecto</b>.</div></td>',N,
		T,T,T,T,T,T,'<td>',N,
		T,T,T,T,T,T,T,'<table class=\'middle\'><tbody>',N;
		$langs = array_merge(array('default'),$GLOBALS['blueCommerce']['langAllowed']);
		foreach($langs as $lang){echo T,T,T,T,T,T,T,T,'<tr><td style=\'width:1px;\'>',$lang,'</td><td><div class=\'inputTextSimple\'><input type=\'text\' name=\'productPrice_',$lang,'\' value=\'\'/></div></td><td style=\'width:1px;\'>',str_replace('<select>','<select name=\'productCurrency_'.$lang.'\'>',$cmbCurrency),'</td></tr>',N;}
		echo T,T,T,T,T,T,T,T,'<tr><td colspan=\'3\'><ul class=\'assisOptions\'><li><a href=\'#\'>Añadir nuevo precio al listado</a></li></ul></td>',N,
		T,T,T,T,T,T,T,'</tbody></table>',N,
		T,T,T,T,T,T,'</td>',N,
		T,T,T,T,T,'</tr>',N,

		T,T,T,T,T,'<tr><td><h6>Retenciones del producto</h6><div class=\'blueInfo\'>Las retenciones son los impuestos tributados en cada pais que hay que aplicar al precio de venta del producto.</div></td>',N,
		T,T,T,T,T,T,'<td>',N,
		T,T,T,T,T,T,T,'<table class=\'middle\'><tbody>',N;
		$langs = array_merge(array('default'),$GLOBALS['blueCommerce']['langAllowed']);
		foreach($langs as $lang){echo T,T,T,T,T,T,T,T,'<tr><td style=\'width:1px;\'>',$lang,'</td><td><div class=\'inputTextSimple\'><input type=\'text\' name=\'productVat_',$lang,'\' value=\'\'/></div></td><td style=\'width:1px;\'>',str_replace('<select>','<select name=\'productVatUnit_'.$lang.'\'>',$cmbVat),'</td></tr>',N;}
		echo T,T,T,T,T,T,T,T,'<tr><td colspan=\'3\'><ul class=\'assisOptions\'><li><a href=\'#\'>Añadir nueva retención al listado</a></li></ul></td>',N,
		T,T,T,T,T,T,T,'</tbody></table>',N,
		T,T,T,T,T,T,'</td>',N,
		T,T,T,T,T,'</tr>',N,
T,T,T,T,T,'<input type=\'hidden\' name=\'next\' id=\'next\' value=\'\' />',N,
		T,T,T,T,T,'<tr><td class=\'submit\' colspan=\'2\'><input type=\'submit\' value=\'Guardar producto\'/><input type=\'button\' onclick="$_(\'next\').value = \'desc\';form.submit();" value=\'Siguiente\' /></td></tr>',N,

		T,T,T,T,'</tbody></table>',N,
		T,T,T,T,'</form>',N,
		T,T,T,'</div>',N;
		//show();
	}

	function save(){
		if(count($_POST) < 1){
			echo 'error';
			return;
		}
		$r = product_save($_POST,false,true);
		if($r['errorCode'] === 0){
			if(isset($_POST['next']) && $_POST['next'] != ''){header('Location: '.$GLOBALS['baseURL_currentASSIS'].'show/'.$r['data']['id'].'/'.$_POST['next']);exit;}
			header('Location: '.$GLOBALS['baseURL_currentASSIS'].'last');exit;
		}
		else{print_r($r);}
	}

	function appendImage($id){
		if(count($_POST) < 1){return;}
		foreach($_POST as $k=>$p){
			if(substr($k,0,4) != 'file'){continue;}
			$r = product_image_append($id,$_POST[$k]);
		}
		if($_POST['next'] != ''){header('Location: '.$GLOBALS['baseURL_currentASSIS'].'show/'.$id.'/'.$_POST['next']);exit;}
		header('Location: '.$GLOBALS['baseURL_currentASSIS'].'show/'.$id.'/images');exit;
		echo 'OK';
	}

	function imagePortrait($id,$file){
		$r = product_image_toPortrait($id,$file);
print_r($r);
	}
	function imageRemove($id,$file){
$r = product_image_remove($id,$file);
print_r($r);
	}

	function getByRelativeID($relID){
		//FIXME: hay que mostrar varios datos sobre este producto.
		//FIXME: como las estadísticas de compra, el % de carros en el que aparece y alguna especie de evolución
		$row = product_getByRelativeID($relID,false,true);
		print_r($row);
	}

	function csv($mode = false,$step = 1){
		//print_r($_FILES);
		if($mode == 'import' && isset($_FILES['csv']['tmp_name']) && $_FILES['csv']['tmp_name'] != '' && $step == 1){
			include_once('inc_strings.php');
			include_once('API_product.php');
			include_once('API_category.php');
			
			$catSelect = '';$cats = category_helper_parseCategoriesByLang(category_getCategories(false,true),$GLOBALS['LANGCODE']);foreach($cats as $cv){$catSelect .= '<option value="'.$cv['id'].'">'.$cv['categoryTitle'].'</option>'.N;}
			
			echo '<div style="display: none;" id="catOptions">'.$catSelect.'</div>';
			
			$tmpFile = '/tmp/'.time();file_put_contents($tmpFile,utf8_encode(file_get_contents($_FILES['csv']['tmp_name'])));$fp = fopen($tmpFile, 'r');
			while (($line = fgetcsv($fp, 0, $_POST['csvDelimiter'], $_POST['csvEnclosure'])) !== FALSE) {
				$line = array_diff($line, array(''));
				if(count($line) > 0 ){
					$newLine = array();while(is_array($newLine) && count(array_diff($newLine, array(''))) == 0){$newLine = fgetcsv($fp, 0, $_POST['csvDelimiter'], $_POST['csvEnclosure']);}
					echo '<form method=\'post\' action=\'',$GLOBALS['baseURL_currentASSIS'],'csv/import/2/\'>',N,
						'<input type="hidden" name="csvFile" value="'.$tmpFile.'" />',N,
						'<input type="hidden" name="csvDelimiter" value=\''.$_POST['csvDelimiter'].'\' />',N,
						'<input type="hidden" name="csvEnclosure" value=\''.$_POST['csvEnclosure'].'\' />',N,
						'<table><tbody>';
					foreach ($line as $k=>$v) {
						$select = '<select name="'.strings_stringToURL($v).'" onchange="setCategory(this)"><option value="">Ignorar</option>'.N;foreach(array_keys($GLOBALS['tables']['products']) as $val){$select .= '<option>'.$val.'</option>'.N;}$select .= '<option value="image">Image URL</option>';$select .= '</select>';
						echo '<tr><td>'.utf8_decode($v).' <span style="color: #aaa;">('.strip_tags($newLine[$k]).')</span></td><td>'.$select.'</td><td><div id="cat'.$v.'"></div></td></tr>',N;
					}
					echo '<tr><td><input type="checkbox" name="kiloWeight" /></td><td colspan="2">Marcar si el peso está en kilogramos en lugar de gramos</td></tr>';
					echo '<tr><td><input type="submit" value="Importar" /></td><td></td><td></td></tr>';
					echo '</tbody></table></form>';
					break;

				}
			}
			fclose($fp);
		}
		
		if(count($_POST) > 0 && $step == 2){
		//echo '<pre>';print_r($_POST);exit;
			set_time_limit(300);
			include_once('inc_strings.php');
			include_once('API_product.php');
			include_once('API_category.php');
			$csvFile = $_POST['csvFile'];
			
			$fp = fopen($csvFile, 'r');
			$fields = false;
			$values = array();
			$categories = array();
			$cExists = category_helper_parseCategoriesByLang(category_getCategories(false,true),'default');
			$cEx = array();foreach ($cExists as $k=>$v){$cEx[] = $v['categoryTitle'];}

			while (($line = fgetcsv($fp, 0, $_POST['csvDelimiter'], $_POST['csvEnclosure'])) !== FALSE){
				if(count(array_diff($line, array(''))) == 0){continue;} 
				if($fields === false){
					//Rellenamos el array de fields con los títulos de los campos
					$fields = $line;
					foreach($fields as $k=>$f){$fields[$k] = strings_stringToURL($f);}
					continue;
				}
				
				$v = array();
				//FIXME: Optimizar
				$total = count($fields);
				for($i=0; $i<$total;$i++){
					if($_POST[$fields[$i]] != ''){
						$t = utf8_decode(trim($line[$i]));
						$f = $_POST[$fields[$i]];
						
						//Generar categorías
						if($f == 'productCategories'){
							if(!isset($v[$f])){$v[$f] = '';}
							//Comprobar si existe categoria e insertar
							$cats = explode(',',$t);
							foreach($cats as $value){
								$val = trim($value);
								if($val == ''){continue;}
								if(!in_array($val, $cEx) && !in_array(strings_stringToURL($val), array_keys($categories))){
									$c = array('categoryTitle_default' => $val,'categoryTitle_es-es' => $val);
									$cat = category_helper_parseCategoriesByLang(array(category_save($c,false,true)),'default');
									$categories[$cat[0]['categoryTitleFixed']] = $cat[0]['id'];
									$v[$f] .= ','.$categories[strings_stringToURL($val)].',';
								}else{
									$c = category_getByTitleFixed(strings_stringToURL($val),false,true);
									$v[$f] .= ','.$c['id'].',';
								}
							}
							
							$v[$f] = str_replace(',,', ',', $v[$f]);
							continue;
						}
						
						//Campos con etiquetas de idioma
						if($f=='productTitle'||$f=='productPrice'||$f=='productCurrency'||$f=='productVat'||$f=='productVatUnit'||$f=='productDescription'||$f=='productOldPrice'){
							if($f=='productPrice'||$f=='productVat'||$f=='productOldPrice'){$t=str_replace(',','.',$t);}
							if(preg_match('/^<div lang.*?<\/div>$/', $t)){
								//Tiene etiquetas de idioma
								common_indexByLang($t);foreach($line[$i] as $k=>$val){$v[$f.'_'.$k] = $val;}continue;
							}
							$v[$f.'_default'] = trim(str_replace('&nbsp',' ',$t));unset($v[$f]);continue;
						}
						
						
						//Generar campo de dimensiones
						if($f=='productDimensions'){
							if(!isset($v[$f])){$v[$f] = '';}
							$v[$f] .= '*'.$t.'*';
							$v[$f] = str_replace('**','*',$v[$f]);
						}
						
						$v[$f] = $t;
					}
				}$values[] = $v;
			}
			
			//Cargar en la base de datos
			include_once('inc_databaseSqlite3.php');
			$db = new sqlite3($GLOBALS['db']['product']);
			
			$allProducts = product_getProducts($db,true);
			ksort($allProducts);
			$allProducts = array_values($allProducts);
			
			$prodIndex = array();$cats = array();
			foreach($allProducts as $key => $value){
				if($value['productRelativeID'] != ''){
					$prodIndex[$value['productRelativeID']]['id'] = $value['id'];
					if(isset($value['productCategories'])){
						$prodIndex[$value['productRelativeID']]['cats'] = $value['productCategories'];
					}
					//$prodIndex[$value['id']] = $value['productRelativeID'];
				} 
				//else {
				//	$prodIndex[$value['id']] = preg_replace('/<div lang=\'default\'>([^<]*)<\/div>/','$1',$value['productTitleFixed']);
				//}
			}
			include_once('inc_strings.php');
				//print_r($values);echo "\n"; //Nuevos
				//print_r($prodIndex);echo "\n"; //Viejos
				//exit;
			foreach ($values as $k=>$p){
				if(isset($p['productRelativeID'])){$values[$k]['productRelativeID'] = str_replace(' ', '_', $p['productRelativeID']);}
				if(isset($p['productRelativeID']) && isset($prodIndex[$p['productRelativeID']])){
					$values[$k]['id'] = $p['id'] = $prodIndex[$p['productRelativeID']]['id'];
					//Concatenamos las categorías viejas a las nuevas
					if(isset($p['productCategories'])){
						$cats = $p['productCategories'] . $prodIndex[$p['productRelativeID']]['cats'];
						$c = explode(',',$cats);$c = array_diff($c,array(''));
						$cn = explode(',', $p['productCategories']);$cn = array_diff($cn,array(''));
						
						$c = array_unique(array_merge($c,$cn));
						
						$cats = implode(',', $c);
						$cats = ','.$cats.',';
						$p['productCategories'] = $cats;
						$values[$k]['productCategories'] = $cats;
						//print_r($cats);
					}
					
					//if(($prodId = array_search($p['productRelativeID'],$prodIndex)) !== false){
					//	$values[$k]['id'] = $p['id'] = $prodId;
					//}
				}
				//else {
				//	$values[$k]['productTitleFixed'] = $p['productTitleFixed'] = strings_stringToURL($p['productTitle_default']);
				//	if(($prodId = array_search($p['productTitleFixed'],$prodIndex)) !== false){
				//		$values[$k]['id'] = $p['id'] = $prodId;
				//	}
				//}
 				//Comprobar si existe de otra manera
 				if(!isset($p['productStatus'])){$p['productStatus']=1;}
 				//print_r($p);
				$r = product_save($p,$db,true);
				if(isset($r['errorDescription'])){
					//echo '<pre>';
					//print_r($p);
					//print_r($r);
					//echo '</pre>';
				}
				if(isset($r['data'])){$values[$k]['id'] = $r['data']['id'];}
			}
			$db->close();
			
			foreach ($values as $k=>$p){
				if(isset($p['image']) && $p['image'] != '' && !file_exists('../clients/'.$GLOBALS['CLIENT'].'/db/products/'.$p['id'])){
					$imgTypes = array('jpg','png','gif','peg');
					if(!in_array(strtolower(substr($p['image'],-3,3)),$imgTypes)){continue;}
					$pId = $p['id'];
					$images = array_diff(explode(',',$p['image']),array(''));
					
					foreach ($images as $k=>$v){
						$image = trim($v);
						$imn = product_image_appendFromUrl($pId,$image,true);
						if($imn){product_image_toPortrait($pId,$imn);}
					}
				}
			}
			//unlink($csvFile);
		}
	
	
		//if(!in_array($mode,array('export','import'))){$mode = 'import';}
//FIXME: si csv es import procesar el fichero
//print_r($_FILES);
		
		switch($mode){
			case 'export':
				ob_end_clean();
				$products = product_getProducts(false,true);
				header('Content-type: text/csv');  
				header('Cache-Control: no-store, no-cache');  
				header('Content-Disposition: attachment; filename="products.csv"');
				$header = '';$setHeader = true;
				$products = product_helper_parseProductsByLang($products,$GLOBALS['LANGCODE']);
				foreach($products as $k=>$v){
					$line = '';
					foreach($v as $j=>$l){
						if($setHeader){
							$header .= '"'.str_replace('"','\"',$j).'";';
						}
						$l = str_replace('"','\"',$l);
						$l = preg_replace('/\r\n/',' ',$l);
						$line .= '"'.$l.'";';
					}
					$header.='productUrl;';
					preg_match('/<div lang=\'default\'>(.*?)<\/div>/ms',$v['productTitleFixed'],$m);$pFixed = $m[1];
					$line .= '"'.$GLOBALS['clientURL'].'p/'.$v['id'].'/'.$pFixed.'";';
					$line = substr($line,0,-1);
					if($setHeader){
						$header = substr($header,0,-1);
						$setHeader = false;
						echo $header,"\n";
					}
					echo $line,"\n";
				}
				exit;
				break;
			case 'import':
			default:
				echo T,T,T,'<h2>Export CSV</h2>',N,
				T,T,T,'<p>Export your products via CSV.</p>',N,
				T,T,T,'<div><a href=\'',$GLOBALS['baseURL_currentASSIS'],'csv/export\'>export</a></div>',N;

				echo T,T,T,'<h2>Import CSV</h2>',N,
				T,T,T,'<p>Import your products via CSV.</p>',N,
				T,T,T,'<form action=\'',$GLOBALS['baseURL_currentASSIS'],'csv/import\' method=\'post\' enctype=\'multipart/form-data\'>',N,
				T,T,T,T,'<div>Delimitador de campo <input type="text" name="csvDelimiter" value=";" /></div>',N,
				T,T,T,T,'<div>Cierre de campo <input type="text" name="csvEnclosure" value=\'"\' /></div>',N,
				T,T,T,T,'<div>File <input name=\'csv\' type=\'file\'/></div>',N,
				T,T,T,T,'<div><input type=\'submit\' value=\'send\'/></div>',N,
				T,T,T,'</form>',N;
				break;
		}
	}
	
	function delete() {
		$db = new sqlite3($GLOBALS['db']['product']);
		$db->exec('delete from products;');
		/*/
		/* No borramos la secuencia para que un producto nuevo no coja una id que otro producto tuvo antes
		/*/
		//$db->exec('delete from sqlite_sequence where name = \'products\';');
		
		$db->exec('delete from categories where categoryTitle not like \'%system_%\';');
		//$db->exec('delete from sqlite_sequence where name = \'categories\';');
		$db->close();
		
		echo 'Todos los productos han sido borrados.';
	}

	function listWithoutImages(){
		$limit = $GLOBALS['currentPage'] * 20 - 20;
		$pool = '../clients/'.$GLOBALS['CLIENT'].'/db/products/';
		$folders = '';if($handle = opendir($pool)){while(false !== ($file = readdir($handle))){if(($file[0]!='.')){$folders[] = $file;}}closedir($handle);}
		$noImage = array();foreach($folders as $folder){if(count(glob($pool.$folder.'/*')) === 0){$noImage[] = $folder;}}

		$products = product_getByIDs($noImage,false,true);
		$products = product_helper_parseProductsByLang($products,$GLOBALS['LANGCODE']);
		echo T,T,T,'<div class=\'block\'><h2>Productos que actualmente no tienen ninguna imagen</h2>',N,
		T,T,T,'<p>Es posible que se haya insertado algun producto y se haya olvidado añadirle alguna imáge, o que las opciones de importación de CSV no fueran capaces de recuperar las imágenes correctamente.</p>',N;
		helper_tableHeader();
		foreach($products as $product){helper_paintProductRow($product);}
		echo T,T,T,'</tbody></table>',N,
		T,T,T,'<div class=\'tableOptions\'>';
		helper_paintBaseProductOptions();
		echo T,T,T,'</div>',N,
		T,T,T,'<ul class=\'pager\'><li><a href=\'',$GLOBALS['baseURL_currentASSIS'],'last/page/',($GLOBALS['currentPage']-1),'/\'>prev</a></li><li>',$GLOBALS['currentPage'],'</li><li><a href=\'',$GLOBALS['baseURL_currentASSIS'],'last/page/',($GLOBALS['currentPage']+1),'/\'>next</a></li></ul>',
		T,T,T,'</div>',N;
	}
	
	function setDefaultPortrait(){
		$pool = '../clients/'.$GLOBALS['CLIENT'].'/db/products/';
		
		if($handle = opendir($pool)){
			while(false !== ($file = readdir($handle))){
				if(($file[0]!='.' && count(glob($pool.$file.'/*'))>0 && count(glob($pool.$file.'/gallery/portrait/*'))===0)){
					$origFile = $pool.$file.'/gallery/orig';
					$d = opendir($origFile);while($im = readdir($d)){if($im[0]!='.'){imagePortrait($file,$im);break;}}closedir($d);
				}
			}
			closedir($handle);
		}
	}
	
	function regenerateImages(){
		//product_image_thumbs($pID,$imgName,$novalidate = false)
		
		$pool = '../clients/'.$GLOBALS['CLIENT'].'/db/products/';
		
		echo '<pre>';
		if($handle = opendir($pool)){
			while(false !== ($pID = readdir($handle))){
				if($pID[0]!='.'){
					$origFile = $pool.$pID.'/gallery/orig';
					$d = opendir($origFile);
					while($im = readdir($d)){
						if($im[0]!='.'){
							echo $pID.' - '.$im."\n";
							//imagePortrait($file,$im);
							product_image_thumbs($pID,$im);
							//break;
						}
					}
					closedir($d);
				}
			}
			closedir($handle);
		}
		echo '</pre>';
	}
?>
<style>
	table.productTable{border-collapse:separate;}
	table.productTable tr{}
	table.productTable > tbody > tr > td{vertical-align:top;background:white;border-bottom:1px dotted #888;}
	table.productTable table.middle td{vertical-align:middle;}
	table.productTable h6{font-size:15px;color:#333;padding:0;margin:10px 0 0 0;}
	table.productTable td.submit{border:0;}
	#descriptionHolder > div{padding:10px;border:1px solid #AAA;border-radius:10px;margin:10px 0;}
	#descriptionHolder > div > h6{margin:0;padding:0 0 5px 0;font-size:18px;}
</style>
