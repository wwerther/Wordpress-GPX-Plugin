<?php
// vim: set ts=4 et nu ai syntax=php indentexpr= ff=unix :vim

class render_highchart {

    public $script_depencies = array ('highcharts','highchartsexport');

	public function create_series($seriesname,$seriescolor,$seriesaxis,$series_data_name,$dashstyle=null,$seriestype=null) {
        $dashstyle=is_null($dashstyle)? '' : "dashStyle: '$dashstyle',";               
        $seriestype=is_null($seriestype)? "type: 'spline'," : "type: '$seriestype',";               
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
             data: $series_data_name
          }
        ";
    }

 	public function create_axis($axistitle,$axiscolor,$leftside=true,$axisno=0,$formatter=null) {
        $opposite='false';
        if ($leftside==false) $opposite='true';

        if (!is_null($formatter)){
            $formatter="
            formatter: function() {
               $formatter
            },
            ";
        } else $formatter='';

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

    public function create_xaxis ($mode='time') {
        return '';
    }

    public function renderoptions  ($varname,$xaxes,$yaxes) {
        return '';
    }

     public function renderseries ($varname,$series) {
        return '';
     }

     public function renderplot ($container,$optionname,$seriesname) {
        return '';
     }

     public function renderaddon ($container) {
        return '';
     }

     public function rendercontainer ($container,$metadata) {
        return '';
     }

}

?>
