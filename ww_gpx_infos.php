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
define('GPX2CHART_SHORTCODE','gpx2chart');

class GPX2CHART {

    static $container_name='GPX2CHART_CONTAINER';

	static $add_script;
    static $foot_script_content='';

    static $debug=false;

    static function debug ($text,$headline='') {
        if (self::$debug) {
            return "\n<!-- gpx2chart $headline\n$text\n gpx2chart -->\n";
        }
        return '';
    }
 
	public static function init() {
		add_shortcode(GPX2CHART_SHORTCODE, array(__CLASS__, 'handle_shortcode'));

        self::$add_script=0;
        self::$foot_script_content='<script type="text/javascript">';


        if (self::$debug) {
            wp_register_script('highcharts', plugins_url('/js/highcharts/highcharts.src.js',__FILE__), array('jquery'), '2.1.4', false);
    		wp_register_script('highchartsexport', plugins_url('/js/highcharts/modules/exporting.js',__FILE__), array('jquery','highcharts'), '2.1.4', false);

            wp_register_script('excanvas', plugins_url('/js/flot/excanvas.js',__FILE__), array('jquery'), '2.1.4', false);
            wp_register_script('flot', plugins_url('/js/flot/jquery.flot.js',__FILE__), array('jquery'), '2.1.4', false);
        } else {
            wp_register_script('highcharts', plugins_url('/js/highcharts/highcharts.js',__FILE__), array('jquery'), '2.1.4', false);
    		wp_register_script('highchartsexport', plugins_url('/js/highcharts/modules/exporting.js',__FILE__), array('jquery','highcharts'), '2.1.4', false);

            wp_register_script('excanvas', plugins_url('/js/flot/excanvas.min.js',__FILE__), array('jquery'), '2.1.4', false);
            wp_register_script('flot', plugins_url('/js/flot/jquery.flot.min.js',__FILE__), array('jquery'), '2.1.4', false);

        }

        add_action('wp_footer', array(__CLASS__, 'add_script'));
	}

	public static function create_series($seriesname,$seriescolor,$seriesaxis,$series_data_name,$dashstyle=null,$seriestype=null) {
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

 	public static function create_axis($axistitle,$axiscolor,$leftside=true,$axisno=0,$formatter=null) {
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
            <div id="${container}chart" class="gpx2chartchart"></div>
            <div id="${container}flot" style="width:576px;height:300px" class="gpx2chartchart"></div>
            <div id="${container}meta" class="gpx2chartmeta">
            $metadata
            </div>
            <div id="${container}debug" class="gpx2chartdebug" style="display:none;"> </div>
        </div>
EOT;

        $yaxis=array();
        $series=array();
        $series_units=array();
        $series_names=array();

        $axisno=0;
        foreach ($process as $elem) {
            array_push($yaxis,self::create_axis($axistitle[$elem],$colors[$elem],$axisleft[$elem],$axisno));
            array_push($series,self::create_series($seriesname[$elem],$colors[$elem],$axisno,$jsvar[$elem],$dashstyle[$elem],$seriestype[$elem]));
            array_push($series_units,"'".$seriesname[$elem]."':'".$seriesunit[$elem]."'");
            $axisno++;
        }

        # We need additional entries for names and units
        foreach (array('time','distance') as $elem) {
            array_push($series_names,"'".$elem."':'".$seriesname[$elem]."'");
            array_push($series_units,"'".$seriesname[$elem]."':'".$seriesunit[$elem]."'");
        }

        $series_units = join (',',$series_units);
        $series_names = join (',',$series_names);
#categories: $jsvar[xAxis],
        $postcontent.=<<<EOT
var \$${container}debug = jQuery('#${container}debug');

/*
 * console.log("Log");
 * console.error("Err");
 * console.warn("War");
 * console.debug("Deb"); 
 */

chart$divno = new Highcharts.Chart({
      chart: {
         renderTo: '${container}chart',
         zoomType: 'x'
      },
      title: {
         text: '$title'
      },
      subtitle: {
         text: '$subtitle'
      },
      xAxis: [{
         type: 'datetime',
         labels: {
            formatter: function() {
                return Highcharts.dateFormat('%d.%m %H:%M:%S', this.value);
            },
            rotation: 90,
            align: 'left',
            showFirstLabel: true,
            showLastLabel: true
         }
      }],
      yAxis: [
EOT;

$postcontent.=join(',',$yaxis);

$postcontent.=<<<EOT
      ],
      tooltip: {
         shared: true,
         crosshairs: true,
         borderColor: '#CDCDCD',
         formatter: function() {
            var s = '<b>'+ Highcharts.dateFormat('%d.%m.%Y %H:%M:%S', this.x) +'</b>';
            jQuery.each(this.points, function(i, point) {
                var unit = { $series_units } [point.series.name];
                s += '<br/><span style="font-weight:bold;color:'+point.series.color+'">'+ point.series.name+':</span>'+ Math.round(point.y*100)/100 +' '+ unit+'';
            });
            var name = { $series_names }['distance'];
            var unit = { $series_units }[name];
            s+= '<br/><span style="font-weight:bold;">'+name+':</span></td><td>'+Math.round($jsvar[totaldistance][this.x]/1000*100)/100+unit;
            var name = { $series_names }['time'];
            var unit = { $series_units }[name];
            s+= '<br/><span style="font-weight:bold;">'+name+':</span></td><td>'+Math.floor($jsvar[totalinterval][this.x]/3600)+':'+Math.floor($jsvar[totalinterval][this.x]/60)%60+':'+$jsvar[totalinterval][this.x]%60+unit;
            return s;
          }
      },
      plotOptions: {
         area: {
            fillOpacity: 0.5
         },
         series: {
            point: {
                events: {
                    mouseOver: function() {
                        var lat=$jsvar[lat][this.x];
                        var lon=$jsvar[lon][this.x];
                        \$${container}debug.html('Lat: '+ lat +', Lon: '+ lon);
/*
                        markers=map.getLayersByName('Marker')[0];
                        var ll = new OpenLayers.LonLat(lon,lat).transform(map.displayProjection, map.projection);
                        markers.clearMarkers();
                        var size = new OpenLayers.Size(21,25);
                        var offset = new OpenLayers.Pixel(-(size.w/2), -size.h);
                        var icon = new OpenLayers.Icon('http://wwerther.de/wp-content/plugins/osm/icons/marker_blue.png', size, offset);
                        markers.addMarker(new OpenLayers.Marker(ll,icon));
*/
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
      legend: {
         layout: 'horizontal',
         align: 'center',
         verticalAlign: 'bottom',
         floating: false,
         backgroundColor: '#FFFFFF'
      },
      series: [
EOT;

$postcontent.=join(',',$series);

$postcontent.=<<<EOT
        ],
      exporting: {
        enabled: $enableexport,
        filename: 'custom-file-name'
      }
   });
EOT;


    $postcontent.=<<<EOT

    var flotoptions$divno={
    
    };
    var flotdata$divno=
  [ { label: "Foo", data: [ [10, 1], [17, -14], [30, 5] ] },
    { label: "Bar", data: [ [11, 13], [19, 11], [30, -7] ] } ] ;
    jQuery.plot(jQuery("#${container}flot"), flotdata$divno, flotoptions$divno);
EOT;

    self::$foot_script_content.=$postcontent;
    return $directcontent;

    }
 
	public static function add_script() {
        if (self::$add_script>0) {
            wp_print_scripts('highcharts');
        	wp_print_scripts('highchartsexport');

            wp_print_scripts('flot');
        	wp_print_scripts('excanvas');

            print self::$foot_script_content;
            print "</script>";
        }
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
