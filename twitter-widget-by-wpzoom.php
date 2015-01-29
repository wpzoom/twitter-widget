<?php

/**
 * Plugin Name: Twitter Widget by WPZOOM
 * Plugin URI: http://wpzoom.com/
 * Description: Displays a Twitter Feed that you can fully customize via CSS.
 * Author: WPZOOM
 * Author URI: http://wpzoom.com/
 * Version: 1.0
 * License: GPLv2 or later
*/

require_once( plugin_dir_path( __FILE__ ) . 'class.wpzoom-twitter-widget.php' );

if ( is_admin() ) {
	require_once( plugin_dir_path( __FILE__ ) . 'wpzoom-twitter-widget-settings.php' );
}

add_action( 'widgets_init', 'zoom_twitter_widget_register' );
function zoom_twitter_widget_register() {
	register_widget( 'Wpzoom_Twitter_Widget' );
}
