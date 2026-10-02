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
 * renders that pattern's block content directly (get_post() + do_blocks(),
 * with do_shortcode() over the result so any shortcode embedded inside the
 * pattern, e.g. [iteras-ordering], still renders — do_blocks() alone won't
 * process that).
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

		$pattern = get_post( (int) $pattern_id );
		if ( ! $pattern || 'wp_block' !== $pattern->post_type || 'publish' !== $pattern->post_status ) {
			return '';
		}

		// do_blocks() alone only renders block markup — it doesn't process any
		// shortcode text sitting inside it (e.g. a Shortcode block, or a
		// shortcode typed directly into a paragraph). WordPress's own the_content
		// pipeline runs do_blocks (priority 9) then do_shortcode (priority 11) as
		// two separate steps over the same string; mirror that here so nested
		// shortcodes inside a synced pattern actually render.
		return do_shortcode( do_blocks( $pattern->post_content ) );
	}
}

if ( ! function_exists( 'gopublish_iteras_register_paywall_cta_shortcode' ) ) {
	function gopublish_iteras_register_paywall_cta_shortcode() {
		add_shortcode( 'iteras-paywall-cta', 'iteras_paywall_cta_shortcode' );
	}
}
add_action( 'init', 'gopublish_iteras_register_paywall_cta_shortcode' );
