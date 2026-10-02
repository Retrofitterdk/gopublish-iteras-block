/**
 * "Iteras Paywall Label" — a variation of core/paragraph, pre-bound to the
 * gopublish-iteras-block/paywall-label Block Bindings source (registered in
 * inc/block-bindings.php). Lets an editor drop the same dynamic per-paywall
 * eyebrow text used in the site's paywalled-post templates into any real
 * post/page content, a widget, or anywhere else the block editor is used —
 * not just template patterns wired up in PHP.
 *
 * Defaults to the "paywalled" className so it automatically inherits the
 * site's existing CSS for this element, including the rule that hides it
 * for readers who already have access to the post
 * (".has-access .paywalled { display: none }", see the theme's
 * src/scss/pages/post.scss) — the binding itself always resolves to some
 * text regardless of access status, exactly like the original static label;
 * hiding it for unlocked readers is handled by that CSS, not by this
 * variation or the binding source.
 */
import { registerBlockVariation } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

registerBlockVariation( 'core/paragraph', {
	name: 'iteras-paywall-label',
	title: __( 'Iteras Paywall Label', 'gopublish-iteras-block' ),
	description: __(
		'A paragraph bound to the current post’s resolved paywall label (e.g. "Abonnement", "Erhverv"), the same dynamic text shown on paywalled-post templates. Automatically hides itself for readers who already have access.',
		'gopublish-iteras-block'
	),
	icon: 'tag',
	keywords: [ 'iteras', 'paywall', 'subscription', 'abonnement', 'label' ],
	attributes: {
		className: 'paywalled',
		metadata: {
			bindings: {
				content: {
					source: 'gopublish-iteras-block/paywall-label',
				},
			},
		},
	},
	scope: [ 'inserter', 'transform' ],
	isActive: ( blockAttributes ) =>
		blockAttributes?.metadata?.bindings?.content?.source ===
		'gopublish-iteras-block/paywall-label',
} );
