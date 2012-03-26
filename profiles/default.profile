<!-- vi: syntax=xml nu:ts=2:et:sw=2 
Profile for GPX2Chart: default.profile
-->

<!-- 
GPX2Chart-Configuration:

Define Defaults for rendering the charts. These values can be overwritten by values defined in the shortcode

################ Set the engine that should be used for this template (flot, highcharts, jqplot...)
#=engine:flot
#=css.inline:true
#=type:running
#=maxelem:0
#=data.embed:cadence heartrate elevation speed totaldistance totalinterval totalrise totalfall lat lon
#=data.series:cadence heartrate elevation speed totaldistance totalinterval totalrise totalfall lat lon
#=data.yaxis.show:cadence heartrate elevation speed
#=graph.meta.display:

################ Dimensions
#=chart.width:576px
#=chart.height:300px

################ Define the default-colors
#=heartrate.color:#AA4643
#=cadence.color:#4572A7
#=elevation.color:#89A54E
#=speed.color:#CACA00
#=time.color:#000

################ What unit's should be used?
#=heartrate.unit:bpm
#=cadence.unit:rpm
#=elevation.unit:m
#=speed.unit:km/h

################ What's the name of the series in the legend?
#=heartrate.legend.title:Heartrate
#=cadence.legend.title:Cadence
#=speed.legend.title:Speed
#=elevation.legend.title:Altitude

#=heartrate.series.name:Heartrate
#=cadence.series.name:Cadence
#=elevation.series.name:Altitude
#=speed.series.name:Speed
#=distance.series.name:Distance
#=time.series.name:Time
#=totaldistance.series.name:Distance
#=totaltime.series.name:Time
#=totalrise.series.name:Rise
#=totalfall.series.name:Fall
#=lat.series.name:Latitude
#=lon.series.name:Longitude

################ Where should the axis be ? left or right-side?
#=heartrate.yaxis.left:true
#=cadence.yaxis.left:true
#=elevation.yaxis.left:false
#=speed.yaxis.left:true

################ What labels should the axis have?
#=heartrate.axis.title:Heartrate (bpm)
#=cadence.axis.title:Cadence (rpm)
#=elevation.axis.title:Altitude (m)
#=speed.axis.title:Speed (km/h)

################ How should the ticks of the axis should be labeled?
#=heartrate.axis.format:return value.toFixed(axis.tickDecimals) + "bpm  ";
#=cadence.axis.format:return value.toFixed(axis.tickDecimals) + "rpm";
#=elevation.axis.format:return value.toFixed(axis.tickDecimals) + "m";
#=speed.axis.format:return value.toFixed(axis.tickDecimals) + "km/h";
#=distance.axis.format:return value.toFixed(axis.tickDecimals) + "km";
#=time.axis.format:return value.toFixed(axis.tickDecimals) + "h";

#=elevation.series.type:areaspline
#=heartrate.dash.style:shortdot
{configuration}

-->

<style type="text/css" data-condition="{css.inline}" >

    #{id} {
      font-family: Verdana, Helvetica, Arial, sans-serif;
      font-size: normal;
      width: 90%;
    }

    #{id} h1 {
      font-size: 32px;
      margin:0;
    }
    #{id} h2 {
      font-size: large;
      margin: 0;
    }
    #{id} h3 {
      font-size: xx-small;
      text-align: right;
      margin: 0;
    }

    #{id} table {
      border: none;
      margin: 0px;
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

    #{id}chart {
      width: {chart.width}; 
      height: {chart.height}; 
      padding: 0px; 
      position: relative;
    }
</style>

