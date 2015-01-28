<?php

add_filter( 'plugin_action_links', 'wpzoom_twitter_timeline_action_links', 10, 2 );
function wpzoom_twitter_timeline_action_links( $links, $file ) {
	if ( $file != plugin_basename( realpath( dirname(__FILE__) . '/../twitter-timeline-by-wpzoom.php' ) ) ) {
		return $links;
	}

	$settings_link = sprintf(
		'<a href="%1$s">%2$s</a>',
		menu_page_url( 'wpzoom_twitter_timeline', false ),
		esc_html__('Settings', 'wpzoom-twitter-timeline')
	);

	array_unshift( $links, $settings_link );

	return $links;
}

add_action( 'admin_menu', 'wpzoom_twitter_menu_page_create' );
function wpzoom_twitter_menu_page_create() {
	add_options_page(
		__( 'Twitter Timeline by WPZOOM', 'wpzoom-twitter-timeline' ),
		__( 'Twitter Timeline' ),
		'manage_options',
		'wpzoom_twitter_timeline',
		'wpzoom_twitter_timeline_settings'
	);
}

function wpzoom_twitter_timeline_settings() {
	require_once dirname( __FILE__ ) . '/../twitteroauth/WPZOOM_Twitter_OAuth.php';

	$consumer_key        = get_option( 'wpzoom_twitter_timeline_consumer_key' );
	$consumer_secret     = get_option( 'wpzoom_twitter_timeline_consumer_secret' );
	$access_token        = get_option( 'wpzoom_twitter_timeline_access_token' );
	$access_token_secret = get_option( 'wpzoom_twitter_timeline_access_token_secret' );

	$some_filled = ( $consumer_key || $consumer_secret || $access_token || $access_token_secret );

	$connection = null;
	if ( $some_filled ) {
		$connection = new WPZOOM_TwitterOAuth_TwitterOAuth( $consumer_key, $consumer_secret, $access_token, $access_token_secret );
		$connection->get( 'account/verify_credentials' );
	}
	?>

	<div class="wrap">
		<h2><?php printf( __( 'Twitter Timeline by <a href="%1$s">WPZOOM</a>' ), 'http://www.wpzoom.com/' ); ?></h2>

		<?php if ( $some_filled && 200 != $connection->lastHttpCode() ) : ?>

			<div class="error">
				<p>
					<?php
					printf(
						__( 'Bad Authentification Data. Please <a href="%1$s" target="_blank">read instructions</a> and provide correct credentials.', 'wpzoom-twitter-timeline' ),
						'http://www.wpzoom.com/docs/twitter-widget-with-api-version-1-1-setup-instructions/'
					);
					?>
				</p>
			</div>

		<?php endif; ?>

		<p>
			<?php _e( 'Display your most recent thoughts from twitter. First you need to setup API keys in order for widget to be able to display your tweets.', 'wpzoom-twitter-timeline' ) ?>
		</p>

		<p>
			<?php
			printf(
				__( 'Navigate to documentation page and <a href="%1$s" target="_blank">read instructions</a>.', 'wpzoom-twitter-timeline' ),
				'http://www.wpzoom.com/docs/twitter-widget-with-api-version-1-1-setup-instructions/'
			);
			?>
		</p>

		<form method="post" action="options.php">
			<?php settings_fields( 'wpzoom-twitter-timeline-settings' ); ?>
			<?php do_settings_sections( 'wpzoom-twitter-timeline-settings' ); ?>

			<?php submit_button(); ?>
		</form>
	</div>

<?php
}

add_action( 'admin_init', 'wpzoom_twitter_timeline_register' );
function wpzoom_twitter_timeline_register() {
	register_setting( 'wpzoom-twitter-timeline-settings', 'wpzoom_twitter_timeline_consumer_key' );
	register_setting( 'wpzoom-twitter-timeline-settings', 'wpzoom_twitter_timeline_consumer_secret' );

	register_setting( 'wpzoom-twitter-timeline-settings', 'wpzoom_twitter_timeline_access_token' );
	register_setting( 'wpzoom-twitter-timeline-settings', 'wpzoom_twitter_timeline_access_token_secret' );

	add_settings_section( 'wpzoom-twitter-timeline-settings-id', null, null, 'wpzoom-twitter-timeline-settings' );

	$api_fields = array( 'consumer_key', 'consumer_secret', 'access_token', 'access_token_secret' );

	foreach ( $api_fields as $field ) {
		register_setting( 'wpzoom-twitter-timeline-settings', 'wpzoom_twitter_timeline_' . $field );

		add_settings_field( 'wpzoom_twitter_timeline_' . $field, ucwords( str_replace( '_', ' ', $field ) ), 'wpzoom_twitter_timeline_' . $field, 'wpzoom-twitter-timeline-settings', 'wpzoom-twitter-timeline-settings-id' );
	}
}


function wpzoom_twitter_timeline_consumer_key() {
	wpzoom_twitter_timeline_api_field( 'consumer_key' );
}

function wpzoom_twitter_timeline_consumer_secret() {
	wpzoom_twitter_timeline_api_field( 'consumer_secret' );
}

function wpzoom_twitter_timeline_access_token() {
	wpzoom_twitter_timeline_api_field( 'access_token' );
}

function wpzoom_twitter_timeline_access_token_secret() {
	wpzoom_twitter_timeline_api_field( 'access_token_secret' );
}

function wpzoom_twitter_timeline_api_field( $field ) {
	printf(
		'<input type="text" id="title" name="wpzoom_twitter_timeline_' . $field . '" value="%s" />',
		get_option( 'wpzoom_twitter_timeline_' . $field ) !== false ? esc_attr( get_option( 'wpzoom_twitter_timeline_' . $field ) ) : ''
	);
}
