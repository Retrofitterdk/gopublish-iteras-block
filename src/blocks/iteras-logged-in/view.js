/**
 * Frontend-only script for the Iteras Login Status block. Reveals whichever
 * instance(s) match the current visitor's actual login state, read directly
 * from their iteraspass cookie in the browser — via Iteras' own client-side
 * Iteras.paywallPass() (https://app.iteras.dk/api/docs/js/paywall/) — rather
 * than deciding server-side. This is what lets a page using this block stay
 * fully cacheable: render.php always outputs every instance's content,
 * hidden by default (see its own comments for the full reasoning), and this
 * script is the only thing that ever reveals it.
 *
 * Iteras.paywallPass() parses the cookie and returns its expiry and
 * customer ID along with whether a pass exists at all — a stronger check
 * than Iteras.signedIn() (which is bare `!!readCookie("iteraspass")`, with
 * no expiry check at all). Still weaker than this plugin's own PHP-side
 * iteras_user_has_access(): no HMAC signature verification happens here,
 * since the signing key is a server-only secret and can't be checked from
 * the browser. That's an inherent limit of any client-side check, not
 * something this script can close — acceptable here because this block only
 * ever controls a status *display*, never actual paywalled content access
 * (which stays fully, correctly enforced server-side regardless of what
 * this block shows).
 *
 * Also corrects the "Iteras Customer ID" paragraph variation's text
 * (src/block-variations/customer-id.js) wherever one appears on the page —
 * it's resolved server-side via a Block Binding
 * (gopublish-iteras-block/customer-id in inc/block-bindings.php), which
 * would otherwise freeze a stale customer ID into a cached page exactly
 * like this block's own visibility used to. Block variations can't have
 * their own viewScript, so this piggybacks on this block's — consistent
 * with this plugin's own documented convention that the Customer ID
 * variation should always be wrapped inside an Iteras Login Status block
 * anyway (see that file's own comments).
 */
document.addEventListener( 'DOMContentLoaded', function () {
	if ( typeof window.Iteras === 'undefined' ) {
		return; // Iteras' client library didn't load — leave everything hidden, the safe default.
	}

	var pass = null;
	if ( typeof Iteras.paywallPass === 'function' ) {
		pass = Iteras.paywallPass();
	}

	var isLoggedIn = !! pass && pass.expires instanceof Date && pass.expires.getTime() > Date.now();

	// Fall back to the weaker, presence-only check only if paywallPass()
	// itself isn't available (a future Iteras.js change, not expected today).
	if ( typeof Iteras.paywallPass !== 'function' && typeof Iteras.signedIn === 'function' ) {
		isLoggedIn = Iteras.signedIn();
	}

	document.querySelectorAll( '[data-iteras-show-when]' ).forEach( function ( el ) {
		var showWhen = el.getAttribute( 'data-iteras-show-when' );
		var shouldShow = ( showWhen === 'not-logged-in' ) ? ! isLoggedIn : isLoggedIn;

		if ( shouldShow ) {
			el.style.display = '';
		}
	} );

	document.querySelectorAll( '.iteras-customer-id' ).forEach( function ( el ) {
		el.textContent = ( pass && pass.customer ) ? pass.customer : '';
	} );
} );
