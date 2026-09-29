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

// No paywall ID given falls back to *all* paywalls configured in Iteras
// (iteras_user_has_access()'s own default) — "does this visitor hold a
// valid pass for anything", matching the shortcodes' own default when no
// paywallid attribute is given.
$is_logged_in = iteras_user_has_access();
$show         = ( 'not-logged-in' === $show_when ) ? ! $is_logged_in : $is_logged_in;

if ( ! $show ) {
	return;
}

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo $content;
