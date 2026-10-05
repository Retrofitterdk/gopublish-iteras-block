/**
 * Frontend-only script for the Iteras Logout Button block. Wires up
 * Iteras' own client-side Iteras.logoutFromWall() JS API
 * (https://app.iteras.dk/api/docs/js/paywall/) on click.
 *
 * Always passes an explicit `redirect`, never calls logoutFromWall() with
 * no options. Iteras' own docs say omitting `redirect` reloads the current
 * page by default, but confirmed in practice (real test, a page with no
 * active Iteras.wall() instance — e.g. a plain login page) that the
 * cookie does clear but the promised reload doesn't actually fire, with no
 * error and no visible feedback. Passing an explicit `redirect` was
 * confirmed reliable in the same test. So: use the configured link if one
 * was added via the toolbar; otherwise fall back to the current page's own
 * URL, which gives the same "stay here, just refreshed" behaviour the
 * default was supposed to provide, without relying on it.
 *
 * The `typeof window.Iteras === 'undefined'` guard below is a deliberate
 * runtime check, not a build-time script dependency on Iteras' own
 * `iteras-api-script` handle (enqueued by the Iteras plugin itself, see
 * enqueue_scripts() in iteras/public/iteras-public.php) — that handle
 * isn't something wp-scripts can detect automatically for this file's
 * generated index.asset.php, and in practice there's no real race
 * condition to guard against here: the check only matters at the moment of
 * *click*, and a visitor can't click this button before the page — and
 * therefore every script on it, including Iteras' externally-hosted one —
 * has finished loading.
 */
document.addEventListener( 'DOMContentLoaded', function () {
	document
		.querySelectorAll(
			'.wp-block-gopublish-iteras-block-iteras-logout'
		)
		.forEach( function ( button ) {
			button.addEventListener( 'click', function () {
				if (
					typeof window.Iteras === 'undefined' ||
					typeof window.Iteras.logoutFromWall !== 'function'
				) {
					return;
				}

				var redirect = button.dataset.redirect || window.location.href;

				window.Iteras.logoutFromWall( { redirect: redirect } );
			} );
		} );
} );
