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
		element     : null,
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

var OACGraph = new Class({
	Implements: [Options],
	
	options: {
		linkpaper: false,
		linkedpaper: null,
		element: null,
		type: 'bargraph',
		x: 0,
		y: 0,
		height: 300,
		width: 400,
		min: 0,
		max: 100,
		labels: undefined,
		overlay: {},
		graphOptions: {},
		chartOptions: {
			vgutter: 20,
			gutter: 20,
		},
		axisOptions: {}
	},
	
	rescale: true,
	
	initialize: function(opts) {
		this.setOptions(opts);
		if( (typeOf(this.options.element) !== 'element') && (this.options.linkedpaper === null ) ) return;
		this.element = this.options.element;
		if(this.options.linkpaper) {
			if (this.options.linkedpaper !== null) {
				this.paper = this.options.linkedpaper;
			} else {
				this.paper = this.options.linkedpaper = Raphael(this.element, this.options.width, this.options.height);
			}
		} else {
			this.paper = Raphael(this.element, this.options.width, this.options.height);
		}
	},
	
	hoverfin: function() {
	    var point = this.bar || this || undefined;
	    if (point === undefined ) return;
	    this.tag = this.paper.g.popup(point.x, point.y, point.value || "0").insertBefore(this).toFront();
	},
	
	hoverfout: function() {
	    this.tag.remove();    
	},
	
	redraw: function( data, displaylabels, labels, x, y, width, height ) {
		x = x || 0;
		y = y || 0;
		width = width || this.paper.width;
		height = height || this.paper.height;
		displaylabels = displaylabels || false;
		labels = labels || this.options.labels;
		var	gutter  = this.options.chartOptions.gutter,
			vgutter = this.options.chartOptions.vgutter,
			startx  = x,
			starty  = y,
			gwidth  = width,
			gheight = height,
			yfrom   = this.options.axisOptions.from || ( this.options.min > 0 ) ? 0 : Math.floor(this.options.min),
			yto     = this.options.axisOptions.to   || this.options.max,
			ysteps  = this.options.axisOptions.step || 10,
			graphtitle, xlabel, ylabel, chart, chartx, charty, charth, chartw, minmax;
		
		if( this.options.type == 'deviationbarchart' ) {
		    minmax = Math.max(yto, Math.abs(yfrom));
		    yto = minmax;
		    yfrom = -minmax;
		}
		
		this.options.chartOptions.to = yto;
		this.options.chartOptions.from = yfrom;
		this.paper.clear();
		
		
		
		// Add the title, shifting everything down
		if (this.options.graphOptions.title !== undefined ) {
			graphtitle = this.paper.text( width/2, y, this.options.graphOptions.title );
			graphtitle.attr({'font-size': 16 });
			gtbb = graphtitle.getBBox();
			graphtitle.attr({y: y+gtbb.height/1.75 });
			starty = gtbb.height/1.75+vgutter;
			gheight -= starty;
		}
	
		// Add an xlabel, squishing everything up
		if( this.options.graphOptions.xlabel !== undefined ) {
			xlabel = this.paper.text( width/2, height, this.options.graphOptions.xlabel );
			xlabel.attr({'font-size': 12 });
			xlbb = xlabel.getBBox();
			xlabel.attr({y: height-(xlbb.height/1.5)-vgutter/2 });
			gheight -= xlbb.height+vgutter*2;
		}
	
	    // Need to rescale for the labels
	    gheight = (gheight/10)*9;
		// Add a ylabel, shifting everything to the right (RTL must be made later)
		if( this.options.graphOptions.ylabel !== undefined ) {
			ylabel = this.paper.text( x, gheight/2, this.options.graphOptions.ylabel+(this.options.graphOptions.yunits ? " ("+this.options.graphOptions.yunits+")" : "") );
			ylabel.attr({'font-size': 12 });
			ylbb = ylabel.getBBox();
			ylabel.attr({rotation: -90, x: x+ylbb.height/1.5+gutter/2});
			startx = startx + ylbb.height+gutter;
		}
		
		// Draw the axis and shift everything right
		var	yaxis   = this.paper.g.axis(startx+gutter, gheight+vgutter+2, gheight-vgutter, yfrom, yto, ysteps, 1 ),
			yaxisbb = yaxis.all.getBBox();
			yaxis.all.translate(yaxisbb.width/2, 0);
			startx += yaxisbb.width;
			
		
		gwidth -= startx;
		gheight += (vgutter*2)+3;
		
		starty = starty - vgutter;
		chartx = (this.options.type=='linechart') ? startx-(gutter/2)-2 : startx+gutter, charty = starty+vgutter/2, chartw = gwidth-(gutter*2), charth = gheight-vgutter;
		chart = this[this.options.type](chartx, charty, chartw, charth, undefined, [data], this.options.chartOptions);
		if(labels && ((this.options.type === 'barchart') || (this.options.type === 'deviationbarchart'))) {
		    chart.label([this.options.labels], true, -45);
		}
		if( this.options.type == 'barchart') {
		    if (this.options.min < 0) {
		        var unith = (chart.bars[0][0].h)/(chart.bars[0][0].value),
		            liney = Math.abs(yfrom) * unith;
		        this.paper.path("M"+(chartx-(gutter/2)-3)+" "+(charty+charth-vgutter-liney)+" L"+(chartw+chartx)+" "+(charty+charth-vgutter-liney));
	        } else {
        		this.paper.path("M"+(chartx-(gutter/2)-3)+" "+(charty+charth-vgutter)+" L"+(chartw+chartx)+" "+(charty+charth-vgutter));
        	}
        }
    	else if( this.options.type == 'deviationbarchart' ) {
    	    this.paper.path("M"+(chartx-(gutter/2)-3)+" "+(((charty+charth)/2)+(vgutter/2)+1)+" L"+(chartw+chartx)+" "+(((charty+charth)/2)+(vgutter/2)+1));
    	} else if ( this.options.type == 'linechart' ) {
    	    this.paper.g.axis(chartx+gutter, charty+charth-vgutter, chartw-(gutter*2), 0, data.length-1, data.length-1, undefined, this.options.labels, undefined, undefined, -45);
    	}
    	
    	if(this.options.overlay.data !== {} ) {
    	    // lets draw an overlay graph
    	    if( this.options.overlay.type === 'linechart' ) {
    	        if(this.options.type === 'barchart' || this.options.type === 'deviationbarchart') {
    	            this.options.overlay.offset = (chart.bars[0][0].w)/2;
    	        } else {
    	            this.options.overlay.offset = 0;
    	        }
    	        this.options.overlay.chart = this[this.options.overlay.type](chartx+this.options.overlay.offset,charty,chartw-(this.options.overlay.offset*2),charth, undefined, [this.options.overlay.data], this.options.overlay.chartOptions);
    	    }
    	}
    	chart.hover(this.hoverfin, this.hoverfout);
    	this.chart = {chart: chart, x: chartx, y: charty, w: chartw, h: charth};
	},
	
	draw: function(data) {
	    if (!this.chart) return;
	    this.chart.chart.remove();
	    this.chart.chart = this[this.options.type](this.chart.x, this.chart.y, this.chart.w, this.chart.h, undefined, [data], this.options.chartOptions);
	    if(this.options.overlay !== {}) {
	        this.options.overlay.chart.toFront();
	    }
	    this.chart.chart.hover(this.hoverfin, this.hoverfout);
	}
});


