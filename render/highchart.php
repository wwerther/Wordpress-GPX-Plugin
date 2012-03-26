<?php
// vim: set ts=4 et nu ai syntax=php indentexpr= ff=unix :vim

class render_highchart {

    public $script_depencies = array ('highcharts','highchartsexport');

	public function create_series($seriesid,$seriesname,$seriescolor,$seriesaxis,$series_data_name,$dashstyle=null,$seriestype=null,$labelformat=null) {

        if ($seriesaxis<0) return;
        $seriesaxis--;

        $dashstyle=is_null($dashstyle)? '' : "dashStyle: '$dashstyle',";               
        $seriestype=is_null($seriestype)? "type: 'spline'," : "type: '$seriestype',";     
        $labelformat=is_null($labelformat)? "" : "labelformat: function(value) { $labelformat },";
        return "
            {
             name: '$seriesname',
             color: '$seriescolor',
             yAxis: $seriesaxis,
             $dashstyle
             marker: {
                enabled: false
             },
             $seriestype
             $labelformat
             data: $series_data_name
          }
        ";
    }

 	public function create_axis($axistitle,$axiscolor,$leftside=true,$axisno=0,$formatter=null) {
        if ($axisno<0) return;

        $opposite='false';
        if ($leftside==false) $opposite='true';

        if (!is_null($formatter)){
            $formatter="
            formatter: function() {
               $formatter
            },
            ";
        } else $formatter='';

        $formatter='';

        return "
          { // Another Y-Axis No: $axisno
             labels: {
                $formatter
                style: {
                color: '$axiscolor'
            }
         },
         title: {
            text: '$axistitle',
            style: {
               color: '$axiscolor'
            }
         },
         opposite: $opposite
      }
      ";
    }

    public function create_xaxis ($mode='datetime') {
        return <<<EOT
{
         type: '$mode',
         labels: {
            formatter: function() {
                return Highcharts.dateFormat('%d.%m %H:%M', this.value);
            },
            rotation: 90,
            align: 'left',
            showFirstLabel: true,
            showLastLabel: true
        }
}
EOT;
    }

    public function renderoptions  ($varname,$xaxes,$yaxes) {
        return <<<EOT
<!-- Render-Options -container $varname  -->
<script type="text/javascript">
var $varname={
    xAxis: [ $xaxes ],
    yAxis: [ $yaxes ],
    plotOptions: {
         area: {
            fillOpacity: 0.5
         }
    },
    legend: {
        layout: 'vertical',
        align: 'center',
        verticalAlign: 'top',
        floating: true,
        backgroundColor: '#FFFFFF'
    },
    exporting: {
        enabled: 1 $enableexport,
        filename: 'custom-file-name'
    },
    tooltip: {
        shared: true,
        crosshairs: true,
        borderColor: '#CDCDCD',
        formatter: function() {
            var s = '<b>'+ Highcharts.dateFormat('%d.%m.%Y %H:%M:%S', this.x) +'</b>';
            jQuery.each(this.points, function(i, point) {
                var unit = { $series_units } [point.series.name];
                value=Math.round(point.y*100)/100;
                if (point.series.labelformat) value=point.series.labelformat(point.y);
                s += '<br/><span style="font-weight:bold;color:'+point.series.color+'">'+ point.series.name+':</span>'+ value;
            });
/*            var name = { $series_names }['distance'];
            var unit = { $series_units }[name];
            s+= '<br/><span style="font-weight:bold;">'+name+':</span></td><td>'+Math.round($jsvar[totaldistance][this.x]/1000*100)/100+unit;
            var name = { $series_names }['time'];
            var unit = { $series_units }[name];
            s+= '<br/><span style="font-weight:bold;">'+name+':</span></td><td>'+Math.floor($jsvar[totalinterval][this.x]/3600)+':'+Math.floor($jsvar[totalinterval][this.x]/60)%60+':'+$jsvar[totalint];
*/
            return s;
          }
    }
}
</script>
EOT;
    }

     public function renderseries ($varname,$series) {
        return <<<EOT
<!-- Render-Series -container $varname -->
<script type="text/javascript">
    var $varname = [ $series ];
</script>
EOT;
     }

     public function renderplot ($container,$seriesname,$optionname) {
        return <<<EOT
<!-- Renderplot-container -->
<script type="text/javascript">
chart$container = new Highcharts.Chart({
      chart: {
         renderTo: '${container}',
         zoomType: 'x'
      },
      xAxis: $optionname.xAxis,
      yAxis: $optionname.yAxis,
      plotOptions: $optionname.plotOptions,
      series: $seriesname,
      legend: $optionname.legend,
      tooltip: $optionname.tooltip,
      exporting: $optionname.exporting
   });
</script>
EOT;
     }


/* Not used at the moment */
/*
      title: {
         text: '$title'
      },
      subtitle: {
         text: '$subtitle'
      },
      tooltip: {
      },
      plotOptions: {
         series: {
            point: {
                events: {
                    mouseOver: function() {
                        var lat=$jsvar[lat][this.x];
                        var lon=$jsvar[lon][this.x];
                        \$${container}debug.html('Lat: '+ lat +', Lon: '+ lon);
                    }
                }
            },
            events: {
                mouseOut: function() {
                    \$${container}debug.empty();
                }
            }
        }
      },
 
 */




     public function renderaddon ($container) {
        return <<<EOT
<!-- ADDON-container -->
<script type="text/javascript">
</script>
EOT;
     }

     public function rendercontainer ($container,$metadata) {
        return <<<EOT
         <div id="${container}chart" style="width:576px;height:300px" class="gpx2chartchart"></div>
         <div id="${container}debug" class="gpx2chartdebug" > </div>
EOT;
     }

}

?>
