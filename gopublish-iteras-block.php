<?php
/**
 * Plugin Name:       Go:Publish Iteras Block
 * Description:       A block that reveals or hides inner content based on Iteras subscription access.
 * Requires at least: 6.1
 * Requires PHP:      7.4
 * Version:           1.0.0
 * Author:            Retrofitter
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       gopublish-iteras-block
 * Requires Plugins:  iteras
 *
 * @package GopublishIterasBlock
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once plugin_dir_path( __FILE__ ) . 'inc/functions-iteras.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/post-content-layout-fix.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/shortcode-paywall-cta.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/block-bindings.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/content-paywall-override.php';

if ( is_admin() ) {
	require_once plugin_dir_path( __FILE__ ) . 'inc/admin-settings.php';
}

if ( ! function_exists( 'gopublish_iteras_block_init' ) ) {
	function gopublish_iteras_block_init() {
		// Register the pre-built PHP manifest so WordPress can look up block
		// metadata from an in-memory array instead of parsing block.json on
		// every request. Introduced in WordPress 6.7; guarded for earlier versions.
		if ( function_exists( 'wp_register_block_metadata_collection' ) ) {
			wp_register_block_metadata_collection(
				__DIR__ . '/build/blocks',
				__DIR__ . '/build/blocks-manifest.php'
			);
		}

		register_block_type( __DIR__ . '/build/blocks/iteras-paywall' );
		register_block_type( __DIR__ . '/build/blocks/iteras-logged-in' );
		register_block_type( __DIR__ . '/build/blocks/iteras-logout' );
		register_block_type( __DIR__ . '/build/blocks/iteras-paywall-login' );

		wp_set_script_translations(
			'gopublish-iteras-block-iteras-paywall-editor-script',
			'gopublish-iteras-block',
			plugin_dir_path( __FILE__ ) . 'languages'
		);

		wp_set_script_translations(
			'gopublish-iteras-block-iteras-logged-in-editor-script',
			'gopublish-iteras-block',
			plugin_dir_path( __FILE__ ) . 'languages'
		);

		wp_set_script_translations(
			'gopublish-iteras-block-iteras-logout-editor-script',
			'gopublish-iteras-block',
			plugin_dir_path( __FILE__ ) . 'languages'
		);

		wp_set_script_translations(
			'gopublish-iteras-block-iteras-paywall-login-editor-script',
			'gopublish-iteras-block',
			plugin_dir_path( __FILE__ ) . 'languages'
		);
	}
}
add_action( 'init', 'gopublish_iteras_block_init' );

if ( ! function_exists( 'gopublish_iteras_enqueue_block_variations' ) ) {
	/**
	 * Enqueues this plugin's block variations independently of any single
	 * block's own registration — they're a separate concern (they register
	 * a variation of core/paragraph, not anything to do with the Iteras
	 * Paywall or Iteras Login Status blocks), so they get their own load
	 * path rather than riding along with one of those blocks' scripts.
	 *
	 * src/block-variations/block.json is a build-tooling marker only (it
	 * gives wp-scripts an editorScript entry point to bundle from) and is
	 * never passed to register_block_type() — the compiled script is
	 * enqueued directly here instead, using the same index.asset.php
	 * dependency/version manifest register_block_type() would otherwise
	 * read automatically.
	 */
	function gopublish_iteras_enqueue_block_variations() {
		$asset_file = __DIR__ . '/build/block-variations/index.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$asset = require $asset_file;

		wp_enqueue_script(
			'gopublish-iteras-block-block-variations',
			plugins_url( 'build/block-variations/index.js', __FILE__ ),
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_set_script_translations(
			'gopublish-iteras-block-block-variations',
			'gopublish-iteras-block',
			plugin_dir_path( __FILE__ ) . 'languages'
		);
	}
}
add_action( 'enqueue_block_editor_assets', 'gopublish_iteras_enqueue_block_variations' );

