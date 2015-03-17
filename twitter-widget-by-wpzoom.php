<?php

/**
 * Plugin Name: Twitter Widget by WPZOOM
 * Plugin URI: http://wpzoom.com/
 * Description: Displays a Twitter Feed that you can fully customize via CSS.
 * Author: WPZOOM
 * Author URI: http://wpzoom.com/
 * Version: 1.0.1
 * License: GPLv2 or later
*/

require_once( plugin_dir_path( __FILE__ ) . 'class.zoom-twitter-timeline-widget.php' );

if ( is_admin() ) {
	require_once( plugin_dir_path( __FILE__ ) . 'zoom-twitter-widget-settings.php' );
}

add_action( 'widgets_init', 'zoom_twitter_timeline_widget_register' );
function zoom_twitter_timeline_widget_register() {
	register_widget( 'Zoom_Twitter_Timeline_Widget' );
}
