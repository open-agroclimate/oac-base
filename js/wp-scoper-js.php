<?php

$handlerurl = substr( $_SERVER['PHP_SELF'], 0, strrpos( $_SERVER['PHP_SELF'], '/' ) );

$js = '';
$js .= "jQuery(document).ready( function($) {\n\t";
$js .= 'scoperhandlerurl = "'.$handlerurl."/wp-scoper-js-handler.php\";\n";
$js .= <<< EOJS
	$(".wp-scoper-select-linked").queue( "cb-stack", function( next ) {
		scope = getCurrentScope( $(this) );
		$.get( scoperhandlerurl+'?action=get_ddl_children&scope='+scope+'&pp='+$(this).val(), function( data ) {
			var d = $.parseJSON( data );
			for( var i = 0; i < d.length; i++ ) {
				var replace = $("#wp-scoper-"+scope+"-"+d[i]['replace']);
				replace.empty();
				replace.html( d[i]['ddl'] );
			}
		});
		next();
	});

	// Always want to try and run the above before anything else, so let's do that.
	if( $.isArray( $(".wp-scoper-select-linked").queue() ) ) {
		var lastFun = $(".wp-scoper-select-linked").queue( "cb-stack" ).pop();
		$(".wp-scoper-select-linked").queue( "cb-stack").unshift( lastFun );
	}
	
	$(".wp-scoper-select-linked").bind('change', function() {
		if ( $(this).data('hasRun') == undefined ) {
			$(this).data('hasRun', false);
			$(this).data('wp-scoper', false );
		}

		if( ( $(this).data('hasRun') == false ) || ( ( $(this).data('wp-scoper') == true ) && ( $(this).data('hasRun') == true ) )) {
			var tmpQueue = $.extend( true, [], $(this).queue( "cb-stack" ) );
			$(this).dequeue( "cb-stack" );
			$(this).queue( "cb-stack", tmpQueue );
			$(this).data('hasRun', true);
			$(this).data('wp-scoper', true );
		} else {
			$(this).data('hasRun', false );
		}
	});

	$(".wp-scoper-select-final").bind('change', function() {
		var scope = getCurrentScope( $(this) );
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
