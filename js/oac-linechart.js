(function(nil) {
    OACGraph.implement('linechart', function(x, y, w, h, xdata, ydata, opts) {
        if( xdata === undefined ) xdata = [].range(0, ydata[0].length-1);
        return this.paper.g.linechart(x, y, w, h, [xdata], ydata, opts);
    });
}).call(this);