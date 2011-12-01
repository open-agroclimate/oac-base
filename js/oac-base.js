// Utility functions

(function($) {
  $.fn.oacGetURI = function() {
    var _myURI = this.attr('src').substr(7).split('/');
    _myURI.shift();
    _myURI.pop();
    _myURI.pop();
    return '/'+_myURI.join('/')+'/';
  };

  /* Very ugly, will fix later */

  $.fn.oacBarchart = function(x,y,w,h,data,ticks,labels,gOpts) {
    var r = Raphael($(this).attr('id')),
        g = Raphael.g,
        labels = labels || {},
        gOpts = gOpts || {},
        colors = gOpts.colors,
        fin    = gOpts.fin,
        fout   = gOpts.fout,
        flatdata = _.flatten(data),
        dmin = _.min(flatdata),
        dmax = _.max(flatdata),
        topx = x,
        topy = y,
        botx = w,
        boty = h,
        ymin, ymax, title, ylabel, xlabel, xaxis, yaxis;

    if( dmin < 0 ) {
      if( dmax < 0 ) {
       ymax = 0;
       ymin = dmin;
      } else {
        temp = Math.abs(dmin);
        ymax = ( temp > dmax ? temp : dmax );
        ymin = -ymax;
      }
    } else {
      ymax = dmax;
      ymin = 0;
    }

    if(labels.title) {
      title = r.text(x, y, labels.title).attr('font-size', 20);
      _bb = title.getBBox();
      _y = _bb.height/2;
      title.translate((w/2), _y+5); // +5 y-padding
      topy = _bb.y+_bb.height+5; //5 pixel padding
      boty -= _bb.height+10;
    }
    if(labels.debug) r.rect(topx,topy,botx,boty).attr('stroke', labels.debug).attr('fill', labels.debug).attr('opacity', 0.15);

    if(labels.x) {
      xlabel = r.text(x, topy+boty, labels.x).attr('font-size', 13);
      _bb = xlabel.getBBox();
      xlabel.translate((w/2),-((_bb.height/2)+5));
      boty -= _bb.height+5;
    }
    if(labels.debug) r.rect(topx,topy,botx,boty).attr('stroke', labels.debug).attr('fill', labels.debug).attr('opacity', 0.15);

    if(labels.y) {
      ylabel = r.text(x,y+(h/2), labels.y).attr('font-size', 13);
      _x = (ylabel.getBBox().height)/2;
      ylabel.translate(_x+5, 0);
      ylabel.rotate(-90);
      topx += (_x*2)+10;
      botx -= (_x*2)+10; 
    }
    if(labels.debug) r.rect(topx,topy,botx,boty).attr('stroke', labels.debug).attr('fill', labels.debug).attr('opacity', 0.15);

    /* Offsets for charts without labels */
    if(topy === y) { topy+=10; boty-=10;}
    if(topx === x) { topx+=10; botx-=10;}
    boty-=10;
    botx-=10;
    if(labels.debug) r.rect(topx,topy,botx,boty).attr('stroke', labels.debug).attr('fill', labels.debug).attr('opacity', 0.15);

    /* For positional use only */
    yaxis = g.axis(topx,topy+boty,boty,ymin,ymax,10,1,null, null, null, -90, r);
    _x = yaxis.all.getBBox().width;
    topx += _x+5;
    botx -= _x+5;
    yaxis.remove();
    xaxis = g.axis(topx,boty+topy,botx,null,null,ticks.length,0,ticks, null, null, -30, r);
    xaxis.text.attr('text-anchor', 'end').rotate(30).translate(2.5,-2.5).rotate(-30);
    _y = xaxis.all.getBBox().height;
    boty -= _y;
    xaxis.remove();
    if(labels.debug) r.rect(topx,topy,botx,boty).attr('stroke', labels.debug).attr('fill', labels.debug).attr('opacity', 0.15);

    /* The actual axes are here */
    yaxis = g.axis(topx,boty+topy,boty,ymin,ymax,10,1,null, null, null, -90, r);
    // Multitype test
    bdata = data;
    opts = {vgutter:0};
    if(colors) opts['colors'] = colors;
    chart = r.barchart(topx,topy, botx,(dmin < 0) ? boty/2 :boty, bdata, opts);
    _series = chart.bars;
    if(_series[0].type === 'set') {
      _.each(_series[0], function(b,i) {
        _faub = (_series[0][i].x-(_series[0][i].w/2));
        _faue = (_series[_series.length-1][i].x+_series[_series.length-1][i].w/2);
        if(labels.debug){
          r.circle((_faub+_faue)/2, boty+topy+7, 5);
        }
        txt = r.text((_faub+_faue)/2, boty+topy+7, ticks[i]).attr('font-size', 11);
        _bb = txt.getBBox();
        txt.translate(-(_bb.width/2),0).rotate(-30,((_faub+_faue)/2)+(_bb.width/2), boty+topy+7);
      });
    } else {
      _barcenter = (chart.bars[0].w)/2;
      _.each(chart.bars, function(b,i) {
        txt = r.text(b.x, boty+topy+7, ticks[i]).attr('font-size', 11);
        _bb = txt.getBBox();
        txt.translate(-(_bb.width/2),0).rotate(-30,b.x+(_bb.width/2), boty+topy+7);
      });
    }
  };

  $.fn.oacLinechart = function(x,y,w,h,data,ticks,labels,gOpts) {
    var r = Raphael($(this).attr('id')),
        g = Raphael.g,
        labels = labels || {},
        gOpts = gOpts || {},
        colors = gOpts.colors,
        fin    = gOpts.fin,
        fout   = gOpts.fout,
        flatdata = _.flatten(data),
        dmin = _.min(flatdata),
        dmax = _.max(flatdata),
        topx = x,
        topy = y,
        botx = w,
        boty = h,
        ymin, ymax, title, ylabel, xlabel, xaxis, yaxis;

    if( dmin < 0 ) {
      if( dmax < 0 ) {
       ymax = 0;
       ymin = dmin;
      } else {
        temp = Math.abs(dmin);
        ymax = ( temp > dmax ? temp : dmax );
        ymin = -ymax;
      }
    } else {
      ymax = dmax;
      ymin = 0;
    }

    if(labels.title) {
      title = r.text(x, y, labels.title).attr('font-size', 20);
      _bb = title.getBBox();
      _y = _bb.height/2;
      title.translate((w/2), _y+5); // +5 y-padding
      topy = _bb.y+_bb.height+5; //5 pixel padding
      boty -= _bb.height+10;
    }
    if(labels.debug) r.rect(topx,topy,botx,boty).attr('stroke', labels.debug).attr('fill', labels.debug).attr('opacity', 0.15);

    if(labels.x) {
      xlabel = r.text(x, topy+boty, labels.x).attr('font-size', 13);
      _bb = xlabel.getBBox();
      xlabel.translate((w/2),-((_bb.height/2)+5));
      boty -= _bb.height+5;
    }
    if(labels.debug) r.rect(topx,topy,botx,boty).attr('stroke', labels.debug).attr('fill', labels.debug).attr('opacity', 0.15);

    if(labels.y) {
      ylabel = r.text(x,y+(h/2), labels.y).attr('font-size', 13);
      _x = (ylabel.getBBox().height)/2;
      ylabel.translate(_x+5, 0);
      ylabel.rotate(-90);
      topx += (_x*2)+10;
      botx -= (_x*2)+10; 
    }
    if(labels.debug) r.rect(topx,topy,botx,boty).attr('stroke', labels.debug).attr('fill', labels.debug).attr('opacity', 0.15);

    /* Offsets for charts without labels */
    if(topy === y) { topy+=10; boty-=10;}
    if(topx === x) { topx+=10; botx-=10;}
    boty-=10;
    botx-=10;
    if(labels.debug) r.rect(topx,topy,botx,boty).attr('stroke', labels.debug).attr('fill', labels.debug).attr('opacity', 0.15);

    /* For positional use only */
    yaxis = g.axis(topx,topy+boty,boty,ymin,ymax,10,1,null, null, null, -90, r);
    _x = yaxis.all.getBBox().width;
    topx += _x+5;
    botx -= _x+5;
    yaxis.remove();
    xaxis = g.axis(topx,boty+topy,botx,null,null,ticks.length,0,ticks, null, null, -30, r);
    xaxis.text.attr('text-anchor', 'end').rotate(30).translate(2.5,-2.5).rotate(-30);
    _y = xaxis.all.getBBox().height;
    boty -= _y;
    xaxis.remove();
    if(labels.debug) r.rect(topx,topy,botx,boty).attr('stroke', labels.debug).attr('fill', labels.debug).attr('opacity', 0.15);

    /* The actual axes are here */
    yaxis = g.axis(topx,topy+boty,boty,ymin,ymax,10,1,null, null, null, -90, r);
    xaxis = g.axis(topx,boty+topy,botx,null,null,ticks.length,0,ticks, null, null, -30, r);
    xaxis.text.attr('text-anchor', 'end').rotate(30).translate(2.5,-2.5).rotate(-30);
    opts = {gutter: 0};
    if( colors ) opts['colors'] = colors;
    if( gOpts.shade ) opts['shade'] = gOpts.shade;
    if( gOpts.symbol ) opts['symbol'] = gOpts.symbol;
    if( gOpts.smooth ) opts['smooth'] = gOpts.smooth;
    chart = r.linechart(topx+2,topy+1,botx-2,boty-8,_.range(data[0].length),data,opts);
  };

  $.fn.oacHybridchart = function(x,y,w,h,data,ticks,labels,gOpts) {
    var r = Raphael($(this).attr('id')),
        g = Raphael.g,
        labels = labels || {},
        gOpts = gOpts || {},
        colors = gOpts.colors,
        fin    = gOpts.fin,
        fout   = gOpts.fout,
        flatdata = _.flatten(data),
        dmin = _.min(flatdata),
        dmax = _.max(flatdata),
        topx = x,
        topy = y,
        botx = w,
        boty = h,
        ymin, ymax, title, ylabel, xlabel, xaxis, yaxis;

    if( dmin < 0 ) {
      if( dmax < 0 ) {
       ymax = 0;
       ymin = dmin;
      } else {
        temp = Math.abs(dmin);
        ymax = ( temp > dmax ? temp : dmax );
        ymin = -ymax;
      }
    } else {
      ymax = dmax;
      ymin = 0;
    }

    if(labels.title) {
      title = r.text(x, y, labels.title).attr('font-size', 20);
      _bb = title.getBBox();
      _y = _bb.height/2;
      title.translate((w/2), _y+5); // +5 y-padding
      topy = _bb.y+_bb.height+5; //5 pixel padding
      boty -= _bb.height+10;
    }
    if(labels.debug) r.rect(topx,topy,botx,boty).attr('stroke', labels.debug).attr('fill', labels.debug).attr('opacity', 0.15);

    if(labels.x) {
      xlabel = r.text(x, topy+boty, labels.x).attr('font-size', 13);
      _bb = xlabel.getBBox();
      xlabel.translate((w/2),-((_bb.height/2)+5));
      boty -= _bb.height+5;
    }
    if(labels.debug) r.rect(topx,topy,botx,boty).attr('stroke', labels.debug).attr('fill', labels.debug).attr('opacity', 0.15);

    if(labels.y) {
      ylabel = r.text(x,y+(h/2), labels.y).attr('font-size', 13);
      _x = (ylabel.getBBox().height)/2;
      ylabel.translate(_x+5, 0);
      ylabel.rotate(-90);
      topx += (_x*2)+10;
      botx -= (_x*2)+10; 
    }
    if(labels.debug) r.rect(topx,topy,botx,boty).attr('stroke', labels.debug).attr('fill', labels.debug).attr('opacity', 0.15);

    /* Offsets for charts without labels */
    if(topy === y) { topy+=10; boty-=10;}
    if(topx === x) { topx+=10; botx-=10;}
    boty-=10;
    botx-=10;
    if(labels.debug) r.rect(topx,topy,botx,boty).attr('stroke', labels.debug).attr('fill', labels.debug).attr('opacity', 0.15);

    /* For positional use only */
    yaxis = g.axis(topx,topy+boty,boty,ymin,ymax,10,1,null, null, null, -90, r);
    _x = yaxis.all.getBBox().width;
    topx += _x+5;
    botx -= _x+5;
    yaxis.remove();
    xaxis = g.axis(topx,boty+topy,botx,null,null,ticks.length,0,ticks, null, null, -30, r);
    xaxis.text.attr('text-anchor', 'end').rotate(30).translate(2.5,-2.5).rotate(-30);
    _y = xaxis.all.getBBox().height;
    boty -= _y;
    xaxis.remove();
    if(labels.debug) r.rect(topx,topy,botx,boty).attr('stroke', labels.debug).attr('fill', labels.debug).attr('opacity', 0.15);

    /* The actual axes are here */
    yaxis = g.axis(topx,boty+topy,boty,ymin,ymax,10,1,null, null, null, -90, r);
    // Multtype test
    //bdata = data;
    bdata = [data[0]];
    bmax = _(bdata).chain().flatten().max().value();
    ldata = _.rest(data);
    lmax = _(ldata).chain().flatten().max().value();
    bopts = {vgutter: 0, to: ymax};
    if( colors ) bopts['colors'] = [colors.shift()];
    chart = r.barchart(topx,topy, botx,(dmin < 0) ? boty/2 :boty,bdata, bopts);
    _series = chart.bars;
    if(_series[0].type === 'set') {
      _.each(_series[0], function(b,i) {
        _faub = (_series[0][i].x-(_series[0][i].w/2));
        _faue = (_series[_series.length-1][i].x+_series[_series.length-1][i].w/2);
        if(labels.debug){
          r.circle((_faub+_faue)/2, boty+topy+7, 5);
        }
        txt = r.text((_faub+_faue)/2, boty+topy+7, ticks[i]).attr('font-size', 11);
        _bb = txt.getBBox();
        //txt.translate(-(_bb.width/2),0).rotate(-30,b.x+(_bb.width/2), boty+topy+5);
      });
    } else {
      _barcenter = (_series[0].w)/2;
      _.each(_series, function(b,i) {
        txt = r.text(b.x, boty+topy+7, ticks[i]).attr('font-size', 11);
        _bb = txt.getBBox();
        txt.translate(-(_bb.width/2),0).rotate(-30,b.x+(_bb.width/2), boty+topy+7);
      });
    }
    lopts = {gutter:0};
    if( colors ) lopts['colors'] = colors;
    if( gOpts.shade ) lopts['shade'] = gOpts.shade;
    if( gOpts.symbol ) lopts['symbol'] = gOpts.symbol;
    if( gOpts.smooth ) lopts['smooth'] = gOpts.smooth;
    if( lmax < bmax ) {
      lmin = _(ldata).chain().flatten().min().value();
      _ticks = (boty/ymax);
      topy += ((ymax-lmax)*_ticks)+5;
      boty -= ((ymax-lmax+lmin+5)*_ticks);
    }
    r.linechart(topx+(_series[0][0].w/2)+7,topy-5,botx-25-(_series[0][0].w),boty+10,_.range(ldata[0].length),ldata, lopts);
  };
})(jQuery);
