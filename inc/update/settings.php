<?php
/**
 * This class handles plugin settings, including adding settings page, show fields, save settings
 *
 * @package Meta Box
 */

/**
 * Meta Box Update Settings class
 *
 * @package Meta Box
 */
class RWMB_Update_Settings {
	/**
	 * The update option object.
	 *
	 * @var object
	 */
	private $option;

	/**
	 * The update checker object
	 *
	 * @var object
	 */
	private $checker;

	/**
	 * Constructor.
	 *
	 * @param object $checker Update checker object.
	 * @param object $option  Update option object.
	 */
	public function __construct( $checker, $option ) {
		$this->checker = $checker;
		$this->option  = $option;
	}

	/**
	 * Add hooks to create the settings page.
	 */
	public function init() {
		// Whether to enable Meta Box menu. Priority 1 makes sure it runs before adding Meta Box menu.
		$admin_menu_hook = $this->option->is_network_activated() ? 'network_admin_menu' : 'admin_menu';
		add_action( $admin_menu_hook, array( $this, 'enable_menu' ), 1 );
	}

	/**
	 * Enable Meta Box menu when a premium extension is installed.
	 */
	public function enable_menu() {
		if ( ! $this->checker->has_extensions() ) {
			return;
		}

		// Enable Meta Box menu only in single site.
		if ( ! $this->option->is_network_activated() ) {
			add_filter( 'rwmb_admin_menu', '__return_true' );
		}

		// Add submenu. Priority 90 makes it the last sub-menu item.
		$admin_menu_hook = $this->option->is_network_activated() ? 'network_admin_menu' : 'admin_menu';
		add_action( $admin_menu_hook, array( $this, 'add_settings_page' ), 90 );
	}

	/**
	 * Add settings page.
	 */
	public function add_settings_page() {
		$parent     = $this->option->is_network_activated() ? 'settings.php' : 'meta-box';
		$capability = $this->option->is_network_activated() ? 'manage_network_options' : 'manage_options';
		$title      = $this->option->is_network_activated() ? esc_html__( 'Meta Box License', 'meta-box' ) : esc_html__( 'License', 'meta-box' );
		$page_hook  = add_submenu_page(
			$parent,
			$title,
			$title,
			$capability,
			'meta-box-updater',
			array( $this, 'render' )
		);
		add_action( "load-{$page_hook}", array( $this, 'save' ) );
	}

	/**
	 * Render the content of settings page.
	 */
	public function render() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Meta Box License', 'meta-box' ); ?></h1>
			<p><?php esc_html_e( 'Please enter your license key to enable automatic updates for Meta Box extensions.', 'meta-box' ); ?></p>
			<p>
				<?php
				printf(
					// Translators: %1$s - URL to the My Account page, %2$s - URL to the pricing page.
					wp_kses_post(esc_html__( 'To get the license key, visit the <a href="%1$s" target="_blank">My Account</a> page on metabox.io website. If you have not purchased any extension yet, please <a href="%2$s" target="_blank">get a new license here</a>.', 'meta-box' ) ),
					'https://metabox.io/my-account/',
					'https://metabox.io/pricing/'
				);
				?>
			</p>

			<form action="" method="post">
				<?php wp_nonce_field( 'meta-box' ); ?>

				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'License Key', 'meta-box' ); ?></th>
						<td>
							<?php
							$messages = array(
								// Translators: %1$s - URL to the pricing page.
								'invalid' =>esc_html__( 'Your license key is <b>invalid</b>. Please update your license key or <a href="%1$s" target="_blank">get a new one here</a>.', 'meta-box' ),
								// Translators: %1$s - URL to the pricing page.
								'error'   =>esc_html__( 'Your license key is <b>invalid</b>. Please update your license key or <a href="%1$s" target="_blank">get a new one here</a>.', 'meta-box' ),
								// Translators: %2$s - URL to the My Account page.
								'expired' =>esc_html__( 'Your license key is <b>expired</b>. Please <a href="%2$s" target="_blank">renew your license</a>.', 'meta-box' ),
								'active'  =>esc_html__( 'Your license key is <b>active</b>.', 'meta-box' ),
							);
							$status   = $this->option->get_license_status();
							$api_key  = in_array( $status, array( 'expired', 'active' ), true ) ? '********************************' : $this->option->get( 'api_key' );
							?>
							<input class="regular-text" name="meta_box_updater[api_key]" value="<?php echo esc_attr( $api_key ); ?>" type="password">
							<?php if ( isset( $messages[ $status ] ) ) : ?>
								<p class="description"><?php echo wp_kses_post( sprintf( $messages[ $status ], 'https://metabox.io/pricing/', 'https://metabox.io/my-account/' ) ); ?></p>
							<?php endif; ?>
						</td>
					</tr>
				</table>

				<?php submit_button(esc_html__( 'Save Changes', 'meta-box' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Save update settings.
	 */
	public function save() {
		$request = rwmb_request();
		if ( ! $request->post( 'submit' ) ) {
			return;
		}
		check_admin_referer( 'meta-box' );

		$option           = $request->post( 'meta_box_updater', array() );
		$option           = (array) $option;
		$option['status'] = 'active';

		$args           = $option;
		$args['action'] = 'check_license';
		$response       = $this->checker->request( $args );
		$status         = isset( $response['status'] ) ? $response['status'] : 'invalid';

		if ( false === $response ) {
			add_settings_error( '', 'mb-error',esc_html__( 'Something wrong with the connection to metabox.io. Please try again later.', 'meta-box' ) );
		} elseif ( 'active' === $status ) {
			add_settings_error( '', 'mb-success',esc_html__( 'Your license is activated.', 'meta-box' ), 'updated' );
		} elseif ( 'expired' === $status ) {
			// Translators: %s - URL to the My Account page.
			$message =esc_html__( 'License expired. Please renew on the <a href="%s" target="_blank">My Account</a> page on metabox.io website.', 'meta-box' );
			$message = wp_kses_post( sprintf( $message, 'https://metabox.io/my-account/' ) );

			add_settings_error( '', 'mb-expired', $message );
		} else {
			// Translators: %1$s - URL to the My Account page, %2$s - URL to the pricing page.
			$message =esc_html__( 'Invalid license. Please <a href="%1$s" target="_blank">check again</a> or <a href="%2$s" target="_blank">get a new license here</a>.', 'meta-box' );
			$message = wp_kses_post( sprintf( $message, 'https://metabox.io/my-account/', 'https://metabox.io/pricing/' ) );

			add_settings_error( '', 'mb-invalid', $message );
		}

		$option['status'] = $status;

		$admin_notices_hook = $this->option->is_network_activated() ? 'network_admin_notices' : 'admin_notices';
		add_action( $admin_notices_hook, 'settings_errors' );

		$this->option->update( $option );
	}
}
