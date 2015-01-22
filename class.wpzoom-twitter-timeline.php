<?php

class Wpzoom_Twitter_Timeline {
	public function __construct() {
		add_action( 'widgets_init', array( $this, 'twitter_widget_init' ) );
	}

	public function twitter_widget_init() {
		register_widget( 'Wpzoom_Twitter_Timeline_Widget' );
	}
}
