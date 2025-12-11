<?php
/**
 * Component Checker Class
 *
 * Verifies if BuddyPress components are active.
 *
 * @package BuddyPressExtended
 */

namespace BuddyPressExtended;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Component Checker Class.
 *
 * Checks if required BuddyPress components are active.
 */
class Component_Checker {

	/**
	 * Whether hooks have been initialized.
	 *
	 * @var bool
	 */
	private static $hooks_initialized = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
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

		self::$hooks_initialized = true;
		// Display admin notice if messages component is not active.
		add_action( 'admin_notices', array( $this, 'check_messages_component' ) );
	}

	/**
	 * Check if BuddyPress Messages component is active.
	 *
	 * @return bool True if active, false otherwise.
	 */
	public function is_messages_active() {
		if ( ! function_exists( 'bp_is_active' ) ) {
			return false;
		}

		return bp_is_active( 'messages' );
	}

	/**
	 * Display admin notice if Messages component is not enabled.
	 *
	 * @return void
	 */
	public function check_messages_component() {
		// Only show on admin pages.
		if ( ! is_admin() ) {
			return;
		}

		// Check if messages component is active.
		if ( $this->is_messages_active() ) {
			return;
		}

		// Don't show on BuddyPress settings page.
		$screen = get_current_screen();
		if ( $screen && 'settings_page_bp-components' === $screen->id ) {
			return;
		}

		?>
		<div class="notice notice-warning is-dismissible">
			<p>
				<strong><?php esc_html_e( 'BuddyPress Extended Features:', 'buddypress-extended-features' ); ?></strong>
				<?php
				printf(
					/* translators: %s: Link to BuddyPress components settings */
					wp_kses_post(
						__( 'The Private Messaging component must be enabled for the Share feature to work. <a href="%s">Enable it now</a>.', 'buddypress-extended-features' )
					),
					esc_url( admin_url( 'options-general.php?page=bp-components' ) )
				);
				?>
			</p>
		</div>
		<?php
	}
}

