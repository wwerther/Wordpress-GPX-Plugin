<?php
// vim: set ts=4 et nu ai syntax=php indentexpr= :vim
/*
Plugin Name: GPX Infos
Plugin URI: http://wwerther.de/
Description: GPX-Infos - a WP-Plugin for extracting some nice graphs from GPX-Files
Version: 0.1
Author: Walter Werther
Author URI: http://wwerther.de/
Update Server: http://wwerther.de/wp-content/download/wp/
Min WP Version: 3.1.2
Max WP Version: 3.1.2
 */


require_once(dirname(__FILE__).'/ww_gpx.php');

/*
 * Our shortcode-Handler for GPX-Files
 * It provides support for the necessary parameters that are defined in
 * http://codex.wordpress.org/Shortcode_API
 */
function ww_gpx_info_handler( $atts, $content=null, $code="" ) {
    // $atts    ::= array of attributes
    // $content ::= text within enclosing form of shortcode element
    // $code    ::= the shortcode found, when == callback name
    // examples: [my-shortcode]
    //           [my-shortcode/]
    //           [my-shortcode foo='bar']
    //           [my-shortcode foo='bar'/]
    //           [my-shortcode]content[/my-shortcode]
    //           [my-shortcode foo='bar']content[/my-shortcode]
    #        return 'Das ist nur ein Tes---';
    #
    if (! array_key_exists('href',$atts)) {
        return 'Attribute HREF is missing';
    }

    $gpx=new WW_GPX($atts['href']);

    $gpx->parse();


#$text=nl2br($gpx->dump());

$time=$gpx->getall('time');
$hrs=$gpx->getall('heartrate');
$elev=$gpx->getall('elevation');
$cadence=$gpx->getall('cadence');
$count=count($hrs);

$text.=<<<EOT
    <div id="chart_div">Leeres DIV</div>
    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript">
      google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = new google.visualization.DataTable(
         {
            cols: [
                   {id: 'time', label: 'Time', type: 'string'},
                   {id: 'hr', label: 'Heartrate', type: 'number'},
                   {id: 'ele2', label: 'Elevation', type: 'string'},
                   {id: 'cadence', label: 'Cadence', type: 'number'},
                   {id: 'Elevation', label: 'Elevation', type: 'number'},
                  ],
            rows: [
EOT;



#$text.= "data.addRows($count);\n";
for ($c=0;$c<$count;$c++) {

   $text.="{c:[{v: '".$time[$c]."'}, {v: ".$hrs[$c]."}, {v: ".$elev[$c]."}, {v: ".$cadence[$c]."}, {v: ".$elev[$c]."}]},\n";
/*                    {c:[{v: 'Eat'}, {v: 2}]},
                    {c:[{v: 'Commute'}, {v: 2}]},
                    {c:[{v: 'Watch TV'}, {v:2}]},
                    {c:[{v: 'Sleep'}, {v:7, f:'7.000'}]}
/*
    $text.= "data.setValue($c, 0,'".$time[$c]."' );\n";
    $text.= "data.setValue($c, 1,".$hrs[$c]." );\n";
    $text.= "data.setValue($c, 2,".$elev[$c]." );\n";
    $text.= "data.setValue($c, 3,".$cadence[$c]." );\n";*/
}

$text.=<<<EOT

                  ]
         },
         0.6
        );
EOT;

/*        data.addColumn('number', 'Sales');
        data.addColumn('number', 'Expenses');
        data.addRows(4);

        
        data.setValue(0, 0, '2004');
        data.setValue(0, 1, 1000);
        data.setValue(0, 2, 400);
        data.setValue(1, 0, '2005');
        data.setValue(1, 1, 1170);
        data.setValue(1, 2, 460);
        data.setValue(2, 0, '2006');
        data.setValue(2, 1, 860);
        data.setValue(2, 2, 580);
        data.setValue(3, 0, '2007');
        data.setValue(3, 1, 1030);
        data.setValue(3, 2, 540);
*/
$text.=<<<EOT
        var chart = new google.visualization.LineChart(document.getElementById('chart_div'));
        chart.draw(data, {
                width: 600, 
                height: 400, 
                title: 'Heartrate',
                curveType: "function",
                'displayAnnotations': true,
                'thickness': 2,
                'scaleColumns': [1, 2]
                });
      }
    </script>
EOT;

    return $text;

    exit;
}


/*
 * I just define a small test, wether or not the add_shortcode function 
 * already exists. This allows me to do a compilation test of this file
 * without the full overhead of wordpress
 */
if (! function_exists('add_shortcode')) {
        function add_shortcode ($shortcode,$function) {
                echo "Only Test-Case: $shortcode: $function";

                print ww_gpx_info_handler(array('href'=>'http://sonne/heartrate.gpx'),null,'');
        };
}

/*
 * Register our shortcode to the Wordpress-Handlers
 */
add_shortcode( 'wwgpxinfo', 'ww_gpx_info_handler' );

?> 
