<?php

class Wpzoom_Twitter_Timeline_Widget extends WP_Widget {
	/**
	 * @var array
	 */
	protected $defaults;

	/**
	 * @var WPZOOM_TwitterOAuth_TwitterOAuth
	 */
	protected $connection;

	public function __construct() {
		parent::__construct(
			'wpzoom_twitter_timeline',
			esc_html__( 'Twitter Timeline by WPZOOM', 'wpzoom-twitter-timeline' ),
			array(
				'classname'   => 'wpzoom_twitter_timeline',
				'description' => __( 'Displays a Twitter Timeline.', 'wpzoom-twitter-timeline' ),
			)
		);

		$this->defaults = array(
			'title'                => esc_html__( 'Tweets', 'wpzoom-twitter-timeline' ),
			'tweet-limit'          => 5,
			'screen-name'          => '',
			'show-timestamp'       => true,
			'show-follow-button'   => true,
			'show-followers-count' => true
		);
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

		if ( false === $tweets ) {
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

		var_dump($instance);
		?>

		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php esc_html_e( 'Title:', 'wpzoom-twitter-timeline' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>"/>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'screen-name' ); ?>"><?php esc_html_e( 'Twitter Username:', 'wpzoom-twitter-timeline' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'screen-name' ); ?>" name="<?php echo $this->get_field_name( 'screen-name' ); ?>" type="text" value="<?php echo esc_attr( $instance['screen-name'] ); ?>"/>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'tweet-limit' ); ?>"><?php esc_html_e( '# of Tweets Shown:', 'wpzoom-twitter-timeline' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'tweet-limit' ); ?>" name="<?php echo $this->get_field_name( 'tweet-limit' ); ?>" type="number" min="1" max="20" value="<?php echo esc_attr( $instance['tweet-limit'] ); ?>"/>
		</p>

		<p>
			<input class="checkbox" type="checkbox" <?php checked( $instance['show-timestamp'] ); ?> id="<?php echo $this->get_field_id( 'show-timestamp' ); ?>" name="<?php echo $this->get_field_name( 'show-timestamp' ); ?>" />
			<label for="<?php echo $this->get_field_id( 'show-timestamp' ); ?>"><?php _e( 'Show Timestamp', 'wpzoom-twitter-timeline' ); ?></label>
		</p>

		<p>
			<input class="checkbox" type="checkbox" <?php checked( $instance['show-follow-button'] ); ?> id="<?php echo $this->get_field_id( 'show-follow-button' ); ?>" name="<?php echo $this->get_field_name( 'show-follow-button' ); ?>" />
			<label for="<?php echo $this->get_field_id( 'show-follow-button' ); ?>"><?php _e(' Display Follow me button', 'wpzoom-twitter-timeline' ); ?></label>
		</p>

		<p>
			<input class="checkbox" type="checkbox" <?php checked( $instance['show-followers-count'] ); ?> id="<?php echo $this->get_field_id( 'show-followers-count' ); ?>" name="<?php echo $this->get_field_name( 'show-followers-count' ); ?>" />
			<label for="<?php echo $this->get_field_id( 'show-followers-count' ); ?>"><?php _e(' Show follower count? ', 'wpzoom-twitter-timeline'); ?></label>
		</p>

	<?php
	}

	protected function display_tweets( $tweets, $instance ) {
		?>
		<ul class="zoom-twitter-timeline__items">

			<?php foreach ( $tweets as $tweet ) : ?>
				<?php
				$text = $this->parse_text( $tweet->text );
				$link = 'https://twitter.com/statuses/' . $tweet->id_str;
				$time = strtotime( $tweet->created_at );
				?>

				<li class="zoom-twitter-timeline__item">
					<p class="zoom-twitter_timeline__message">
						<?php echo $text; ?>

						<?php if ( $instance['show-timestamp'] ) : ?>

							<a class="zoom-twitter-timeline__item-permalink" href="<?php echo esc_url( $link ); ?>">
								<time class="zoom-twitter-timeline__item-timestamp" datetime="<?php echo esc_attr( mysql2date( 'c', $time ) ); ?>">
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

		if ( ! $show_follow_button ) return;

		?>
		<div class="zoom-twitter-timeline__follow-me">
			<a class="twitter-follow-button" href="https://twitter.com/<?php echo esc_attr( $screen_name ); ?>" data-show-count="<?php echo ($show_followers_count ? 'true' : 'false'); ?>">
				Follow @<?php echo esc_html( $screen_name ); ?>
			</a>
			<script src="//platform.twitter.com/widgets.js" type="text/javascript"></script>
		</div>
		<?php
	}

	/**
	 * Output errors if widget or plugin is misconfigured and current user can manage options (plugin settings).
	 *
	 * @return void
	 */
	protected function display_errors() {
		if ( 200 == $this->connection->lastHttpCode() ) {
			return;
		}

		if ( current_user_can( 'manage_options' ) ) {
			?>
			<p>
				<?php
				if ( 404 == $this->connection->lastHttpCode() ) {
					printf(
						__( 'Twitter Timeline: Non-existent username.', 'wpzoom-twitter-timeline' )
					);
				} else {
					printf(
						__( 'Twitter Timeline misconfigured, check <a href="%1$s" target="_blank">settings page</a> and <a href="%2$s" target="_blank">read instructions</a>. Error code: %3$s.', 'wpzoom-twitter-timeline' ),
						admin_url( 'options-general.php?page=wpzoom_twitter_timeline' ),
						'http://www.wpzoom.com/docs/twitter-widget-with-api-version-1-1-setup-instructions/',
						$this->connection->lastHttpCode()
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

		if ( false !== ( $cache = get_transient( $transient ) ) ) {
			return $cache;
		}

		require_once dirname( __FILE__ ) . '/../twitteroauth/WPZOOM_Twitter_OAuth.php';

		$consumer_key        = get_option( 'wpzoom_twitter_timeline_consumer_key' );
		$consumer_secret     = get_option( 'wpzoom_twitter_timeline_consumer_secret' );
		$access_token        = get_option( 'wpzoom_twitter_timeline_access_token' );
		$access_token_secret = get_option( 'wpzoom_twitter_timeline_access_token_secret' );

		$this->connection = new WPZOOM_TwitterOAuth_TwitterOAuth( $consumer_key, $consumer_secret, $access_token, $access_token_secret );

		$tweets = $this->connection->get( 'statuses/user_timeline', array(
			'screen_name'         => $screen_name,
			'count'               => $tweet_limit,
			'trim_user'           => true,
			'contributor_details' => false,
			'include_entities'    => false
		) );

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
			return sprintf( __( '%1$s ago', 'wpzoom-twitter-timeline' ), human_time_diff( $timestamp ) );
		} else {
			return mysql2date( get_option( 'date_format' ), $timestamp );
		}
	}

	public function text_parse_links( $matches ) {
		return '<a class="zoom-twitter-timeline__message-link" href="' . $matches[0] . '" target="_blank">' . $matches[0] . '</a>';
	}

	public function text_parse_usernames( $matches ) {
		return '<a class="zoom-twitter-timeline__message-user-link" href="http://twitter.com/' . $matches[0] . '" target="_blank">' . $matches[0] . '</a>';
	}
}
