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

// define data-container
if (! gpx2chartdata) {
  var gpx2chartdata=new Array();
}
