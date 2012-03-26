<!-- vi: syntax=xml nu:ts=2:et:sw=2 
Profile for GPX2Chart: default.profile
-->

<div id="{id}" class="gpx2chart" style="width:90%">
<!-- 
GPX2Chart-Configuration:

Define Defaults for rendering the charts. These values can be overwritten by values defined in the shortcode

Set the engine that should be used for this template (flot, highcharts, jqplot...)
#=engine:flot
#=type:running

Define the default-colors
#=heartrate.color:#AA4643
#=cadence.color:#4572A7
#=elevation.color:#89A54E
#=speed.color:#CACA00
#=time.color:#000

Where should the axis be
#=heartrate.axisleft:true
#=cadence.axisleft:true
#=elevation.axisleft:false

#=elevation.speed:false
#=heartrate.dashstyle:shortdot
#=elevation.seriestype:areaspline
#=css.inline:true

{configuration}

-->
  <style type="text/css" data-condition="{css.inline}" >
    #{id} {
      font-family: Verdana, Helvetica, Arial, sans-serif;
      font-size: normal;
    }
    #{id} table {
      border: none;
    }
    #{id} tr td {
      padding: 0px 0px 0px 10px;
      border: none;
    }
    #{id} tr th {
      padding: 0px 0px 0px 10px;
      border: none;
    }
    #{id} .gpxcadence th {
      color: {cadence.color};
    }
    #{id} .gpxheartrate th {
      color: {heartrate.color};
    }
    #{id} .gpxelevation th {
      color: {elevation.color};
    }
  </style>
        <!-- Initial GPX2Chart Javascript -->
<!--        <script type="text/javascript" data-condition="{instance}=3" >
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
           if (! gpx2chartdata) {
               var gpx2chartdata=new Array();
           }
        </script> -->

  <h3>
  <a data-condition="{type}=climbing" title="By chris ? (Own work) [Public domain], via Wikimedia Commons" href="http://commons.wikimedia.org/wiki/File%3AClimbing_pictogram.svg"><img width="32" alt="Climbing pictogram" src="{icons.url}Climbing_pictogram.svg"/></a>

  <a data-condition="{type}=swimming" title="By Thadius856 (SVG conversion) & Parutakupiu (original image) (Own work) [Public domain or Public domain], via Wikimedia Commons" href="http://commons.wikimedia.org/wiki/File%3ASwimming_pictogram.svg"><img width="32" alt="Swimming pictogram" src="{icons.url}Swimming_pictogram.svg"/></a>

  <a data-condition="{type}=cycling" title="By Thadius856 (SVG conversion) & Parutakupiu (original image) (Own work) [Public domain or Public domain], via Wikimedia Commons" href="http://commons.wikimedia.org/wiki/File%3ACycling_(road)_pictogram.svg"><img width="32" alt="Cycling (road) pictogram" src="{icons.url}Cycling_pictogram.svg"/></a>

  <a data-condition="{type}=skiing" title="By Thadius856 (SVG conversion) & Parutakupiu (original image) (Own work) [Public domain or Public domain], via Wikimedia Commons" href="http://commons.wikimedia.org/wiki/File%3AAlpine_skiing_pictogram.svg"><img width="32" alt="Alpine skiing pictogram" src="{icons.url}Alpine_skiing_pictogram.svg"/></a>

  <a data-condition="{type}=running" title="By Thadius856 (SVG conversion) & Parutakupiu (original image) (Own work) [Public domain or Public domain], via Wikimedia Commons" href="http://commons.wikimedia.org/wiki/File%3AAthletics_pictogram.svg"><img width="32" alt="Athletics pictogram" src="{icons.url}Athletics_pictogram.svg"/></a>

  {headline}</h3>

	{data.js.dataarray}

	<div id="{id}chart" class="gpx2chartchart" style="width: 576px; height: 300px; padding: 0px; position: relative;">
		{chartdiv}
	</div>
  <div id="{id}meta" class="gpx2chartmeta">
    <table>
      <thead><tr><th>Value</th><th>min</th><th>avg</th><th>max</th><th>unit</th></tr></thead>
      <tbody>
        <tr data-condition="#?cad" class="gpxcadence" ><th>{cadence.name}<th><td>{cad:min}</td>{cad:avg}<td><td>{cad:max}</td><td>{cad:unit}</td></tr>
        <tr data-condition="#?hr" class="gpxheartrate" ><th>{hr:name}<th><td>{hr:min}</td>{hr:avg}<td><td>{hr:max}</td><td>{hr:unit}</td></tr>
        <tr data-condition="#?elevation" class="gpxelevation" ><th>{elevation:name}<th><td>{elevation:min}</td>{elevation:avg}<td><td>{elevation:max}</td><td>{elevation:unit}</td></tr>
        <tr data-condition=""><th>{elevation:name}<th><td>{elevation:min}</td>{elevation:avg}<td><td>{elevation:max}</td><td>{elevation:unit}</td></tr>
        <tr data-condition="#?elevation"><th>{elevation:name}<th><td>{elevation:raise}</td>{elevation:fall}<td><td>&nbsp;</td><td>{elevation:unit}</td></tr>
        <tr data-condition="#?time"><th>{time:name}<th><td>{time:start}</td>{time:end}<td><td>&nbsp;</td><td>&nbsp;</td></tr>
        <tr data-condition="#?distance"><th>{distance:name}<th><td>{distance:total}</td>{distance:unit}<td><td>&nbsp;</td><td>&nbsp;</td></tr>
      </tbody>
    </table>
  </div>
</div>
