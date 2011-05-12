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

class WW_GPX_INFO {

    static $container_name='WW_GPX_CONTAINER';

	static $add_script;
    static $foot_script_content='';
 
	function init() {
		add_shortcode('wwgpxinfo', array(__CLASS__, 'handle_shortcode'));

        self::$add_script=0;
        self::$foot_script_content='<script type="text/javascript">$=jQuery;';

        wp_register_script('highcharts', plugins_url().'/ww_gpx_infos'.'/js/highcharts.js', array('jquery'), '2.1.4', false);
		wp_register_script('highchartsexport', plugins_url().'/ww_gpx_infos'.'/js/modules/exporting.js', array('jquery','highcharts'), '2.1.4', false);

        add_action('wp_footer', array(__CLASS__, 'add_script'));
	}

    function create_series($seriesname,$seriescolor,$seriesaxis,$series_data_name,$dashstyle=null) {
        if (!is_null($dashstyle)){
            $dashstyle="dashStyle: '$dashstyle',";               
        } else $dashstyle='';
        return <<<EOT
        {
         name: '$seriesname',
         color: '$seriescolor',
         yAxis: $seriesaxis,
         $dashstyle
         marker: {
            enabled: false
         },
         type: 'spline',
         data: $series_data_name
      }
EOT;

    }

