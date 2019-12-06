<?php
/**
 * Support section.
 *
 * @package Meta Box
 */

?>
<div id="support" class="gt-tab-pane">
	<p class="about-description">
		<?php
		$allowed_html = array(
			'a' => array(
				'href' => array(),
			),
		);
		// Translators: %s - link to documentation.
		echo wp_kses( sprintf(esc_html__( 'Still need help with Meta Box? We offer excellent support for you. But don\'t forget to check our <a href="%s">documentation</a> first.', 'meta-box' ), 'https://docs.metabox.io?utm_source=WordPress&utm_medium=link&utm_campaign=plugin' ), $allowed_html );
		?>
	</p>
	<div class="two">
		<div class="col">
			<h3><?php esc_html_e( 'Free Support', 'meta-box' ); ?></h3>
			<p><?php esc_html_e( 'If you have any question about how to use the plugin, please open a new topic on WordPress.org support forum or open a new issue on Github (preferable). We will try to answer as soon as we can.', 'meta-box' ); ?><p>
			<p><a class="button" target="_blank" href="https://github.com/wpmetabox/meta-box/issues"><?php esc_html_e( 'Go to Github', 'meta-box' ); ?> &rarr;</a></p>
			<p><a class="button" target="_blank" href="https://wordpress.org/support/plugin/meta-box"><?php esc_html_e( 'Go to WordPress.org', 'meta-box' ); ?> &rarr;</a></p>
		</div>

		<div class="col">
			<h3><?php esc_html_e( 'Premium Support', 'meta-box' ); ?></h3>
			<p><?php esc_html_e( 'For users that have bought premium extensions, the support is provided in the Meta Box Support forum. Any question will be answered with technical details within 24 hours.', 'meta-box' ); ?><p>
			<p><a class="button" target="_blank" href="https://metabox.io/support/?utm_source=WordPress&utm_medium=link&utm_campaign=plugin"><?php esc_html_e( 'Go to support forum', 'meta-box' ); ?> &rarr;</a></p>
		</div>
	</div>
</div>
