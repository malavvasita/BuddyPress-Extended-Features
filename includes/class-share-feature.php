<?php
/**
 * Share Feature Class
 *
 * Handles the frontend share functionality.
 *
 * @package BuddyPressExtended
 */

namespace BuddyPressExtended;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Share Feature Class.
 *
 * Manages the share button and modal functionality.
 */
class Share_Feature {

	/**
	 * Admin settings instance.
	 *
	 * @var Admin_Settings
	 */
	private $admin_settings;

	/**
	 * Whether hooks have been initialized.
	 *
	 * @var bool
	 */
	private static $hooks_initialized = false;

	/**
	 * Constructor.
	 *
	 * @param Admin_Settings $admin_settings Optional. Admin settings instance.
	 */
	public function __construct( $admin_settings = null ) {
		// Use provided instance or create new one.
		if ( null === $admin_settings ) {
			$this->admin_settings = new Admin_Settings();
		} else {
			$this->admin_settings = $admin_settings;
		}
		$this->init();
	}

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	private function init() {
		// Prevent duplicate hook registration.
		if ( self::$hooks_initialized ) {
			return;
		}

		// Only proceed if share feature is enabled.
		$settings = $this->admin_settings->get_settings();
		if ( empty( $settings['share_enabled'] ) ) {
			return;
		}

		self::$hooks_initialized = true;

		// Enqueue scripts and styles.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Add share button to content.
		add_filter( 'the_content', array( $this, 'add_share_button' ), 20 );

		// Add share button to BuddyPress activities.
		if ( function_exists( 'bp_is_active' ) && bp_is_active( 'activity' ) ) {
			add_action( 'bp_activity_entry_meta', array( $this, 'add_activity_share_button' ), 20 );
		}

		// AJAX handlers.
		add_action( 'wp_ajax_bpef_get_friends', array( $this, 'ajax_get_friends' ) );
		add_action( 'wp_ajax_bpef_send_share', array( $this, 'ajax_send_share' ) );

		// Add modal template.
		add_action( 'wp_footer', array( $this, 'render_modal_template' ) );
	}

	/**
	 * Enqueue scripts and styles.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		// Only enqueue if user is logged in and has BuddyPress.
		if ( ! is_user_logged_in() || ! function_exists( 'bp_is_active' ) ) {
			return;
		}

		// Enqueue styles.
		wp_enqueue_style(
			'bpef-share-style',
			BPEF_PLUGIN_URL . 'assets/css/share-style.css',
			array(),
			BPEF_VERSION
		);

		// Enqueue scripts.
		wp_enqueue_script(
			'bpef-share-script',
			BPEF_PLUGIN_URL . 'assets/js/share-script.js',
			array( 'jquery' ),
			BPEF_VERSION,
			true
		);

		// Localize script.
		wp_localize_script(
			'bpef-share-script',
			'bpefShare',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'bpef_share_nonce' ),
				'buttonText'   => $this->admin_settings->get_settings()['share_button_text'],
				'sendingText'  => __( 'Sending...', 'buddypress-extended-features' ),
				'successText'  => __( 'Shared successfully!', 'buddypress-extended-features' ),
				'errorText'    => __( 'An error occurred. Please try again.', 'buddypress-extended-features' ),
				'selectFriend' => __( 'Please select at least one friend.', 'buddypress-extended-features' ),
			)
		);
	}

	/**
	 * Add share button to post content.
	 *
	 * @param string $content Post content.
	 * @return string Modified content.
	 */
	public function add_share_button( $content ) {
		// Only show on single posts/pages.
		if ( ! is_singular() ) {
			return $content;
		}

		// Check if user is logged in.
		if ( ! is_user_logged_in() ) {
			return $content;
		}

		// Check if messages component is active.
		$component_checker = new Component_Checker();
		if ( ! $component_checker->is_messages_active() ) {
			return $content;
		}

		// Get settings.
		$settings = $this->admin_settings->get_settings();

		// Check if this post type is enabled.
		$post_type = get_post_type();
		if ( ! in_array( $post_type, $settings['content_types'], true ) ) {
			return $content;
		}

		// Get post ID.
		$post_id = get_the_ID();

		// Build share button.
		$button = $this->build_share_button( $post_id, $post_type );

		// Append button to content.
		$content .= $button;

		return $content;
	}

