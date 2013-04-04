<?php
	echo '<ul class=\'assisOptions\'>',
		'<li><a href=\''.$GLOBALS['baseURL_currentASSIS'].'add\'>Enviar notificación</a></li>',
		'<li><a href=\''.$GLOBALS['baseURL_currentASSIS'].'last\'>Últimas notificaciones</a></li>','
	</ul>';

	include_once('API_notifications.php');
	function main(){
		last();
	}

	function last(){
		//FIXME: se trae todas
		$rows = notifications_get(false,true,20);
		echo T,T,T,'<div class=\'block\'><h2>Últimas notificaciones</h2>',N,
		T,T,T,'<p>Estas son las últimas notificaciones añadidas al sistema. Debes intentar comprobar las notificaciones regularmente para estar informado de los diferentes eventos que suceden dentro del gestor de la tienda.</p>',N,
		T,T,T,'<table><thead><tr><td style=\'width:1px;\'><span>id</span></td><td>Título</td><td>Texto</td><td>Tags</td><td>Fecha</td><td>Componente</td></tr></thead><tbody>',N;
		foreach($rows as $row){
			echo T,T,T,T,'<tr><td>',$row['id'],'</td>',
			'<td><div class=\'nobr\'>',$row['notificationTitle'],'</div></td>',
			'<td><div class=\'nobr\'>',$row['notificationText'],'</div></td>',
			'<td>',$row['notificationTags'],'</td>',
			'<td>',$row['notificationDate'],' ',$row['notificationTime'],'</td>',
			'<td>',$row['notificationModule'],'</td>',N,
			'</tr>',N;
		}
		echo T,T,T,'</tbody></table>',N;
		echo '<ul class=\'pager\'><li><a href=\'',$GLOBALS['baseURL_currentASSIS'],'last/page/',($GLOBALS['currentPage']-1),'/\'>prev</a></li><li>',$GLOBALS['currentPage'],'</li><li><a href=\'',$GLOBALS['baseURL_currentASSIS'],'last/page/',($GLOBALS['currentPage']+1),'/\'>next</a></li></ul>';
		echo T,T,T,'</div>';
	}
?>
