<?php
/**
 * [iteras-paywall-cta] shortcode.
 *
 * Meant to be dropped into Iteras' own "Call-to-action content" paywall box
 * (Iteras plugin settings -> paywall_box), which runs through do_shortcode()
 * for every paywalled post. Resolves whichever synced pattern should be
 * shown to unlock the post it renders inside — the pattern assigned to its
 * paywall under Settings -> Iteras Paywall CTA, or that screen's "Default"
 * pattern when the post's paywall IDs don't resolve to exactly one — and
 * renders it via the theme's existing [synced_pattern] shortcode
 * (wp-content/themes/olfi/inc/shortcodes.php).
 *
 * Renamed from the earlier [iteras-ordering-for-post] shortcode, which
 * resolved a plain Iteras ordering ID instead of a whole pattern. If that
 * older tag is still in Iteras' paywall box, replace it with this one.
 *
 * @package GopublishIterasBlock
 * @since   0.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'iteras_paywall_cta_shortcode' ) ) {
	function iteras_paywall_cta_shortcode(): string {
		if ( ! class_exists( 'Iteras' ) ) {
			return '';
		}

		// Already unlocked — nothing to sell.
		if ( iteras_user_has_access_for_post() ) {
			return '';
		}

		$pattern_id = iteras_get_paywall_cta_pattern_id_for_post();

		if ( ! $pattern_id ) {
			$pattern_id = iteras_get_default_paywall_cta_pattern_id();
		}

		if ( ! $pattern_id ) {
			return '';
		}

		return do_shortcode( '[synced_pattern id="' . absint( $pattern_id ) . '"]' );
	}
}

if ( ! function_exists( 'gopublish_iteras_register_paywall_cta_shortcode' ) ) {
	function gopublish_iteras_register_paywall_cta_shortcode() {
		add_shortcode( 'iteras-paywall-cta', 'iteras_paywall_cta_shortcode' );
	}
}
add_action( 'init', 'gopublish_iteras_register_paywall_cta_shortcode' );