	/**
	 * Add share button to BuddyPress activity.
	 *
	 * @return void
	 */
	public function add_activity_share_button() {
		// Check if user is logged in.
		if ( ! is_user_logged_in() ) {
			return;
		}

		// Check if messages component is active.
		$component_checker = new Component_Checker();
		if ( ! $component_checker->is_messages_active() ) {
			return;
		}

		// Get settings.
		$settings = $this->admin_settings->get_settings();

		// Check if activity is enabled.
		if ( ! in_array( 'activity', $settings['content_types'], true ) ) {
			return;
		}

		// Get activity ID.
		$activity_id = bp_get_activity_id();

		if ( ! $activity_id ) {
			return;
		}

		// Build share button.
		$button = $this->build_share_button( $activity_id, 'activity' );

		// Output button.
		echo wp_kses_post( $button );
	}

	/**
	 * Build share button HTML.
	 *
	 * @param int    $item_id   Item ID (post ID or activity ID).
	 * @param string $item_type Item type (post type or 'activity').
	 * @return string Share button HTML.
	 */
	private function build_share_button( $item_id, $item_type ) {
		$settings = $this->admin_settings->get_settings();
		$button_text = $settings['share_button_text'];
		$button_class = $settings['share_button_class'];

		$button = sprintf(
			'<div class="bpef-share-wrapper">
				<button
					type="button"
					class="%s"
					data-item-id="%d"
					data-item-type="%s"
					data-nonce="%s"
				>
					%s
				</button>
			</div>',
			esc_attr( $button_class ),
			absint( $item_id ),
			esc_attr( $item_type ),
			esc_attr( wp_create_nonce( 'bpef_share_button_' . $item_id ) ),
			esc_html( $button_text )
		);

		return $button;
	}

