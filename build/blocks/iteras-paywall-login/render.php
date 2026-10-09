<?php
/**
 * Iteras Paywall Login block — server-side render.
 *
 * Deliberately does NOT call Iteras::paywall_shortcode() (the method
 * backing [iteras-paywall-login]) directly, unlike how this plugin reuses
 * Iteras::return_to_page_shortcode() elsewhere (see inc/block-bindings.php)
 * — that method has a real bug that would make the paywall picker below
 * silently do nothing:
 *
 *   if (!empty($attrs) && in_array('paywallid', $attrs)) {
 *     $attrs['paywallid'] = $this->parse_paywall_ids($attrs['paywallid']);
 *   }
 *
 * in_array('paywallid', $attrs) checks whether the STRING "paywallid" is a
 * VALUE anywhere in $attrs — but a shortcode's 'paywallid' attribute is a
 * KEY, never a value (it should be array_key_exists()). Confirmed directly
 * (studio wp eval): passing ['paywallid' => 'abc123'] to paywall_shortcode()
 * produces the exact same output as passing no paywallid at all — it
 * always falls through to $this->get_paywall_ids() (every configured
 * paywall). The raw [iteras-paywall-login paywallid="…"] shortcode has the
 * same bug, for the same reason. This is a bug in Iteras' own code, not
 * something introduced here, and (per this plugin's own rule) not
 * something to patch in Iteras' files directly.
 *
 * So this builds the same <script>document.write(Iteras.paywalliframe(…))
 * output Iteras' own shortcode produces, but with that one line fixed —
 * reusing Iteras::combine_attributes() (a generic, bug-free helper) for the
 * rest, rather than reimplementing it.
 *
 * Variables available:
 *   $attributes (array) Block attributes: 'paywallIds', 'accessLevel'.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Iteras' ) ) {
	return;
}

$iteras = Iteras::get_instance();

$paywall_ids = ! empty( $attributes['paywallIds'] )
	? $attributes['paywallIds']
	: $iteras->get_paywall_ids();

if ( empty( $paywall_ids ) ) {
	// Mirrors Iteras' own paywall_shortcode() message for this same case.
	echo '<!-- ITERAS paywall enabled but not configured properly: missing paywalls, sync in settings -->';
	return;
}

$extra_attrs = [];
if ( ! empty( $attributes['accessLevel'] ) ) {
	$extra_attrs['access'] = $attributes['accessLevel'];
}

printf(
	'<script>document.write(Iteras.paywalliframe({"profile": %s, "paywallid": %s%s}));</script>',
	wp_json_encode( $iteras->settings['profile_name'] ?? '' ),
	wp_json_encode( $paywall_ids ),
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- combine_attributes() already JSON-encodes each value.
	$iteras->combine_attributes( $extra_attrs )
);
