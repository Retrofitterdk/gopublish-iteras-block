=== Go:Publish Iteras Block ===
Contributors:      retrofitter
Tags:              iteras, paywall, subscription, access control, block
Requires at least: 6.5
Tested up to:      7.0
Stable tag:        0.7.0
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

**Iteras Login Status block**

Adds a second block, **Iteras Login Status**, alongside Iteras Paywall — a block equivalent of Iteras' own `[iteras-if-logged-in]` / `[iteras-if-not-logged-in]` shortcodes, for places shortcodes aren't usable (template parts, synced patterns used outside the main content, etc). Its sidebar panel has one control: "Show content to" — **visitors who are logged in** or **visitors who are NOT logged in**. "Logged in" means holding a valid Iteras subscription pass of any kind, not a WordPress account login.

Unlike the Iteras Paywall block, this block is deliberately unscoped — it always checks for a pass against any paywall configured in Iteras, with no per-paywall restriction. An earlier version let editors optionally restrict it to specific paywalls, but combined with "NOT logged in" that produced a negated-OR ("shown to everyone except visitors who qualify for at least one of the checked paywalls") that was genuinely hard to reason about, and duplicated what the Iteras Paywall block already does. Use the Iteras Paywall block instead for paywall-specific content gating; use Iteras Login Status only to answer "is this visitor a subscriber of any kind, yes or no."

Like the shortcodes it replaces, WordPress users with the `edit_pages` capability always see the content in either mode, and the block fails open (content always shown) when Iteras' server-side validation setting is disabled.

**Showing a subscriber's customer ID**

The plugin can also display a visitor's Iteras customer ID (their account number) directly in post/page content, a widget, or anywhere else the block editor is used — read straight from their signed `iteraspass` cookie, no API call needed. Search for **"Iteras Customer ID"** in the block inserter: it's a paragraph pre-bound to the `gopublish-iteras-block/customer-id` Block Bindings source, resolving to the bare ID (e.g. `251848`) or nothing at all if the visitor isn't logged in with a valid pass.

Because a block binding can only replace a whole block's content, not fill in one word inside a sentence, this block won't compose something like "You're logged in with customer number 251848" by itself. Wrap it together with your own static text paragraph inside an **Iteras Login Status** block (set to "logged in") — that hides the whole group together for logged-out visitors, rather than leaving your static text showing with nothing after it.

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

= How is the Iteras Login Status block different from the Iteras Paywall block? =

Iteras Paywall gates content by a *specific* paywall (defaulting to the current post's own assigned paywall(s)). Iteras Login Status answers a simpler, unscoped question — "does this visitor hold a valid Iteras pass for anything at all" — which is what makes it usable in contexts with no specific post/paywall in scope, like a site header or footer. It has no paywall-selection option; use Iteras Paywall instead when you need to gate by a specific paywall.

= Can I restrict the Iteras Login Status block to a specific paywall? =

No, by design. An earlier version allowed this, but combined with the block's "visitors who are NOT logged in" mode it produced a negated-OR condition ("shown to everyone except visitors who qualify for at least one of the checked paywalls") that was too easy to get backwards. Use the Iteras Paywall block for paywall-specific gating instead.

= Is the customer ID trustworthy, or could a visitor fake it? =

It's trustworthy. `iteras_get_customer_id()` verifies the `iteraspass` cookie's HMAC signature and checks it hasn't expired before returning anything — a forged or tampered cookie returns nothing at all, the same way a forged pass fails the normal access check.

= Where does the customer ID actually come from? =

It's read directly out of the `iteraspass` cookie, which turns out to carry more than this plugin originally parsed out of it. Iteras' own docs describe the cookie as carrying "the access level and customer number", and a real captured cookie confirmed the full format: `{access_levels}|{paywall_ids}|{expiry}|{customer_id}|{client_ip}/{algo}:{hmac}`. No API call to Iteras is made.

== Changelog ==

= 0.7.0 =
* Add `iteras_get_customer_id()` helper — reads the visitor's Iteras customer ID directly from their signed `iteraspass` cookie (no API call), verifying the HMAC signature and expiry first.
* Add a `gopublish-iteras-block/customer-id` Block Bindings source and an "Iteras Customer ID" paragraph variation, for displaying a subscriber's customer ID anywhere the block editor is used.

= 0.6.0 =
* `[iteras-paywall-cta]` now renders a resolved synced pattern directly (`get_post()` + `do_blocks()` + `do_shortcode()`) instead of routing through a `[synced_pattern]` shortcode previously provided by the active theme. That theme shortcode had no other callers left and has since been removed — this plugin no longer depends on the theme for this feature.

= 0.5.0 =
* Add "Iteras Login Status" block — a block equivalent of `[iteras-if-logged-in]` / `[iteras-if-not-logged-in]` for use where shortcodes aren't usable, with a "Show content to: logged in / not logged in" toggle. Deliberately unscoped (no per-paywall restriction) to avoid a confusing negated-OR condition; use the Iteras Paywall block for paywall-specific gating.

= 0.4.0 =
* Add "Iteras Paywall CTA" settings screen (Settings → Iteras Paywall CTA): one tab per synced paywall plus a Default tab, each pairing a label with a synced-pattern picker.
* Add `[iteras-paywall-cta]` shortcode, for use inside Iteras' own "Call-to-action content" box, resolving and rendering the right synced pattern per post.
* Add a `gopublish-iteras-block/paywall-label` Block Bindings source so themes can render a per-paywall label anywhere (e.g. an "Erhverv"/"Privat" eyebrow instead of one generic label).
* Auto-register a "Paywall" synced-pattern category so the pattern picker stays short as more unrelated patterns are added.
* Replaces the earlier "Iteras Ordering" screen and `[iteras-ordering-for-post]` shortcode (raw ordering-ID mapping) with the pattern-based system above.

= 0.1.0 =
* Initial release.