	/**
	 * AJAX handler to get user's friends.
	 *
	 * @return void
	 */
	public function ajax_get_friends() {
		// Verify nonce.
		check_ajax_referer( 'bpef_share_nonce', 'nonce' );

		// Check if user is logged in.
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'buddypress-extended-features' ) ) );
		}

		// Check if messages component is active.
		$component_checker = new Component_Checker();
		if ( ! $component_checker->is_messages_active() ) {
			wp_send_json_error( array( 'message' => __( 'Messages component is not active.', 'buddypress-extended-features' ) ) );
		}

		// Get current user ID.
		$user_id = get_current_user_id();

		// Get friends.
		$friends = $this->get_user_friends( $user_id );

		if ( empty( $friends ) ) {
			wp_send_json_error( array( 'message' => __( 'You have no friends to share with.', 'buddypress-extended-features' ) ) );
		}

		wp_send_json_success( array( 'friends' => $friends ) );
	}

	/**
	 * Get user's friends.
	 *
	 * @param int $user_id User ID.
	 * @return array Array of friend data.
	 */
	private function get_user_friends( $user_id ) {
		$friends = array();

		// Check if friends component is active.
		if ( ! function_exists( 'bp_is_active' ) || ! bp_is_active( 'friends' ) ) {
			return $friends;
		}

		// Get friends using BuddyPress function.
		if ( function_exists( 'friends_get_friend_user_ids' ) ) {
			$friend_ids = friends_get_friend_user_ids( $user_id );
		} else {
			// Fallback: use database query.
			global $wpdb;
			$bp = buddypress();

			// Check if table name is available.
			if ( ! isset( $bp->friends->table_name ) ) {
				return $friends;
			}

			$friend_ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT friend_user_id FROM {$bp->friends->table_name}
					WHERE initiator_user_id = %d AND is_confirmed = 1
					UNION
					SELECT initiator_user_id FROM {$bp->friends->table_name}
					WHERE friend_user_id = %d AND is_confirmed = 1",
					$user_id,
					$user_id
				)
			);
		}

		if ( empty( $friend_ids ) ) {
			return $friends;
		}

		// Build friends array with avatar and name.
		foreach ( $friend_ids as $friend_id ) {
			$friend = get_userdata( $friend_id );
			if ( ! $friend ) {
				continue;
			}

			$friends[] = array(
				'id'     => $friend_id,
				'name'   => bp_core_get_user_displayname( $friend_id ),
				'avatar' => bp_core_fetch_avatar(
					array(
						'item_id' => $friend_id,
						'type'    => 'thumb',
						'html'    => false,
					)
				),
			);
		}

		return $friends;
	}

	/**
	 * AJAX handler to send share message.
	 *
	 * @return void
	 */
	public function ajax_send_share() {
		// Verify nonce.
		check_ajax_referer( 'bpef_share_nonce', 'nonce' );

		// Check if user is logged in.
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'You must be logged in.', 'buddypress-extended-features' ) ) );
		}

		// Check if messages component is active.
		$component_checker = new Component_Checker();
		if ( ! $component_checker->is_messages_active() ) {
			wp_send_json_error( array( 'message' => __( 'Messages component is not active.', 'buddypress-extended-features' ) ) );
		}

		// Get and sanitize input.
		$item_id   = isset( $_POST['item_id'] ) ? absint( $_POST['item_id'] ) : 0;
		$item_type = isset( $_POST['item_type'] ) ? sanitize_text_field( wp_unslash( $_POST['item_type'] ) ) : '';
		$friend_ids = isset( $_POST['friend_ids'] ) ? array_map( 'absint', (array) $_POST['friend_ids'] ) : array();

		// Validate input.
		if ( empty( $item_id ) || empty( $item_type ) || empty( $friend_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid data provided.', 'buddypress-extended-features' ) ) );
		}

		// Verify button nonce.
		$button_nonce = isset( $_POST['button_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['button_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $button_nonce, 'bpef_share_button_' . $item_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'buddypress-extended-features' ) ) );
		}

		// Get current user ID.
		$current_user_id = get_current_user_id();

		// Build message content.
		$message_content = $this->build_share_message( $item_id, $item_type, $current_user_id );

		// Send messages to each friend.
		$sent_count = 0;
		$errors     = array();

		foreach ( $friend_ids as $friend_id ) {
			// Verify friend relationship.
			if ( ! $this->is_friend( $current_user_id, $friend_id ) ) {
				$errors[] = sprintf(
					/* translators: %d: Friend ID */
					__( 'User %d is not your friend.', 'buddypress-extended-features' ),
					$friend_id
				);
				continue;
			}

			// Send message using BuddyPress function.
			$result = messages_new_message(
				array(
					'recipients' => array( $friend_id ),
					'subject'    => $this->build_share_subject( $item_type ),
					'content'    => $message_content,
					'sender_id'  => $current_user_id,
				)
			);

			if ( $result ) {
				$sent_count++;
			} else {
				$errors[] = sprintf(
					/* translators: %d: Friend ID */
					__( 'Failed to send message to user %d.', 'buddypress-extended-features' ),
					$friend_id
				);
			}
		}

		if ( $sent_count > 0 ) {
			wp_send_json_success(
				array(
					'message' => sprintf(
						/* translators: %d: Number of messages sent */
						_n(
							'Shared with %d friend.',
							'Shared with %d friends.',
							$sent_count,
							'buddypress-extended-features'
						),
						$sent_count
					),
					'sent_count' => $sent_count,
				)
			);
		} else {
			wp_send_json_error(
				array(
					'message' => __( 'Failed to send messages.', 'buddypress-extended-features' ),
					'errors'  => $errors,
				)
			);
		}
	}

	/**
	 * Build share message content.
	 *
	 * @param int    $item_id   Item ID.
	 * @param string $item_type Item type.
	 * @param int    $user_id   User ID who is sharing.
	 * @return string Message content.
	 */
	private function build_share_message( $item_id, $item_type, $user_id ) {
		$user_name = bp_core_get_user_displayname( $user_id );
		$item_url  = '';
		$item_title = '';

		if ( 'activity' === $item_type ) {
			// Get activity.
			$activity = new \BP_Activity_Activity( $item_id );
			if ( $activity->id ) {
				$item_url   = bp_activity_get_permalink( $item_id );
				$item_title = $activity->content;
			}
		} else {
			// Get post.
			$post = get_post( $item_id );
			if ( $post ) {
				$item_url   = get_permalink( $item_id );
				$item_title = get_the_title( $item_id );
			}
		}

		if ( empty( $item_url ) ) {
			return '';
		}

		$message = sprintf(
			/* translators: 1: User name, 2: Item title, 3: Item URL */
			__( '%1$s shared this with you: %2$s - %3$s', 'buddypress-extended-features' ),
			$user_name,
			$item_title,
			$item_url
		);

		return $message;
	}

	/**
	 * Build share message subject.
	 *
	 * @param string $item_type Item type.
	 * @return string Message subject.
	 */
	private function build_share_subject( $item_type ) {
		if ( 'activity' === $item_type ) {
			return __( 'Shared Activity', 'buddypress-extended-features' );
		}

		return __( 'Shared Content', 'buddypress-extended-features' );
	}

	/**
	 * Check if two users are friends.
	 *
	 * @param int $user_id   User ID.
	 * @param int $friend_id Friend ID.
	 * @return bool True if friends, false otherwise.
	 */
	private function is_friend( $user_id, $friend_id ) {
		if ( ! function_exists( 'bp_is_active' ) || ! bp_is_active( 'friends' ) ) {
			return false;
		}

		if ( function_exists( 'friends_check_friendship' ) ) {
			return friends_check_friendship( $user_id, $friend_id );
		}

		// Fallback: check database.
		global $wpdb;
		$bp = buddypress();

		// Check if table name is available.
		if ( ! isset( $bp->friends->table_name ) ) {
			return false;
		}

		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$bp->friends->table_name}
				WHERE (
					(initiator_user_id = %d AND friend_user_id = %d)
					OR (initiator_user_id = %d AND friend_user_id = %d)
				) AND is_confirmed = 1",
				$user_id,
				$friend_id,
				$friend_id,
				$user_id
			)
		);

		return (bool) $count;
	}

	/**
	 * Render modal template in footer.
	 *
	 * @return void
	 */
	public function render_modal_template() {
		// Only show for logged-in users.
		if ( ! is_user_logged_in() ) {
			return;
		}

		// Check if messages component is active.
		$component_checker = new Component_Checker();
		if ( ! $component_checker->is_messages_active() ) {
			return;
		}

		// Get settings.
		$settings = $this->admin_settings->get_settings();
		if ( empty( $settings['share_enabled'] ) ) {
			return;
		}

		?>
		<div id="bpef-share-modal" class="bpef-modal" style="display: none;">
			<div class="bpef-modal-overlay"></div>
			<div class="bpef-modal-content">
				<div class="bpef-modal-header">
					<h3><?php esc_html_e( 'Share with Friends', 'buddypress-extended-features' ); ?></h3>
					<button type="button" class="bpef-modal-close" aria-label="<?php esc_attr_e( 'Close', 'buddypress-extended-features' ); ?>">
						&times;
					</button>
				</div>
				<div class="bpef-modal-body">
					<div class="bpef-search-wrapper">
						<input
							type="search"
							id="bpef-friend-search"
							class="bpef-friend-search"
							placeholder="<?php esc_attr_e( 'Search friends...', 'buddypress-extended-features' ); ?>"
						/>
					</div>
					<div class="bpef-friends-list" id="bpef-friends-list">
						<div class="bpef-loading">
							<?php esc_html_e( 'Loading friends...', 'buddypress-extended-features' ); ?>
						</div>
					</div>
				</div>
				<div class="bpef-modal-footer">
					<button type="button" class="bpef-modal-cancel">
						<?php esc_html_e( 'Cancel', 'buddypress-extended-features' ); ?>
					</button>
					<button type="button" class="bpef-modal-send" disabled>
						<?php esc_html_e( 'Send', 'buddypress-extended-features' ); ?>
					</button>
				</div>
			</div>
		</div>
		<?php
	}
}

