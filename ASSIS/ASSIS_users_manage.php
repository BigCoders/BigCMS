<?php
	echo '<ul class=\'assisOptions\'>',
		'<li><a href=\''.$GLOBALS['baseURL_currentASSIS'].'add\'>Add new user</a></li>',
		'<li><a href=\''.$GLOBALS['baseURL_currentASSIS'].'last\'>View lasts</a></li>','
	</ul>';

	include_once('API_users.php');
	function main(){
		
	}

	function add(){
		echo '<form action=\'',$GLOBALS['baseURL_currentASSIS'],'save\'  method=\'post\'>',N,
		T,'<ul>',N;
		$tableKeys = array_keys($GLOBALS['tables']['users']);
		foreach($tableKeys as $k){
			echo T,T,'<li>',$k,'</li>',N,
			T,T,'<li><input name=\'',$k,'\'></li>',N;
		}
		echo T,T,'<li><input type=\'submit\' value=\'save\'/></li>',N,
		T,'</ul>',N,
		'</form>';
	}

	function last(){
		include('API_category.php');//<- para qué?
		$rows = users_getUsers(false,true,20);
		echo T,T,T,'<h2>Last users in table</h2>',N,
		T,T,T,'<p>Here is a list of the last users added.</p>',N,
		T,T,T,'<table><thead><tr><td><span>id</span></td><td>userName</td><td></td><td></td><td></td><td>View Carts</td></tr></thead><tbody>',N;
		foreach($rows as $row){
			echo T,T,T,T,'<tr><td><a href=\'#\'>',$row['id'],'</a></td><td>',$row['userName'],'</td><td>',$row['userMail'],'</td><td>',$row['userLastLogin'],'</td>',
			'<td>',$row['userClass'],'</td><td><a href=\'',$GLOBALS['baseURL_ASSIS'],'cart_manage/viewCartsByUserID/',$row['id'],'\'>view carts</a></td>',
			'<td><a href=\'',$GLOBALS['baseURL_ASSIS'],'cart_manage/viewShippingInfoByUserID/',$row['id'],'\'>view shippingInfo</a></td>',
			'<td><a href=\'',$GLOBALS['baseURL_currentASSIS'],'edit/',$row['id'],'\'><div><a href=\'javascript:\' onclick=\'assis.changeClass(this.parentNode,',json_encode($row),');\'>edit</a></div></a></td></tr>',N;
		}
		echo T,T,T,'</tbody></table>',N;
		echo '<ul class=\'pager\'><li><a href=\'',$GLOBALS['baseURL_currentASSIS'],'last/page/',($GLOBALS['currentPage']-1),'/\'>prev</a></li><li>',$GLOBALS['currentPage'],'</li><li><a href=\'',$GLOBALS['baseURL_currentASSIS'],'last/page/',($GLOBALS['currentPage']+1),'/\'>next</a></li></ul>';
	}

	function save(){
		if(count($_POST) > 0){
			$r = users_create($_POST);
			print_r($r);
		}
	}

	function webtrace($uID){
		$trackingDB = '../clients/'.$GLOBALS['CLIENT'].'/db/tracking.db';
		if(!file_exists($trackingDB)){header('Location: '.$GLOBALS['baseURL_currentASSIS']);}
		$db = new sqlite3($trackingDB,SQLITE3_OPEN_READONLY);
		$query = 'SELECT * FROM tracking WHERE trackingUser = '.$uID.' ORDER BY trackingDate DESC,trackingTime DESC LIMIT 30';
		//echo $query;
		$r = $db->query($query);
		$rows = array();if($r){while($row = $r->fetchArray(SQLITE3_ASSOC)){$rows[] = $row;}}

		echo T,T,T,'<div class=\'block\'><h2>Tracking de visitas de ','</h2>',N,
		T,T,T,'<p>Tracking de la páginas visitadas por este usuario, podemos ver los camínos más comunes que utilizó para llegar a determinados puntos.</p>',N,
		T,T,T,'<table><thead><tr><td style=\'width:1px;\'><span>ip</span></td><td>userAgent</td><td style=\'width:1px;\'>url</td><td style=\'width:1px;\'></td><td style=\'width:1px;\'>fecha</td><td style=\'width:1px;\'></td></tr></thead><tbody>',N;
		foreach($rows as $row){
			$trackingURL = str_replace($GLOBALS['clientURL'],'',$row['trackingURL']);
			$trackingURL = ($trackingURL == '') ? '/' : $trackingURL;
			$refererURL = str_replace($GLOBALS['clientURL'],'',$row['trackingReferer']);
			$refererURL = ($refererURL == '') ? '/' : $refererURL;
			echo T,T,T,T,'<tr><td><a href=\'#\'>',$row['trackingIP'],'</a></td><td><div class=\'nobr\' style=\'width:60px;\'>',$row['trackingUserAgent'],'</div></td><td>',$trackingURL,'</td><td>',$refererURL,'</td><td>',$row['trackingDate'],'</td>',
			'<td>',$row['trackingTime'],'</td></tr>',N;
		}
		echo T,T,T,'</tbody></table></div>',N;
		$db->close();
	}
?>

<script type='text/javascript'>
	var API_USR = 'g/PHP/API_users.php';
	var assis = {
		changeClass: function(a,user){
			var i = info_create('assis',{'.width':'300px'},a);
			var h = i.infoContainer;
			$C('H2',{innerHTML:'Change user class'},h);

			var ul = $C('UL',{},h);
			var li = $C('LI',{},ul);
			$C('INPUT',{name:'command',type:'hidden',value:'changeClass'},li);
			var ck = $C('INPUT',{name:'admin',className:'checkbox',type:'checkbox',checked:(user.userClass.match(/,admin,/) ? 'checked' : '')},li);
			var sp = $C('SPAN',{innerHTML:'Admin'},li);

			var d = $C('UL',{className:'buttonHolder'},h);
			gnomeButton_create('Cancelar',function(){info_destroy(i);},d,'assisButton');
			gnomeButton_create('Aceptar',function(){z(i);},d,'assisButton');

			function z(i){
				var p = $parseForm(i);
				var classes = '';
				for(var a in p){if(a!='command' && p[a]==true){classes += a+',';}}
				var p = $toUrl({'command':p['command'],'userID':user.id,'userClass':classes});
				ajaxPetition(API_USR,p,function(ajax){
					var r = jsonDecode(ajax.responseText);if(parseInt(r.errorCode)>0){alert(print_r(r));return;}
					info_destroy(i);
				});
			}
		}
	};
</script>
