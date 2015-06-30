<?php

class Zoom_Twitter_Timeline_Widget extends WP_Widget {
	/**
	 * @var array
	 */
	protected $defaults;

	/**
	 * @var WPZOOM_TwitterOAuth_TwitterOAuth
	 */
	protected $connection;

	/**
	 * @var int
	 */
	protected $lastHttpCode;

	public function __construct() {
		parent::__construct(
			'zoom_twitter_widget',
			esc_html__( 'Twitter Widget by WPZOOM', 'zoom-twitter-widget' ),
			array(
				'classname'   => 'zoom-twitter-widget',
				'description' => __( 'Displays a Twitter Timeline.', 'zoom-twitter-widget' ),
			)
		);

		$this->defaults = array(
			'title'                => esc_html__( 'Tweets', 'zoom-twitter-widget' ),
			'tweet-limit'          => 5,
			'screen-name'          => '',
			'show-timestamp'       => true,
			'show-follow-button'   => true,
			'show-followers-count' => true
		);

		if ( is_active_widget( false, false, $this->id_base ) || is_active_widget( false, false, 'monster' ) ) {
			add_action( 'wp_footer', array( $this, 'library' ) );
		}
	}

	public function library() {
		?>
		<script type="text/javascript">
			!function(d,s,id){
				var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';
				if(!d.getElementById(id)){
					js=d.createElement(s);
					js.id=id;js.src=p+"://platform.twitter.com/widgets.js";
					fjs.parentNode.insertBefore(js,fjs);
				}
			}(document,"script","twitter-wjs");
		</script>
	<?php
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		$instance = wp_parse_args( (array) $instance, $this->defaults );

		echo $args['before_widget'];

		if ( $instance['title'] ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
		}

		$tweets = $this->get_tweets( $instance['screen-name'], $instance['tweet-limit'] );

		if ( false === $tweets || empty( $tweets ) ) {
			$this->display_errors();
		} else {
			$this->display_tweets( $tweets, $instance );
			$this->display_follow( $instance );
		}

		echo $args['after_widget'];
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance['title'] = sanitize_text_field( $new_instance['title'] );

		$instance['tweet-limit'] = ( 0 !== (int) $new_instance['tweet-limit'] ) ? (int) $new_instance['tweet-limit'] : null;

		$instance['screen-name'] = sanitize_text_field( $new_instance['screen-name'] );

		$instance['show-timestamp']       = (bool) $new_instance['show-timestamp'];
		$instance['show-follow-button']   = (bool) $new_instance['show-follow-button'];
		$instance['show-followers-count'] = (bool) $new_instance['show-followers-count'];

		// empty cache
		delete_transient( 'zoom_twitter_t6e_' . $new_instance['screen-name'] . '_' . $new_instance['tweet-limit'] );

		return $instance;
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 *
	 * @return string|void
	 */
	public function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, $this->defaults );
		?>

		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php esc_html_e( 'Title:', 'zoom-twitter-widget' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>"/>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'screen-name' ); ?>"><?php esc_html_e( 'Twitter Username:', 'zoom-twitter-widget' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'screen-name' ); ?>" name="<?php echo $this->get_field_name( 'screen-name' ); ?>" type="text" value="<?php echo esc_attr( $instance['screen-name'] ); ?>"/>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'tweet-limit' ); ?>"><?php esc_html_e( '# of Tweets Shown:', 'zoom-twitter-widget' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'tweet-limit' ); ?>" name="<?php echo $this->get_field_name( 'tweet-limit' ); ?>" type="number" min="1" max="20" value="<?php echo esc_attr( $instance['tweet-limit'] ); ?>"/>
		</p>

		<p>
			<input class="checkbox" type="checkbox" <?php checked( $instance['show-timestamp'] ); ?> id="<?php echo $this->get_field_id( 'show-timestamp' ); ?>" name="<?php echo $this->get_field_name( 'show-timestamp' ); ?>" />
			<label for="<?php echo $this->get_field_id( 'show-timestamp' ); ?>"><?php _e( 'Show Timestamp', 'zoom-twitter-widget' ); ?></label>
		</p>

		<p>
			<input class="checkbox" type="checkbox" <?php checked( $instance['show-follow-button'] ); ?> id="<?php echo $this->get_field_id( 'show-follow-button' ); ?>" name="<?php echo $this->get_field_name( 'show-follow-button' ); ?>" />
			<label for="<?php echo $this->get_field_id( 'show-follow-button' ); ?>"><?php _e(' Display Follow me button', 'zoom-twitter-widget' ); ?></label>
		</p>

		<p>
			<input class="checkbox" type="checkbox" <?php checked( $instance['show-followers-count'] ); ?> id="<?php echo $this->get_field_id( 'show-followers-count' ); ?>" name="<?php echo $this->get_field_name( 'show-followers-count' ); ?>" />
			<label for="<?php echo $this->get_field_id( 'show-followers-count' ); ?>"><?php _e(' Show follower count? ', 'zoom-twitter-widget'); ?></label>
		</p>

	<?php
	}

