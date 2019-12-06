<?php
/**
 * Add about page for the Meta Box plugin.
 *
 * @package Meta Box
 */

/**
 * About page class.
 */
class RWMB_About {
	/**
	 * The updater checker object.
	 *
	 * @var object
	 */
	private $update_checker;

	/**
	 * Constructor.
	 *
	 * @param object $update_checker The updater checker object.
	 */
	public function __construct( $update_checker ) {
		$this->update_checker = $update_checker;
	}

	/**
	 * Init hooks.
	 */
	public function init() {
		// Add links to about page in the plugin action links.
		add_filter( 'plugin_action_links_meta-box/meta-box.php', array( $this, 'plugin_links' ) );

		// Add a shared top-level admin menu and Dashboard page. Use priority 5 to show Dashboard at the top.
		add_action( 'admin_menu', array( $this, 'add_menu' ), 5 );
		add_action( 'admin_menu', array( $this, 'add_submenu' ), 5 );

		// If no admin menu, then hide the About page.
		add_action( 'admin_head', array( $this, 'hide_page' ) );

		// Redirect to about page after activation.
		add_action( 'activated_plugin', array( $this, 'redirect' ), 10, 2 );
	}

	/**
	 * Add links to About page.
	 *
	 * @param array $links Array of plugin links.
	 *
	 * @return array
	 */
	public function plugin_links( $links ) {
		$links[] = '<a href="' . esc_url( $this->get_menu_link() ) . '">' . esc_html__( 'About', 'meta-box' ) . '</a>';
		return $links;
	}

	/**
	 * Register admin page.
	 */
	public function add_menu() {
		if ( ! $this->has_menu() ) {
			return;
		}
		add_menu_page(
		esc_html__( 'Meta Box', 'meta-box' ),
		esc_html__( 'Meta Box', 'meta-box' ),
			'activate_plugins',
			'meta-box',
			'__return_null',
			'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4KPHN2ZyB2aWV3Qm94PSIxNjQuMzI4IDE0OS40NDEgNTMuNDcgNDIuNjYiIHdpZHRoPSI1My40NyIgaGVpZ2h0PSI0Mi42NiIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICA8cGF0aCBkPSJNIDIwNC42NjggMTc5LjM5MSBMIDIwNS40ODggMTYwLjU1MSBMIDIwNS4zMTggMTYwLjUyMSBMIDE5My44ODggMTkyLjEwMSBMIDE4OC4xNDggMTkyLjEwMSBMIDE3Ni43NzggMTYwLjY0MSBMIDE3Ni42MDggMTYwLjY2MSBMIDE3Ny40MjggMTc5LjM5MSBMIDE3Ny40MjggMTg2LjA5MSBMIDE4MS45OTggMTg2Ljk3MSBMIDE4MS45OTggMTkyLjEwMSBMIDE2NC4zMjggMTkyLjEwMSBMIDE2NC4zMjggMTg2Ljk3MSBMIDE2OC44NjggMTg2LjA5MSBMIDE2OC44NjggMTU1LjQ4MSBMIDE2NC4zMjggMTU0LjYwMSBMIDE2NC4zMjggMTQ5LjQ0MSBMIDE2OC44NjggMTQ5LjQ0MSBMIDE4MC4wMjggMTQ5LjQ0MSBMIDE5MC44OTggMTgwLjg4MSBMIDE5MS4wNzggMTgwLjg4MSBMIDIwMi4wMzggMTQ5LjQ0MSBMIDIxNy43OTggMTQ5LjQ0MSBMIDIxNy43OTggMTU0LjYwMSBMIDIxMy4yMjggMTU1LjQ4MSBMIDIxMy4yMjggMTg2LjA5MSBMIDIxNy43OTggMTg2Ljk3MSBMIDIxNy43OTggMTkyLjEwMSBMIDIwMC4xMjggMTkyLjEwMSBMIDIwMC4xMjggMTg2Ljk3MSBMIDIwNC42NjggMTg2LjA5MSBMIDIwNC42NjggMTc5LjM5MSBaIiBzdHlsZT0iZmlsbDogcmdiKDE1OCwgMTYzLCAxNjgpOyB3aGl0ZS1zcGFjZTogcHJlOyIvPgo8L3N2Zz4='
		);
	}

