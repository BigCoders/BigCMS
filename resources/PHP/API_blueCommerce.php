<?php
	//$r = blueCommerce_packetClient('frutasycestas.com');
	function blueCommerce_packetClient($clientName,$dest = false){
		$clientPath = '../clients/'.$clientName.'/';
		if(!file_exists($clientPath)){
			return;
		}

		$backupName = $clientName.'_'.date('Y_m_d__H_i_s').'.zip';
		$backupPath = '../backups/'.$clientName.'/';
		if(!file_exists($backupPath)){@mkdir($backupPath);}
		if(!file_exists($backupPath)){
echo "Permission denied";
			return;
		}
		chmod($backupPath,0777);

		$zip = new ZipArchive();
		if($zip->open($backupPath.$backupName,ZIPARCHIVE::CREATE) === true){
			$dir = preg_replace('/[\/]{2,}/','/',$clientPath);
			$dirs = array($dir);
			while(count($dirs)){
				$dir = current($dirs);
				$zipDir = substr($dir,11);
				$zip->addEmptyDir($zipDir);
				$dh = opendir($dir);
				while($file = readdir($dh)){
					if($file[0]=='.'){continue;}
					if(is_file($dir.$file)){$zip->addFile($dir.$file,$zipDir.$file);}
					elseif(is_dir($dir.$file)){$dirs[] = $dir.$file.'/';}
				}
				closedir($dh);
				array_shift($dirs);
			}
			$zip->close();
		}

		return;
	}
?>
