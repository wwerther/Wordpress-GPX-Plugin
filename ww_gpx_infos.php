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
    	self::$add_script++;

        $divno=self::$add_script;

        $error=0;
        $container=self::$container_name.$divno;
        $postcontent='';
        
        $directcontent='<div id="'.$container.'">'."\n";
        if (! array_key_exists('href',$atts)) {
            $directcontent.="Attribute HREF is missing<br/>";
            $error++;
        }


        if ($error>0) return $directcontent;

        $gpx=new WW_GPX($atts['href']);
    
        $gpx->parse();

        #$text=nl2br($gpx->dump());


        $title = $gpx->meta->name;
        $time=$gpx->getall('time');
        $subtitle=strftime('%Y:%m:%d %H:%M',$time[0])."-".strftime('%Y:%m:%d %H:%M',$time[count($time)-1]);

        $time=$gpx->compact_array($time,40);

        $time=array_map(function($value) {
            return strftime('%H:%M:%S',$value);
        }, $time);

        $hrs=$gpx->compact_array($gpx->getall('heartrate'),10);
        $elev=$gpx->compact_array($gpx->getall('elevation'),10);
        $cadence=$gpx->compact_array($gpx->getall('cadence'),10);
        $distance=$gpx->compact_array($gpx->getall('distance'),10);
        $speed=$gpx->compact_array($gpx->getall('speed'),10);

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
            step: 1,
            rotation: 90,
            align: 'left'
         }
      }],
      yAxis: [{ // Primary yAxis
         labels: {
            formatter: function() {
               return this.value +'bpm';
            },
            style: {
               color: '#AA4643'
            }
         },
         title: {
            text: 'Heartrate',
            style: {
               color: '#AA4643'
            }
         },
         opposite: false
         
      }, { // Secondary yAxis
         gridLineWidth: 0,
         title: {
            text: 'Cadence',
            style: {
               color: '#4572A7'
            }
         },
         labels: {
            formatter: function() {
               return this.value +' rpm';
            },
            style: {
               color: '#4572A7'
            }
         },
         opposite: true
       
      }, { // Tertiary yAxis
         gridLineWidth: 0,
         title: {
            text: 'Elevation',
            style: {
               color: '#89A54E'
            }
         },
         labels: {
            formatter: function() {
               return this.value +' m';
            },
            style: {
               color: '#89A54E'
            }
         },
         opposite: true
      }],
      tooltip: {
         shared: true,
         crosshairs: true,
      
         formatter: function() {

            var s = '<b>'+ this.x +'</b>';
            
            $.each(this.points, function(i, point) {

                var unit = {
                    'Heartrate': 'bpm',
                    'Cadence': 'rpm',
                    'Elevation': 'm'
                    }[point.series.name];
                s += '<br/><span style="color:'+point.series.color+'">'+ point.series.name+':</span> '+ Math.round(point.y*100)/100 +' '+ unit;
            });
            
            return s;
          }
      },
      legend: {
         layout: 'vertical',
         align: 'left',
         x: 120,
         verticalAlign: 'top',
         y: 80,
         floating: true,
         backgroundColor: '#FFFFFF'
      },
      series: [
       {
         name: 'Heartrate',
         type: 'spline',
         color: '#AA4643',
         yAxis: 0,
         data: hrs$divno,
         marker: {
            enabled: false
         },
         dashStyle: 'shortdot'               
      
      }, 
      {
         name: 'Cadence',
         type: 'spline',
         color: '#4572A7',
         yAxis: 1,
         data: cadence$divno,
         marker: {
            enabled: false
         },
         dashStyle: 'solid'               
      
      },
      
      {
         name: 'Elevation',
         color: '#89A54E',
         yAxis: 2,
         marker: {
            enabled: false
         },
         type: 'spline',
         data: elev$divno
      }],
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
