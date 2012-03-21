<?php
// vim: set ts=4 et nu ai syntax=php indentexpr= :vim
/*
Plugin Name: gpx2chart
Plugin URI: http://wordpress.org/extend/plugins/gpx2chart/
Description: gpx2chart - a WP-Plugin for extracting some nice graphs from GPX-Files
Version: 0.2.2
Author: Walter Werther
Author URI: http://wwerther.de/
Update Server: http://downloads.wordpress.org/plugin
Min WP Version: 3.1.2
Max WP Version: 3.3.1
 */


define ('GPX2CHART_PLUGIN_VER','0.2.2');

// Include helper
require_once(dirname(__FILE__).'/ww_gpx_helper.php');

if (! defined('GPX2CHART_SHORTCODE')) define('GPX2CHART_SHORTCODE','gpx2chart');

if ( ! defined( 'WP_CONTENT_URL' ) )
      define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content' );
if ( ! defined( 'WP_CONTENT_DIR' ) )
      define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
if ( ! defined( 'WP_PLUGIN_URL' ) )
      define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );
if ( ! defined( 'WP_PLUGIN_DIR' ) )
      define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );
define ("GPX2CHART_PLUGIN_URL", WP_PLUGIN_URL."/gpx2chart/");
#define ("GPX2CHART_PLUGIN_ICONS_URL", GPX2CHART_PLUGIN_URL."icons/");


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

    public function __construct() {
        $this->init();
    }
 
	public static function init() {

        add_action('admin_menu', array(__CLASS__, 'admin_menu'));

		add_shortcode(GPX2CHART_SHORTCODE, array(__CLASS__, 'handle_shortcode'));

        self::$add_script=0;
        if (self::$debug) {
            wp_register_script('strftime', plugins_url('/js/helper/strftime.js',__FILE__)) ;
            wp_register_script('sprintf', plugins_url('/js/helper/sprintf.js',__FILE__)) ;

            wp_register_script('highcharts', plugins_url('/js/highcharts/highcharts.src.js',__FILE__), array('jquery'), '2.1.4', false);
   	        wp_register_script('highchartsexport', plugins_url('/js/highcharts/modules/exporting.js',__FILE__), array('jquery','highcharts'), '2.1.4', false);

            wp_register_script('excanvas', plugins_url('/js/flot/excanvas.js',__FILE__), array('jquery'), '2.1.4', false);

            wp_register_script('flot', plugins_url('/js/flot/jquery.flot.js',__FILE__), array('jquery','excanvas','highchartsexport','strftime','sprintf'), '2.1.4', false);
            wp_register_script('flotcross', plugins_url('/js/flot/jquery.flot.crosshair.js',__FILE__), array('jquery','flot'), '2.1.4', false);
            wp_register_script('flotnavigate', plugins_url('/js/flot/jquery.flot.navigate.js',__FILE__), array('jquery','flot'), '2.1.4', false);
            wp_register_script('flotselection', plugins_url('/js/flot/jquery.flot.selection.js',__FILE__), array('jquery','flot'), '2.1.4', false);
        } else {
            wp_register_script('strftime', plugins_url('/js/helper/strftime.min.js',__FILE__)) ;
            wp_register_script('sprintf', plugins_url('/js/helper/sprintf.min.js',__FILE__)) ;

            wp_register_script('highcharts', plugins_url('/js/highcharts/highcharts.js',__FILE__), array('jquery'), '2.1.4', false);
    	    wp_register_script('highchartsexport', plugins_url('/js/highcharts/modules/exporting.js',__FILE__), array('jquery','highcharts'), '2.1.4', false);
            wp_register_script('excanvas', plugins_url('/js/flot/excanvas.min.js',__FILE__), array('jquery'), '2.1.4', false);

            wp_register_script('flot', plugins_url('/js/flot/jquery.flot.min.js',__FILE__), array('jquery','excanvas','highchartsexport','strftime','sprintf'), '2.1.4', false);
            wp_register_script('flotcross', plugins_url('/js/flot/jquery.flot.crosshair.min.js',__FILE__), array('jquery','flot'), '2.1.4', false);
            wp_register_script('flotnavigate', plugins_url('/js/flot/jquery.flot.navigate.min.js',__FILE__), array('jquery','flot'), '2.1.4', false);
            wp_register_script('flotselection', plugins_url('/js/flot/jquery.flot.selection.min.js',__FILE__), array('jquery','flot'), '2.1.4', false);
        }
        wp_enqueue_style('GPX2CHART', plugins_url('css/gpx2chart.css',__FILE__), false, '1.0.0', 'screen');
	}


	public static function formattime($value) {
            return strftime('%H:%M:%S',$value);
	}


	function admin_menu($not_used){
    // place the info in the plugin settings page
		add_options_page(__('GPX2Chart Settings', 'GPX2Chart'), __('GPX2Chart', 'GPX2Chart'), 5, basename(__FILE__), array('GPX2CHART', 'options_page_gpx'));
	}

	public static function options_page_gpx() {
        if(isset($_POST['Options'])){
		} else{
#			add_option('osm_custom_field', 0);
#			add_option('osm_zoom_level', 0);
		}
    // name of the custom field to store Long and Lat
    // for the geodata of the post
#		$osm_custom_field  = get_option('osm_custom_field');                                                  

    // zoomlevel for the link the OSM page
#    $osm_zoom_level    = get_option('osm_zoom_level');
    include('gpx2chart_options.php');	

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
        self::$debug=self::$debug ? self::$debug : in_array('debug',$atts);

        $divno=self::$add_script;

 
        $error=array();
        $container=self::$container_name.$divno;
        $directcontent='';
        if ($divno==1) {
            $directcontent.=self::basicscript();
        }

        $directcontent.=self::debug(var_export ($atts,true),"Attributes");


        /* Scan for available rendering engines */
        $dh  = opendir(dirname(__FILE__).'/render');
        $engines=array();
        while (false !== ($filename = readdir($dh))) {
            /* Skip meta-Directories */
            if (substr($filename,0,1) == '.') continue;
            $engines[] = basename($filename,'.php');
        }
        $directcontent.=self::debug(var_export($engines,true),"Engines");

        $rendername=array_key_exists('render',$atts) ? $atts['render'] : self::$default_rendername;
        $rendername=in_array($rendername, $engines) ? $rendername : self::$default_rendername;

        if (! class_exists($rendername)) require_once(dirname(__FILE__)."/render/$rendername.php");
        $rendername='render_'.$rendername;
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
        if (count($error)>0) return $directcontent."<div class='gpx2charterror'>".join("<br/>\n",$error)."</div></div>";

        # Input series provided by GPX-files are: 
        #                                               (time, lat, lon) heartrate, cadence, elevation 
        # Calculated series are:                        
        #                                               speed, distance, interval, rise, fall
        # Series that make sense to be displayed as graph: 
        #                                               heartrate, cadence, elevation, speed
        # Series that make sense to be displayed additional in the meta-data: 
        #                                               time, totaldistance, totalinterval, totalrise, totalfall

        $colors['heartrate']='#AA4643';
        $colors['cadence']='#4572A7';
        $colors['elevation']='#89A54E';
        $colors['speed']='#CACA00';
        $colors['time']='#000000';

        $colors['totaldistance']='#000000';
        $colors['totalinterval']='#000000';
        $colors['totalrise']='#000000';
        $colors['totalfall']='#000000';
   
        $axistitle['heartrate']='Heartrate (bpm)';
        $axistitle['cadence']='Cadence (rpm)';
        $axistitle['elevation']='Elevation (m)';
        $axistitle['speed']='Speed (km/h)';

        $seriesname['heartrate']='Heartrate';
        $seriesname['cadence']='Cadence';
        $seriesname['elevation']='Elevation';
        $seriesname['speed']='Speed';
        $seriesname['distance']='Distance';
        $seriesname['time']='Time';
        $seriesname['totaldistance']='Distance';
        $seriesname['totalinterval']='Time';
        $seriesname['totalrise']='Rise';
        $seriesname['totalfall']='Fall';
        $seriesname['lat']='Latitude';
        $seriesname['lon']='Longitude';

        $axisleft['heartrate']=true;
        $axisleft['cadence']=true;
        $axisleft['elevation']=false;
        $axisleft['speed']=false;

        $jsvar['xAxis']="gpx2chartdata[$divno]['xAxis']";

        $jsvar['heartrate']="gpx2chartdata[$divno]['heartrate']";
        $jsvar['cadence']="gpx2chartdata[$divno]['cadence']";
        $jsvar['elevation']="gpx2chartdata[$divno]['elevation']";
        $jsvar['speed']="gpx2chartdata[$divno]['speed']";

        $jsvar['totaldistance']="gpx2chartdata[$divno]['totaldistance']";
        $jsvar['totalinterval']="gpx2chartdata[$divno]['totalinterval']";
        $jsvar['totalrise']="gpx2chartdata[$divno]['totalrise']";
        $jsvar['totalfall']="gpx2chartdata[$divno]['totalfall']";
        $jsvar['lat']="gpx2chartdata[$divno]['lat']";
        $jsvar['lon']="gpx2chartdata[$divno]['lon']";

        # Formatter for Axis-Labels
        $formatter['heartrate']='return value.toFixed(axis.tickDecimals) + "bpm";';
        $formatter['cadence']='return value.toFixed(axis.tickDecimals) + "rpm";';
        $formatter['elevation']='return value.toFixed(axis.tickDecimals) + "m";';
        $formatter['speed']='return value.toFixed(axis.tickDecimals);';
        $formatter['distance']='return value.toFixed(axis.tickDecimals) + "km";';
        $formatter['time']='return value.toFixed(axis.tickDecimals) + "h";';

        # Formatter for Tooltip-Values
        $labelformat['heartrate']='return value + " bpm";';
        $labelformat['cadence']='return value + " rpm";';
        $labelformat['elevation']='return Math.round(value) + " m";';
        $labelformat['speed']='return Math.round(value*100)/100 + " km/h";';
        $labelformat['totaldistance']='if (value>1000) return sprintf("%.2f km",Math.round(value/10)/100); return Math.round(value) + " m"';
        $labelformat['totalinterval']='return sprintf("%02d:%02d:%02d",Math.floor(value/3600),Math.floor(value/60)%60,value%60);';
        $labelformat['totalrise']='if (value>1000) return Math.round(value/10)/100 + " km"; return Math.round(value) + " m"';
        $labelformat['totalfall']='if (value>1000) return Math.round(value/10)/100 + " km"; return Math.round(value) + " m"';

        $dashstyle['heartrate']='shortdot';
        $seriestype['elevation']='areaspline';

        # Adjust the display of elevation a little bit, so the graph does not look to rough if we don't have high differences between min and max
        # In this case we have at least 40m that are displayed
        $additionalparameters['elevation']='min: '.($gpx->min('elevation')-20).',max: '.($gpx->max('elevation')+20).',';

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

        $gpx->setmaxelem($maxelem);
#       $gpx->setmaxelem(0);

        $met=array();
        foreach ($metadata as $elem) {
            $text="<tr><th style=\"padding: 0px 0px 0px 10px;border:none;color:".$colors[$elem].";\">".$seriesname[$elem]."</th><td style=\"padding: 0px 0px 0px 10px;border:none\">";
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
            $text.=" ".$seriesunit[$elem]."</td></tr>";
            array_push($met,$text);
        }
        $metadata="<table>".join(' ',$met)."</table>";

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
            array_push($yaxis,$render->create_axis($axistitle[$elem],$colors[$elem],$axisleft[$elem],$axisno,$formatter[$elem],$additionalparameters[$elem]));
            array_push($series,$render->create_series($elem,$seriesname[$elem],$colors[$elem],$axisno,$jsvar[$elem],$dashstyle[$elem],$seriestype[$elem],$labelformat[$elem]));
    #        array_push($series_units,"'".$seriesname[$elem]."':'".$seriesunit[$elem]."'");
            $axisno++;
        }
        foreach (array('totaldistance','totalinterval' /*,'totalrise','totalfall','lat','lon' */) as $elem) {
            array_push($series,$render->create_series($elem,$seriesname[$elem],$colors[$elem],-1,$jsvar[$elem],$dashstyle[$elem],$seriestype[$elem],$labelformat[$elem]));
        }

        $xaxis=array();
        array_push($xaxis,$render->create_xaxis());
    
        $directcontent.='<script type="text/javascript">'."gpx2chartdata[$divno]=new Array();";
        foreach ($process as $elem) {
           $directcontent.=$jsvar[$elem]."= new Array(".join(",",$gpx->return_pair($elem) ).");\n";
        }

        foreach (array('totaldistance','totalinterval','totalrise','totalfall','lat','lon') as $elem) {
           $directcontent.=$jsvar[$elem]."= new Array(".join(",",$gpx->return_pair($elem) ).");\n";
 #           $directcontent.=$jsvar[$elem]."={".join(",",$gpx->return_assoc($elem) )."};\n";
        }