    function create_axis($axistitle,$axiscolor,$leftside=true,$axisno=0,$formatter=null) {
        $opposite='false';
        if ($leftside==false) $opposite='true';

        if (!is_null($formatter)){
            $formatter=<<<EOT
            formatter: function() {
               $formatter
            },
EOT;
        } else $formatter='';

        return <<<EOT
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
EOT;
    }


/*
 * Our shortcode-Handler for GPX-Files
 * It provides support for the necessary parameters that are defined in
 * http://codex.wordpress.org/Shortcode_API
 */
	function handle_shortcode( $atts, $content=null, $code="" ) {
        // $atts    ::= array of attributes
        // $content ::= text within enclosing form of shortcode element
        // $code    ::= the shortcode found, when == callback name
        // examples: [my-shortcode]
        //           [my-shortcode/]
        //           [my-shortcode foo='bar']
        //           [my-shortcode foo='bar'/]
        //           [my-shortcode]content[/my-shortcode]
        //           [my-shortcode foo='bar']content[/my-shortcode]
        //           [wwgpxinfo href="<GPX-Source>" (maxelem="51")     ]
    	self::$add_script++;

        $divno=self::$add_script;

        $error=0;
        $container=self::$container_name.$divno;
        $postcontent='';
        
        $directcontent='<div id="'.$container.'">'."\n";

        /*
         * Evaluate mandatory attributes
         */
        if (! array_key_exists('href',$atts)) {
            $directcontent.="Attribute HREF is missing<br/>";
            $error++;
        }

        /* In Case of errors we abort here*/
        if ($error>0) return $directcontent."</div>";


        /* 
         * Evaluate optional attributes 
         */

        $maxelem=51;
        if (array_key_exists('maxelem',$atts)) {
            $maxelem=intval($atts['maxelem']);
        }

        

        $gpx=new WW_GPX($atts['href']);
    
        $gpx->parse();

        #$text=nl2br($gpx->dump());

        $colors['heartrate']='#AA4643';
        $colors['cadence']='#4572A7';
        $colors['elevation']='#89A54E';
        $colors['speed']='#CACA00';

        $axis['heartrate']=0;
        $axis['cadence']=1;
        $axis['elevation']=2;
        $axis['speed']=3;

        $seriesname['heartrate']='Heartrate';
        $seriesname['cadence']='Cadence';
        $seriesname['elevation']='Elevation';
        $seriesname['speed']='Speed';

        $seriesunit['heartrate']='bpm';
        $seriesunit['cadence']='rpm';
        $seriesunit['elevation']='m';
        $seriesunit['speed']='km/h';
        

        $dashstyle['heartrate']='shortdot';

        $title = $gpx->meta->name;
        $time=$gpx->getall('time');
        $subtitle=strftime('%Y:%m:%d %H:%M',$time[0])."-".strftime('%Y:%m:%d %H:%M',$time[count($time)-1]);

        $time=$gpx->compact_array($time,$maxelem);

        $time=array_map(function($value) {
            return strftime('%H:%M:%S',$value);
        }, $time);

        $hrs=$gpx->compact_array($gpx->getall('heartrate'),$maxelem);
        $elev=$gpx->compact_array($gpx->getall('elevation'),$maxelem);
        $cadence=$gpx->compact_array($gpx->getall('cadence'),$maxelem);
        $distance=$gpx->compact_array($gpx->getall('distance'),$maxelem);
        $speed=$gpx->compact_array($gpx->getall('speed'),$maxelem);

        $directcontent.='<script type="text/javascript">'."\n";
        $directcontent.="var dtimes$divno = ['".join("','",$time)."'];\n";
        $directcontent.="var hrs$divno = [".join(',',$hrs)."];\n";

        $directcontent.="var elev$divno = [".join(',',$elev)."];\n";

        $directcontent.="var cadence$divno = [".join(',',$cadence)."];\n";
        $directcontent.="var distance$divno = [".join(',',$distance)."];\n";
        $directcontent.="var speed$divno = [".join(',',$speed)."];\n";
        $directcontent.="</script>\n";

        $metadata="Spd: ".$gpx->averagespeed()."km/h HR: ".$gpx->averageheartrate()."bpm Total: ".$gpx->totaldistance()." km";

        $directcontent.=$gpx->dump();

        $directcontent.=<<<EOT
        <div id="${container}chart"></div>
        <div id="${container}meta">
        $metadata
        </div>
</div>
EOT;

        $yaxis=array();
        $series=array();
        $series_units=array();

        array_push($yaxis,self::create_axis('Heartrate (bpm)',$colors['heartrate'],true,0));
        array_push($yaxis,self::create_axis('Cadence (rpm)',$colors['cadence'],true,1)); 
        array_push($yaxis,self::create_axis('Elevation',$colors['elevation'],false,2,"return this.value + ' m';")); 
        array_push($yaxis,self::create_axis('Speed (km/h)',$colors['speed'],false,3)); 

        array_push($series,self::create_series($seriesname['heartrate'],$colors['heartrate'],$axis['heartrate'],"hrs$divno",$dashstyle['heartrate']));
        array_push($series,self::create_series($seriesname['cadence'],$colors['cadence'],$axis['cadence'],"cadence$divno",$dashstyle['cadence']));
        array_push($series,self::create_series($seriesname['elevation'],$colors['elevation'],$axis['elevation'],"elev$divno",$dashstyle['elevation']));
        array_push($series,self::create_series($seriesname['speed'],$colors['speed'],$axis['speed'],"speed$divno",$dashstyle['speed']));

        array_push($series_units,"'".$seriesname['heartrate']."':'".$seriesunit['heartrate']."'");
        array_push($series_units,"'".$seriesname['cadence']."':'".$seriesunit['cadence']."'");
        array_push($series_units,"'".$seriesname['elevation']."':'".$seriesunit['elevation']."'");
        array_push($series_units,"'".$seriesname['speed']."':'".$seriesunit['speed']."'");

        $series_units = join (',',$series_units);

        $postcontent.=<<<EOT
 chart$divno = new Highcharts.Chart({
      chart: {
         renderTo: '${container}chart',
         zoomType: 'xy'
      },
      title: {
         text: '$title'
      },
      subtitle: {
         text: '$subtitle'
      },
      xAxis: [{
         categories: dtimes$divno,
         labels: {
            step: 2,
            rotation: 90,
            align: 'left'
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

            var s = '<b>'+ this.x +'</b>';
            
            $.each(this.points, function(i, point) {
                var unit = { $series_units } [point.series.name];
                s += '<br/><span style="color:'+point.series.color+'">'+ point.series.name+':</span>'+ Math.round(point.y*100)/100 +' '+ unit;
            });
            
            return s;
          }
      },
      legend: {
         layout: 'horizontal',
         align: 'center',
         verticalAlign: 'bottom',
         y:-80,
         floating: true,
         backgroundColor: '#FFFFFF'
      },
      series: [
EOT;

$postcontent.=join(',',$series);

$postcontent.=<<<EOT
        ],
      exporting: {
        enabled: true,
        filename: 'custom-file-name'
      }
   });
EOT;

    self::$foot_script_content.=$postcontent;
    return $directcontent;

    }
 
	function add_script() {
        if (self::$add_script>0) {
            wp_print_scripts('highcharts');
        	wp_print_scripts('highchartsexport');

            print self::$foot_script_content;
            print "</script>";
    		self::$add_script=0;
            self::$foot_script_content='<script type="text/javascript">$=jQuery;';

        }
	}
}
 
/*
 * I just define a small test, wether or not the add_shortcode function 
 * already exists. This allows me to do a compilation test of this file
 * without the full overhead of wordpress
 */
if (! function_exists('add_shortcode')) {
        function wp_register_script() {
        }
        function plugins_url() {
        }
        function add_action() {
        }
        function wp_print_scripts() {
        }
        function add_shortcode ($shortcode,$function) {
                echo "Only Test-Case: $shortcode: $function";

                print WW_GPX_INFO::handle_shortcode(array('href'=>'http://sonne/heartrate.gpx'),null,'');
                print WW_GPX_INFO::add_script();
        };
}


WW_GPX_INFO::init();

?> 
