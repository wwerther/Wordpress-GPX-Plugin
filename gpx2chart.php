<?php
// vim: set ts=4 et nu ai syntax=php indentexpr= :vim
/*
Plugin Name: gpx2chart
Plugin URI: http://wwerther.de/static/gpx2chart
Description: gpx2chart - a WP-Plugin for extracting some nice graphs from GPX-Files. Samples can be found on <a href="http://wwerther.de/static/gpx2chart">GPX2Chart plugin page</a>. Default-configuration can be done on the [<a href="options-general.php?page=gpx2chart.php">settings-page</a>].
Version: 0.3.0
Author: Walter Werther
Author URI: http://wwerther.de/
Update Server: http://downloads.wordpress.org/plugin
Min WP Version: 3.1.2
Max WP Version: 3.3.1
 */

#

define ('GPX2CHART_PLUGIN_VER','0.3.0');

// Include helper
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'ww_gpx_helper.php');

if ( ! defined( 'WP_CONTENT_URL' ) ) define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content' );
if ( ! defined( 'WP_CONTENT_DIR' ) ) define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
if ( ! defined( 'WP_PLUGIN_URL' ) )  define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );
if ( ! defined( 'WP_PLUGIN_DIR' ) )  define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR .DIRECTORY_SEPARATOR. 'plugins' );

if (! defined('GPX2CHART_SHORTCODE')) define('GPX2CHART_SHORTCODE','gpx2chart');
if (! defined('GPX2CHART_PLUGIN_URL')) define ("GPX2CHART_PLUGIN_URL", WP_PLUGIN_URL."/gpx2chart/");
if (! defined('GPX2CHART_PLUGIN_DIR')) define ("GPX2CHART_PLUGIN_DIR", WP_PLUGIN_DIR.DIRECTORY_SEPARATOR."gpx2chart".DIRECTORY_SEPARATOR);
if (! defined('GPX2CHART_PLUGIN_ICONS_URL')) define ("GPX2CHART_PLUGIN_ICONS_URL", GPX2CHART_PLUGIN_URL."/icons/");
if (! defined('GPX2CHART_PROFILES')) define ("GPX2CHART_PROFILES",GPX2CHART_PLUGIN_DIR."profiles".DIRECTORY_SEPARATOR);
if (! defined('GPX2CHART_CSS_DIR')) define ("GPX2CHART_CSS_DIR",GPX2CHART_PLUGIN_DIR."css".DIRECTORY_SEPARATOR);
if (! defined('GPX2CHART_CONTAINERPREFIX')) define ("GPX2CHART_CONTAINERPREFIX",'GPX2CHART');
if (! defined('GPX2CHART_OPTIONS')) define ("GPX2CHART_OPTIONS",'gpx2chart_option');
if (! defined('GPX2CHART_TEXTDOMAIN')) define ("GPX2CHART_TEXTDOMAIN",'GPX2CHART-plugin');

class GPX2CHART {

    static $container_name=GPX2CHART_CONTAINERPREFIX;
    static $default_rendername='flot';

    public $configuration=Array();
    public $data=Array();

	public $instance=0;
    public $debug=true;

    public function debug ($text,$headline='') {
        if ($this->debug) {
            return "\n<!-- gpx2chart $headline\n$text\n gpx2chart -->\n";
        }
        return '';
    }

    public function __construct() {
        add_action('admin_menu', array(&$this, 'admin_menu'));
        add_action('init', array(&$this, 'init'));
        add_filter('the_posts', array(&$this,'conditionally_add_scripts_and_styles')); # http://beerpla.net/2010/01/13/wordpress-plugin-development-how-to-include-css-and-javascript-conditionally-and-only-when-needed-by-the-posts/
		add_shortcode(GPX2CHART_SHORTCODE, array(&$this, 'handle_shortcode'));
    }

    public function conditionally_add_scripts_and_styles($posts){
        if (empty($posts)) return $posts;
 
    	$shortcode_found = false; // use this flag to see if styles and scripts need to be enqueued
	    foreach ($posts as $post) {
		    if (stripos($post->post_content, '['.GPX2CHART_SHORTCODE) !== false) {
    			$shortcode_found = true; // bingo!
	    		break;
    		}
	    }
 
    	if ($shortcode_found) {
	    	// enqueue here
		    wp_enqueue_style('gpx2chart');
    		wp_enqueue_script('gpx2chart');
    		wp_enqueue_script('flotcross');
    		wp_enqueue_script('flotnavigate');
    		wp_enqueue_script('flotselection');

    		wp_enqueue_script('highcharts');
    		wp_enqueue_script('highchartsexport');
	    }
 
    	return $posts;
    }

