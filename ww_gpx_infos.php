<?php
// vim: set ts=4 et nu :vim
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
    return 'Das ist nur ein Tes---';
}


/*
 * I just define a small test, wether or not the add_shortcode function 
 * already exists. This allows me to do a compilation test of this file
 * without the full overhead of wordpress
 */
if (! function_exists('add_shortcode')) {
        function add_shortcode ($shortcode,$function) {
                echo "Only Test-Case: $shortcode: $function";
        };
}

/*
 * Register our shortcode to the Wordpress-Handlers
 */
add_shortcode( 'wwgpxinfo', 'ww_gpx_info_handler' );

?> 
