/**
 * Frontend-only script correcting has-access/no-access classes client-side
 * for posts rendered as teasers inside a query loop (the homepage, an
 * archive) on an otherwise-cacheable page.
 *
 * This file is only the logic — identical on every page, so a real
 * browser-cacheable asset rather than inlined into the HTML. The data it
 * reads (window.gopublishIterasPostAccess) is attached via
 * wp_add_inline_script() from gopublish_iteras_emit_post_access_correction()
 * in gopublish-iteras-block.php, since which posts actually rendered this
 * request (and their paywall IDs) is only known at the very end of it —
 * that function also skips enqueuing this script at all when no such post
 * was rendered.
 *
 * Reads the visitor's own iteraspass cookie client-side via Iteras'
 * Iteras.paywallPass() (https://app.iteras.dk/api/docs/js/paywall/), which
 * exposes the full per-paywall access-level map, not just presence —
 * replicating the same "sub, or user when that paywall allows it" check
 * iteras_user_has_access() does server-side
 * (iteras_paywall_allows_user_level(), see inc/admin-settings.php's
 * "Registered users" checkbox).
 */
( function () {
	function run() {
		var data = window.gopublishIterasPostAccess;
		if ( ! data || typeof window.Iteras === 'undefined' || typeof Iteras.paywallPass !== 'function' ) {
			return;
		}

		var postPaywalls = data.postPaywalls || {};
		var userLevelAllowed = data.userLevelAllowed || {};

		var pass = Iteras.paywallPass();
		var passValid = !! pass && pass.expires instanceof Date && pass.expires.getTime() > Date.now();
		var passPaywalls = ( passValid && pass.paywalls ) ? pass.paywalls : {};

		document.querySelectorAll( '.has-access, .no-access' ).forEach( function ( el ) {
			var postId = null;
			el.classList.forEach( function ( c ) {
				var match = /^post-(\d+)$/.exec( c );
				if ( match ) {
					postId = match[ 1 ];
				}
			} );

			var paywallIds = postId ? postPaywalls[ postId ] : null;
			if ( ! paywallIds ) {
				return; // Not a post we recorded — its server-rendered value is already correct for everyone.
			}

			var hasAccess = false;
			for ( var i = 0; i < paywallIds.length; i++ ) {
				var level = passPaywalls[ paywallIds[ i ] ];
				if ( level === 'sub' || ( level === 'user' && userLevelAllowed[ paywallIds[ i ] ] ) ) {
					hasAccess = true;
					break;
				}
			}

			el.classList.toggle( 'has-access', hasAccess );
			el.classList.toggle( 'no-access', ! hasAccess );
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', run );
	} else {
		run();
	}
} )();
