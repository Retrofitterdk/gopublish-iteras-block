/**
 * "Iteras Customer ID" — a variation of core/paragraph, pre-bound to the
 * gopublish-iteras-block/customer-id Block Bindings source (registered in
 * inc/block-bindings.php). Displays the current visitor's Iteras customer
 * ID, read from their iteraspass cookie — no API call needed.
 *
 * Deliberately resolves to the bare ID only (e.g. "251848"), not a full
 * sentence — consistent with the Iteras Paywall Label variation. It also
 * resolves to an empty string when the visitor isn't logged in with a
 * valid pass, with no fallback text of its own. To build something like
 * "You're logged in with customer number 251848", wrap this block together
 * with your own static text paragraph inside an Iteras Login Status block
 * (set to "logged in") — that hides the whole group as one unit for
 * logged-out visitors, rather than leaving orphaned static text behind
 * when this block alone resolves empty.
 */
import { registerBlockVariation } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

registerBlockVariation( 'core/paragraph', {
	name: 'iteras-customer-id',
	title: __( 'Iteras Customer ID', 'gopublish-iteras-block' ),
	description: __(
		'A paragraph bound to the current visitor’s Iteras customer ID. Empty when not logged in with a valid pass — wrap it with your own text inside an Iteras Login Status block (set to "logged in") so the whole thing shows/hides together.',
		'gopublish-iteras-block'
	),
	icon: 'id',
	keywords: [ 'iteras', 'customer', 'subscriber', 'account', 'id', 'number' ],
	attributes: {
		className: 'iteras-customer-id',
		metadata: {
			bindings: {
				content: {
					source: 'gopublish-iteras-block/customer-id',
				},
			},
		},
	},
	scope: [ 'inserter', 'transform' ],
	isActive: ( blockAttributes ) =>
		blockAttributes?.metadata?.bindings?.content?.source ===
		'gopublish-iteras-block/customer-id',
} );