	public function init() {
        load_plugin_textdomain(GPX2CHART_TEXTDOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/');

        $minimized='.min';
        if ($this->debug) {
            $minimized='';
        }

        wp_deregister_style('gpx2chart');
        wp_register_style('gpx2chart', GPX2CHART_PLUGIN_URL."css/gpx2chart.css", false, '1.0.0', 'screen');

        wp_deregister_script('strftime');
        wp_deregister_script('sprintf');
        wp_deregister_script('excanvas');
        wp_deregister_script('flot');
        wp_deregister_script('flotcross');
        wp_deregister_script('flotnavigate');
        wp_deregister_script('flotselection');
        wp_deregister_script('flotselection');
        wp_deregister_script('gpx2chart');

        wp_register_script('gpx2chart', GPX2CHART_PLUGIN_URL."js/gpx2chart$minimized.js") ;

        wp_register_script('strftime', GPX2CHART_PLUGIN_URL."js/helper/strftime${minimized}.js") ;
        wp_register_script('sprintf', GPX2CHART_PLUGIN_URL."js/helper/sprintf${minimized}.js") ;
        wp_register_script('excanvas', GPX2CHART_PLUGIN_URL."js/flot/excanvas${minimized}.js", array('jquery'), '2.1.4', false);

        wp_register_script('flot', GPX2CHART_PLUGIN_URL."js/flot/jquery.flot${minimized}.js", array('jquery','excanvas','strftime','sprintf'), '2.1.4', false);
        wp_register_script('flotcross', GPX2CHART_PLUGIN_URL."js/flot/jquery.flot.crosshair$minimized.js", array('jquery','flot'), '2.1.4', false);
        wp_register_script('flotnavigate', GPX2CHART_PLUGIN_URL."js/flot/jquery.flot.navigate$minimized.js", array('jquery','flot'), '2.1.4', false);
        wp_register_script('flotselection', GPX2CHART_PLUGIN_URL."js/flot/jquery.flot.selection$minimized.js", array('jquery','flot'), '2.1.4', false);

        /* Only register Highcharts if library is present */
        if (file_exists(join(DIRECTORY_SEPARATOR, array(GPX2CHART_PLUGIN_DIR,'js','highcharts')))) {
            $minimized='';
            if ($this->debug) {
                $minimized='.src';
            }
            wp_register_script('highcharts', GPX2CHART_PLUGIN_URL."js/highcharts/highcharts$minimized.js", array('jquery'), '2.1.4', false);
            wp_register_script('highchartsexport', GPX2CHART_PLUGIN_URL."js/highcharts/modules/exporting$minimized.js", array('jquery','highcharts'), '2.1.4', false);
         }
	}

	public static function formattime($value) {
            return strftime('%H:%M:%S',$value);
	}

	function admin_menu($not_used){
    // place the info in the plugin settings page
		add_options_page(__('GPX2Chart Settings',GPX2CHART_TEXTDOMAIN), __('GPX2Chart',GPX2CHART_TEXTDOMAIN), 5, basename(__FILE__), array('GPX2CHART', 'options_page_gpx'));
	}

    public static function options_page_gpx() {
        if(isset($_POST['Options'])){
            $newvalue = $_POST['gpx2chartoptions'];
            if ( stripslashes(get_option( GPX2CHART_OPTIONS )) != $newvalue ) {
                update_option(GPX2CHART_OPTIONS, $newvalue );
            } else {
                $deprecated = ' ';
                $autoload = 'no';
                add_option( $option_name, $newvalue, $deprecated, $autoload );
            }
        }
        if(isset($_POST['Reset'])){
                delete_option(GPX2CHART_OPTIONS);
        }
        $gpx2chart_config=stripslashes(get_option(GPX2CHART_OPTIONS,file_get_contents(GPX2CHART_PROFILES.'default.conf')));
        include('gpx2chart_options.php');	
    }

/*
 * Our shortcode-Handler for GPX-Files
 * It provides support for the necessary parameters that are defined in
 * http://codex.wordpress.org/Shortcode_API
 */
	public function handle_shortcode( $atts, $content=null, $code="" ) {
        // $atts    ::= array of attributes
        // $content ::= text within enclosing form of shortcode element
        // $code    ::= the shortcode found, when == callback name
        //           [gpx2chart href="<GPX-Source>" (maxelem="51") (debug) (width="90%") (metadata="heartrate cadence distance speed") (display="heartrate cadence elevation speed")]
    	$this->instance++;

        /* Clear configuration and data array to determine fresh settings */
        $this->configuration=Array();
        $this->data=Array();
        $this->gpx=null;

        /* Check if we are in "debug mode". Create a more verbose output then */
        $this->debug=in_array('debug',$atts) ? true : $this->debug;

        /* Determine the profile that should be used to display this chart */
        $pattern='/#=(\S+?):(.+?)\n/';
        preg_replace_callback($pattern,array(&$this,'readconfiguration'),stripslashes(get_option( GPX2CHART_OPTIONS )));
        $this->configuration['profile']=array_key_exists('profile',$this->configuration) ? $this->configuration['profile'].'.profile' : 'default.profile';
        $this->configuration['profile']=in_array('profile',$atts) ? basename($atts['profile']).'.profile' : $this->configuration['profile'];

        $this->configuration['container.name']=GPX2CHART_CONTAINERPREFIX;
        $this->configuration['icons.url']=GPX2CHART_PLUGIN_ICONS_URL;

        /* Load the profile */
        $profilecontent=file_get_contents(GPX2CHART_PROFILES.DIRECTORY_SEPARATOR.$this->configuration['profile']);

        /* Read the default settings of this template */
        $pattern='/#=(\S+?):(.+?)\n/';
        $profilecontent=preg_replace_callback($pattern,array(&$this,'readconfiguration'),$profilecontent);

        /* Override with settings from default configuration in web-gui */
        preg_replace_callback($pattern,array(&$this,'readconfiguration'),stripslashes(get_option( GPX2CHART_OPTIONS )));

        /* Override configuration with values defined in attributes */
        foreach ($atts as $key=>$value) {
            if (is_int($key)) {
                if (preg_match('#(.+)(?:=["\'](.+)["\'])#',$value,$match)) {;
                    $key=$match[1];
                    $value=$match[2];
                } else {
                    $key=$value;
                }
            };
            $key=str_replace('_','.',$key);
            $key=str_replace('-','.',$key);
            $this->configuration[$key]=$value;
        }
        $this->configuration['headline']=array_key_exists('headline',$this->configuration) ? $this->configuration['headline'] : ucfirst($this->configuration['type']);

        $this->configuration['debug']=$this->debug;
        $this->configuration['profile']=$this->profile;
        $this->configuration['instance']=$this->instance;
        $this->configuration['id']=$this->configuration['container.name'].$this->configuration['instance'];

        $divno=$this->instance;
 
        $error=array();

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

#       foreach ($render->script_depencies as $depency) wp_enqueue_script($depency);

        /*
         * Evaluate mandatory attributes
         */
        if (! array_key_exists('href',$atts)) array_push($error,"Attribute HREF is missing");

        /* 
         * Evaluate optional attributes 
         */
        $maxelem=array_key_exists('maxelem',$atts) ? intval($atts['maxelem']) : 51;
        $width=array_key_exists('width',$atts) ? $atts['width'] : '90%';

        # read in the GPX-file
        $gpx=new WW_GPX($atts['href']);
        $this->gpx=$gpx;

        # try to parse the XML only if we don't have errors yet
        if ((count($error)==0) and (! $gpx->parse())) array_push($error,"Error parsing GPX-File");

        /* In case of errors we abort here */
        if (count($error)>0) {
            $this->gpx=null;
            $this->configuration['error']=1;
            $this->configuration['error.text']=join("<br/>\n",$error);
            return $this->renderpage($profilecontent);
        }

        # Input series provided by GPX-files are: 
        #                                               (time, lat, lon) heartrate, cadence, elevation 
        # Calculated series are:                        
        #                                               speed, distance, interval, rise, fall
        # Series that make sense to be displayed as graph: 
        #                                               heartrate, cadence, elevation, speed
        # Series that make sense to be displayed additional in the meta-data: 
        #                                               time, totaldistance, totalinterval, totalrise, totalfall

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

        # Formatter for Tooltip-Values
        $labelformat['heartrate']='return value + " bpm";';
        $labelformat['cadence']='return value + " rpm";';
        $labelformat['elevation']='return Math.round(value) + " m";';
        $labelformat['speed']='return Math.round(value*100)/100 + " km/h";';
        $labelformat['totaldistance']='if (value>1000) return sprintf("%.2f km",Math.round(value/10)/100); return Math.round(value) + " m"';
        $labelformat['totalinterval']='return sprintf("%02d:%02d:%02d",Math.floor(value/3600),Math.floor(value/60)%60,value%60);';
        $labelformat['totalrise']='if (value>1000) return Math.round(value/10)/100 + " km"; return Math.round(value) + " m"';
        $labelformat['totalfall']='if (value>1000) return Math.round(value/10)/100 + " km"; return Math.round(value) + " m"';
       
        # Adjust the display of elevation a little bit, so the graph does not look to rough if we don't have high differences between min and max
        # In this case we have at least 40m that are displayed
        $additionalparameters['elevation']='min: '.($gpx->min('elevation')-20).',max: '.($gpx->max('elevation')+20).',';

        # The maximum series that are available
        # $process=array('heartrate','cadence','elevation','speed');
        # $metadata=array('heartrate','cadence','distance','speed');

        # If we have defined a display variable we intersect the two arrays and take only the ones that are in both
        # $process=$atts['display'] ? array_intersect($process,split(' ',$atts['display'])) : $process;
        # $metadata=$atts['metadata'] ? array_intersect($metadata,split(' ',$atts['metadata'])) : $metadata;

        # We remove the entries where we don't have data in our GPX-File
        $this->configuration['data.embed']=explode(" ",$this->configuration['data.embed']);
        $this->configuration['data.embed.available']=array_diff($this->configuration['data.embed'],$gpx->getunavailable());
        $this->configuration['data.series']=explode(" ",$this->configuration['data.series']);
        $this->configuration['data.series.available']=array_diff($this->configuration['data.series'],$gpx->getunavailable());
        $this->configuration['data.yaxis.show']=explode(" ",$this->configuration['data.yaxis.show']);
        $this->configuration['data.yaxis.show.available']=array_diff($this->configuration['data.yaxis.show'],$gpx->getunavailable());
        
        $this->configuration['title'] = array_key_exists('title',$this->configuration) ? $this->configuration['title'] : $gpx->meta->name;
        $this->configuration['subtitle']=array_key_exists('subtitle',$this->configuration) ? $this->configuration['subtitle'] : strftime('%d.%m.%Y %H:%M',$gpx[0]['time'])."-".strftime('%d.%m.%Y %H:%M',$gpx[-1]['time']);

        $gpx->setmaxelem($this->configuration['maxelem']);

        $dataarrays=Array();
        foreach ($this->configuration['data.embed.available'] as $elem) {
           array_push($dataarrays,"gpx2chart['data'][$divno]['$elem']= new Array(".join(",",$gpx->return_pair($elem) ).");");
        }

        $yaxis=array();
        $axisno=1;
        foreach ($this->configuration['data.yaxis.show.available'] as $elem) {
            array_push($yaxis,$render->create_axis($this->configuration["$elem.axis.title"],$this->configuration["$elem.color"],$this->configuration["$elem.yaxis.left"],$axisno,$this->configuration["$elem.axis.format"],$additionalparameters[$elem]));
            $this->configuration["$elem.yaxis.no"]=$axisno;
            $axisno++;
        }

        $series=array();
        foreach ($this->configuration['data.series.available'] as $elem) {
            $axisno=array_key_exists("$elem.yaxis.no",$this->configuration) ? $this->configuration["$elem.yaxis.no"] : -1;
            array_push($series,$render->create_series($elem,$this->configuration["$elem.series.name"],$this->configuration["$elem.color"],$axisno,"gpx2chart['data'][$divno]['$elem']",$this->configuration["$elem.dash.style"],$this->configuration["$elem.series.type"],$labelformat[$elem]));
        }


        $xaxis=array();
        array_push($xaxis,$render->create_xaxis());


        $this->data['data.js.dataarray']=join("\n",$dataarrays);

        $this->data['data.js.xaxis']=join(",\n",$xaxis);
        $this->data['data.js.yaxis']=join(",\n",$yaxis);
        
        $this->data['data.js.options']=$render->renderoptions("gpx2chart['options'][$divno]",$this->data['data.js.xaxis'],$this->data['data.js.yaxis']);
        $this->data['data.js.series']=$render->renderseries("gpx2chart['series'][$divno]",join(',',$series));

        $this->data['data.js.render']=$render->renderplot($this->configuration['id']."chart","gpx2chart['series'][$divno]","gpx2chart['options'][$divno]");
        $this->data['data.js.addon']=$directcontent.=$render->renderaddon($this->configuration['id'],$this->configuration['instance']);


        return $this->renderpage($profilecontent);

    }

    private function renderpage($profilecontent) {
        # Replace the default variables
        $pattern='/\{(\S+?)\}/';
        $profilecontent=preg_replace_callback($pattern,array(__CLASS__,'getvalue'),$profilecontent);

        # Evaluate data-condition fields
        #   Therefore search for matching HTML-tags containing the data-condition attribute. Currently "tag in tag" is not supported!
        #       take the data-condition and evaluate the string if it is equal, greater or lower (in case of integer). 
        #       If no operator is present check wether a string is present at all.
        #   if the evaluation is successful leave the marked code in the HTML-body else remove it.
        $pattern='#<(\w+)\s(?:.{0,}\s+)?data-condition="(\S*)".*?>(.*?)</\1>#iXu';
        $profilecontent=preg_replace_callback($pattern,array(__CLASS__,'datacondition'),$profilecontent);
        return $profilecontent;
    }

    public function datacondition($matches) {
        $validate=$matches[2];
        if (preg_match('#(.*)([=<>&]+)(.*)#',$validate,$operation)) {
            switch ($operation[2]) {
                case '=':
                    if ($operation[1]==$operation[3]) return "<!-- $operation[1]:$operation[2]:$operation[3] -->".$matches[0];
#                   return '<!-- !EQU -->';
                break;
                case '<':
                    if ($operation[1]==$operation[3]) return $matches[0];
#                   return '<!-- !LT -->';
                break;
                case '>':
                    if ($operation[1]==$operation[3]) return $matches[0];
#                   return '<!-- !GT -->';
                break;
                case '&':
                    if ($operation[1] and $operation[3]) return $matches[0];
                break;
                default:
                    return "<!-- unknown operation $operation[2] in $matches[2] -->";
                break;
            }
        } else {
            if (strlen($matches[2])>0) return '<!-- VAL -->'.$matches[0];
#            return "<!-- COND:$matches[2] -->";
        }
        return '';
    }

    protected function readconfiguration($matches) {
        $value=$matches[2];
        $value=preg_replace('/[\n\r]/','',$value);
        if ($value=='false') $value=false;
        if ($value=='true') $value=true;
        $this->configuration[$matches[1]]=$value;
        return '';
    }

    public function getvalue($matches) {
        switch ($matches[1]) {
            case 'configuration':
                return 'Configuration:'.var_export ($this->configuration,true)."\nData:".join(',',array_keys($this->data))."\n";
            break;

            case 'calc.heartrate.avg':
                if (is_null($this->gpx)) return '';
                return $this->gpx->averageheartrate();
            break;
            case 'calc.cadence.avg':
                if (is_null($this->gpx)) return '';
                return $this->gpx->averagecadence();
            break;
            case 'calc.speed.avg':
                if (is_null($this->gpx)) return '';
                return $this->gpx->averagespeed();
            case 'calc.distance.total':
                if (is_null($this->gpx)) return '';
                return $this->gpx->totaldistance();
            break;
            default:

                list($module,$function,$series,$type)=explode('.',$matches[1]);

                if ($module=='gpx') {
                    if (is_null($this->gpx)) return '';

                    if ($function=='calc') {
                        switch ($type) {
                            case 'min':
                                return sprintf('%0.2f',$this->gpx->min($series));
                            break;
                            case 'max':
                                return sprintf('%0.2f',$this->gpx->max($series));
                            break;
                            case 'avg':
                                return sprintf('%0.2f',$this->gpx->avg($series));
                            break;
                            default:
                                return "GPX: unknown function $type on $series";
                        }
                    } elseif ($function=='contain') {
#                   return "<!-- next $matches[1] $module:$function:$series:$type -->";
                        return $this->gpx->contain($series);
                    } elseif ($function=='stat') {
                        if ($series=='elevation') {
                            if ($type=='rise') {
                                return sprintf('%0.2f',$this->gpx[-1]['totalrise']);
                            } elseif ($type=='fall') {
                                return sprintf('%0.2f',$this->gpx[-1]['totalfall']);

                            } else {
                                return "GPX: unknown stat $type on $series";
                            }
                        } elseif ($series=='distance') {
                            if ($type=='max') {
                                return sprintf('%0.2f',$this->gpx[-1]['totaldistance']/$this->configuration['distance.scale']);
                            } else {
                                return "GPX: unknown stat $type on $series";
                            }                           
                        } else {
                                return "GPX: unknown stat $type on $series";
                        }
                    } else {
                       return "Unknown function $contain with $series:$type";
                    }
                }

                if (array_key_exists($matches[1],$this->configuration)) {
                    return $this->configuration[$matches[1]];
                } else {
                    if (array_key_exists($matches[1],$this->data)) {
                        return $this->data[$matches[1]];
                    }
#                   return "<!-- next $matches[1] $module:$function:$series:$type -->";
                   return '';
                }
            break;
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

$pGPX2Chart=new GPX2CHART();
#GPX2CHART::init();

?>
