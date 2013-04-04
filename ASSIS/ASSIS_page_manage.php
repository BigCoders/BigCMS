<?php
	include_once('API_pages.php');

	echo '<ul class=\'assisOptions\'>',
		'<li><a href=\''.$GLOBALS['baseURL_currentASSIS'].'\'><img class=\'block icon16 gohome\' src=\'g/images/t.gif\'/></a></li>',
		'<li><a href=\''.$GLOBALS['baseURL_currentASSIS'].'create\'>Crear nueva</a></li>',
	'</ul>';
	
	function main(){
		$pages = page_getWhere(1,false,true);
		echo T,T,T,'<div class=\'block\'><h2>Páginas de contenido</h2>',N,
		T,T,T,'<p>Las páginas de contenido otorgan un valor añadido a su página o posibilitan la creacion de un blog.</p>',N,
		T,T,T,'<table><thead><tr><td style="width:1px;"><span>id</span></td><td style="width:1px;">idioma</td><td style="width:1px;">título</td><td>keyword</td><td style="width:1px;"></td></tr></thead><tbody>',N;
		foreach($pages as $page){
			echo T,T,T,T,'<tr><td>',$page['id'],'</td>',
			'<td>',$page['pageLang'],'</td>',
			'<td><div class="nobr">',$page['pageTitle'],'</div></td>',
			'<td>',$page['pageTitleFixed'],'</td>',
			'<td><a href=\'',$GLOBALS['baseURL_currentASSIS'],'create/',$page['id'],'\'>editar</a></td>',
			'</tr>',N;
		}
		echo T,T,T,'</tbody></table></div>',N;
	}

	function create($id = false){
		if(isset($_POST['pageTitle'])){
			$r = page_save($_POST,false,true);
			header('Location: '.$GLOBALS['baseURL_currentASSIS']);exit;
print_r($r);
exit;
		}

		$page = array('id'=>'','pageTitle'=>'','pageTags'=>'','pageLang'=>'','pageText'=>'','pageDescription'=>'');
		if($id !== false){$page = page_getByID($id,false,true);}

		echo T,T,T,'<div class=\'block\'><h2>Crear una nueva página</h2>',N,
		T,T,T,'<p>Añade nuevos elementos al menú actual, ten en cuenta que al agregar nuevos elementos estos aparecerán de forma automática en cualquier maqueta que esté usando este menú.</p>',N,
		T,T,T,'<form action=\'',$GLOBALS['baseURL_currentURI'],'\' method=\'post\'>',N,
		T,T,T,T,'<input type=\'hidden\' name=\'id\' value=\'',$page['id'],'\'/>',N,
		T,T,T,'<table><tbody>',N,
		T,T,T,T,'<tr><td>Título de la página</td><td><div class=\'inputTextSimple\'><input type=\'text\' name=\'pageTitle\' value=\'',$page['pageTitle'],'\'/></div></td></tr>',N,
		T,T,T,T,'<tr><td>Tags (separados por ",")</td><td><div class=\'inputTextSimple\'><input type=\'text\' name=\'pageTags\' value=\'',$page['pageTags'],'\'/></div></td></tr>',N,
		T,T,T,T,'<tr><td>Idioma (si tuviera)</td><td><div class=\'inputTextSimple\'><input type=\'text\' name=\'pageLang\' value=\'',$page['pageLang'],'\'/></div></td></tr>',N,
		T,T,T,T,'<tr><td>Descripción (Meta)</td><td><div class=\'inputTextSimple\'><textarea name=\'pageDescription\'>',$page['pageDescription'],'</textarea></div></td></tr>',N,
		T,T,T,T,'<tr><td colspan="2">';
			echo T,T,T,T,'<textarea id=\'editable\' style="width:100%;height:400px;" name=\'pageText\'>',$page['pageText'],'</textarea>',N;

			echo T,T,T,'<script type=\'text/javascript\' src=\'g/js/tinymce/tiny_mce.js\'></script>',N,
			T,T,T,'<script type=\'text/javascript\'>',N,
			T,T,T,T,'tinyMCE.init({',N,
			T,T,T,T,T,'mode : "exact",',N,
			T,T,T,T,T,'elements : "editable",',N,
			T,T,T,T,T,'theme : "advanced",
			theme_advanced_statusbar_location: "bottom",
			theme_advanced_toolbar_location: "top",
			theme_advanced_toolbar_align: "left",
			theme_advanced_resizing: true,
			theme_advanced_resize_horizontal: false,
			plugins: "emotions,inlinepopups,insertdatetime,preview,searchreplace,contextmenu,paste,fullscreen,noneditable",
			theme_advanced_buttons1: "bold,italic,underline,strikethrough,forecolor,|,justifyleft,justifycenter,justifyright,justifyfull,formatselect,fontsizeselect,|,sub,sup,|,bullist,numlist,|,outdent,indent",
			theme_advanced_buttons2: "cut,copy,paste,pastetext,|,search,replace,|,undo,redo,|,link,unlink,image,youtube,code,emotions,|,removeformat,visualaid,|,insertdate,inserttime,preview,fullscreen",
			theme_advanced_buttons3: "",
			theme_advanced_buttons4: "",
			theme_advanced_statusbar_location: "bottom",
			external_link_list_url: "lists/link_list.js",
			external_image_list_url: "lists/image_list.js",
			valid_elements: "@[style],img[src|class|alt],a[href|target=_blank],-span,-strong,-em,-strike,u,#p,br,-ol,-ul,-li,-sub,-sup,-pre,-address,-h1,-h2,-h3,-h4,-h5,-h6",
			object_resizing: false',N,
			T,T,T,T,'});',N,
			T,T,T,'</script>',N;

		echo T,T,T,T,'<td><tr>',
		T,T,T,'</tbody></table>',N,
		T,T,T,'<input type=\'submit\' value=\'send\'/>',N,
		T,T,T,'</form></div>',N;
	}
	
	function save($lang) {
		if(count($_POST) > 0){
			$langFile = '../clients/'.$GLOBALS['CLIENT'].'/db/lang/'.$lang.'.php';
			if(!is_writable(dirname($langFile))){$GLOBALS['warn'] = 'Unable to write lang changes. Please, check the permission rights in the lang folder.';return main();}
			
			$content = file_get_contents($langFile);
			
			$blob = '';$pages = '';
			foreach($_POST as $k=>$v){
				$string = json_encode($v,(JSON_HEX_QUOT | JSON_HEX_APOS));
				if($string === '""'){continue;}
				$string = str_replace(array('\u00e1','\u00e9','\u00ed','\u00f3','\u00fa','\u00f1',
					 '\u00c1','\u00c9','\u00cd','\u00d3','\u00da','\u00d1'),
				   array('á','é','í','ó','ú','ñ',
					 'Á','É','Í','Ó','Ú','Ñ'),$string);
				$string = preg_replace('/\\\\u([0-9abcdef]{4})/','&#x$1;',$string);
				$string = substr($string,1,-1);
				$string = str_replace('\/','/',$string);
				$string = str_replace('\\r\\n', '', $string);
				
				//$string = html_entity_decode($string);
				$content = preg_replace('/[\n\t]*\$LANG\[\''.$k.'\'\] = \'[^\']*\';/', '', $content);
				
				//$re = '/$LANG[\'page_'.$k.']/';
				$blob .= T.'$LANG[\''.$k.'\'] = \''.$string.'\';'.N;
			}
			$blob .= '?>';
			$content = str_replace('?>', $blob, $content);			
			
			$fp = fopen($langFile,'w');fwrite($fp,$content);fclose($fp);
			
			header('Location: '.$GLOBALS['baseURL_currentASSIS']);exit;
		}
	}
?>
