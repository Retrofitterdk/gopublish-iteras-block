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
		return iteras_user_has_access( $paywall_ids ) ? false : $paywall_ids;
	}
}
add_filter( 'iteras_override_content_paywall', 'gopublish_iteras_user_level_override_paywall', 10, 4 );
