(function(nil) {
    OACGraph.implement('barchart', function(x, y, w, h, _ignored, data, opts) {
        return this.paper.g.barchart(x, y, w, h, data, opts);
    });
    
    OACGraph.implement('deviationbarchart', function(x, y, w, h, _ignored, data, opts) {
       return this.paper.g.barchart(x, y, w, h, data, opts); 
    });
}).call(this);