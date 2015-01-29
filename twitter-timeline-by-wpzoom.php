<?php

/**
 * Plugin Name: Twitter Timeline by WPZOOM
 * Plugin URI: http://wpzoom.com/
 * Description: Displays a Twitter Feed that you can fully customize via CSS.
 * Author: WPZOOM
 * Author URI: http://wpzoom.com/
 * Version: 1.0
 * License: GPLv2 or later
*/

require_once( plugin_dir_path( __FILE__ ) . 'class.wpzoom-twitter-timeline.php' );
require_once( plugin_dir_path( __FILE__ ) . 'widgets/class.wpzoom-twitter-timeline-widget.php' );

if ( is_admin() ) {
	require_once( plugin_dir_path( __FILE__ ) . 'admin/wpzoom-twitter-timelime-settings.php' );
}

new Wpzoom_Twitter_Timeline();
