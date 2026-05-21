<?php
/**
 * Iteras Paywall block — server-side render.
 *
 * Variables available:
 *   $attributes (array)    Block attributes, including 'paywallIds'.
 *   $content    (string)   Rendered inner-blocks HTML (from save()).
 *   $block      (WP_Block) Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$paywall_ids = ! empty( $attributes['paywallIds'] ) ? $attributes['paywallIds'] : [];

// When Iteras is not active, fail open so content remains visible.
if ( ! class_exists( 'Iteras' ) ) {
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $content;
	return;
}

$has_access = ! empty( $paywall_ids )
	? iteras_user_has_access( $paywall_ids )
	: iteras_user_has_access_for_post();

if ( ! $has_access ) {
	return;
}

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo $content;
