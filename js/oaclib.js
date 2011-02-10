(function(window, document, undefined) {
	var oac = (function() {
		var oac = function() {
			return new oac.fn.init();	
		};
	
	oac.fn = oac.prototype = {
		init: function() {
			return this;
		},
		cleanData: function( data, labels ) {
			var modData = [],
				modLabels = [];
				
			for( var index = 0; index < data.length; index++ ) {
				if( !isNaN(data[index]) ) {
					modData.push( data[index]);
					if( labels !== undefined ) {
						modLabels.push( labels[index] );
					}
				}
			}
						
			return (labels === undefined) ? { data: modData, labels: undefined} : { data: modData, labels: modLabels };
		},
		drawTable: function( tableBody, html ) {
			jQuery(tableBody).empty();
			jQuery(tableBody).append(html);
		},
		highlightTableRow: function( el, enso ) {
			var elements = jQuery(el).parent();
			if( jQuery(elements).hasClass('highlight') ) {
				return;
			}
			var tableId = jQuery(el).parents("table").attr('id');
			oac.fn.highlightCells( tableId, elements, enso );
		},
		highlightTableCol: function( el, enso ) {
			if( jQuery(el).hasClass('highlight' ) ) {
				return;
			}
			var tableId = jQuery(el).parents("table").attr('id');
			var elements = jQuery("#"+tableId+" td."+jQuery(el).attr('class'));
			oac.fn.highlightCells( tableId, elements, enso );
		},
		tableHighlightedIndices: function( tableBodies ) {
			var indices = [];
			for( var i = 0; i < tableBodies.length; i++ ) {
				indices.push( jQuery(tableBodies[i]).find('.highlight').first().index() );
			}
			return indices.length === 1 ? indices[0] : indices;
		},
		highlightCells: function( tableId, elements, enso ) {
			jQuery("#"+tableId+" tr,#"+tableId+" td").removeClass('oac-enso-1 oac-enso-2 oac-enso-3 oac-enso-4 highlight');
			jQuery(elements).addClass('oac-enso-'+enso+' highlight');
		},
		getHighlightedData: function( tableBody ) {
			var data = [],
				cells = jQuery(tableBody).find(".highlight");
				if( jQuery(cells).is("tr") ) {
					cells = jQuery(cells).children("td");
				}
				jQuery(cells).each( function () {
					data.push(jQuery(this).text());
				})
				return data;
				
		},
		// graphOptions = { title: "Title of the graph", xlabel: "The label on the x-axis", ylabel: "The label of the bars", yunits: "Unit of measure (eg. mm, F, C, inches)"}
		// barOptions = { typical bargraph.opts}
		// axisOptions = { from: starting value (default 0), to: ending value (default the max value of data), steps: how many hashes (default 10) }
		graphBarWithAxis: function(paper, x, y, width, height, data, labels, graphOptions, barOptions, axisOptions) {
			graphOptions = graphOptions || {};
			barOptions   = barOptions ||{};
			axisOptions  = axisOptions || {};
			var	vgutter     = barOptions.vgutter || 20,
				gutter      = parseFloat(barOptions.gutter || "20%"),
				from        = axisOptions.from || 0,
				to          = axisOptions.to   || Math.max.apply(null, data),
				steps       = axisOptions.step || 10,
				startx      = 0,
				starty      = 0,
				gwidth      = width,
				gheight     = height,
				graphtitle, xlabel, ylabel;
			
			// Add the title, shifting everything down
			if (graphOptions.title !== undefined ) {
				graphtitle = paper.text( width/2, y, graphOptions.title );
				graphtitle.attr({'font-size': 19 });
				gtbb = graphtitle.getBBox();
				graphtitle.attr({y: y+gtbb.height/1.75 });
				starty = gtbb.height/1.75;
				gheight -= starty;
			}
		
			// Add an xlabel, squishing everything up
			if( graphOptions.xlabel !== undefined ) {
				xlabel = paper.text( width/2, height, graphOptions.xlabel );
				xlabel.attr({'font-size': 14 });
				xlbb = xlabel.getBBox();
				xlabel.attr({y: height-(xlbb.height/1.5)-vgutter/2 });
				gheight -= xlbb.height/1.25+vgutter;
			}
		
			// Add a ylabel, shifting everything to the right (RTL must be made later)
			if( graphOptions.ylabel !== undefined ) {
				ylabel = paper.text( x, gheight/2, graphOptions.ylabel+(graphOptions.yunit ? " ("+graphOptions.yunit+")" : "") );
				ylabel.attr({'font-size': 14 });
				ylbb = ylabel.getBBox();
				ylabel.attr({rotation: -90, x: x+ylbb.height/1.5+gutter/2});
				startx = startx + ylbb.height+gutter;
			}
			
			// Draw the axis and shift everything again to the right
			var	axis   = paper.g.axis(startx+gutter, gheight-starty, gheight-2*vgutter, from, to, steps, 1 ),
				axisbb = axis.all.getBBox();
				axis.all.translate(axisbb.width/2);
				startx += axisbb.width/2+gutter*2;
			
			gwidth -= startx;
			// for some reason I cannot get the [[]] color to work, so I hacked it out
			if( !jQuery.isArray( barOptions.colors ) && barOptions.colors !== undefined ) {
				var _color = barOptions.colors;
				barOptions.colors = [];
				jQuery.each(data, function() {
					barOptions.colors.push(_color);
				});
				
			}
			
			var bargraph = paper.g.barchart(startx, starty, gwidth, gheight, data, barOptions);
			if( labels !== undefined ) {
				bargraph.label(labels, true);
			}
			return bargraph;
		},
		chartWithAxis: function(chartFun, paper, x, y, width, height, data, labels, graphOptions, chartOptions, axisOptions) {
			graphOptions = graphOptions || {};
			chartOptions = chartOptions ||{};
			axisOptions  = axisOptions || {};
			var	vgutter     = chartOptions.vgutter || 20,
				gutter      = parseFloat(chartOptions.gutter || "20%"),
				isbar       = ( chartFun === this.barchart ) ? true : false,
				yfrom       = axisOptions.from || 0,
				yto         = axisOptions.to   || Math.max.apply(null, data),
				ysteps      = axisOptions.step || 10,
				startx      = 0,
				starty      = 0,
				gwidth      = width,
				gheight     = height,
				graphtitle, xlabel, ylabel;
			
			chartOptions.gutter = gutter;
			
			// Add the title, shifting everything down
			if (graphOptions.title !== undefined ) {
				graphtitle = paper.text( width/2, y, graphOptions.title );
				graphtitle.attr({'font-size': 19 });
				gtbb = graphtitle.getBBox();
				graphtitle.attr({y: y+gtbb.height/1.75 });
				starty = gtbb.height/1.75+vgutter;
				gheight -= starty;
			}
		
			// Add an xlabel, squishing everything up
			if( graphOptions.xlabel !== undefined ) {
				xlabel = paper.text( width/2, height, graphOptions.xlabel );
				xlabel.attr({'font-size': 14 });
				xlbb = xlabel.getBBox();
				xlabel.attr({y: height-(xlbb.height/1.5)-vgutter/2 });
				gheight -= xlbb.height+vgutter*2;
			}
		
			// Add a ylabel, shifting everything to the right (RTL must be made later)
			if( graphOptions.ylabel !== undefined ) {
				ylabel = paper.text( x, gheight/2, graphOptions.ylabel+(graphOptions.yunits ? " ("+graphOptions.yunits+")" : "") );
				ylabel.attr({'font-size': 14 });
				ylbb = ylabel.getBBox();
				ylabel.attr({rotation: -90, x: x+ylbb.height/1.5+gutter/2});
				startx = startx + ylbb.height+gutter;
			}
			
			if( chartFun === oac().deviationbarchart ) {
				var min = Math.min.apply(Math, data),
				    max = Math.max.apply(Math, data);
				
				yto = Math.max(Math.abs(min), max);
				yfrom = -yto;
				ystep = 5;
				
			}
			
			// Draw the axis and shift everything again to the up and to the right (respoctivley)
			var	yaxis   = paper.g.axis(startx+gutter, gheight+vgutter+1, gheight-vgutter, yfrom, yto, ysteps, 1 ),
				yaxisbb = yaxis.all.getBBox();
				yaxis.all.translate(yaxisbb.width/2);
				startx += yaxisbb.width;
				
			
			gwidth -= startx;
			gheight += vgutter*2;
			return chartFun( paper, startx, starty, gwidth, gheight, data, labels, chartOptions, axisOptions );
			
		},
		barchart: function(paper, x, y, width, height, data, labels, opts, labelopts) {
			opts = opts || {};
			labelopts = labelopts || {};
			opts.vgutter = opts.vgutter || 20;
			y = y - opts.vgutter;
			if( !jQuery.isArray( opts.colors ) && opts.colors !== undefined ) {
				var _color = opts.colors;
				opts.colors = [];
				jQuery.each(data, function() {
					opts.colors.push(_color);
				});
			}
			var bargraph = paper.g.barchart(x+opts.gutter, y+opts.vgutter/2, (width-(opts.gutter*2)), height-opts.vgutter, data, opts);
			if( labels !== undefined ) {
				bargraph.label(labels, true, true );
				bargraph.labels.attr(labelopts);
			}
			return {graph: bargraph, labels: bargraph.labels};
		},
		deviationbarchart: function(paper, x, y, width, height, data, labels, opts, labelopts ) {
			console.log("Called deviation");
			return oac().barchart(paper, x, y, width, height, data, labels, opts, labelopts);
		},
		linechart: function(paper, x, y, width, height, data, labels, opts, axisopts) {
			opts = opts || {};
			axisopts = axisopts || {};
			var vgutter = opts.vgutter || 20,
			    gutter  = opts.gutter  || 20,	
			    xfrom   = 0,
				xto     = labels.length-1 || undefined,
				xsteps  = labels.length-1 || undefined;
			
			// make adjustments for vgutter that doesn't exist in line graphs
			y -= vgutter/2;
			height -= vgutter;
			
			var xdata = [],
				l = data.length;
				
			for( var i = 0; i < l; i++ ) {
				xdata[i] = i;
			}
			
			if( !jQuery.isArray( opts.colors ) && opts.colors !== undefined ) {
				opts.colors = [opts.colors];
			}
			opts.smooth = false;
			opts.symbol = "o";
			opts.shade = true;
			var xaxis = paper.g.axis(x+gutter, height+1, width-(gutter*2), xfrom, xto, xsteps, undefined, labels),
				linegraph = paper.g.linechart(x,y,width,height,xdata, data, opts);
			xaxis.text.attr(axisopts);
			return { graph: linegraph, labels: xaxis.text };
			
		},
		version: 0.1,
	};
	
	// Required for this type of late instantiation
	oac.fn.init.prototype = oac.fn;
	return ( window.oac = oac );
	})();
})(this, document);
