/*
GPX2CHART-Javascript functions
vi: syntax=javascript nu:ts=2:et:sw=2 
*/

// Define debug function 
if (! window.gpx2chartdebug) {
  function gpx2chartdebug(text, container) {
    if (console) {
      if (console.debug) {
        console.debug(text);
      }
    }
    if (container) {
      jQuery(container).html(text)
    }
  }
}

if (! gpx2chart) { 
  var gpx2chart=new Array();
  gpx2chart['options']=new Array();
  gpx2chart['series']=new Array();
  gpx2chart['data']=new Array();
  gpx2chart['handle']=new Array();
}
