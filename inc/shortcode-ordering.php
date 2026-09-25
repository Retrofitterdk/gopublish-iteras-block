<?php
/**
 * [iteras-ordering-for-post] shortcode.
 *
 * Meant to be dropped into Iteras' own "Call-to-action content" paywall box
 * (Iteras plugin settings -> paywall_box), which runs through do_shortcode()
 * for every paywalled post. Resolves the ordering form that actually sells
 * the subscription needed for whichever post it renders inside, using the
 * mapping configured under Settings -> Iteras Ordering.
 *
 * @package GopublishIterasBlock
 * @since   0.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'iteras_ordering_for_post_shortcode' ) ) {
	function iteras_ordering_for_post_shortcode(): string {
		if ( ! class_exists( 'Iteras' ) ) {
			return '';
		}

		// Already unlocked — nothing to sell.
		if ( iteras_user_has_access_for_post() ) {
			return '';
		}

		$ordering_id = iteras_get_ordering_id_for_post();

		if ( $ordering_id ) {
			return do_shortcode( '[iteras-ordering orderingid="' . esc_attr( $ordering_id ) . '"]' );
		}

		$fallback = iteras_get_ordering_fallback_cta();

		return $fallback !== '' ? wp_kses_post( do_shortcode( $fallback ) ) : '';
	}
}

if ( ! function_exists( 'gopublish_iteras_register_ordering_shortcode' ) ) {
	function gopublish_iteras_register_ordering_shortcode() {
		add_shortcode( 'iteras-ordering-for-post', 'iteras_ordering_for_post_shortcode' );
	}
}
add_action( 'init', 'gopublish_iteras_register_ordering_shortcode' );
