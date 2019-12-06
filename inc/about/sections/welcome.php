<?php
/**
 * Welcome section.
 *
 * @package Meta Box
 */

?>
<h1>
	<?php
	$plugin = get_plugin_data( RWMB_DIR . 'meta-box.php', false, false );

	// Translators: %s - Plugin name.
	echo esc_html( sprintf(esc_html__( 'Welcome to %s', 'meta-box' ), $plugin['Name'] ) );
	?>
</h1>
<div class="about-text"><?php esc_html_e( 'Meta Box is a free Gutenberg and GDPR-compatible WordPress custom fields plugin and framework that makes quick work of customizing a website with—you guessed it—meta boxes and custom fields in WordPress. Follow the instruction below to get started!', 'meta-box' ); ?></div>
<a target="_blank" class="wp-badge" href="<?php echo esc_url( 'https://metabox.io/?utm_source=WordPress&utm_medium=link&utm_campaign=plugin' ); ?>"><?php echo esc_html( $plugin['Name'] ); ?></a>
<p class="about-buttons">
	<a target="_blank" class="button" href="<?php echo esc_url( 'https://docs.metabox.io?utm_source=WordPress&utm_medium=link&utm_campaign=plugin' ); ?>"><?php esc_html_e( 'Documentation', 'meta-box' ); ?></a>
	<a target="_blank" class="button" href="<?php echo esc_url( 'https://metabox.io/plugins/?utm_source=WordPress&utm_medium=link&utm_campaign=plugin' ); ?>"><?php esc_html_e( 'Extensions', 'meta-box' ); ?></a>
	<a target="_blank" class="button" href="<?php echo esc_url( 'https://metabox.io/support/?utm_source=WordPress&utm_medium=link&utm_campaign=plugin' ); ?>"><?php esc_html_e( 'Support', 'meta-box' ); ?></a>
	<a target="_blank" class="button" href="https://www.facebook.com/sharer/sharer.php?u=https%3A//metabox.io"><span class="dashicons dashicons-facebook-alt"></span> Share</a>
	<a target="_blank" class="button" href="https://twitter.com/home?status=Reduce%20your%20dev%20time!%20Meta%20Box%20is%20the%20most%20powerful%20custom%20fields%20plugin%20for%20WordPress%20on%20the%20web%20https%3A//metabox.io%20via%20%40wpmetabox"><span class="dashicons dashicons-twitter"></span> Tweet</a>
</p>
