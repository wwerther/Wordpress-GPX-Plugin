<?php
// vim: set ts=4 et nu ai syntax=php indentexpr= :vim
/*
Plugin Name: gpx2chart
Plugin URI: http://wordpress.org/extend/plugins/gpx2chart/
Description: gpx2chart - a WP-Plugin for extracting some nice graphs from GPX-Files
Version: 0.1.5
Author: Walter Werther
Author URI: http://wwerther.de/
Update Server: http://downloads.wordpress.org/plugin
Min WP Version: 3.1.2
Max WP Version: 3.1.2
 */


require_once(dirname(__FILE__).'/ww_gpx.php');

if (! defined('GPX2CHART_SHORTCODE')) define('GPX2CHART_SHORTCODE','gpx2chart');

class GPX2CHART {

    static $container_name='GPX2CHART';
    static $default_rendername='flot';
	static $add_script;

    static $debug=true;

    static function debug ($text,$headline='') {
        if (self::$debug) {
            return "\n<!-- gpx2chart $headline\n$text\n gpx2chart -->\n";
        }
        return '';
    }
 
	public static function init() {
		add_shortcode(GPX2CHART_SHORTCODE, array(__CLASS__, 'handle_shortcode'));

        self::$add_script=0;

        if (self::$debug) {
            wp_register_script('excanvas', plugins_url('/js/flot/excanvas.js',__FILE__), array('jquery'), '2.1.4', false);
            wp_register_script('flot', plugins_url('/js/flot/jquery.flot.js',__FILE__), array('jquery'), '2.1.4', false);
            wp_register_script('flotcross', plugins_url('/js/flot/jquery.flot.crosshair.js',__FILE__), array('jquery','flot'), '2.1.4', false);
            wp_register_script('flotaxis', plugins_url('/js/flot-axislabels/jquery.flot.axislabels.js',__FILE__), array('jquery','flot'), '2.1.4', false);
            wp_register_script('flotnavigate', plugins_url('/js/flot/jquery.flot.navigate.js',__FILE__), array('jquery','flot'), '2.1.4', false);
            wp_register_script('flotselection', plugins_url('/js/flot/jquery.flot.selection.js',__FILE__), array('jquery','flot'), '2.1.4', false);
            wp_register_script('strftime', "http://hacks.bluesmoon.info/strftime/strftime.js",__FILE__) ;
        } else {
            wp_register_script('excanvas', plugins_url('/js/flot/excanvas.min.js',__FILE__), array('jquery'), '2.1.4', false);
            wp_register_script('flot', plugins_url('/js/flot/jquery.flot.min.js',__FILE__), array('jquery'), '2.1.4', false);
            wp_register_script('flotcross', plugins_url('/js/flot/jquery.flot.crosshair.min.js',__FILE__), array('jquery','flot'), '2.1.4', false);
            wp_register_script('flotaxis', plugins_url('/js/flot-axislabels/jquery.flot.axislabels.js',__FILE__), array('jquery','flot'), '2.1.4', false);
            wp_register_script('flotnavigate', plugins_url('/js/flot/jquery.flot.navigate.js',__FILE__), array('jquery','flot'), '2.1.4', false);
            wp_register_script('flotselection', plugins_url('/js/flot/jquery.flot.selection.js',__FILE__), array('jquery','flot'), '2.1.4', false);
            wp_register_script('strftime', "http://hacks.bluesmoon.info/strftime/strftime.js",__FILE__) ;
        }

	}


