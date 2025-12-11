<?php
/**
 * Admin Settings Class
 *
 * Handles admin settings page and options.
 *
 * @package BuddyPressExtended
 */

namespace BuddyPressExtended;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Admin Settings Class.
 *
 * Manages plugin settings and admin interface.
 */
class Admin_Settings {

	/**
	 * Option group name.
	 *
	 * @var string
	 */
	const OPTION_GROUP = 'bpef_settings';

	/**
	 * Option name.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'bpef_settings';

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
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Add settings page to admin menu.
	 *
	 * @return void
	 */
	public function add_settings_page() {
		// Add under Settings menu.
		add_options_page(
			__( 'BuddyPress Extended Features', 'buddypress-extended-features' ),
			__( 'BP Extended Features', 'buddypress-extended-features' ),
			'manage_options',
			'bpef-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register plugin settings.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			self::OPTION_GROUP,
			self::OPTION_NAME,
			array(
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => $this->get_default_settings(),
			)
		);

		// General settings section.
		add_settings_section(
			'bpef_general_section',
			__( 'General Settings', 'buddypress-extended-features' ),
			array( $this, 'render_general_section' ),
			'bpef-settings'
		);

		// Share feature enabled.
		add_settings_field(
			'share_enabled',
			__( 'Enable Share Feature', 'buddypress-extended-features' ),
			array( $this, 'render_share_enabled_field' ),
			'bpef-settings',
			'bpef_general_section'
		);

		// Content types.
		add_settings_field(
			'content_types',
			__( 'Content Types', 'buddypress-extended-features' ),
			array( $this, 'render_content_types_field' ),
			'bpef-settings',
			'bpef_general_section'
		);

		// Share button text.
		add_settings_field(
			'share_button_text',
			__( 'Share Button Text', 'buddypress-extended-features' ),
			array( $this, 'render_share_button_text_field' ),
			'bpef-settings',
			'bpef_general_section'
		);

		// Share button CSS class.
		add_settings_field(
			'share_button_class',
			__( 'Share Button CSS Class', 'buddypress-extended-features' ),
			array( $this, 'render_share_button_class_field' ),
			'bpef-settings',
			'bpef_general_section'
		);
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array $input Raw input data.
	 * @return array Sanitized settings.
	 */
	public function sanitize_settings( $input ) {
		$sanitized = array();

		// Share enabled.
		$sanitized['share_enabled'] = isset( $input['share_enabled'] ) ? 1 : 0;

		// Content types.
		if ( isset( $input['content_types'] ) && is_array( $input['content_types'] ) ) {
			$sanitized['content_types'] = array_map( 'sanitize_text_field', $input['content_types'] );
		} else {
			$sanitized['content_types'] = array();
		}

		// Share button text.
		$sanitized['share_button_text'] = isset( $input['share_button_text'] )
			? sanitize_text_field( $input['share_button_text'] )
			: __( 'Share', 'buddypress-extended-features' );

		// Share button CSS class.
		$sanitized['share_button_class'] = isset( $input['share_button_class'] )
			? sanitize_text_field( $input['share_button_class'] )
			: 'bpef-share-button';

		return $sanitized;
	}

	/**
	 * Get default settings.
	 *
	 * @return array Default settings.
	 */
	private function get_default_settings() {
		return array(
			'share_enabled'      => 1,
			'content_types'      => array( 'post', 'page' ),
			'share_button_text'  => __( 'Share', 'buddypress-extended-features' ),
			'share_button_class' => 'bpef-share-button',
		);
	}

	/**
	 * Get settings.
	 *
	 * @return array Plugin settings.
	 */
	public function get_settings() {
		return wp_parse_args(
			get_option( self::OPTION_NAME, array() ),
			$this->get_default_settings()
		);
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Check if messages component is active.
		$component_checker = new Component_Checker();
		$messages_active   = $component_checker->is_messages_active();

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php if ( ! $messages_active ) : ?>
				<div class="notice notice-error">
					<p>
						<?php
						esc_html_e(
							'The Private Messaging component must be enabled for the Share feature to work.',
							'buddypress-extended-features'
						);
						?>
					</p>
				</div>
			<?php endif; ?>

			<form action="options.php" method="post">
				<?php
				settings_fields( self::OPTION_GROUP );
				do_settings_sections( 'bpef-settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render general section description.
	 *
	 * @return void
	 */
	public function render_general_section() {
		?>
		<p>
			<?php
			esc_html_e(
				'Configure the general settings for BuddyPress Extended Features.',
				'buddypress-extended-features'
			);
			?>
		</p>
		<?php
	}

	/**
	 * Render share enabled field.
	 *
	 * @return void
	 */
	public function render_share_enabled_field() {
		$settings = $this->get_settings();
		$value    = isset( $settings['share_enabled'] ) ? $settings['share_enabled'] : 1;
		?>
		<label>
			<input
				type="checkbox"
				name="<?php echo esc_attr( self::OPTION_NAME ); ?>[share_enabled]"
				value="1"
				<?php checked( $value, 1 ); ?>
			/>
			<?php esc_html_e( 'Enable the share feature', 'buddypress-extended-features' ); ?>
		</label>
		<p class="description">
			<?php
			esc_html_e(
				'When enabled, users can share content with their BuddyPress friends via private messages.',
				'buddypress-extended-features'
			);
			?>
		</p>
		<?php
	}

	/**
	 * Render content types field.
	 *
	 * @return void
	 */
	public function render_content_types_field() {
		$settings     = $this->get_settings();
		$selected     = isset( $settings['content_types'] ) ? $settings['content_types'] : array( 'post', 'page' );
		$post_types   = get_post_types( array( 'public' => true ), 'objects' );
		$bp_activities = array();

		// Check if BuddyPress activities should be included.
		if ( function_exists( 'bp_is_active' ) && bp_is_active( 'activity' ) ) {
			$bp_activities['activity'] = __( 'BuddyPress Activity', 'buddypress-extended-features' );
		}

		?>
		<fieldset>
			<?php foreach ( $post_types as $post_type ) : ?>
				<label>
					<input
						type="checkbox"
						name="<?php echo esc_attr( self::OPTION_NAME ); ?>[content_types][]"
						value="<?php echo esc_attr( $post_type->name ); ?>"
						<?php checked( in_array( $post_type->name, $selected, true ) ); ?>
					/>
					<?php echo esc_html( $post_type->label ); ?>
				</label><br />
			<?php endforeach; ?>

			<?php if ( ! empty( $bp_activities ) ) : ?>
				<?php foreach ( $bp_activities as $key => $label ) : ?>
					<label>
						<input
							type="checkbox"
							name="<?php echo esc_attr( self::OPTION_NAME ); ?>[content_types][]"
							value="<?php echo esc_attr( $key ); ?>"
							<?php checked( in_array( $key, $selected, true ) ); ?>
						/>
						<?php echo esc_html( $label ); ?>
					</label><br />
				<?php endforeach; ?>
			<?php endif; ?>
		</fieldset>
		<p class="description">
			<?php
			esc_html_e(
				'Select which content types should display the share button.',
				'buddypress-extended-features'
			);
			?>
		</p>
		<?php
	}

	/**
	 * Render share button text field.
	 *
	 * @return void
	 */
	public function render_share_button_text_field() {
		$settings = $this->get_settings();
		$value    = isset( $settings['share_button_text'] )
			? $settings['share_button_text']
			: __( 'Share', 'buddypress-extended-features' );
		?>
		<input
			type="text"
			name="<?php echo esc_attr( self::OPTION_NAME ); ?>[share_button_text]"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
		/>
		<p class="description">
			<?php
			esc_html_e(
				'Customize the text displayed on the share button.',
				'buddypress-extended-features'
			);
			?>
		</p>
		<?php
	}

	/**
	 * Render share button CSS class field.
	 *
	 * @return void
	 */
	public function render_share_button_class_field() {
		$settings = $this->get_settings();
		$value    = isset( $settings['share_button_class'] )
			? $settings['share_button_class']
			: 'bpef-share-button';
		?>
		<input
			type="text"
			name="<?php echo esc_attr( self::OPTION_NAME ); ?>[share_button_class]"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
		/>
		<p class="description">
			<?php
			esc_html_e(
				'Customize the CSS class applied to the share button for styling purposes.',
				'buddypress-extended-features'
			);
			?>
		</p>
		<?php
	}
}