if ( ! function_exists( 'gopublish_iteras_block_editor_data' ) ) {
	function gopublish_iteras_block_editor_data() {
		$paywalls = [];
		if ( class_exists( 'Iteras' ) ) {
			// Iteras::load_settings() runs on init; by enqueue_block_editor_assets
			// all init callbacks have completed and settings are fully populated.
			$paywalls = array_values( Iteras::get_instance()->settings['paywalls'] ?? [] );
		}

		// Merge in each paywall's "Registered users" setting (see
		// inc/admin-settings.php) so the Iteras Paywall Login block's editor
		// can warn when "Registered users" access is picked for a paywall
		// that doesn't actually grant it — see iteras_paywall_allows_user_level().
		foreach ( $paywalls as &$paywall ) {
			$paywall['allow_user_level'] = function_exists( 'iteras_paywall_allows_user_level' )
				? iteras_paywall_allows_user_level( $paywall['paywall_id'] ?? '' )
				: false;
		}
		unset( $paywall );

		wp_localize_script(
			'gopublish-iteras-block-iteras-paywall-editor-script',
			'gopublishIterasBlockData',
			[ 'paywalls' => $paywalls ]
		);
	}
}
add_action( 'enqueue_block_editor_assets', 'gopublish_iteras_block_editor_data' );

function body_class_for_paywall_access( $classes ) {
	if ( is_singular() ) {
		if ( iteras_user_has_access_for_post() ) {
			$classes[] = 'has-access';			
		} else {
			$classes[] = 'no-access';
		}
	}
	return $classes;
}
add_filter( 'body_class', 'body_class_for_paywall_access' );

function post_class_for_paywall_access( $classes, $class, $post_id) {
	// if ( is_singular() ) {
		if ( iteras_user_has_access_for_post($post_id) ) {
			$classes[] = 'has-access';			
		} else {
			$classes[] = 'no-access';
		}
	// }
	return $classes;
}
add_filter( 'post_class', 'post_class_for_paywall_access', 10, 3 );

/**
 * Moved here from the Olfi theme's inc/custom.php — this is a plain Iteras
 * fact about the post (is it gated at all), not a theme/presentation
 * concern, and belongs with iteras_is_post_paywalled() rather than
 * duplicated per-theme. Distinct from body_class_for_paywall_access()
 * above: "is-paywalled" is the same for every visitor (a post-level fact,
 * e.g. for showing a "Premium" badge to everyone), while "has-access" is
 * per-visitor (can *this* visitor actually read it).
 *
 * Skips the front page specifically (not just is_singular(), which a
 * static front page still satisfies) — the homepage itself was never
 * meant to be tagged as paywalled/not-paywalled content.
 */
function body_class_for_paywalled_content( $classes ) {
	if ( is_front_page() || ! is_singular() ) {
		return $classes;
	}

	$classes[] = iteras_is_post_paywalled( get_the_ID() ) ? 'is-paywalled' : 'is-not-paywalled';

	return $classes;
}
add_filter( 'body_class', 'body_class_for_paywalled_content' );

/**
 * Moved here from the Olfi theme's inc/custom.php — see
 * body_class_for_paywalled_content() above for why this lives here instead,
 * and how it differs from post_class_for_paywall_access().
 */
function post_class_for_paywalled_content( $classes, $class, $post_id ) {
	$classes[] = iteras_is_post_paywalled( $post_id ) ? 'is-paywalled' : 'is-not-paywalled';

	return $classes;
}
add_filter( 'post_class', 'post_class_for_paywalled_content', 10, 3 );

/**
 * A third, independent class pair: does this visitor hold a valid Iteras
 * pass for *anything at all* — the same question the Iteras Login Status
 * block answers (iteras_user_has_access() with no paywall restriction) —
 * deliberately not "does this visitor have access to this post"
 * (has-access/no-access above) or "is this post gated at all"
 * (is-paywalled/is-not-paywalled above). Those are about a specific post;
 * this is purely about the visitor, so no is_singular() guard and no
 * post_class sibling — it would be the same value on every post in a
 * query loop, since it doesn't vary per post.
 *
 * Prefixed with "iteras-" (unlike the other two pairs) specifically
 * because "logged in" on its own is ambiguous with a WordPress account
 * login, which this has nothing to do with — current_user_can('edit_pages')
 * editors are still unconditionally included via iteras_user_has_access()'s
 * own bypass, consistent with every other access check in this plugin.
 */
function body_class_for_login_status( $classes ) {
	$classes[] = iteras_user_has_access() ? 'iteras-logged-in' : 'iteras-not-logged-in';

	return $classes;
}
add_filter( 'body_class', 'body_class_for_login_status' );