	public static function formattime($value) {
            return strftime('%H:%M:%S',$value);
	}

/*
 * Our shortcode-Handler for GPX-Files
 * It provides support for the necessary parameters that are defined in
 * http://codex.wordpress.org/Shortcode_API
 */
	public static function handle_shortcode( $atts, $content=null, $code="" ) {
        // $atts    ::= array of attributes
        // $content ::= text within enclosing form of shortcode element
        // $code    ::= the shortcode found, when == callback name
        //           [gpx2chart href="<GPX-Source>" (maxelem="51") (debug) (width="90%") (metadata="heartrate cadence distance speed") (display="heartrate cadence elevation speed")]
    	self::$add_script++;

        /* Check if we are in "debug mode". Create a more verbose output then */
        self::$debug=in_array('debug',$atts);

        $divno=self::$add_script;

        $error=array();
        $container=self::$container_name.$divno;
        $directcontent='';
        $postcontent='';

        $directcontent.=self::debug(var_export ($atts,true),"Attributes");

        $rendername=array_key_exists('render',$atts) ? $atts['render'] : self::$default_rendername;
        $rendername=in_array($rendername, array('flot','highcharts')) ? 'render_'.$rendername : 'render_'.self::$default_rendername;

        if (! class_exists($rendername)) require_once(dirname(__FILE__)."/$rendername.php");
        $render=new $rendername();

        foreach ($render->script_depencies as $depency) wp_print_scripts($depency);

        /*
         * Evaluate mandatory attributes
         */
        if (! array_key_exists('href',$atts)) array_push($error,"Attribute HREF is missing");

        /* 
         * Evaluate optional attributes 
         */
        $maxelem=array_key_exists('maxelem',$atts) ? intval($atts['maxelem']) : 51;
        $width=array_key_exists('width',$atts) ? $atts['width'] : '90%';

        /* Create the master container */
        $directcontent.='<div id="'.$container.'" class="gpx2chart" style="width:'.$width.'"'.">\n";

        # read in the GPX-file
        $gpx=new WW_GPX($atts['href']);
        # try to parse the XML only if we don't have errors yet
        if ((count($error)==0) and (! $gpx->parse())) array_push($error,"Error parsing GPX-File");

        /* In case of errors we abort here */
        if (count($error)>0) return $directcontent.join("<br/>\n",$error)."</div>";

        $colors['heartrate']='#AA4643';
        $colors['cadence']='#4572A7';
        $colors['elevation']='#89A54E';
        $colors['speed']='#CACA00';
   
        $axistitle['heartrate']='Heartrate (bpm)';
        $axistitle['cadence']='Cadence (rpm)';
        $axistitle['elevation']='Elevation (m)';
        $axistitle['speed']='Speed (km/h)';

        $axisleft['heartrate']=true;
        $axisleft['cadence']=true;
        $axisleft['elevation']=false;
        $axisleft['speed']=false;

        $jsvar['heartrate']="data[$divno]['hrs']";
        $jsvar['cadence']="data[$divno]['cadence']";
        $jsvar['elevation']="data[$divno]['elevation']";
        $jsvar['speed']="data[$divno]['speed']";
        $jsvar['xAxis']="data[$divno]['xAxis']";
        $jsvar['totaldistance']="data[$divno]['totaldistance']";
        $jsvar['totalinterval']="data[$divno]['totalinterval']";
        $jsvar['lat']="data[$divno]['lat']";
        $jsvar['lon']="data[$divno]['lon']";

        $seriesname['heartrate']='Heartrate';
        $seriesname['cadence']='Cadence';
        $seriesname['elevation']='Elevation';
        $seriesname['speed']='Speed';
        $seriesname['distance']='Distance';
        $seriesname['time']='Time';

        $seriesunit['heartrate']='bpm';
        $seriesunit['cadence']='rpm';
        $seriesunit['elevation']='m';
        $seriesunit['speed']='km/h';
        $seriesunit['distance']='km';
        $seriesunit['time']='h';

        $formatter['heartrate']='return value.toFixed(axis.tickDecimals) + "bpm";';
        $formatter['cadence']='return value.toFixed(axis.tickDecimals) + "rpm";';
        $formatter['elevation']='return value.toFixed(axis.tickDecimals) + "m";';
        $formatter['speed']='return value.toFixed(axis.tickDecimals) + "km/h";';
        $formatter['distance']='return value.toFixed(axis.tickDecimals) + "km";';
        $formatter['time']='return value.toFixed(axis.tickDecimals) + "h";';

        $dashstyle['heartrate']='shortdot';
        $seriestype['elevation']='areaspline';

        $params=array('heartrate','cadence','elevation','speed');
        foreach ($params as $param) {
            $axistitle[$param]=array_key_exists('title_'.$param,$atts) ? $atts['title_'.$param] : $axistitle[$param];
            $colors[$param]=array_key_exists('color_'.$param,$atts) ? $atts['color_'.$param] : $colors[$param];
            $dashstyle[$param]=array_key_exists('dashstyle_'.$param,$atts) ? $atts['dashstyle_'.$param] : $dashstyle[$param];
            $seriestype[$param]=array_key_exists('seriestype_'.$param,$atts) ? $atts['seriestype_'.$param] : $seriestype[$param];
        }

        $enableexport='false';

        # The maximum series that are available
        $process=array('heartrate','cadence','elevation','speed');
        $metadata=array('heartrate','cadence','distance','speed');

        # If we have defined a display variable we intersect the two arrays and take only the ones that are in both
        $process=$atts['display'] ? array_intersect($process,split(' ',$atts['display'])) : $process;
        $metadata=$atts['metadata'] ? array_intersect($metadata,split(' ',$atts['metadata'])) : $metadata;

        # We remove the entries where we don't have data in our GPX-File
        $process=array_diff($process,$gpx->getunavailable());
        $metadata=array_diff($metadata,$gpx->getunavailable());

        $title = $gpx->meta->name;
        $subtitle=strftime('%d.%m.%Y %H:%M',$gpx[0]['time'])."-".strftime('%d.%m.%Y %H:%M',$gpx[-1]['time']);

        $directcontent.='<script type="text/javascript">'."
           if (! data) {
               var data=new Array();
           }
           data[$divno]=new Array();
        ";

        $gpx->setmaxelem($maxelem);
#       $gpx->setmaxelem(0);
        foreach ($process as $elem) {
           $directcontent.=$jsvar[$elem]."= new Array(".join(",",$gpx->return_pair($elem) ).");\n";
        }
        $directcontent.=$jsvar['totaldistance']."={".join(",",$gpx->return_assoc('totaldistance'))."};\n";
        $directcontent.=$jsvar['totalinterval']."={".join(",",$gpx->return_assoc('totalinterval') )."};\n";

        $directcontent.=$jsvar['lat']."={".join(",",$gpx->return_assoc('lat') )."};\n";
        $directcontent.=$jsvar['lon']."={".join(",",$gpx->return_assoc('lon') )."};\n";

        $directcontent.="</script>\n";

        $met=array();
        foreach ($metadata as $elem) {
            $text=$seriesname[$elem].": ";
            switch ($elem) {
                case 'heartrate': {
                    $text.=$gpx->averageheartrate();
                    break;
                }
                case 'cadence': {
                    $text.=$gpx->averagecadence();
                    break;
                }
                case 'speed': {
                    $text.=$gpx->averagespeed();
                    break;
                }
                case 'distance': {
                    $text.=$gpx->totaldistance();
                    break;
                }
            }
            $text.=" ".$seriesunit[$elem];
            array_push($met,$text);
        }
        $metadata=join(' ',$met);

        $directcontent.=<<<EOT
            <div id="${container}chart" style="width:576px;height:300px" class="gpx2chartchart"></div>
            <div id="${container}meta" class="gpx2chartmeta"> <!-- style="-webkit-transform: rotate(90deg);-moz-transform: rotate(90deg);-ms-transform: rotate(90deg);-o-transform: rotate(90deg);transform: rotate(90deg);"> -->
            $metadata
            </div>
            <div id="${container}debug" class="gpx2chartdebug" > </div>
        </div>
EOT;

        $yaxis=array();
        $series=array();
        $series_units=array();
        $series_names=array();

        # We need additional entries for names and units
        foreach (array('time','distance') as $elem) {
            array_push($series_names,"'".$elem."':'".$seriesname[$elem]."'");
            array_push($series_units,"'".$seriesname[$elem]."':'".$seriesunit[$elem]."'");
        }

        $series_units = join (',',$series_units);
        $series_names = join (',',$series_names);
#categories: $jsvar[xAxis],

    $yaxis=array();
    $series=array();
    $series_units=array();
    $series_names=array();
    $axisno=1;
    foreach ($process as $elem) {
        array_push($yaxis,$render->create_axis($axistitle[$elem],$colors[$elem],$axisleft[$elem],$axisno,$formatter[$elem]));
        array_push($series,$render->create_series($seriesname[$elem],$colors[$elem],$axisno,$jsvar[$elem],$dashstyle[$elem],$seriestype[$elem]));
#        array_push($series_units,"'".$seriesname[$elem]."':'".$seriesunit[$elem]."'");
        $axisno++;
    }

    $directcontent.=<<<EOT
<script type="text/javascript">
    var flotoptions$divno={
           grid: { 
            hoverable: true,
            mouseActiveIgnoreY: true,
            autoHighlight: false,
           },
           xaxes: [ { mode: 'time' } ],
           yaxes: [
EOT;

    $directcontent.=join(',',$yaxis);

    $directcontent.=<<<EOT
           ],
           legend: { show: false, position: 'sw' },
           crosshair: { mode: 'x' },
           selection: { mode: 'x' },
           panning: {
                interactive: true
            }
};
EOT;

    $directcontent.="var flotdata$divno=[\n";
    $directcontent.=join(',',$series);
    $directcontent.="\n];\n";

$directcontent.=<<<EOT
    var flot$container=jQuery("#${container}chart")
    jQuery.plot(flot$container, flotdata$divno, flotoptions$divno);
    console.debug("Now binding hover function");


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
        // console.debug("Ranges: ",ranges);
    }); 


</script>
EOT;

    return $directcontent;

    }
 
}
 
/*
 * I just define a small test-scenario, wether or not the add_shortcode function 
 * already exists. This allows me to do a compilation test of this file
 * without the full overhead of wordpress
 * This is used when I do a git commit to guarantee, that the code will compile
 * properly
 */
if (! function_exists('add_shortcode')) {
        function wp_register_script($name, $plugin, $deps, $vers, $switch) {
            print "REGISTER: $name, $plugin\n";
        }
        function plugins_url($module, $file) {
            print "PLUGINS_URL: $module, $file \n";
            return $module;
        }
        function add_action($hook, $action) {
            print "ADD_ACTION: $hook, $action[1]\n";
        }
        function wp_print_scripts($script) {
            print "WP_PRINT_SCRIPT: $script\n";
        }
        function add_shortcode ($shortcode,$function) {
                echo "Only Test-Case: $shortcode: $function";

                print GPX2CHART::handle_shortcode(array('href'=>'http://sonne/cadence.gpx','maxelem'=>0),null,'');
                print GPX2CHART::add_script();
        };
}


GPX2CHART::init();

?>
