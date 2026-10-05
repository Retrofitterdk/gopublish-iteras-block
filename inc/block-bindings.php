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

/**
 * "gopublish-iteras-block/login-url" — resolves the URL to Iteras' own
 * login page (the "User landing page" setting, $settings['user_url']) with
 * a return-to parameter appended, so a visitor who logs in there gets
 * redirected back to whichever page they came from.
 *
 * Deliberately reads `user_url`, not `subscribe_url`, even though the
 * latter is Iteras' own default for roughly the same purpose elsewhere
 * (e.g. if_logged_in_link_shortcode()). The Iteras Login Button is meant
 * for an "already a subscriber? log in here" context, sitting next to a
 * paywall call-to-action that's already selling subscriptions —
 * `subscribe_url` is documented to serve both logging in *and* becoming a
 * paying subscriber (see admin/views/admin.php's description for it), so
 * sending an existing subscriber there would mean landing on another
 * sales pitch right after the one they just saw, which feels circular.
 * `user_url` is a separate setting for exactly this login-only case —
 * it's hidden by default in Iteras' own settings screen, revealed by
 * gopublish_iteras_reveal_user_url_field() in inc/admin-settings.php.
 *
 * Reuses Iteras::return_to_page_shortcode() directly — the exact public
 * method backing Iteras' own [iteras-return-to-page] shortcode — rather
 * than reimplementing its iterasnext/urlencode(REQUEST_URI) logic. No
 * uses_context needed: like that shortcode, it reads the live request URL
 * directly, not anything tied to a specific post.
 *
 * Pairs with the "Iteras Login Button" core/button variation
 * (src/block-variations/login-button.js), which binds only the button's
 * `url` attribute (one of the block/attribute pairs WordPress core
 * supports for bindings, see wp-includes/block-bindings.php) — the
 * button's own text stays freely editable, since only the URL needs to be
 * dynamic here.
 */
if ( ! function_exists( 'gopublish_iteras_login_url_binding_callback' ) ) {
	function gopublish_iteras_login_url_binding_callback() {
		if ( ! class_exists( 'Iteras' ) ) {
			return '';
		}

		$iteras    = Iteras::get_instance();
		$login_url = $iteras->settings['user_url'] ?? '';

		if ( '' === $login_url ) {
			return '';
		}

		// Note: when $login_url has no existing query string, Iteras' own
		// return_to_page_shortcode() triggers a harmless PHP "Undefined
		// array key 'query'" warning internally (parse_url() omits the key
		// entirely for a URL with no '?', and its code unconditionally
		// appends to it right after). The returned URL is still correct —
		// tried pre-empting this by appending a bare '?' first, but that
		// makes parse_url() return an empty-but-defined 'query' key, which
		// flips a *different* branch of their own code (the one that
		// prepends '&' between an existing query and the new parameter)
		// and produces "?&iterasnext=..." — a real, visible bug, worse than
		// the warning it was meant to silence. Left as a plain call; the
		// warning is cosmetic (debug.log only) and the output is correct.
		return $iteras->return_to_page_shortcode( [ 'url' => $login_url ] );
	}
}

if ( ! function_exists( 'gopublish_iteras_register_login_url_binding' ) ) {
	function gopublish_iteras_register_login_url_binding() {
		register_block_bindings_source(
			'gopublish-iteras-block/login-url',
			[
				'label'              => __( 'Iteras Login URL', 'gopublish-iteras-block' ),
				'get_value_callback' => 'gopublish_iteras_login_url_binding_callback',
			]
		);
	}
}
add_action( 'init', 'gopublish_iteras_register_login_url_binding' );
