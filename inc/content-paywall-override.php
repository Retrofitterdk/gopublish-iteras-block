<?php
/**
 * Lets paywalls configured as "registered users" (see
 * inc/admin-settings.php, iteras_paywall_allows_user_level() in
 * inc/functions-iteras.php) grant a visitor the full, untruncated article —
 * not just recognition in this plugin's own blocks.
 *
 * Iteras' own content truncation (Iteras::resolve_paywall_state() /
 * potentially_paywall_content() in iteras-public.php) is driven entirely by
 * its private pass_authorized(), which only ever recognises "sub" level —
 * there's no hook to change *how* that check evaluates. The only exposed
 * extension point is the 'iteras_override_content_paywall' filter, which can
 * force post_is_paywalled to false outright (or swap which paywall IDs
 * apply), but can't make Iteras' own check accept "user" level. So instead
 * of trying to fix that check, this unwalls the post entirely for the
 * current visitor once our own, separate check decides they qualify.
 *
 * Caching gap, found and fixed in 1.3.0: Iteras' own cache-prevention
 * (potentially_prevent_caching(), template_redirect priority 1) reads the
 * exact same post_is_paywalled value this filter forces to false — so the
 * moment it applies, Iteras' own logic sees "not paywalled" and sends no
 * protective headers at all, with no way to know a specific visitor's own
 * pass is the only reason. Confirmed directly: an anonymous request to a
 * "Registered users"-enabled post sent Iteras' full Cache-Control: no-cache
 * header set; the same post requested with a valid "user"-level pass sent
 * none whatsoever — meaning any caching layer in front (confirmed
 * separately for this site's SiteGround Dynamic Cache, see the Olfi
 * theme's inc/custom.php) would cache the fully unlocked response and
 * serve it to every subsequent visitor, including ones with no pass at
 * all, completely defeating that paywall. gopublish_iteras_emit_no_cache_signals()
 * below closes this independently of Iteras' own (blind, in this one
 * case) decision, whenever this filter is the reason a post opened up.
 *
 * @package GopublishIterasBlock
 * @since   0.10.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'gopublish_iteras_user_level_override_paywall' ) ) {
	/**
	 * @param string[]|null $paywall_ids     Paywall IDs assigned to the post.
	 * @param WP_Post       $post
	 * @param bool          $user_authorized Whether Iteras' own (sub-only) check already authorized this visitor.
	 * @param array         $settings        Iteras plugin settings.
	 * @return string[]|false|null
	 */
	function gopublish_iteras_user_level_override_paywall( $paywall_ids, $post, $user_authorized, $settings ) {
		if ( $user_authorized || empty( $paywall_ids ) ) {
			return $paywall_ids;
		}

		// A post can carry several paywall IDs, satisfied by a pass matching
		// *any* one of them (the same OR semantics Iteras' own check, and
		// iteras_user_has_access() below, already use) — so this doesn't
		// require every ID on the post to allow "user" level, only that the
		// visitor's pass actually matches one that does.
		if ( ! iteras_user_has_access( $paywall_ids ) ) {
			return $paywall_ids;
		}

		// This visitor is only seeing the full post because of their own
		// "user"-level pass — the response is never safe to cache, since
		// the exact same cached copy would then be served in full to every
		// other visitor too, including ones with no pass at all. See the
		// file docblock above for why Iteras' own cache-prevention can't be
		// relied on here.
		if ( ! empty( $settings['paywall_server_side_validation'] ) ) {
			gopublish_iteras_emit_no_cache_signals();
		}

		return false;
	}
}
add_filter( 'iteras_override_content_paywall', 'gopublish_iteras_user_level_override_paywall', 10, 4 );

if ( ! function_exists( 'gopublish_iteras_emit_no_cache_signals' ) ) {
	/**
	 * Replicates Iteras::emit_no_cache_signals() (iteras-public.php) — a
	 * private method, so it can't be called directly. Same header/constant
	 * set, for the same reason: make sure nothing downstream (a page cache
	 * plugin, a reverse proxy, a CDN) stores this specific, visitor-specific
	 * response.
	 */
	function gopublish_iteras_emit_no_cache_signals() {
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		if ( ! defined( 'DONOTCACHEOBJECT' ) ) {
			define( 'DONOTCACHEOBJECT', true );
		}
		if ( ! defined( 'DONOTCACHEDB' ) ) {
			define( 'DONOTCACHEDB', true );
		}

		do_action( 'litespeed_control_set_nocache', 'gopublish-iteras-block: user-level paywall override' );
		add_filter( 'rocket_override_donotcachepage', '__return_true' );

		nocache_headers();

		if ( ! headers_sent() ) {
			header( 'Cache-Control: private, no-cache, no-store, max-age=0, must-revalidate', true );
			header( 'Pragma: no-cache', true );
			header( 'Expires: Wed, 11 Jan 1984 05:00:00 GMT', true );
			header( 'Vary: Cookie', false );
			header( 'X-LiteSpeed-Cache-Control: no-cache', true );
			header( 'X-Accel-Expires: 0', true );
		}
	}
}