	protected function display_tweets( $tweets, $instance ) {
		?>
		<ul class="zoom-twitter-widget__items">

			<?php foreach ( $tweets as $tweet ) : ?>
				<?php
				$text = $this->parse_text( $tweet->text );
				$link = 'https://twitter.com/statuses/' . $tweet->id_str;
				$time = strtotime( $tweet->created_at );
				?>

				<li class="zoom-twitter-widget__item">
					<p class="zoom-twitter_widget__message">
						<?php echo $text; ?>

						<?php if ( $instance['show-timestamp'] ) : ?>

							<a class="zoom-twitter-widget__item-permalink" href="<?php echo esc_url( $link ); ?>">
								<time class="zoom-twitter-widget__item-timestamp" datetime="<?php echo esc_attr( date( 'c', $time ) ); ?>">
									<?php echo $this->human_time_diff_maybe( $time ); ?>
								</time>
							</a>

						<?php endif; ?>
					</p>
				</li>

			<?php endforeach; ?>

		</ul>
		<?php
	}

	protected function display_follow( $instance ) {
		$screen_name          = $instance['screen-name'];
		$show_follow_button   = $instance['show-follow-button'];
		$show_followers_count = $instance['show-followers-count'];

		if ( ! $show_follow_button || empty( $screen_name ) ) return;

		?>
		<div class="zoom-twitter-widget__follow-me">
			<a class="twitter-follow-button" href="https://twitter.com/<?php echo esc_attr( $screen_name ); ?>" data-show-count="<?php echo ($show_followers_count ? 'true' : 'false'); ?>">
				<?php _e( 'Follow &commat;', 'zoom-twitter-widget' ); echo esc_html( $screen_name ); ?>
			</a>
		</div>
		<?php
	}

	/**
	 * Output errors if widget or plugin is misconfigured and current user can manage options (plugin settings).
	 *
	 * @return void
	 */
	protected function display_errors() {
		if ( 200 == $this->lastHttpCode ) {
			return;
		}

		if ( current_user_can( 'manage_options' ) ) {
			?>
			<p>
				<?php
				if ( 404 == $this->lastHttpCode ) {
					printf(
						__( 'Twitter Widget: Non-existent username.', 'zoom-twitter-widget' )
					);
				} else {
					printf(
						__( 'Twitter Widget misconfigured, check <a href="%1$s" target="_blank">settings page</a> and <a href="%2$s" target="_blank">read instructions</a>. Error code: %3$s.', 'zoom-twitter-widget' ),
						admin_url( 'options-general.php?page=zoom_twitter_widget' ),
						'http://www.wpzoom.com/docs/twitter-widget-with-api-version-1-1-setup-instructions/',
						$this->lastHttpCode
					);
				}
				?>
			</p>
		<?php
		} else {
			echo "&#8230;";
		}
	}

