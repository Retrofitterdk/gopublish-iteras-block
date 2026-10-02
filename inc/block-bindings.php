<?php
/**
 * Registers the "gopublish-iteras-block/paywall-label" Block Bindings
 * source: resolves per-post text for the "ABONNEMENT" eyebrow label shown
 * on paywalled posts (see wp-content/themes/olfi/patterns/post-meta.php).
 *
 * Mirrors the existing "gopublish/featured-category" binding source
 * registered in gopublish-essentials/inc/functions-fields.php, the
 * established pattern in this codebase for "same block markup, different
 * text per post" inside a reusable pattern.
 *
 * Loaded unconditionally (not admin-only) since bindings must resolve on
 * the public-facing render, not just in the block editor.
 *
 * @package GopublishIterasBlock
 * @since   0.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'gopublish_iteras_paywall_label_binding_callback' ) ) {
	function gopublish_iteras_paywall_label_binding_callback( $source_args, $block_instance ) {
		$post_id = $block_instance->context['postId'] ?? null;

		if ( ! $post_id || ! class_exists( 'Iteras' ) ) {
			return __( 'Subscription', 'olfi' );
		}

		$label = iteras_get_paywall_label_for_post( (int) $post_id );

		if ( ! $label ) {
			$label = iteras_get_default_paywall_cta_label();
		}

		return $label !== '' ? $label : __( 'Subscription', 'olfi' );
	}
}

if ( ! function_exists( 'gopublish_iteras_register_paywall_label_binding' ) ) {
	function gopublish_iteras_register_paywall_label_binding() {
		register_block_bindings_source(
			'gopublish-iteras-block/paywall-label',
			[
				'label'              => __( 'Iteras Paywall Label', 'gopublish-iteras-block' ),
				'get_value_callback' => 'gopublish_iteras_paywall_label_binding_callback',
				'uses_context'       => [ 'postId' ],
			]
		);
	}
}
add_action( 'init', 'gopublish_iteras_register_paywall_label_binding' );

/**
 * "gopublish-iteras-block/customer-id" — resolves the current visitor's
 * Iteras customer ID (iteras_get_customer_id(), see functions-iteras.php),
 * read from their iteraspass cookie. Unlike the paywall-label source above,
 * this is purely visitor-specific, not post-specific, so it needs no
 * uses_context — the same value applies regardless of which post/page the
 * bound block happens to be on.
 *
 * Returns '' when there's no valid pass (not logged in, forged, or
 * expired) — deliberately bare, with no "not logged in" fallback text of
 * its own. Paired with the "Iteras Customer ID" paragraph variation
 * (src/block-variations/customer-id.js), which editors are expected to
 * wrap, together with their own static text, inside an Iteras Login Status
 * block (mode: logged in) — that's what actually hides the whole thing for
 * logged-out visitors, not this binding.
 */
if ( ! function_exists( 'gopublish_iteras_customer_id_binding_callback' ) ) {
	function gopublish_iteras_customer_id_binding_callback() {
		return iteras_get_customer_id() ?? '';
	}
}

if ( ! function_exists( 'gopublish_iteras_register_customer_id_binding' ) ) {
	function gopublish_iteras_register_customer_id_binding() {
		register_block_bindings_source(
			'gopublish-iteras-block/customer-id',
			[
				'label'              => __( 'Iteras Customer ID', 'gopublish-iteras-block' ),
				'get_value_callback' => 'gopublish_iteras_customer_id_binding_callback',
			]
		);
	}
}
add_action( 'init', 'gopublish_iteras_register_customer_id_binding' );
