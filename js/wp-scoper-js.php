<?php

$handlerurl = substr( $_SERVER['PHP_SELF'], 0, strrpos( $_SERVER['PHP_SELF'], '/' ) );

$js = '';
$js .= "jQuery(document).ready( function($) {\n\t";
$js .= 'handlerurl = "'.$handlerurl."/wp-scoper-js-handler.php\";\n";
$js .= <<< EOJS
	$(".wp-scoper-select-linked").live('change', function() {
		scope = getCurrentScope( $(this) );
		$.get( handlerurl+'?action=get_ddl_children&scope='+scope+'&pp='+$(this).val(), function( data ) {
			var d = $.parseJSON( data );
			for( var i = 0; i < d.length; i++ ) {
				var replace = $("#wp-scoper-"+scope+"-"+d[i]['replace']);
				replace.empty();
				replace.html( d[i]['ddl'] );
			}
			alert( wpScoperGetFinal( scope ) );
		});
	});

	$(".wp-scoper-select-final").live('change', function() {
		var scope = getCurrentScope( $(this) );
		alert( wpScoperGetFinal( scope ) );
	});
});

function getCurrentScope(el) {
	var scope = el.attr('id').substr(10);
	scope = scope.substr(0, scope.indexOf('-'));
	return scope;
}

function wpScoperGetFinal( scope ) {
	return jQuery("select[id^='wp-scoper-"+scope+"'].wp-scoper-select-final").val();
}

EOJS;

header( 'Content-Type: text/javascript' );
echo $js;
?>