<div id="{id}" class="gpx2chart" >
  <h1>
  <a data-condition="{type}=climbing" title="By chris ? (Own work) [Public domain], via Wikimedia Commons" href="http://commons.wikimedia.org/wiki/File%3AClimbing_pictogram.svg"><img width="32" alt="Climbing pictogram" src="{icons.url}Climbing_pictogram.svg.png"/></a>

  <a data-condition="{type}=swimming" title="By Thadius856 (SVG conversion) & Parutakupiu (original image) (Own work) [Public domain or Public domain], via Wikimedia Commons" href="http://commons.wikimedia.org/wiki/File%3ASwimming_pictogram.svg"><img width="32" alt="Swimming pictogram" src="{icons.url}Swimming_pictogram.svg.png"/></a>

  <a data-condition="{type}=cycling" title="By Thadius856 (SVG conversion) & Parutakupiu (original image) (Own work) [Public domain or Public domain], via Wikimedia Commons" href="http://commons.wikimedia.org/wiki/File%3ACycling_(road)_pictogram.svg"><img width="32" alt="Cycling (road) pictogram" src="{icons.url}Cycling_road_pictogram.svg.png"/></a>

  <a data-condition="{type}=skiing" title="By Thadius856 (SVG conversion) & Parutakupiu (original image) (Own work) [Public domain or Public domain], via Wikimedia Commons" href="http://commons.wikimedia.org/wiki/File%3AAlpine_skiing_pictogram.svg"><img width="32" alt="Alpine skiing pictogram" src="{icons.url}Alpine_skiing_pictogram.svg.png"/></a>

  <a data-condition="{type}=running" title="By Thadius856 (SVG conversion) & Parutakupiu (original image) (Own work) [Public domain or Public domain], via Wikimedia Commons" href="http://commons.wikimedia.org/wiki/File%3AAthletics_pictogram.svg"><img width="32" alt="Athletics pictogram" src="{icons.url}Athletics_pictogram.svg.png"/></a>
  {headline}</h1>
  <h2>{title}</h2>
  <h3>{subtitle}</h3>
  <div data-condition="{error}" id="{id}error" class="gpx2charterror"><img width="48" height="48" src="{icons.url}Dialog-error.png"/>{error.text}</div>

	<div id="{id}chart" class="gpx2chartchart"> </div>

  <div id="{id}meta" class="gpx2chartmeta">
    <table>
      <thead><tr><th>Value</th><th>min</th><th>avg</th><th>max</th><th>unit</th></tr></thead>
      <tbody>
        <tr data-condition="{gpx.contain.cadence}" class="gpxcadence" ><th>{cadence.legend.title}</th><td>{gpx.calc.cadence.min}</td><td>{gpx.calc.cadence.avg}</td><td>{gpx.calc.cadence.max}</td><td>{cadence.unit}</td></tr>
        <tr data-condition="{gpx.contain.heartrate}" class="gpxheartrate" ><th>{heartrate.legend.title}</th><td>{gpx.calc.heartrate.min}</td><td>{gpx.calc.heartrate.avg}</td><td>{gpx.calc.heartrate.max}</td><td>{heartrate.unit}</td></tr>
        <tr data-condition="{gpx.contain.elevation}" class="gpxelevation" ><th>{elevation.legend.title}</th><td>{gpx.calc.elevation.min}</td><td>{gpx.calc.elevation.avg}</td><td>{gpx.calc.elevation.max}</td><td>{elevation.unit}</td></tr>
        <tr data-condition="{gpx.contain.elevation}" class="gpxelevation"><th>&nbsp;</th><td colspan="2">Rise: {gpx.stat.elevation.rise}{elevation.unit}</td><td colspan="2">Fall: {gpx.stat.elevation.fall}{elevation.unit}</td></tr>
        <tr data-condition="{gpx.contain.time}"><th>{time.legend.title}</th><td>{gpx.calc.time.min}</td><td>&nbsp;</td><td>{gpx.calc.time.max}<td></tr>
        <tr data-condition="{gpx.contain.distance}"><th>{distance.legend.title}</th><td>&nbsp;</td><td>&nbsp;</td><td>{gpx.calc.distance.max}</td><td>{distance.unit}</td></tr>
      </tbody>
    </table>
  </div>

  <div id="{id}tooltip" class="gpx2charttooltip"> </div>

  <div id="{id}debug" class="gpx2chartdebug" > </div>

  <script type="text/javascript">
    gpx2chart['data'][{instance}]=new Array();
{data.js.dataarray}
  </script>

  {data.js.options}

  {data.js.series}

  {data.js.render}

  {data.js.addon}

</div>
