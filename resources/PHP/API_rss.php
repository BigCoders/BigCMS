<?php
	include_once('API_users.php');
	if(!isset($GLOBALS['CLIENT'])){if($_SERVER['SERVER_NAME'] !== 'localhost'){$GLOBALS['CLIENT'] = $_SERVER['SERVER_NAME'];}else{$r = preg_match('/([a-zA-Z0-9\.]+)\/g\/PHP\/([a-zA-Z0-9_]+)\.php$/',$_SERVER['REQUEST_URI'],$m);if(!$r){exit;}$GLOBALS['CLIENT'] = $m[1];}}

	if(!defined('T')){define('T',"\t");}
	if(!defined('N')){define('N',"\n");}

	function rss_helper_header(){
		header('Content-Type: text/xml; charset=UTF-8');
		echo '<?xml version=\'1.0\' encoding=\'UTF-8\'?>',N,
		'<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:dc="http://purl.org/dc/elements/1.1/" ',
		'xmlns:atom="http://www.w3.org/2005/Atom" xmlns:sy="http://purl.org/rss/1.0/modules/syndication/" ',
		'xmlns:georss="http://www.georss.org/georss" xmlns:geo="http://www.w3.org/2003/01/geo/wgs84_pos#" xmlns:media="http://search.yahoo.com/mrss/">',N,
		T,'<channel>',N;
	}

	function rss_helper_getLanguajes(){
		if(!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])){return array('1.0'=>array('en-en'));}
		$languageList = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
		$languages = array();
		$languageRanges = explode(',',trim($languageList));
		foreach($languageRanges as $languageRange){
			if(!preg_match('/(\*|[a-zA-Z0-9]{1,8}(?:-[a-zA-Z0-9]{1,8})*)(?:\s*;\s*q\s*=\s*(0(?:\.\d{0,3})|1(?:\.0{0,3})))?/',trim($languageRange),$match)){continue;}
			if(!isset($match[2])){$match[2] = '1.0';}
			else{$match[2] = (string)floatval($match[2]);}

			if(!isset($languages[$match[2]])){$languages[$match[2]] = array();}
			$languages[$match[2]][] = strtolower($match[1]);
		}
		krsort($languages);
		return $languages;
	}

	function rss_products($lang = false){
		if($lang){
			//FIXME: comprobamos que esté en los idiomas permitidos
		}
		if($lang === false){
			$langs = rss_helper_getLanguajes();
			list($lang) = array_shift($langs);
		}

		rss_helper_header();
		$currentURL = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];

		echo T,T,'<title>All Products RSS</title>',N,
		T,T,'<atom:link href=\'',$currentURL,'\' rel=\'self\' type=\'application/rss+xml\' />',N,
		T,T,'<link>',$GLOBALS['clientURL'],'</link>',N,
		T,T,'<language>',$lang,'</language>',N,
		T,T,'<description></description>',N,
		T,T,'<pubDate>',date('D, d M Y'),' 00:00:00 +1000</pubDate>',N,
		T,T,'<generator></generator>',N;//FIXME: El administrador de la página

		include_once('API_product.php');
		//FIXME: hay que hacer un where con el lenguaje
		$products = product_getProducts(false,true,100);
//FIXME: $itemLink está bastante hardcodeado
		foreach($products as $p){
			$itemLink = $GLOBALS['clientURL'].'p/'.$p['id'].'/'.$p['productTitleFixed'].'/';
			$itemPortrait = $GLOBALS['clientURL'].'pi/'.$p['id'].'/102';
			$itemPortraitFile = '../clients/'.$GLOBALS['CLIENT'].'/db/products/'.$p['id'].'/gallery/portrait/102/index.png';

			echo T,T,'<item>',N,
			T,T,T,'<title>'.$p['productTitle'].'</title>',N,
			T,T,T,'<link>',$itemLink,'</link>',N,
			//FIXME: faltan los comentarios
			T,T,T,'<comments></comments>',N,
			T,T,T,'<pubDate>',date('D, d M Y',strtotime($p['productCreationDate'])),' ',$p['productCreationTime'],' +1000</pubDate>',N,
			T,T,T,'<category><![CDATA[Internet]]></category>',N,
			T,T,T,'<guid isPermaLink=\'true\'>',$itemLink,'</guid>',N,
			T,T,T,'<description>','&lt;img src=&quot;',$itemPortrait,'&quot;/&gt;&lt;p&gt;',html_entity_decode($p['productDescription'],ENT_QUOTES,'UTF-8'),'&lt;/p&gt;','</description>',N,
			T,T,T,'<enclosure url=\'',$itemPortrait,'\' length=\'',filesize($itemPortraitFile),'\' type=\'image/jpeg\'/>',N,
			T,T,'</item>',N;
		}

		echo T,'</channel>',N,
		'</rss>';
		exit;
	}
?>
