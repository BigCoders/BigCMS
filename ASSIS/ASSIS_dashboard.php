<?php
	$GLOBALS['userPath'] = '../clients/'.$GLOBALS['CLIENT'].'/db/users/';
	/*echo '<ul class=\'assisOptions\'>',
		'<li><a href=\''.$GLOBALS['baseURL_currentASSIS'].'add\'>Add new user</a></li>',
		'<li><a href=\''.$GLOBALS['baseURL_currentASSIS'].'last\'>View lasts</a></li>','
	</ul>';*/

	function main(){
		$target = '../clients/'.$GLOBALS['CLIENT'].'/db/tracking/sessions/';
		//FIXME: comprobar que la URL existe
		$currentTimeStamp = time();
		$firstMinutesAgo = strtotime('-5 minutes');
		$secondMinutesAgo = strtotime('-10 minutes');
		$userIDs = array();
		$connected = array();$disconnecting = array();if($handle = opendir($target)){while(false !== ($file = readdir($handle))){
			if($file[0]=='.'){continue;}
			$mtime = stat($target.$file);$mtime = $mtime['mtime'];
			$arr = explode('_',$file);
			$user = array('ip'=>$arr[0],'session'=>$arr[1],'userID'=>(isset($arr[2]) ? $arr[2] : ''),'mtime'=>$mtime);
			if($user['userID'] > 0){$userIDs[] = $user['userID'];}
			if($mtime > $firstMinutesAgo){$connected[] = $user;}
			elseif($mtime > $secondMinutesAgo){$disconnecting[] = $user;}
			else{unlink($target.$file);}
		}closedir($handle);}

		$userArray = array();if(count($userIDs) > 0){$userArray = users_resolveIds($userIDs,false,true);}

		echo T,T,T,'<div class=\'block visitors\'>',N;

		echo T,T,T,T,'<div class=\'rightNow\'><h2 class=\'center\'>Right now</h2>',N,
		T,T,T,T,T,'<p class=\'center\'>Based on the activity of the last 5 minutes</p>',N,
		T,T,T,T,T,'<p class=\'count\'>',count($connected),'</p>',N,
		T,T,T,T,T,'<p class=\'center\'>active visitors on site</p>',N,
		T,T,T,T,'</div>',N;

		echo T,T,T,T,'<div class=\'clientsTable\'><h2>User table</h2><p>users connected</p>',
		'<table><thead><tr><td><span>usuario</span></td><td><span>ip/sesión</span></td><td style=\'width:1px;\'>ÚltimoContacto</td><td style=\'width:1px;\'>status</td></tr></thead><tbody>',N;
		foreach($connected as $u){
			echo T,T,T,T,'<tr><td>',dashboard_helper_getUserTD($u['userID'],$userArray),'</td><td>',$u['ip'],' (',$u['session'],')</td><td>',date('i:s',$currentTimeStamp-$u['mtime']),'</td><td>online</td></tr>',N;
		}
		foreach($disconnecting as $u){
			echo T,T,T,T,'<tr><td>',dashboard_helper_getUserTD($u['userID'],$userArray),'</td><td>',$u['ip'],' (',$u['session'],')</td><td>',date('i:s',$currentTimeStamp-$u['mtime']),'</td><td>disconnecting</td></tr>',N;
		}
		echo T,T,T,'</tbody></table></div>',N;
		echo T,T,T,'</div>',N;
	}

	function dashboard_helper_getUserTD($userID,$userArray){
		if(empty($userID) || !isset($userArray[$userID])){return '';}
		$userAvatarFile = $GLOBALS['userPath'].$userID.'/avatars/avatar32.jpeg';
		$td = '';
		$user = $userArray[$userID];
		if(file_exists($userAvatarFile)){$td .= '<a href=\''.$GLOBALS['baseURL_ASSIS'].'users_manage/webtrace/'.$userID.'\'><img src=\''.$GLOBALS['clientURL'].'ua/'.$userID.'/avatar32\'/></a>';}
		$userName = $user['userName'];$pos = strpos($user['userName'],',');if($pos !== false){$userName = substr($user['userName'],0,$pos);}
		$td .= '<a href=\''.$GLOBALS['baseURL_ASSIS'].'users_manage/webtrace/'.$userID.'\'>'.$userName.'</a>';
		return $td;
	}
?>
<style>
.block.visitors{position:relative;padding-top:1px;margin-top:10px;}
.block.visitors > div.rightNow{position:absolute;width:180px;top:0;background:#CCC;padding:10px;}
.block.visitors > div.rightNow > h2{margin:0;}
.block.visitors > div.rightNow > p.count{text-align:center;margin:0;font-size:35px;}
.block.visitors > div.rightNow > .center{text-align:center;}
.block.visitors > div.clientsTable{margin-left:220px;}
.block.visitors > div.clientsTable > h2{margin:0;margin-top:10px;}
.block.visitors > div.clientsTable > table td:first-child > a > img{width:20px;border:1px solid #CCC;padding:2px;margin:2px 6px 2px 0;vertical-align:middle;}
</style>
<script type='text/javascript'>
	var API_USR = 'g/PHP/API_users.php';
	var assis = {
		
	};
</script>
