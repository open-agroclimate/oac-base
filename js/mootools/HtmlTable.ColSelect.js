HtmlTable = Class.refactor(HtmlTable, {

	options: {
		/*onColFocus: function(){},
		onRowUnfocus: function(){},*/
		classColSelected: 'table-col-selected',
		classSelectable: 'table-selectable',
		hasIndexColumn: false,
		columnSelectable: false
	},

	initialize: function(){
		this.previous.apply(this, arguments);
		if (this.occluded) return this.occluded;

		this._selectedCols = new Elements();

		this.bound = {
			clickColumn: this.clickColumn.bind(this)
		};

		if (this.options.columnSelectable) this.enableColumnSelect();
	},
	
	enableColumnSelect: function() {
		this.element.addEvent('click:relay(td)', this.bound.clickColumn);
	},
	
	clickColumn: function(event, el) {
		var index = (el.getParent().getChildren('td').indexOf(el)),
		    _selectedCol;
		if( this.options.hasIndexColumn && (index === 0 ) ) return;
		this.element.getElements('.'+this.options.classColSelected).removeClass(this.options.classColSelected);
		_selectedCol = this.element.getElements('tr td:nth-child('+(index+1)+')');
		_selectedCol.addClass(this.options.classColSelected);
		this.fireEvent('colFocus', [index, _selectedCol] );
	},
	
	selectColumnByIndex: function(col) {
	    _selectedCol = this.element.getElements('tr td:nth-child('+(col+1)+')');
	    _selectedCol.addClass(this.options.classColSelected);
	    this.fireEvent('colFocus', [col, _selectedCol]); 
	}
});

// TODO: Optimize for already selected issues.
// TODO: Add ignorecols option