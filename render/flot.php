<?php
// vim: set ts=4 et nu ai syntax=php indentexpr= ff=unix :vim

class render_flot {

    public $script_depencies = array('flot','flotcross','flotnavigate','flotselection','excanvas','strftime');

	public function create_series($seriesid,$seriesname,$seriescolor,$seriesaxis,$series_data_name,$dashstyle=null,$seriestype=null,$labelformat=null) {
        $seriestype=is_null($seriestype)? "" : "lines: { fill: 0.3},";
        $labelformat=is_null($labelformat)? "" : "labelformat: function(value) { $labelformat },";
        return " {
            identifier: '$seriesid',
            label: '$seriesname',
            yaxis: $seriesaxis,
            color: '$seriescolor',
            $seriestype
            $labelformat
            data: $series_data_name
        }
        ";
    }

 	public function create_axis($axistitle,$axiscolor,$leftside=true,$axisno=0,$formatter=null,$free=null) {
        $position=$leftside ? 'left' : 'right';
        $free=is_null($free)? "" : $free;
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
            $free
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
            $varname={
                grid: { 
                    hoverable: true,
                    mouseActiveIgnoreY: true,
                    autoHighlight: false
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
           $varname=[
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

    public function renderaddon ($container,$instance) {

return <<<EOT
<script type="text/javascript">
    if (console) {
        if (console.debug) {
            console.debug("Now binding hover function");
        }
    }
    gpx2chart['handle'][$instance]=jQuery("#${container}chart");

    function format_dataset (color,seriesname,seriesx,seriesy) {
        return "<div class='gpx2chartrow'><div class='gpx2chartlabel' style='color:"+color+";'>"+seriesname+"</div><div class='gpx2chartvalue'>"+seriesy+"</div></div>";
    }

    gpx2chart['handle'][$instance].bind("plothover", function (evt, position, item, placeholder, orgevent){
        plot=placeholder.data('plot')
        series = plot.getData();
        text=""
        if (item) {
            var d = new Date(series[0].data[item.dataIndex][0]);
            text="<div class='gpx2charttoolhead'>"+d.strftime('%d.%m.%Y %H:%M:%S')+"</div><div>";
            for (var i = 0; i < series.length; i++) {
                value=series[i].data[item.dataIndex][1];
                color='#000';
                if (series[i].color) color=series[i].color;
                if (series[i].labelformat) value=series[i].labelformat(value);
                text = text+format_dataset(color,series[i].label,series[i].data[item.dataIndex][0],value);
            }
            text=text+"</div>";
            jQuery("#${container}tooltip").html(text);
            jQuery("#${container}tooltip").css("top",(orgevent.clientY+10)+"px");
            jQuery("#${container}tooltip").css("left",(orgevent.clientX+10)+"px");
            jQuery("#${container}tooltip").css("display","block");
        }
        else {
          // Return normal crosshair operation
          jQuery("#${container}tooltip").css("display","none");
        }
//        gpx2chartdebug(,"#${container}debug");
      })

    gpx2chart['handle'][$instance].bind("plotselected", function (event, ranges) {
        // $("#selection").text(ranges.xaxis.from.toFixed(1) + " to " + ranges.xaxis.to.toFixed(1));
        // var zoom = $("#zoom").attr("checked");
        //gpx2chartdebug("Ranges: ",ranges);
   }); 
</script>
EOT;
}


    public function rendercontainer ($container,$metadata) {
      return <<<EOT
EOT;
    }

}

?>
