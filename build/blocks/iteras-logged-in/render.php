<?php
/**
 * Iteras Login Status block — server-side render.
 *
 * A block equivalent of Iteras' own [iteras-if-logged-in] / [iteras-if-not-logged-in]
 * shortcodes (Iteras::content_by_login_status(), iteras/public/iteras-public.php),
 * for use in places shortcodes aren't usable (template parts, synced patterns
 * used outside the_content, etc). "Logged in" here means the visitor holds a
 * valid Iteras subscription pass (iteraspass cookie), not a WordPress account
 * login — matching the shortcodes' own terminology.
 *
 * Deliberately reimplements iteras_user_has_access()'s "editors always pass"
 * and "no server-side validation" checks explicitly, in the same order Iteras'
 * own content_by_login_status() does, rather than delegating to
 * iteras_user_has_access() directly: that helper's internal editor bypass
 * only makes sense for a single always-show polarity, but here it must apply
 * identically to *both* the "logged in" and "not logged in" branches (an
 * editor always sees the content either way, exactly like the shortcodes).
 *
 * Deliberately unscoped — always checks for a pass against *any* paywall
 * configured in Iteras, with no per-paywall restriction. An earlier version
 * let editors optionally check specific paywalls, but combined with the
 * "not logged in" mode that produced a negated-OR ("shown to everyone except
 * visitors who qualify for at least one of the checked paywalls") that's
 * genuinely hard to reason about, and duplicated what the Iteras Paywall
 * block already does for the "logged in" case. Use that block instead for
 * paywall-specific gating; this block only answers "is this visitor a
 * subscriber of any kind, yes or no."
 *
 * Caching note: the three branches above (Iteras inactive, server-side
 * validation off, current user is a WordPress editor) are all safe to
 * bake into a cached page as-is — the first two are the same for every
 * visitor regardless of their Iteras pass, and the third never actually
 * reaches a cached response in practice, since a logged-in WordPress
 * session already forces a cache bypass at the hosting layer independent
 * of anything Iteras-specific (SiteGround's Dynamic Cache excludes
 * wordpress_logged_in_* by default, for example).
 *
 * The real per-visitor case below does NOT decide server-side whether to
 * show the content — it always renders it, hidden by default, tagged with
 * data-iteras-show-when, and leaves the actual show/hide decision to
 * view.js reading the visitor's own iteraspass cookie client-side via
 * Iteras.paywallPass(). This is what lets a page using this block stay
 * fully cacheable: the HTML is identical for every visitor, and only a
 * small client-side step personalizes it afterwards. See view.js for why
 * that's an intentionally weaker check than this file's own
 * iteras_user_has_access() (no HMAC verification is possible client-side,
 * since the signing key is a server-only secret) — acceptable here since
 * this block only ever controls a status *display*, never actual
 * paywalled content access, which stays fully server-side enforced
 * regardless of what this block shows.
 *
 * The hidden-by-default attribute/style are set on the block's *own*
 * wrapper (save.js's useBlockProps.save() div, already part of $content),
 * via WP_HTML_Tag_Processor — not a new div wrapped around it. An earlier
 * version did wrap $content in a new outer div, which broke this block's
 * "constrained" layout support: that layout type's CSS targets *direct
 * children* of the block's own wrapper to apply max-width/alignwide/
 * alignfull rules, so inserting an extra DOM level meant the actual inner
 * blocks were no longer direct children of it, and all fell back to the
 * default constrained width regardless of their own alignment.
 *
 * Variables available:
 *   $attributes (array)    Block attributes: 'showWhen'.
 *   $content    (string)   Rendered inner-blocks HTML (from save()).
 *   $block      (WP_Block) Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// When Iteras is not active, fail open so content remains visible.
if ( ! class_exists( 'Iteras' ) ) {
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $content;
	return;
}

$iteras = Iteras::get_instance();

// Mirrors content_by_login_status()'s own fail-open behaviour: without
// server-side validation there's no way to check the pass, so show the
// content rather than hiding everything.
if ( empty( $iteras->settings['paywall_server_side_validation'] ) ) {
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $content;
	return;
}

// Editors always see the content, in either mode below — consistent with
// the shortcodes' own current_user_can( 'edit_pages' ) bypass.
if ( current_user_can( 'edit_pages' ) ) {
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $content;
	return;
}

$show_when = $attributes['showWhen'] ?? 'logged-in';

$processor = new WP_HTML_Tag_Processor( $content );

if ( $processor->next_tag() ) {
	$processor->set_attribute( 'data-iteras-show-when', $show_when );

	$existing_style = $processor->get_attribute( 'style' );
	$processor->set_attribute(
		'style',
		( $existing_style ? rtrim( (string) $existing_style, ';' ) . ';' : '' ) . 'display:none'
	);
}

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo $processor->get_updated_html();
