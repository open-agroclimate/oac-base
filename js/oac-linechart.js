(function(nil) {
    OACGraph.implement('linechart', function(x, y, w, h, xdata, ydata, opts) {
        if( xdata === undefined ) xdata = [].range(0, ydata[0].length-1);
        console.log("X: "+xdata.length+"Y: "+ydata[0].length);
        return this.paper.g.linechart(x, y, w, h, [xdata], ydata, opts);
    });
}).call(this);