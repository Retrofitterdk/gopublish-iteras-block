<?php
/**
 * Iteras access-check helpers.
 *
 * Copied from Go:Publish Essentials so this plugin can function independently.
 * All functions are guarded with if ( ! function_exists() ) so they coexist
 * safely if Essentials is also active.
 *
 * @package GopublishIterasBlock
 * @since   0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns true if the current visitor has a valid Iteras subscription pass
 * for at least one of the given paywall IDs.
 *
 * Replicates the access check used by [iteras-if-logged-in paywallid="…"] so
 * it can be called from PHP templates. WordPress editors (edit_pages cap) are
 * always granted access, consistent with the shortcode's own behaviour.
 *
 * Usage:
 *   if ( iteras_user_has_access( 'abc123,def456' ) ) { ... }
 *   if ( iteras_user_has_access( [ 'abc123', 'def456' ] ) ) { ... }
 *   if ( iteras_user_has_access() ) { ... }  // uses plugin's configured paywalls
 *
 * @param string|string[] $paywall_ids Comma-separated string or array of paywall IDs.
 *                                     Falls back to the plugin's configured paywalls when empty.
 * @return bool
 */
if ( ! function_exists( 'iteras_user_has_access' ) ) {
	function iteras_user_has_access( $paywall_ids = '' ): bool {
		if ( ! class_exists( 'Iteras' ) ) {
			return false;
		}

		$iteras = Iteras::get_instance();

		if ( empty( $iteras->settings['paywall_server_side_validation'] ) ) {
			return false;
		}

		// Editors always get access — consistent with [iteras-if-logged-in] behaviour.
		if ( current_user_can( 'edit_pages' ) ) {
			return true;
		}

		// Normalise to an array, falling back to the plugin's configured paywalls.
		if ( is_string( $paywall_ids ) && $paywall_ids !== '' ) {
			$paywall_ids = $iteras->parse_paywall_ids( $paywall_ids );
		}
		if ( empty( $paywall_ids ) ) {
			$paywall_ids = $iteras->get_paywall_ids();
		}

		if ( ! isset( $_COOKIE['iteraspass'] ) ) {
			return false;
		}

		return _iteras_pass_authorized(
			sanitize_text_field( wp_unslash( $_COOKIE['iteraspass'] ) ),
			$paywall_ids,
			$iteras->settings['signing_key'] ?? ''
		);
	}
}

/**
 * Returns true if the current visitor has access to a specific post's paywalled content.
 *
 * Looks up the paywall IDs assigned to the post via its post meta and delegates
 * to iteras_user_has_access(). Falls back to the global $post when no ID is given.
 *
 * Usage:
 *   if ( iteras_user_has_access_for_post() ) { ... }        // current post in the loop
 *   if ( iteras_user_has_access_for_post( $post->ID ) ) { ... }
 *
 * @param int|null $post_id Post ID. Defaults to the current global post.
 * @return bool
 */
if ( ! function_exists( 'iteras_user_has_access_for_post' ) ) {
	function iteras_user_has_access_for_post( ?int $post_id = null ): bool {
		if ( ! class_exists( 'Iteras' ) ) {
			return false;
		}

		if ( $post_id === null ) {
			$post_id = get_the_ID();
		}

		if ( ! $post_id ) {
			return false;
		}

		$iteras      = Iteras::get_instance();
		$paywall_ids = get_post_meta( $post_id, Iteras::POST_META_KEY, true );

		// Backwards compatibility: old single-value meta ("user" or "sub") maps to all paywalls.
		if ( ! is_array( $paywall_ids ) && in_array( $paywall_ids, [ 'user', 'sub' ], true ) ) {
			$paywall_ids = $iteras->get_paywall_ids();
		}

		// No paywall assigned means the content is freely accessible.
		if ( empty( $paywall_ids ) ) {
			return true;
		}

		return iteras_user_has_access( $paywall_ids );
	}
}

/**
 * Validates an Iteras pass cookie value against a list of paywall IDs.
 *
 * This replicates Iteras::pass_authorized(), which is a private method and
 * cannot be called from outside the class. Prefixed with an underscore to
 * signal it is an internal helper; use iteras_user_has_access() instead.
 *
 * @param string   $pass        Raw value of the iteraspass cookie.
 * @param string[] $restriction List of paywall IDs to check against.
 * @param string   $signing_key HMAC signing key from plugin settings.
 * @return bool
 */
if ( ! function_exists( '_iteras_pass_authorized' ) ) {
	function _iteras_pass_authorized( string $pass, array $restriction, string $signing_key ): bool {
		$pos = strrpos( $pass, '/' );
		if ( $pos === false ) {
			return false;
		}

		$data      = substr( $pass, 0, $pos );
		$sig       = substr( $pass, $pos + 1 );
		$sig_parts = explode( ':', $sig, 2 );
		$algo_name = $sig_parts[0] ?? '';
		$hmac      = $sig_parts[1] ?? '';

		$algo = [ 'sha1' => 'sha1', 'sha256' => 'sha256' ][ $algo_name ] ?? null;
		if ( ! $algo ) {
			return false;
		}

		$computed_hmac = hash_hmac( $algo, $data, $signing_key );

		// Skip HMAC verification when no signing key is configured (mirrors original behaviour).
		if ( $computed_hmac !== false && $signing_key && ! hash_equals( $computed_hmac, $hmac ) ) {
			return false;
		}

		$parts  = explode( '|', $data );
		$expiry = strtotime( $parts[2] ?? '' );
		if ( $expiry === false || $expiry < time() ) {
			return false;
		}

		if ( count( $parts ) >= 2 ) {
			$access_levels    = explode( ',', $parts[0] );
			$pass_paywall_ids = explode( ',', $parts[1] );
			$access           = array_combine( $pass_paywall_ids, $access_levels );

			foreach ( $restriction as $r ) {
				if ( ( $access[ $r ] ?? null ) === 'sub' ) {
					return true;
				}
			}
		}

		return false;
	}
}
