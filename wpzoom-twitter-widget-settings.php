<?php

add_filter( 'plugin_action_links', 'wpzoom_twitter_widget_action_links', 10, 2 );
function wpzoom_twitter_widget_action_links( $links, $file ) {
	if ( $file != plugin_basename( dirname(__FILE__) . '/twitter-widget-by-wpzoom.php' ) ) {
		return $links;
	}

	$settings_link = sprintf(
		'<a href="%1$s">%2$s</a>',
		menu_page_url( 'wpzoom_twitter_widget', false ),
		esc_html__( 'Settings', 'wpzoom-twitter-widget' )
	);

	array_unshift( $links, $settings_link );

	return $links;
}

add_action( 'admin_menu', 'wpzoom_twitter_menu_page_create' );
function wpzoom_twitter_menu_page_create() {
	add_options_page(
		__( 'Twitter Widget by WPZOOM', 'wpzoom-twitter-widget' ),
		__( 'Twitter Widget' ),
		'manage_options',
		'wpzoom_twitter_widget',
		'wpzoom_twitter_widget_settings'
	);
}

function wpzoom_twitter_widget_settings() {
	require_once dirname( __FILE__ ) . '/twitteroauth/WPZOOM_Twitter_OAuth.php';

	$consumer_key        = get_option( 'wpzoom_twitter_widget_consumer_key' );
	$consumer_secret     = get_option( 'wpzoom_twitter_widget_consumer_secret' );
	$access_token        = get_option( 'wpzoom_twitter_widget_access_token' );
	$access_token_secret = get_option( 'wpzoom_twitter_widget_access_token_secret' );

	$some_filled = ( $consumer_key || $consumer_secret || $access_token || $access_token_secret );

	$connection = null;
	if ( $some_filled ) {
		$connection = new WPZOOM_TwitterOAuth_TwitterOAuth( $consumer_key, $consumer_secret, $access_token, $access_token_secret );
		$connection->get( 'account/verify_credentials' );
	}
	?>

	<div class="wrap">
		<h2><?php printf( __( 'Twitter Widget by <a href="%1$s">WPZOOM</a>' ), 'http://www.wpzoom.com/' ); ?></h2>

		<?php if ( $some_filled && 200 != $connection->lastHttpCode() ) : ?>

			<div class="error">
				<p>
					<?php
					printf(
						__( 'Bad Authentification Data. Please <a href="%1$s" target="_blank">read instructions</a> and provide correct credentials.', 'wpzoom-twitter-widget' ),
						'http://www.wpzoom.com/docs/twitter-widget-with-api-version-1-1-setup-instructions/'
					);
					?>
				</p>
			</div>

		<?php endif; ?>

		<p>
			<?php _e( 'Display your most recent thoughts from twitter. First you need to setup API keys in order for widget to be able to display your tweets.', 'wpzoom-twitter-widget' ) ?>
		</p>

		<p>
			<?php
			printf(
				__( 'Navigate to documentation page and <a href="%1$s" target="_blank">read instructions</a>.', 'wpzoom-twitter-widget' ),
				'http://www.wpzoom.com/docs/twitter-widget-with-api-version-1-1-setup-instructions/'
			);
			?>
		</p>

		<form method="post" action="options.php">
			<?php settings_fields( 'wpzoom-twitter-widget-settings' ); ?>
			<?php do_settings_sections( 'wpzoom-twitter-widget-settings' ); ?>

			<?php submit_button(); ?>
		</form>
	</div>

<?php
}

add_action( 'admin_init', 'wpzoom_twitter_widget_register' );
function wpzoom_twitter_widget_register() {
	register_setting( 'wpzoom-twitter-widget-settings', 'wpzoom_twitter_widget_consumer_key' );
	register_setting( 'wpzoom-twitter-widget-settings', 'wpzoom_twitter_widget_consumer_secret' );

	register_setting( 'wpzoom-twitter-widget-settings', 'wpzoom_twitter_widget_access_token' );
	register_setting( 'wpzoom-twitter-widget-settings', 'wpzoom_twitter_widget_access_token_secret' );

	add_settings_section( 'wpzoom-twitter-widget-settings-id', null, null, 'wpzoom-twitter-widget-settings' );

	$api_fields = array( 'consumer_key', 'consumer_secret', 'access_token', 'access_token_secret' );

	foreach ( $api_fields as $field ) {
		register_setting( 'wpzoom-twitter-widget-settings', 'wpzoom_twitter_widget_' . $field );

		add_settings_field( 'wpzoom_twitter_widget_' . $field, ucwords( str_replace( '_', ' ', $field ) ), 'wpzoom_twitter_widget_' . $field, 'wpzoom-twitter-widget-settings', 'wpzoom-twitter-widget-settings-id' );
	}
}


function wpzoom_twitter_widget_consumer_key() {
	wpzoom_twitter_widget_api_field( 'consumer_key' );
}

function wpzoom_twitter_widget_consumer_secret() {
	wpzoom_twitter_widget_api_field( 'consumer_secret' );
}

function wpzoom_twitter_widget_access_token() {
	wpzoom_twitter_widget_api_field( 'access_token' );
}

function wpzoom_twitter_widget_access_token_secret() {
	wpzoom_twitter_widget_api_field( 'access_token_secret' );
}

function wpzoom_twitter_widget_api_field( $field ) {
	printf(
		'<input class="widefat" type="text" id="title" name="wpzoom_twitter_widget_' . $field . '" value="%s" />',
		get_option( 'wpzoom_twitter_widget_' . $field ) !== false ? esc_attr( get_option( 'wpzoom_twitter_widget_' . $field ) ) : ''
	);
}
