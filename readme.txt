=== Go:Publish Iteras Block ===
Contributors:      retrofitter
Tags:              iteras, paywall, subscription, access control, block
Requires at least: 6.5
Tested up to:      7.0
Stable tag:        0.1.0
Requires PHP:      7.4
Requires Plugins:  iteras
License:           GPL-2.0-or-later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

A block that reveals or hides inner content based on Iteras subscription access.

== Description ==

Go:Publish Iteras Block adds an **Iteras Paywall** block to the WordPress block editor. Place any blocks inside it — text, images, tables, embeds — and they will only be rendered for visitors who hold a valid Iteras subscription pass. Everyone else sees nothing.

**How it works**

The block is a dynamic container (server-side rendered). When a page loads, `render.php` calls the Iteras access helpers to decide whether to output the inner blocks or return early. No JavaScript is required on the frontend; access is enforced entirely in PHP.

**Paywall selection**

By default the block checks the paywall IDs assigned to the current post via the Iteras plugin meta. Editors can override this in the block's sidebar panel by ticking one or more checkboxes — one per paywall configured in the Iteras plugin settings. Access is granted if the visitor holds a valid pass for any one of the selected paywalls. Leave all boxes unchecked to fall back to the post's own paywall configuration.

**Editor experience**

In the block editor, all inner content is always visible to editors regardless of subscription status, consistent with how the Iteras shortcode `[iteras-if-logged-in]` behaves. A blue "Iteras Paywall" label marks the block's boundary.

**Dependency enforcement**

This plugin declares `Requires Plugins: iteras` (WordPress 6.5+). WordPress will prevent this plugin from being activated if the Iteras plugin is inactive, and will prevent Iteras from being deactivated while this plugin is active.

**Fail-open behaviour**

If the Iteras plugin is deactivated outside of normal WordPress flows (e.g. manually deleted), the block will render its content for all visitors rather than silently hiding it. This avoids unintentional content blackouts during maintenance.

== Installation ==

1. Ensure the Iteras plugin is installed and activated.
2. Upload the `gopublish-iteras-block` directory to `/wp-content/plugins/`.
3. Activate the plugin through the **Plugins** screen in WordPress.
4. Insert the **Iteras Paywall** block from the **Design** category in the block inserter.

== Frequently Asked Questions ==

= Does the block work without the Iteras plugin? =

The block can be inserted and edited without Iteras active, but access checking will not function. If Iteras is inactive, the block fails open — inner content is shown to all visitors. The `Requires Plugins` header prevents this situation under normal WordPress usage.

= Where do I find my paywall IDs? =

Paywall IDs are configured in your Iteras account dashboard. They are also visible in the Iteras plugin settings under **Paywall IDs**.

= Can I restrict different blocks on the same post to different paywalls? =

Yes. Add multiple Iteras Paywall blocks and tick different paywalls in each block's sidebar panel.

= Can I leave all paywall checkboxes unchecked? =

Yes. When no paywall is selected, the block falls back to the paywall IDs assigned to the current post via the Iteras post meta. If no paywall is assigned to the post either, the content is shown to everyone.

= Do editors always see the protected content? =

Yes. Any WordPress user with the `edit_pages` capability is granted access unconditionally, consistent with the Iteras shortcode's own behaviour.

= Is the access check done client-side or server-side? =

Server-side only. The block is dynamic (no static save output is rendered on the frontend). `render.php` runs on every page load and validates the visitor's `iteraspass` cookie against the configured HMAC signing key.

== Changelog ==

= 0.1.0 =
* Initial release.
