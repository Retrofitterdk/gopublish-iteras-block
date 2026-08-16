<?php
/**
 * Fixes Iteras's automatic paywall wrapper for block themes.
 *
 * When Iteras's "auto" integration method is active, Iteras wraps the_content
 * in <div class="iteras-content-wrapper ..."> (see Iteras::potentially_paywall_content()).
 * In a block theme that div sits between core/post-content's own wrapper
 * (which carries the theme's layout class, e.g. "is-layout-constrained") and
 * the actual post blocks. theme.json layout rules (blockGap, alignwide,
 * alignfull, contentSize/wideSize) are applied via selectors that assume
 * those blocks are direct children of the layout wrapper, so the extra,
 * class-less Iteras div silently breaks spacing/alignment.
 *
 * Iteras is a plugin-repo plugin — edits to it would be wiped out on update —
 * so instead of patching iteras-public.php directly, we mirror the enclosing
 * core/post-content block's layout class onto Iteras's wrapper from here.
 *
 * @package GopublishIterasBlock
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var string[] $gopublish_iteras_post_content_layout_stack */
$GLOBALS['gopublish_iteras_post_content_layout_stack'] = array();

/**
 * Maps a block layout "type" attribute to the global-styles class it renders with.
 *
 * @param string $type Layout type, e.g. "constrained", "flow", "default".
 * @return string
 */
function gopublish_iteras_layout_type_to_class( string $type ): string {
	$layout_classes = array(
		'constrained' => 'is-layout-constrained',
		'flow'        => 'is-layout-flow',
		'default'     => 'is-layout-flow',
		'flex'        => 'is-layout-flex',
		'grid'        => 'is-layout-grid',
	);

	return $layout_classes[ $type ] ?? 'is-layout-flow';
}

/**
 * Pushes the layout class of a core/post-content block onto a stack just before
 * it renders (and, in turn, before it triggers the_content filters internally).
 *
 * @param mixed $pre_render
 * @param array $parsed_block
 * @return mixed Unmodified — this only observes the block, it doesn't render it.
 */
function gopublish_iteras_push_post_content_layout( $pre_render, $parsed_block ) {
	if ( ( $parsed_block['blockName'] ?? '' ) === 'core/post-content' ) {
		global $gopublish_iteras_post_content_layout_stack;
		$gopublish_iteras_post_content_layout_stack[] = gopublish_iteras_layout_type_to_class(
			$parsed_block['attrs']['layout']['type'] ?? 'default'
		);
	}

	return $pre_render;
}
add_filter( 'pre_render_block', 'gopublish_iteras_push_post_content_layout', 10, 2 );

/**
 * Pops the layout stack once a core/post-content block has finished rendering.
 *
 * @param string $content
 * @return string Unmodified.
 */
function gopublish_iteras_pop_post_content_layout( string $content ): string {
	global $gopublish_iteras_post_content_layout_stack;
	array_pop( $gopublish_iteras_post_content_layout_stack );

	return $content;
}
add_filter( 'render_block_core/post-content', 'gopublish_iteras_pop_post_content_layout' );

/**
 * Adds the current post-content layout class to Iteras's auto-paywall wrapper div,
 * so it stays inside the theme's layout boundary instead of breaking out of it.
 *
 * The wrapper also gets "alignfull": it is itself a direct child of the outer
 * `.wp-block-post-content.is-layout-constrained`, so without alignfull it would
 * inherit that selector's `max-width: contentSize`, squeezing everything inside
 * it — including any alignwide/alignfull blocks — down to content width. Adding
 * alignfull exempts the wrapper from that outer constraint, while its own mirrored
 * layout class re-establishes the correct contentSize/wideSize for its children.
 *
 * Runs after Iteras's own the_content filter (priority 99), which is what
 * produces the `iteras-content-wrapper` div in the first place.
 *
 * @param string $content
 * @return string
 */
function gopublish_iteras_fix_wrapper_layout_class( string $content ): string {
	global $gopublish_iteras_post_content_layout_stack;

	if ( empty( $gopublish_iteras_post_content_layout_stack ) ) {
		return $content;
	}

	$layout_class = end( $gopublish_iteras_post_content_layout_stack );

	return str_replace(
		'class="iteras-content-wrapper ',
		'class="iteras-content-wrapper alignfull has-global-padding' . $layout_class . ' ',
		$content
	);
}
add_filter( 'the_content', 'gopublish_iteras_fix_wrapper_layout_class', 100 );
