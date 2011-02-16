window.addEvent('domready', function() {
	var scopeHandler = ( $$('script[src$="wp-scoper.js"]')[0].getProperty('src').toURI().get('directory'))+'handler.php';
});

var oacScoper = new Class({
});


// What do we know about the scope?
// * All scopes