	/**
	 * @param $screen_name string Twitter username
	 * @param $tweet_limit int    Number of tweets to retrieve
	 *
	 * @return array|bool Array of tweets or false if method fails
	 */
	protected function get_tweets( $screen_name, $tweet_limit ) {
		$transient = 'zoom_twitter_t6e_' . $screen_name . '_' . $tweet_limit;

		if ( false !== ( $cache = get_transient( $transient ) ) && ( ! $this->settings_changed() ) ) {
			$this->lastHttpCode = get_transient( 'zoom_twitter_t6e_lastHttpCode' );

			return $cache;
		}

		require_once dirname( __FILE__ ) . '/twitteroauth/WPZOOM_Twitter_OAuth.php';

		$consumer_key        = get_option( 'zoom_twitter_widget_consumer_key' );
		$consumer_secret     = get_option( 'zoom_twitter_widget_consumer_secret' );
		$access_token        = get_option( 'zoom_twitter_widget_access_token' );
		$access_token_secret = get_option( 'zoom_twitter_widget_access_token_secret' );

		$this->connection = new WPZOOM_TwitterOAuth_TwitterOAuth( $consumer_key, $consumer_secret, $access_token, $access_token_secret );

		$tweets = $this->connection->get( 'statuses/user_timeline', array(
			'screen_name'         => $screen_name,
			'count'               => $tweet_limit,
			'trim_user'           => true,
			'contributor_details' => false,
			'include_entities'    => false
		) );

		$this->lastHttpCode = $this->connection->lastHttpCode();
		set_transient( 'zoom_twitter_t6e_lastHttpCode', $this->lastHttpCode );

		if ( 200 !== $this->connection->lastHttpCode() ) {
			// throttle for 60 seconds
			set_transient( $transient, array(), 60 );

			return false;
		}

		set_transient( $transient, $tweets, 300 );

		return $tweets;
	}

	protected function parse_text( $text ) {
		$text = esc_html( $text );

		$text = preg_replace_callback(
			'/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/',
			array( $this, 'text_parse_links' ),
			$text
		);

		$text = preg_replace_callback(
			'/@([A-Za-z0-9_]{1,15})/',
			array( $this, 'text_parse_usernames' ),
			$text
		);

		return $text;
	}

	protected function human_time_diff_maybe( $timestamp ) {
		if ( ( abs( time() - $timestamp ) ) < 86400 ) {
			return sprintf( __( '%1$s ago', 'zoom-twitter-widget' ), human_time_diff( $timestamp ) );
		} else {
			return date( get_option( 'date_format' ), $timestamp );
		}
	}

	public function text_parse_links( $matches ) {
		return '<a class="zoom-twitter-widget__message-link" href="' . $matches[0] . '" target="_blank">' . $matches[0] . '</a>';
	}

	public function text_parse_usernames( $matches ) {
		return '<a class="zoom-twitter-widget__message-user-link" href="http://twitter.com/' . $matches[0] . '" target="_blank">' . $matches[0] . '</a>';
	}

	private function settings_changed() {
		$consumer_key        = get_option( 'zoom_twitter_widget_consumer_key' );
		$consumer_secret     = get_option( 'zoom_twitter_widget_consumer_secret' );
		$access_token        = get_option( 'zoom_twitter_widget_access_token' );
		$access_token_secret = get_option( 'zoom_twitter_widget_access_token_secret' );

		$settings_hash = get_option( 'zoom_twitter_widget_settings_hash' );

		$new_settings_hash = md5( $consumer_key . $consumer_secret . $access_token . $access_token_secret );

		if ( $settings_hash == $new_settings_hash ) {
			return false;
		}

		update_option( 'zoom_twitter_widget_settings_hash', $new_settings_hash );

		return true;
	}
}
