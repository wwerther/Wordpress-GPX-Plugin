<?php
// vim: set ts=4 et nu ai syntax=php indentexpr= ff=unix :vim

class render_flot {

    public $script_depencies = array('flot','flotcross','flotnavigate','flotselection','excanvas','strftime');

	public function create_series($seriesname,$seriescolor,$seriesaxis,$series_data_name,$dashstyle=null,$seriestype=null) {
        $seriestype=is_null($seriestype)? "" : "lines: { fill: 0.3},";
        return " {
            label: '$seriesname',
            yaxis: $seriesaxis,
            color: '$seriescolor',
            $seriestype
            data: $series_data_name
        }
        ";
    }

 	public function create_axis($axistitle,$axiscolor,$leftside=true,$axisno=0,$formatter=null) {
        $position=$leftside ? 'left' : 'right';

        if (!is_null($formatter)){
            $formatter="
            tickFormatter: function(value,axis) {
               $formatter
            },
            ";
        } else $formatter='';

        return "
          { // Y-Axis No: $axisno
            axisLabel: '$axistitle',
            position: '$position',
            $formatter
            color: '$axiscolor'
      }
      ";
    }

    public function create_xaxis ($mode='time') {
        return " {mode: '$mode' } ";
    }

    public function renderoptions ($varname,$xaxes,$yaxes) {
        return <<<EOT
<script type="text/javascript">
            var $varname={
                grid: { 
                    hoverable: true,
                    mouseActiveIgnoreY: true,
                    autoHighlight: false,
                },
                xaxes: [ $xaxes ],
                yaxes: [ $yaxes ],
                legend: { show: false, position: 'sw' },
               crosshair: { mode: 'x' },
               selection: { mode: 'x' }
            };
</script>
EOT;
    }

    public function renderseries ($varname,$series) {
        return <<<EOT
<script type="text/javascript">
            var $varname=[
            $series
            ];
</script>

EOT;
    }

    public function renderplot ($container,$optionname,$seriesname) {
        return <<<EOT
<script type="text/javascript">
    jQuery.plot(jQuery("#${container}"), $optionname, $seriesname);
</script>
        
EOT;
    }

    public function renderaddon ($container) {

return <<<EOT
<script type="text/javascript">
    console.debug("Now binding hover function");
    var flot$container=jQuery("#${container}chart");

    function format_dataset (seriesname,seriesx,seriesy) {
        return seriesname+": "+seriesy;
    }

    flot$container.bind("plothover", function (evt, position, item, placeholder) {
        plot=placeholder.data('plot')
        series = plot.getData();
//        console.debug("Placeholder: ",placeholder);
//        console.debug("PlotContainer: ",plot);
        text=""
//        text="Pos: "+position.x+ " Series "+series.length+"<br/> ";
//        console.debug ("Serien: ",series);
        if (item) {
//            console.debug ("Item: ",item);
            var d = new Date(series[0].data[item.dataIndex][0]);
            text="Datum : "+d.strftime('%d.%m.%Y %H:%M:%S');
            for (var i = 0; i < series.length; i++) {
                text = text+"<br/>"+format_dataset(series[i].label,series[i].data[item.dataIndex][0],series[i].data[item.dataIndex][1]);
            }
        }
        else {
          // Return normal crosshair operation
        }
//        console.debug(text);
        jQuery("#${container}debug").html(text);
      })

    flot$container.bind("plotselected", function (event, ranges) {
        // $("#selection").text(ranges.xaxis.from.toFixed(1) + " to " + ranges.xaxis.to.toFixed(1));
        // var zoom = $("#zoom").attr("checked");
        console.debug("Ranges: ",ranges);
   }); 
</script>
EOT;
}


    public function rendercontainer ($container,$metadata) {
      return <<<EOT
            <div id="${container}chart" style="width:576px;height:300px" class="gpx2chartchart"></div>
            <div id="${container}meta" class="gpx2chartmeta">
            $metadata
            </div>
            <div id="${container}debug" class="gpx2chartdebug" > </div>
EOT;
    }

}

?>
