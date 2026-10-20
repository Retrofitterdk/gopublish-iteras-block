/**
 * "Iteras Login Button" — a variation of core/button, pre-bound to the
 * gopublish-iteras-block/login-url Block Bindings source (registered in
 * inc/block-bindings.php). Links to Iteras' own login page (the "Subscribe
 * landing page" setting) with a return-to parameter appended, so the
 * visitor lands back on whichever page they came from once logged in —
 * the same thing Iteras' own [iteras-return-to-page] shortcode does, but
 * usable as an actual link anywhere the block editor is used, including
 * inside synced patterns used as paywall call-to-action content, where a
 * raw shortcode can't become a button's href through the visual editor.
 *
 * Only the `url` attribute is bound — `text` is left completely free, so
 * editors can label the button however they like ("Log in", "Already a
 * subscriber?", etc.) without the "bare value vs. full sentence" tradeoff
 * the paragraph-based variations (paywall-label, customer-id) have to deal
 * with, since the dynamic and static parts are two separate attributes
 * here rather than sharing one `content` string.
 */
import { registerBlockVariation } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

registerBlockVariation( 'core/button', {
	name: 'iteras-login-button',
	title: __( 'Iteras Login Button', 'gopublish-iteras-block' ),
	description: __(
		'A button linking to the Iteras login page, with a return-to parameter appended so visitors land back here after logging in. Edit the button text freely — only the link is bound.',
		'gopublish-iteras-block'
	),
	icon: 'unlock',
	keywords: [ 'iteras', 'login', 'subscribe', 'return', 'redirect' ],
	attributes: {
		text: __( 'Log in', 'gopublish-iteras-block' ),
		metadata: {
			bindings: {
				url: {
					source: 'gopublish-iteras-block/login-url',
				},
			},
		},
	},
	scope: [ 'inserter', 'transform' ],
	isActive: ( blockAttributes ) =>
		blockAttributes?.metadata?.bindings?.url?.source ===
		'gopublish-iteras-block/login-url',
} );
