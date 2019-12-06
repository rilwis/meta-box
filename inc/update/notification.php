<?php
/**
 * This class notifies users to enter or update license key.
 *
 * @package Meta Box
 */

/**
 * Meta Box Update Notification class
 *
 * @package Meta Box
 */
class RWMB_Update_Notification {
	/**
	 * The update option object.
	 *
	 * @var object
	 */
	private $option;

	/**
	 * Settings page URL.
	 *
	 * @var string
	 */
	private $settings_page;

	/**
	 * The update checker object.
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

		$this->settings_page = $option->is_network_activated() ? network_admin_url( 'settings.php?page=meta-box-updater' ) : admin_url( 'admin.php?page=meta-box-updater' );
	}

	/**
	 * Add hooks to show admin notice.
	 */
	public function init() {
		if ( ! $this->checker->has_extensions() ) {
			return;
		}

		// Show update message on Plugins page.
		$extensions = $this->checker->get_extensions();
		foreach ( $extensions as $extension ) {
			$file = "{$extension}/{$extension}.php";
			add_action( "in_plugin_update_message-$file", array( $this, 'show_update_message' ), 10, 2 );
			add_filter( "plugin_action_links_$file", array( $this, 'plugin_links' ), 20 );
		}

		// Show global update notification.
		if ( $this->is_dismissed() ) {
			return;
		}

		$admin_notices_hook = $this->option->is_network_activated() ? 'network_admin_notices' : 'admin_notices';
		add_action( $admin_notices_hook, array( $this, 'notify' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'wp_ajax_mb_dismiss_notification', array( $this, 'dismiss' ) );
	}

	/**
	 * Enqueue the notification script.
	 */
	public function enqueue() {
		wp_enqueue_script( 'mb-notification', RWMB_JS_URL . 'notification.js', array( 'jquery' ), RWMB_VER, true );
		wp_localize_script( 'mb-notification', 'MBNotification', array( 'nonce' => wp_create_nonce( 'dismiss' ) ) );
	}

	/**
	 * Dismiss the notification permanently via ajax.
	 */
	public function dismiss() {
		check_ajax_referer( 'dismiss', 'nonce' );

		$this->option->update(
			array(
				'notification_dismissed'      => 1,
				'notification_dismissed_time' => time(),
			)
		);

		wp_send_json_success();
	}

	/**
	 * Notify users to enter license key.
	 */
	public function notify() {
		// Do not show notification on License page.
		$screen = get_current_screen();
		if ( in_array( $screen->id, array( 'meta-box_page_meta-box-updater', 'settings_page_meta-box-updater-network' ), true ) ) {
			return;
		}

		$messages = array(
			// Translators: %1$s - URL to the settings page, %2$s - URL to the pricing page.
			'no_key'  =>esc_html__( 'You have not set your Meta Box license key yet, which means you are missing out on automatic updates and support! Please <a href="%1$s">enter your license key</a> or <a href="%2$s" target="_blank">get a new one here</a>.', 'meta-box' ),
			// Translators: %1$s - URL to the settings page, %2$s - URL to the pricing page.
			'invalid' =>esc_html__( 'Your license key for Meta Box is <b>invalid</b>. Please <a href="%1$s">update your license key</a> or <a href="%2$s" target="_blank">get a new one</a> to enable automatic updates.', 'meta-box' ),
			// Translators: %1$s - URL to the settings page, %2$s - URL to the pricing page.
			'error'   =>esc_html__( 'Your license key for Meta Box is <b>invalid</b>. Please <a href="%1$s">update your license key</a> or <a href="%2$s" target="_blank">get a new one</a> to enable automatic updates.', 'meta-box' ),
			// Translators: %3$s - URL to the My Account page.
			'expired' =>esc_html__( 'Your license key for Meta Box is <b>expired</b>. Please <a href="%3$s" target="_blank">renew your license</a> to get automatic updates and premium support.', 'meta-box' ),
		);
		$status   = $this->option->get_license_status();
		if ( ! isset( $messages[ $status ] ) ) {
			return;
		}

		echo '<div id="meta-box-notification" class="notice notice-warning is-dismissible"><p><span class="dashicons dashicons-warning" style="color: #f56e28"></span> ', wp_kses_post( sprintf( $messages[ $status ], $this->settings_page, 'https://metabox.io/pricing/', 'https://metabox.io/my-account/' ) ), '</p></div>';
	}

	/**
	 * Show update message on Plugins page.
	 *
	 * @param  array  $plugin_data Plugin data.
	 * @param  object $response    Available plugin update data.
	 */
	public function show_update_message( $plugin_data, $response ) {
		// Users have an active license.
		if ( ! empty( $response->package ) ) {
			return;
		}

		$messages = array(
			// Translators: %1$s - URL to the settings page, %2$s - URL to the pricing page.
			'no_key'  =>esc_html__( 'Please <a href="%1$s">enter your license key</a> or <a href="%2$s" target="_blank">get a new one here</a>.', 'meta-box' ),
			// Translators: %1$s - URL to the settings page, %2$s - URL to the pricing page.
			'invalid' =>esc_html__( 'Your license key is <b>invalid</b>. Please <a href="%1$s">update your license key</a> or <a href="%2$s" target="_blank">get a new one here</a>.', 'meta-box' ),
			// Translators: %1$s - URL to the settings page, %2$s - URL to the pricing page.
			'error'   =>esc_html__( 'Your license key is <b>invalid</b>. Please <a href="%1$s">update your license key</a> or <a href="%2$s" target="_blank">get a new one here</a>.', 'meta-box' ),
			// Translators: %3$s - URL to the My Account page.
			'expired' =>esc_html__( 'Your license key is <b>expired</b>. Please <a href="%3$s" target="_blank">renew your license</a>.', 'meta-box' ),
		);
		$status   = $this->option->get_license_status();
		if ( ! isset( $messages[ $status ] ) ) {
			return;
		}

		echo '<br><span style="width: 26px; height: 20px; display: inline-block;">&nbsp;</span>' . wp_kses_post( sprintf( $messages[ $status ], $this->settings_page, 'https://metabox.io/pricing/', 'https://metabox.io/my-account/' ) );
	}

	/**
	 * Add link for activate or update the license key.
	 *
	 * @param array $links Array of plugin links.
	 *
	 * @return array
	 */
	public function plugin_links( $links ) {
		$status = $this->option->get_license_status();
		if ( 'active' === $status ) {
			return $links;
		}

		$text    = 'no_key' === $status ?esc_html__( 'Activate License', 'meta-box' ) :esc_html__( 'Update License', 'meta-box' );
		$links[] = '<a href="' . esc_url( $this->settings_page ) . '" class="rwmb-activate-license" style="color: #39b54a; font-weight: bold">' . esc_html( $text ) . '</a>';

		return $links;
	}

	/**
	 * Check if the global notification is dismissed.
	 * Auto re-enable the notification every 2 weeks after it's dissmissed.
	 *
	 * @return bool
	 */
	private function is_dismissed() {
		$time = $this->option->get( 'notification_dismissed_time' );

		return $this->option->get( 'notification_dismissed' ) && time() - $time < 14 * DAY_IN_SECONDS;
	}
}
