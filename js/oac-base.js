//A better queue-chain
(function($) { 
  var methods = {
    init:  function() {
      // Use a named queue to add eventControl
    },
    add:   function( queue, f ) {},
    shift: function() {},
    go:    function() {},
    check: function() {},
    reset: function() {}
  };

  $.fn.eventControl = function( method ) {
    if( methods[method]) {
      return methods[method].apply( this, Array.prototype.slice.call( arguments, 1 ));
    } else if ( typeof method === 'object' || ! method ) {
      return methods.init.apply( this, arguments );
    } else {
      $.error( 'Method ' + method + ' does not exist on jQuery.eventControl' );
    }
  };
})(jQuery);