// Fuzzy Array Slicing - Mootools Array Plugin
(function(nil){
    Array.implement({
        fuzzyltrim: function(value, times) {
            if( value === undefined || value === null ) return this;
            if(this[0] !== value ) return this;
            times = times || 0;
            var i = 0;
            while(this[i++] === value);
            if( --i > times ) return this.slice(i-times);
            return this;
        },
        
        fuzzyrtrim: function(value, times) {
            if( value === undefined  || value === null ) return this;
            if(this.getLast() !== value ) return this;
            times = times || 0;
            var i = len = this.length;
            while(this[--i] === value);
            if(len-(++i) > times) return this.slice(0,i+times);
            return this;
        },
        
        fuzzytrim: function(value, times) {
            return this.fuzzyltrim(value, times).fuzzyrtrim(value, times);
        },
        
        intelfuzzyltrim: function(value, times) {
            if( value === undefined || value === null ) return {data: this, index: null};
            if(this[0] !== value ) return {data: this, index: null};
            times = times || 0;
            var i = 0;
            while(this[i++] === value);
            if( --i > times ) return {data: this.slice(i-times), index: i-times};
            return {data: this, index: null};
        },
        
        intelfuzzyrtrim: function(value, times) {
            if( value === undefined  || value === null ) return {data: this, index: null};
            if(this.getLast() !== value ) return {data: this, index: null};
            times = times || 0;
            var i = len = this.length;
            while(this[--i] === value);
            if(len-(++i) > times) return {data: this.slice(0,i+times), index: i+times};
            return {data: this, index: null};
        },
        
        intelfuzzytrim: function(value, times) {
            var a = this.intelfuzzyltrim(value, times),
                b = a.data.intelfuzzyrtrim(value,times);
            return {data: b.data, index: [a.index, a.index+b.index] };
        },
        
        intelclean: function(replacement) {
            replacement = (replacement === undefined ) ? null : replacement;
            var l = this.length,
                remove = [];
            for(var i = 0; i < l; i++) {
                if( Number.from(this[i]) === null ) {
                    remove.push(i);
                    this[i] = replacement;
                }
            }
            return {data: this, removed: remove};
        }
    });
}).call(this);

// Simple tabs - because everything else is too much (ripped from Mootools.net)
function simpleTabs( tabs, content, callback ) {
    tabs.each(function(tab, index){
        tab.addEvent('click', function(){
            tabs.removeClass('selected');
            content.removeClass('selected');
            tabs[index].addClass('selected');
            content[index].addClass('selected');
            callback(index);
            //for (var name in editors) if (editors.hasOwnProperty(name)) editors[name].setDynamicHeight();
        });
    });
}
