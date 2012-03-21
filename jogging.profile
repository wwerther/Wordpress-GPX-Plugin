<!-- vi: syntax=xml nu:ts=2:et:sw=2 
Profile for GPX2Chart: Jogging
-->
<div id="{id}" class="gpx2chart" style="width:90%">
<!-- 
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
-->
  <style type="text/css">
    <!--
    hr {color:sienna;}
    p {margin-left:20px;}
    body {background-image:url("images/back40.gif");}
    -->
  </style>

<!-- #?type=climbing --><a data-condition="{type}=climbing" title="By chris ? (Own work) [Public domain], via Wikimedia Commons" href="http://commons.wikimedia.org/wiki/File%3AClimbing_pictogram.svg"><img width="32" alt="Climbing pictogram" src="//upload.wikimedia.org/wikipedia/commons/thumb/3/33/Climbing_pictogram.svg/32px-Climbing_pictogram.svg.png"/></a>
<!-- #?type=swimming --><a data-condition="{type}=swimming" title="By Thadius856 (SVG conversion) & Parutakupiu (original image) (Own work) [Public domain or Public domain], via Wikimedia Commons" href="http://commons.wikimedia.org/wiki/File%3ASwimming_pictogram.svg"><img width="32" alt="Swimming pictogram" src="//upload.wikimedia.org/wikipedia/commons/thumb/e/eb/Swimming_pictogram.svg/32px-Swimming_pictogram.svg.png"/></a>
<!-- #?type=cycling --><a data-condition="{type}=cycling" title="By Thadius856 (SVG conversion) & Parutakupiu (original image) (Own work) [Public domain or Public domain], via Wikimedia Commons" href="http://commons.wikimedia.org/wiki/File%3ACycling_(road)_pictogram.svg"><img width="32" alt="Cycling (road) pictogram" src="//upload.wikimedia.org/wikipedia/commons/thumb/8/86/Cycling_%28road%29_pictogram.svg/32px-Cycling_%28road%29_pictogram.svg.png"/></a>
<!-- #?type=skiing --><a data-condition="{type}=skiing" title="By Thadius856 (SVG conversion) & Parutakupiu (original image) (Own work) [Public domain or Public domain], via Wikimedia Commons" href="http://commons.wikimedia.org/wiki/File%3AAlpine_skiing_pictogram.svg"><img width="32" alt="Alpine skiing pictogram" src="//upload.wikimedia.org/wikipedia/commons/thumb/a/a1/Alpine_skiing_pictogram.svg/32px-Alpine_skiing_pictogram.svg.png"/></a>
<!-- #?type=running --><a data-condition="{type}=running" title="von Parutakupiu (Eigenes Werk) [Public domain], via Wikimedia Commons" href="http://commons.wikimedia.org/wiki/File%3AOlympic_pictogram_Athletics.png"><img width="32" alt="Olympic pictogram Athletics" src="//upload.wikimedia.org/wikipedia/commons/8/85/Olympic_pictogram_Athletics.png"/></a>

	{dataarrays}
	<div id="{id}chart" class="gpx2chartchart" style="width: 576px; height: 300px; padding: 0px; position: relative;">
		{chartdiv}
	</div>
  <div id="{id}meta" class="gpx2chartmeta">
    <table>
      <thead><tr><th>Value</th><th>min</th><th>avg</th><th>max</th><th>unit</th></tr></thead>
      <tbody>
        <tr data-condition="#?cad"><th>{cad:name}<th><td>{cad:min}</td>{cad:avg}<td><td>{cad:max}</td><td>{cad:unit}</td></tr>
        <tr data-condtion="#?hr"><th>{hr:name}<th><td>{hr:min}</td>{hr:avg}<td><td>{hr:max}</td><td>{hr:unit}</td></tr>
        <tr data-condtion="#?elevation"><th>{elevation:name}<th><td>{elevation:min}</td>{elevation:avg}<td><td>{elevation:max}</td><td>{elevation:unit}</td></tr>
        <tr data-condition="#?elevation"><th>{elevation:name}<th><td>{elevation:raise}</td>{elevation:fall}<td><td>&nbsp;</td><td>{elevation:unit}</td></tr>
        <tr data-condition="#?time"><th>{time:name}<th><td>{time:start}</td>{time:end}<td><td>&nnsp;</td><td>&nnsp;</td></tr>
        <tr data-condition="#?distance"><th>{distance:name}<th><td>{distance:total}</td>{distance:unit}<td><td>&nnsp;</td><td>&nnsp;</td></tr>
      </tbody>
    </table>
  </div>
</div>
