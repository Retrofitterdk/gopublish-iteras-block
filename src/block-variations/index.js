/**
 * Registers this plugin's block variations. Loaded independently of any
 * individual block — see block.json in this directory (a build-tooling
 * marker only, never registered as a real block type) and
 * gopublish_iteras_enqueue_block_variations() in gopublish-iteras-block.php,
 * which enqueues the compiled output of this file directly on
 * enqueue_block_editor_assets.
 *
 * Add further registerBlockVariation() calls here (or import additional
 * files from here) as more variations are needed — they all share this one
 * independent load path rather than being tacked onto an unrelated block.
 */
import './paywall-label';
import './customer-id';
