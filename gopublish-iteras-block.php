<?php
/**
 * Plugin Name:       Go:Publish Iteras Block
 * Description:       A block that reveals or hides inner content based on Iteras subscription access.
 * Requires at least: 6.1
 * Requires PHP:      7.4
 * Version:           0.7.0
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
			$paywalls = Iteras::get_instance()->settings['paywalls'] ?? [];
		}
		wp_localize_script(
			'gopublish-iteras-block-iteras-paywall-editor-script',
			'gopublishIterasBlockData',
			[ 'paywalls' => array_values( $paywalls ) ]
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