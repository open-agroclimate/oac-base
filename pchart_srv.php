<?php

// pChart requires

require_once( 'pchart/class/pData.class' );
require_once( 'pchart/class/pDraw.class' );
require_once( 'pchart/class/pImage.class' );
require_once( 'pchart/class/pPie.class' );
require_once( 'pchart/class/pCache.class' );



if( ( array_key_exists( 'cid', $_GET ) ) && ( array_key_exists( 'fid', $_GET ) ) ) {
	$hash = $_GET['cid'];
	$filemd5 = $_GET['fid'];
	$cache = new pCache( array( 'CacheFolder'=>dirname(__FILE__).'/pchart/cache', 'CacheIndex'=>'i'.$filemd5.'.db', 'CacheDB'=>'c'.$filemd5.'.db' ) );
	if( $cache->isInCache( $hash ) ) {
		$cache->strokeFromCache( $hash );
	} else {
		// Do something not found here
		return;
	}
}

?>