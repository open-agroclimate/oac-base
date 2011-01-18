<?php

$base = get_option( 'oac_base_info', array() );

if( isset( $base['base_url'] ) ) {
	$js = 'jQuery(document).ready( function($) {';
	$js .= 'handlerurl = "'.$base['base_url'].'/js/wp-scoper-js.php";';
	$js .= <<<EOJS
$(".wp-scoper-select-linked").live('change', function() {
	scope = $(this).attr('id').substr( 10 );
	scope = scope.substr( 0, scope.indexOf( '-' ) );
	$.get( 'wp-scoper-js.php?scope='+scope+'&pp='+$(this).val(), function( data ) {
		alert( data );
	});
});
});
EOJS;
}
?>
