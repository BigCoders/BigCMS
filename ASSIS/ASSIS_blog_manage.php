<?php
	session_start();
	
	function helper_getClientLangPages(){
		$r = shell_exec('grep -R "{%LANG_page" ../clients/'.$GLOBALS['CLIENT'].'/views/');
		$r = preg_match_all('/{%LANG_page_([a-zA-Z0-9_]+)%}/',$r,$m);
		return $m;
	}
	
	function main(){
		include_once('inc_strings.php');
		
		$pages = array();
		$blogFolder = '../clients/'.$GLOBALS['CLIENT'].'/db/blog/';
		if(!file_exists($blogFolder)){mkdir($blogFolder,0777,1);}
		if(!file_exists($blogFolder)){return false;}
		if($handle = opendir($blogFolder)){while(false !== ($file = readdir($handle))){if(($file[0]!='.')){$p = substr($file,0,-4);$pages[$p] = array('page'=>$p,'pageFile'=>$file);}}closedir($handle);}
		
		$blogPages = array();
				
		foreach ($pages as $k=>$page) {
			$file = $blogFolder.$page['pageFile'];
			$blob = file_get_contents($file);
			
			preg_match('/^([^\n]*)\n/sm',$blob,$m);
			
			$pageInfo = array();
			$pageInfo['title'] = $m[1];
			$pageInfo['titleFixed'] = substr($page['pageFile'],0,-4);
			$pageInfo['langRoute'] = $file;
			$blogPages[] = $pageInfo;
		}
		
		echo T,T,T,'<div class=\'block\'><h2>Páginas actuales en el blog</h2>',N,
		T,T,T,'<p>Estas son las páginas que hay actualmente en el blog. A través de la siguiente tabla puedes editar y completar cada página.</p>',N,
		T,T,T,'<table><thead>
			<tr><td style="width: 400px"><span>Página</span></td>
				<td style="width: 400px">Título</td>
				<td></td>
			</tr></thead><tbody>',N;
		foreach($blogPages as $page){
			echo T,T,T,T,'<tr><td><span class=\'mono\'>',$page['titleFixed'],'</span>',(is_writable($page['langRoute']) ? '' : ' ( <img class=\'warn\' src=\'g/images/icons/warning_panel.png\'> fichero sin permisos de escritura )'),'</td>',
			'<td>',$page['title'],'</td>',
			'<td><a href=\'',$GLOBALS['baseURL_currentASSIS'],'edit/',$page['titleFixed'],'\'>edit</a> <a href=\'',$GLOBALS['baseURL_currentASSIS'],'delete_page/',$page['titleFixed'],'\'>delete</a></td>',
			'</tr>',N;
		}
		echo T,T,T,T,'<tr><td colspan="3"><input type="button" value="Crear Página" onclick="location.href=\''.$GLOBALS['baseURL_currentASSIS'].'new_page\'" /></td></tr>';
		echo T,T,T,'</tbody></table></div>',N;
		echo '<ul class=\'pager\'><li><a href=\'',$GLOBALS['baseURL_currentASSIS'],'last/page/',($GLOBALS['currentPage']-1),'/\'>prev</a></li><li>',$GLOBALS['currentPage'],'</li><li><a href=\'',$GLOBALS['baseURL_currentASSIS'],'last/page/',($GLOBALS['currentPage']+1),'/\'>next</a></li></ul>';
		
		
	}
	
	function edit($titleFixed,$overwrite = false){
		$blogFolder = '../clients/'.$GLOBALS['CLIENT'].'/db/blog/';
		preg_replace('/[^a-zA-Z0-9\-]*/','', $titleFixed);
		$blogFile = $blogFolder.$titleFixed.'.txt';
		
		if(!file_exists($blogFile)){header('Location: '.$GLOBALS['baseURL_currentASSIS']);exit;}
		
		if(count($_POST) > 0){
			include_once('inc_strings.php');
			
			$oldFile = $_POST['blogOldFile'];
			$newTitle = $_POST['blogTitle'];
			$newFile = strings_stringToURL($newTitle);
			$content = $_POST['blogContent'];
						
			//FIXME: Parsear campos
			$blob = $newTitle.N;
			$blob .= $content.N;
			
			
			$i = 1;
			$newFileExists = $newFile;
			if($newFile != $oldFile){
				while(file_exists($blogFolder.$newFileExists.'.txt')){$newFileExists = $newFile.'-'.$i++;}
				rename($blogFolder.$oldFile.'.txt',$blogFolder.$newFileExists.'.txt');
			}
						
			$fp = fopen($blogFolder.$newFileExists.'.txt','w');fwrite($fp,$blob);fclose($fp);
			header('Location: '.$GLOBALS['baseURL_currentASSIS']);exit;		
		}
		
		$page = file_get_contents($blogFile);
		$page = utf8_decode($page);

		preg_match('/^([^\n]*)\n(.*)/sm',$page,$m);		
		
		echo T,T,T,'<script type=\'text/javascript\' src=\'g/js/tinymce/tiny_mce.js\'></script>',N,
			T,T,T,'<script type=\'text/javascript\'>',N,
			T,T,T,T,'tinyMCE.init({',N,
			T,T,T,T,T,'mode : "textareas",',N,
			T,T,T,T,T,'theme : "advanced",
			theme_advanced_statusbar_location: "bottom",
			theme_advanced_toolbar_location: "top",
			theme_advanced_toolbar_align: "left",
			theme_advanced_resizing: true,
			theme_advanced_resize_horizontal: false,
			plugins: "emotions,inlinepopups,insertdatetime,preview,searchreplace,contextmenu,paste,fullscreen,noneditable,youtubeIframe",
            theme_advanced_buttons1: "bold,italic,underline,strikethrough,forecolor,|,justifyleft,justifycenter,justifyright,justifyfull,formatselect,fontsizeselect,|,sub,sup,|,bullist,numlist,|,outdent,indent",
            theme_advanced_buttons2: "cut,copy,paste,pastetext,|,search,replace,|,undo,redo,|,link,unlink,image,youtubeIframe,code,emotions,|,removeformat,visualaid,|,insertdate,inserttime,preview,fullscreen",
            theme_advanced_buttons3: "",
            theme_advanced_buttons4: "",
            theme_advanced_statusbar_location: "bottom",
            external_link_list_url: "lists/link_list.js",
            external_image_list_url: "lists/image_list.js",
            valid_elements: "@[style],img[src|class|alt],a[href|target=_blank],-span,-strong,-em,-strike,u,#p,br,-ol,-ul,-li,-sub,-sup,-pre,-address,-h1,-h2,-h3,-h4,-h5,-h6,iframe[src|title|width|height|allowfullscreen|frameborder|class|id],object[classid|width|height|codebase|*],param[name|value|_value|*],embed[type|width|height|src|*]",
            object_resizing: false',N,
			T,T,T,T,'});',N,
			T,T,T,'</script>',N,
			T,T,T,'<form action=\'',$GLOBALS['currentURL'],'\'  method=\'post\'>',N,
			T,T,T,T,'<table><tbody>',N,
			T,T,T,T,'<tr>',N,
			T,T,T,T,'<td>Título</td>',N,
			T,T,T,T,'<td><div class=\'inputTextSimple\'><input type=\'hidden\' name=\'blogOldFile\' value=\''.$titleFixed.'\' /><input type=\'text\' name=\'blogTitle\' value=\''.$m[1].'\' /></div></td>',N,
			T,T,T,T,'</tr><tr>',N,
			T,T,T,T,'<td>Contenido</td>',N,
			T,T,T,T,'<td><textarea id=\'pageContent\' name=\'blogContent\' rows=\'40\' cols=\'150\' style=\'width: 100%;\'>'.$m[2].'</textarea></td>',N,		
			T,T,T,T,'</tr><tr>',N,
			T,T,T,T,'<td colspan=\'2\' style=\'text-align: center;\'><input type=\'submit\' value=\'Guardar Página\' /><input type=\'reset\' value=\'Reset\' /></td>',N,
			T,T,T,T,'</tr></tbody></table>',N,
			T,T,T,'</form>';
	}
	
	
	
	function new_page(){
		if(count($_POST) > 0){
			include_once('inc_strings.php');
			include_once('twitter/twitteroauth.php');
			
			$newTitle = $_POST['blogTitle'];
			$newTitleFile = $newFile = strings_stringToURL($newTitle);
			$content = $_POST['blogContent'];
			
			//FIXME: Parsear campos
			$blob = $newTitle.N;
			$blob .= $content.N;		
			
			$i = 1;
			$blogFolder = '../clients/'.$GLOBALS['CLIENT'].'/db/blog/';
			$newFileExists = $newFile;
			while(file_exists($blogFolder.$newFileExists.'.txt')){$newFileExists = $newFile.'-'.$i++;}
			$newFile = $blogFolder.$newFileExists.'.txt';
			
			$fp = fopen($blogFolder.$newFileExists.'.txt','w');fwrite($fp,$blob);fclose($fp);
			
			/*Publicar en twitter*/
			$twitter = $GLOBALS['blueCommerce']['twitterApp'];
			$link = 'http://chirivia.com/blog/'.$newTitleFile;
			$message = $newTitle.' '.$link;
			$connection = new TwitterOAuth($twitter['consumerKey'],$twitter['consumerSecret'],$twitter['oauthToken'],$twitter['oauthTokenSecret']);
			$twitter = $connection->post('statuses/update', array('status' =>utf8_encode($message)));

			header('Location: '.$GLOBALS['baseURL_currentASSIS'].'edit/'.$newFile);
		}
	
		echo T,T,T,'<script type=\'text/javascript\' src=\'g/js/tinymce/tiny_mce.js\'></script>',N,
			T,T,T,'<script type=\'text/javascript\'>',N,
			T,T,T,T,'tinyMCE.init({',N,
			T,T,T,T,T,'mode : "textareas",',N,
			T,T,T,T,T,'theme : "advanced",
			theme_advanced_statusbar_location: "bottom",
			theme_advanced_toolbar_location: "top",
			theme_advanced_toolbar_align: "left",
			theme_advanced_resizing: true,
			theme_advanced_resize_horizontal: false,
			plugins: "emotions,inlinepopups,insertdatetime,preview,searchreplace,contextmenu,paste,fullscreen,noneditable,youtubeIframe",
			theme_advanced_buttons1: "bold,italic,underline,strikethrough,forecolor,|,justifyleft,justifycenter,justifyright,justifyfull,formatselect,fontsizeselect,|,sub,sup,|,bullist,numlist,|,outdent,indent",
			theme_advanced_buttons2: "cut,copy,paste,pastetext,|,search,replace,|,undo,redo,|,link,unlink,image,youtubeIframe,code,emotions,|,removeformat,visualaid,|,insertdate,inserttime,preview,fullscreen",
			theme_advanced_buttons3: "",
			theme_advanced_buttons4: "",
			theme_advanced_statusbar_location: "bottom",
			external_link_list_url: "lists/link_list.js",
			external_image_list_url: "lists/image_list.js",
			valid_elements: "@[style],img[src|class|alt],a[href|target=_blank],-span,-strong,-em,-strike,u,#p,br,-ol,-ul,-li,-sub,-sup,-pre,-address,-h1,-h2,-h3,-h4,-h5,-h6,iframe[src|title|width|height|allowfullscreen|frameborder|class|id],object[classid|width|height|codebase|*],param[name|value|_value|*],embed[type|width|height|src|*]",
			object_resizing: false',N,
			T,T,T,T,'});',N,
			T,T,T,'</script>',N,
			T,T,T,'<form action=\'',$GLOBALS['currentURL'],'\'  method=\'post\'>',N,
			T,T,T,T,'<table><tbody>',N,
			T,T,T,T,'<tr>',N,
			T,T,T,T,'<td>Título</td>',N,
			T,T,T,T,'<td><div class=\'inputTextSimple\'><input type=\'text\' name=\'blogTitle\' /></div></td>',N,
			T,T,T,T,'</tr><tr>',N,
			T,T,T,T,'<td>Contenido</td>',N,
			T,T,T,T,'<td><textarea id=\'pageContent\' name=\'blogContent\' rows=\'40\' cols=\'150\' style=\'width: 100%;\'></textarea></td>',N,		
			T,T,T,T,'</tr><tr>',N,
			T,T,T,T,'<td colspan=\'2\' style=\'text-align: center;\'><input type=\'submit\' value=\'Guardar Página\' /><input type=\'reset\' value=\'Reset\' /></td>',N,
			T,T,T,T,'</tr></tbody></table>',N,
			T,T,T,'</form>';
	}
	
	function delete_page($titleFixed) {
		$blogFolder = '../clients/'.$GLOBALS['CLIENT'].'/db/blog/';

		preg_replace('/[^a-zA-Z0-9\-]*/','', $titleFixed);
		$blogFile = $blogFolder.$titleFixed.'.txt';
		
		if(!file_exists($blogFile)){header('Location: '.$GLOBALS['baseURL_currentASSIS']);exit;}
		
		unlink($blogFile);
		header('Location: '.$GLOBALS['baseURL_currentASSIS']); exit;
	}
	?>
