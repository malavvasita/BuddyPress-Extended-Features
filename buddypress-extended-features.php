<?php
/**
 * Plugin Name:       BuddyPress Extended Features
 * Plugin URI:        https://github.com/malavvasita/buddypress-extended-features
 * Description:       Extends BuddyPress with additional sharing features, allowing users to share content with their friends via private messages.
 * Version:           1.0.0
 * Requires at least: 5.0
 * Requires PHP:      7.4
 * Author:            Malav Vasita
 * Author URI:        https://github.com/malavvasita
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       buddypress-extended-features
 * Domain Path:       /languages
 *
 * @package BuddyPressExtended
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Plugin version.
 */
define( 'BPEF_VERSION', '1.0.0' );

/**
 * Plugin directory path.
 */
define( 'BPEF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL.
 */
define( 'BPEF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Check if BuddyPress is active.
 *
 * @return bool True if BuddyPress is active, false otherwise.
 */
function bpef_is_buddypress_active() {
	// Check if BuddyPress constant is defined (loaded).
	if ( defined( 'BP_VERSION' ) ) {
		return true;
	}

	// Check if BuddyPress function exists.
	if ( function_exists( 'buddypress' ) ) {
		return true;
	}

	// Check if BuddyPress plugin is active.
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$bp_plugin = 'buddypress/bp-loader.php';
	if ( is_plugin_active( $bp_plugin ) || is_plugin_active_for_network( $bp_plugin ) ) {
		return true;
	}

	return false;
}

/**
 * Display admin notice if BuddyPress is not active.
 *
 * @return void
 */
function bpef_buddypress_missing_notice() {
	?>
	<div class="notice notice-error">
		<p>
			<?php
			esc_html_e(
				'BuddyPress Extended Features requires BuddyPress to be installed and activated.',
				'buddypress-extended-features'
			);
			?>
		</p>
	</div>
	<?php
}

/**
 * Autoloader for plugin classes.
 *
 * @param string $class_name The class name to load.
 * @return void
 */
spl_autoload_register( function ( $class_name ) {
	// Only load classes from our namespace.
	if ( strpos( $class_name, 'BuddyPressExtended\\' ) !== 0 ) {
		return;
	}

	// Remove namespace prefix.
	$class_name = str_replace( 'BuddyPressExtended\\', '', $class_name );

	// Convert class name to file path.
	$file_name = 'class-' . str_replace( '_', '-', strtolower( $class_name ) ) . '.php';
	$file_path = BPEF_PLUGIN_DIR . 'includes/' . $file_name;

	if ( file_exists( $file_path ) ) {
		require_once $file_path;
	}
} );

/**
 * Main plugin class.
 *
 * @package BuddyPressExtended
 */
final class BuddyPress_Extended_Features {

	/**
	 * Plugin instance.
	 *
	 * @var BuddyPress_Extended_Features
	 */
	private static $instance = null;

	/**
	 * Component checker instance.
	 *
	 * @var BuddyPressExtended\Component_Checker
	 */
	public $component_checker;

	/**
	 * Share feature instance.
	 *
	 * @var BuddyPressExtended\Share_Feature
	 */
	public $share_feature;

	/**
	 * Admin settings instance.
	 *
	 * @var BuddyPressExtended\Admin_Settings
	 */
	public $admin_settings;

	/**
	 * Get plugin instance.
	 *
	 * @return BuddyPress_Extended_Features
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init();
	}

	/**
	 * Initialize plugin.
	 *
	 * @return void
	 */
	private function init() {
		// Load plugin text domain.
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

		// Initialize component checker.
		$this->component_checker = new BuddyPressExtended\Component_Checker();

		// Initialize admin settings.
		$this->admin_settings = new BuddyPressExtended\Admin_Settings();

		// Initialize share feature only if messages component is active.
		if ( $this->component_checker->is_messages_active() ) {
			$this->share_feature = new BuddyPressExtended\Share_Feature( $this->admin_settings );
		}
	}

	/**
	 * Load plugin text domain.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'buddypress-extended-features',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);
	}
}

/**
 * Initialize plugin.
 *
 * @return BuddyPress_Extended_Features|false Plugin instance or false if BuddyPress is not active.
 */
function bpef_init() {
	// Prevent multiple initializations.
	static $initialized = false;
	if ( $initialized ) {
		return BuddyPress_Extended_Features::get_instance();
	}

	// Check if BuddyPress is active.
	if ( ! bpef_is_buddypress_active() ) {
		add_action( 'admin_notices', 'bpef_buddypress_missing_notice' );
		return false;
	}

	$initialized = true;
	return BuddyPress_Extended_Features::get_instance();
}

// Initialize plugin after plugins are loaded to ensure BuddyPress is available.
add_action( 'plugins_loaded', 'bpef_init', 20 );

