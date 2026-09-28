=== Go:Publish Iteras Block ===
Contributors:      retrofitter
Tags:              iteras, paywall, subscription, access control, block
Requires at least: 6.5
Tested up to:      7.0
Stable tag:        0.4.0
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

**Per-paywall call-to-action content**

The Iteras Paywall block above only gates *other* blocks placed outside the main post content. The paywall visitors actually hit when reading a locked article — the "cut text and call-to-action box" that the Iteras plugin itself inserts into `the_content` — is a single, global box configured once in Iteras' own settings. This plugin adds a **Settings → Iteras Paywall CTA** screen so that box's content can vary by paywall instead of being one fixed message for every subscriber tier.

The screen has one tab per synced Iteras paywall, plus a "Default" tab. Each tab has two fields:

* **Label** — the text used for the eyebrow label ("ABONNEMENT" etc.) shown above paywalled posts, if the active theme wires it up (see "Block Bindings" below).
* **Pattern** — a dropdown of synced patterns (reusable blocks) whose content should replace the default call-to-action box for posts walled by that paywall. Build the pattern visually in the block editor — pricing tables, an `[iteras-ordering]` shortcode, anything — no raw ordering IDs or shortcode syntax required.

If a post's assigned paywall IDs don't resolve to exactly one specific pattern or label (none configured, or several that disagree), the Default tab's pattern and label are used instead.

To activate it, add the shortcode `[iteras-paywall-cta]` once to Iteras' own "Call-to-action content" field (Settings → ITERAS). It resolves and renders the right pattern per post automatically.

To keep the pattern dropdown from growing unwieldy as more synced patterns are added for unrelated purposes, this plugin also registers a **"Paywall"** synced-pattern category. Once at least one pattern is tagged with it (via the Categories panel in the block editor), the dropdown only lists patterns in that category; until then, it falls back to listing every published pattern.

**Block Bindings for the paywall label**

The plugin registers a `gopublish-iteras-block/paywall-label` Block Bindings source (WordPress 6.5+), resolving to the same per-paywall label configured on the settings screen above. A theme can bind any paragraph block's content to this source (`"metadata":{"bindings":{"content":{"source":"gopublish-iteras-block/paywall-label"}}}`, with `uses_context: ["postId"]`) to render a dynamic, per-paywall label anywhere in a template or pattern.

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

= How is the per-paywall call-to-action content different from the Iteras Paywall block? =

The Iteras Paywall block only gates blocks placed *outside* the main post content (e.g. a sidebar or footer CTA elsewhere on the page). The per-paywall call-to-action content configured under **Settings → Iteras Paywall CTA** controls what appears *inside* the paywall box Iteras itself inserts into the article body when a reader lacks access. They're independent features that can both be used on the same site.

= Where is the per-paywall CTA and label configuration stored? =

In a single WordPress option, `gopublish_iteras_paywall_cta_settings`, holding a `default` entry (fallback pattern + label) and a `paywalls` array keyed by paywall ID, each with its own `label` and `pattern_id`. Standard Options API storage — nothing custom.

= What happened to the old ordering-ID mapping screen? =

An earlier version of this plugin had a **Settings → Iteras Ordering** screen mapping each paywall to a plain Iteras "ordering ID" string, consumed by a `[iteras-ordering-for-post]` shortcode. That's been replaced by the pattern-based system described above — a subscription manager now only ever picks a synced pattern instead of typing raw ordering IDs. If `[iteras-ordering-for-post]` is still present in Iteras' call-to-action box, replace it with `[iteras-paywall-cta]`.

== Changelog ==

= 0.4.0 =
* Add "Iteras Paywall CTA" settings screen (Settings → Iteras Paywall CTA): one tab per synced paywall plus a Default tab, each pairing a label with a synced-pattern picker.
* Add `[iteras-paywall-cta]` shortcode, for use inside Iteras' own "Call-to-action content" box, resolving and rendering the right synced pattern per post.
* Add a `gopublish-iteras-block/paywall-label` Block Bindings source so themes can render a per-paywall label anywhere (e.g. an "Erhverv"/"Privat" eyebrow instead of one generic label).
* Auto-register a "Paywall" synced-pattern category so the pattern picker stays short as more unrelated patterns are added.
* Replaces the earlier "Iteras Ordering" screen and `[iteras-ordering-for-post]` shortcode (raw ordering-ID mapping) with the pattern-based system above.

= 0.1.0 =
* Initial release.
