<?php
// vim: set ts=4 et nu ai syntax=php indentexpr= ff=unix :vim

class render_flot {

    public $script_depencies = array('flot','flotcross','excanvas');

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
            label: '$axistitle',
            position: '$position',
            $formatter
            color: '$axiscolor'
      }
      ";
    }


}

?>