#        $directcontent.=$jsvar['totaldistance']."={".join(",",$gpx->return_assoc('totaldistance'))."};\n";
#        $directcontent.=$jsvar['totalinterval']."={".join(",",$gpx->return_assoc('totalinterval') )."};\n";#

#        $directcontent.=$jsvar['lat']."={".join(",",$gpx->return_assoc('lat') )."};\n";
#        $directcontent.=$jsvar['lon']."={".join(",",$gpx->return_assoc('lon') )."};\n";
        $directcontent.="</script>\n";

        $directcontent.=$render->rendercontainer($container,$metadata);
        $directcontent.=$render->renderoptions("flotoptions$divno",join(',',$xaxis),join(',',$yaxis));
        $directcontent.=$render->renderseries("flotseries$divno",join(',',$series));
        $directcontent.=$render->renderplot($container."chart","flotseries$divno","flotoptions$divno");
        $directcontent.=$render->renderaddon($container);


        $directcontent.="</div>";


        $directcontent.=file_get_contents(GPX2CHART_PLUGIN_URL.'/jogging.profile');

        $directcontent.="";


        return $directcontent;

    }

    function basicscript() {
    return <<<EOT
        <!-- Initial GPX2Chart Javascript -->
        <script type="text/javascript">
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
        </script>
EOT;
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

$pGPX2Chart=new GPX2CHART();
#GPX2CHART::init();

?>
