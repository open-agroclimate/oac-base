// A better event controller-ish
// TODO: Set an "always first", "always last" option
var eventControl = new Class({
	initialize: function() {
		this.runner = new Chain;
		this.requeue = undefined;
	},
	
	add: function() {
		this.runner.chain(arguments);
	},
	
	shift: function() {
		this.check();
		this.runner.callChain();
		if( this.runner.$chain.length === 0 ) { this.runner.chain(this.requeue); }
	},
	
	go: function() {
		this.check();
		while( this.runner.callChain() != false );
		this.runner.chain(this.requeue);
	},
	
	check: function() {
		if(this.requeue === undefined) { this.requeue = Array.clone(this.runner.$chain); }
		if(this.requeue.length < this.runner.$chain.length) { this.requeue = Array.clone(this.runner.$chain); }
		return;
	},
	
	reset: function() {
		this.runner.clearChain();
		this.runner.chain(this.requeue);
	}
});

// Scope core code. Other events can be bound to the scope elements (such as
// update tables, draw graphs, etc ), via the OACScope.queue (using the
// eventControl API above)
// When dealing with a scope: 
// * OACScope.parentQueue:  handles any actions for the ancestors of final
// * OACScope.finalQueue:   handles any actions for the final
// * OACScope.finalElement: the "final" element in the chain

var OACScope = new Class({
	Implements: [Options],
	
	options: {
		handler     : (($$('script[src*="oac-base.js"]')[0].getProperty('src').toURI().get('directory'))+'../scoper/wp-scoper-ajax.php').toURI().toString(),
		scope       : '',
		element     : undefined,
		parentClass : '.wp-scope-linked',
		finalClass  : '.wp-scope-final'
	},
	
	// Controls the flow of events for this scope. Can be isolated from
	// other scopes, per plugin
	parentQueue: new eventControl(),
	finalQueue:  new eventControl(),
	initialize: function(opts) {
		this.setOptions(opts);
		if( typeOf(this.options.element) !== 'element' ) return;
		this.bound = {
			finalSelect: this.finalSelect.bind(this),
			parentSelect: this.parentSelect.bind(this),
			updateScope: this.updateScope.bind(this)
		};
		this.req = new Request.JSON({
			url:  this.options.handler,
			method: 'get',
			link: 'cancel',
			noCache: true,
			onSuccess: this.bound.updateScope,
			onFailure: function()  { alert('There is a problem. Please contact the system administrator.'); },
		});
		this.finalElement = this.options.element.getElement(this.options.finalClass);
		var parentElements = this.options.element.getElements(this.options.parentClass);
		parentElements === undefined ? undefined : parentElements.addEvent('change', this.bound.parentSelect);
		this.finalElement.addEvent('change', this.bound.finalSelect);
	},
	
	parentSelect: function(event) {
		this.req.send({data: {
			action: 'get_ddl_children',
			scope: this.options.scope,
			pp: event.target.get('value')
		}});
	},
	
	finalSelect: function(event) {
		this.finalQueue.go();
	},
	
	updateScope: function(data) {
		data.each(function(el){
			var replace = this.options.element.getElementById(el.replace);
			if(typeOf(replace) === 'element') {replace.set({html: el.ddl})};
		}, this);
		this.parentQueue.go();
		// Do we fire the subsequent finalQueue? Check
		this.finalQueue.go();
	},
});