	/**
	 * Add submenu for the About page.
	 */
	public function add_submenu() {
		$parent_menu = $this->has_menu() ? 'meta-box' : $this->get_parent_menu();
		$about       = add_submenu_page(
			$parent_menu,
		esc_html__( 'Welcome to Meta Box', 'meta-box' ),
		esc_html__( 'Dashboard', 'meta-box' ),
			'activate_plugins',
			'meta-box',
			array( $this, 'render' )
		);
		add_action( "load-$about", array( $this, 'load_about' ) );
	}

	/**
	 * Functions and hooks for about page.
	 */
	public function load_about() {
		$this->enqueue();
		add_filter( 'admin_footer_text', array( $this, 'change_footer_text' ) );
	}

	/**
	 * Hide about page from the admin menu.
	 */
	public function hide_page() {
		remove_submenu_page( $this->get_parent_menu(), 'meta-box' );
	}

	/**
	 * Render admin page.
	 */
	public function render() {
		?>
		<div class="wrap">
			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-2">
					<div id="post-body-content">
						<div class="about-wrap">
							<?php
							include dirname( __FILE__ ) . '/sections/welcome.php';
							include dirname( __FILE__ ) . '/sections/tabs.php';
							include dirname( __FILE__ ) . '/sections/getting-started.php';
							include dirname( __FILE__ ) . '/sections/extensions.php';
							include dirname( __FILE__ ) . '/sections/support.php';
							do_action( 'rwmb_about_tabs_content' );
							?>
						</div>
					</div>
					<div id="postbox-container-1" class="postbox-container">
						<?php
						include dirname( __FILE__ ) . '/sections/newsletter.php';
						if ( ! $this->update_checker->has_extensions() ) {
							include dirname( __FILE__ ) . '/sections/upgrade.php';
						}
						?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Enqueue CSS and JS.
	 */
	public function enqueue() {
		wp_enqueue_style( 'meta-box-about', RWMB_URL . 'inc/about/css/about.css', array(), RWMB_VER );
		wp_enqueue_script( 'meta-box-about', RWMB_URL . 'inc/about/js/about.js', array( 'jquery' ), RWMB_VER, true );
	}

	/**
	 * Change WordPress footer text on about page.
	 */
	public function change_footer_text() {
		// Translators: %1$s - link to review form.
		echo wp_kses_post( sprintf(esc_html__( 'Please rate <strong>Meta Box</strong> <a href="%1$s" target="_blank">&#9733;&#9733;&#9733;&#9733;&#9733;</a> on <a href="%1$s" target="_blank">WordPress.org</a> to help us spread the word. Thank you from the Meta Box team!', 'meta-box' ), 'https://wordpress.org/support/view/plugin-reviews/meta-box?filter=5#new-post' ) );
	}

	/**
	 * Redirect to about page after Meta Box has been activated.
	 *
	 * @param string $plugin       Path to the main plugin file from plugins directory.
	 * @param bool   $network_wide Whether to enable the plugin for all sites in the network
	 *                             or just the current site. Multisite only. Default is false.
	 */
	public function redirect( $plugin, $network_wide = false ) {
		if ( 'cli' !== php_sapi_name() && ! $network_wide && 'meta-box/meta-box.php' === $plugin && ! $this->is_bundled() ) {
			wp_safe_redirect( $this->get_menu_link() );
			die;
		}
	}

	/**
	 * Get link to the plugin admin menu.
	 *
	 * @return string
	 */
	protected function get_menu_link() {
		$menu = $this->has_menu() ? 'admin.php?page=meta-box' : $this->get_parent_menu() . '?page=meta-box';
		return admin_url( $menu );
	}

	/**
	 * Get default parent menu, which is Plugins.
	 *
	 * @return string
	 */
	protected function get_parent_menu() {
		return 'plugins.php';
	}

	/**
	 * Check if the plugin has a top-level admin menu.
	 *
	 * @return bool
	 */
	protected function has_menu() {
		return apply_filters( 'rwmb_admin_menu', false );
	}

	/**
	 * Check if Meta Box is bundled by TGM Activation Class.
	 */
	protected function is_bundled() {
		// @codingStandardsIgnoreLine
		foreach ( $_REQUEST as $key => $value ) {
			if ( false !== strpos( $key, 'tgmpa' ) || ( ! is_array( $value ) && false !== strpos( $value, 'tgmpa' ) ) ) {
				return true;
			}
		}
		return false;
	}
}
