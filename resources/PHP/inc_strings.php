<?php
	$GLOBALS['strings_specials'] = array('á','é','í','ó','ú','Á','É','Í','Ó','Ú','ñ','Ñ','ä','ë','ï','ö','ü','Ä','Ë','Ï','Ö','Ü');
	$GLOBALS['strings_normals'] = array('a','e','i','o','u','a','e','i','o','u','n','n','a','e','i','o','u','a','e','i','o','u');
	function strings_UTF8Encode($str){if(!preg_match('/[áéíóúüñÁÉÍÓÚÜÑ]/',$str)){$str = utf8_encode($str);}return $str;}
	function strings_fixString($str){return str_replace($GLOBALS['strings_specials'],$GLOBALS['strings_normals'],strtolower($str));}
	function strings_stringToURL($str){return preg_replace(array('/[ |\.|_]/','/[^a-zA-Z0-9\-]*/','/\-\-/','/(^\-|\-$)/'),array('-','','-',''),strings_fixString($str));}
	function strings_stringToURLWithDot($str){return preg_replace(array('/[ |_]/','/[^a-zA-Z0-9\-\.]*/','/\-\-/','/(^\-|\-$)/'),array('-','','-',''),strings_fixString($str));}
?>
