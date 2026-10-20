=== Go:Publish Iteras Block ===
Contributors:      retrofitter
Tags:              iteras, paywall, subscription, access control, block
Requires at least: 6.5
Tested up to:      7.0
Stable tag:        1.2.1
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
* **Registered users** — a checkbox, off by default. When checked, a visitor holding a registered-but-not-subscribed ("user" level) Iteras pass for that paywall is treated as having access — to the full article (bypassing Iteras' own truncation) and to this plugin's own blocks (Iteras Login Status, Iteras Paywall). Every paywall otherwise keeps requiring a paying ("sub" level) subscription, matching Iteras' own behaviour.

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

**A login button that remembers where visitors came from**

Iteras has its own `[iteras-return-to-page]` shortcode for building a login link that redirects back to the originating page afterwards — but a shortcode can't become a button's link through the visual editor, which makes it awkward to use inside synced patterns built for a paywall call-to-action. Search for **"Iteras Login Button"** in the block inserter instead: it's a regular Button block with only its link bound (not its text, which stays fully editable), pointing at Iteras' login page with the return-to parameter already appended.

It deliberately links to Iteras' **"User landing page"** setting, not **"Subscribe landing page"** — the latter is documented by Iteras as serving both logging in and becoming a paying subscriber, so sending an already-subscribed visitor there means landing on another subscribe pitch right after the paywall call-to-action they just saw. The "User landing page" field is normally hidden on Iteras' own settings screen; this plugin reveals it there (a small CSS tweak, not an edit to Iteras' own files) specifically so it can be configured for this button.

**A logout button**

Add the **Iteras Logout Button** block anywhere to let a subscriber log out, via Iteras' own client-side `Iteras.logoutFromWall()` JavaScript function. It's a real button: click the link icon in its toolbar — same as any other button — to set where it should redirect afterwards, or leave it blank and it reloads the current page. No API call is involved; this runs entirely in the visitor's browser.

This block deliberately always tells Iteras exactly where to redirect, rather than relying on Iteras' own documented "reload the page by default" behaviour — testing showed that default doesn't reliably fire on a page with no active paywall script running (e.g. a plain login page): the visitor's pass cleared correctly, but nothing visibly happened afterwards. Always specifying a destination (the one set via the toolbar, or the current page's own address) avoids that silent dead end.

**A login/subscribe form, without hand-written JavaScript**

Add the **Iteras Paywall Login** block to embed Iteras' own login/subscribe iframe — a block equivalent of the `[iteras-paywall-login]` shortcode, meant for a dedicated login page. Its sidebar panel lets you tick which paywalls it applies to (leave all unticked to apply to every configured paywall) and choose an access level: **Subscribers** (Iteras' own default) or **Registered users**, which also accepts a free, non-paying sign-up.

Choosing "Registered users" only matters for a paywall that has its own **"Registered users"** checkbox enabled on the **Settings → Iteras Paywall CTA** screen (see below) — the block warns in its sidebar if that's not yet the case for whichever paywalls are selected, since otherwise a visitor who signs up this way still won't be granted access anywhere.

This exists as a safer alternative to pasting `Iteras.paywalliframe({...})` directly into an HTML block: easy to get subtly wrong (forgetting the `document.write(...)` wrapper Iteras' own shortcodes all use silently renders nothing), with no editor-visible indication of what it does or what it's configured to require.

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

= Why does the Iteras Login Button use "User landing page" instead of "Subscribe landing page"? =

Because the button is meant for an "already a subscriber? log in here" context, typically sitting right next to a paywall call-to-action that's already trying to sell a subscription. Iteras documents "Subscribe landing page" (`subscribe_url`) as serving both logging in *and* becoming a paying subscriber — sending an existing subscriber there feels circular, landing them on another subscribe pitch. "User landing page" (`user_url`) is a separate, login-only destination for exactly this case. It's hidden by default on Iteras' own settings screen; this plugin reveals it there so it can actually be configured.

= If I leave "User landing page" empty, does the login button fall back to "Subscribe landing page"? =

No, deliberately not — it just renders with no link at all. A fallback would quietly reintroduce the same "lands on a subscribe pitch" problem the button exists to avoid. Fill in Iteras' "User landing page" field (now visible under Settings → ITERAS) to activate it.

= I clicked the Iteras Logout Button and nothing seemed to happen — is it broken? =

Probably not — check whether the visitor's `iteraspass` cookie actually cleared (DevTools → Application → Cookies) before assuming it's broken. In testing, the button reliably logs the visitor out even when nothing visibly changes on screen, because Iteras' own "reload the page by default" behaviour doesn't always fire. As of the current version this block always sets an explicit redirect itself (the link you set via its toolbar, or the current page as a fallback) specifically to avoid that silent case — if you're still seeing no visible change, check that you're on an up-to-date version of this plugin.

= I set `paywallid` on the raw `[iteras-paywall-login]` shortcode and it still shows every paywall — is that a bug? =

Yes, but in Iteras itself, not in this plugin. Iteras' `paywall_shortcode()` checks `in_array('paywallid', $attrs)` to decide whether a `paywallid` attribute was passed — but `paywallid` is a *key* in that array, never a value, so the check is always false and the shortcode always falls back to every configured paywall, regardless of what you set. The **Iteras Paywall Login** block doesn't have this problem: its `render.php` builds the same iframe call directly, with that check fixed on this plugin's side.

= I turned on "Registered users" for a paywall, but a registered (non-subscribing) visitor still can't see the article — why? =

Check that the paywall ID you logged in against is the same one that's actually assigned to that article, and that "Registered users" is enabled for *that* paywall specifically — it's per-paywall, not site-wide. Also check whether any other plugin or theme code has its own callback on Iteras' `iteras_override_content_paywall` filter: that filter chains (each callback receives the previous one's return value), so a callback that ignores its argument and returns a hardcoded value can silently discard this plugin's override, and any other plugin's, for every post. This happened on this site once already — see the 0.10.0 changelog entry below.

== Changelog ==

= 1.2.1 =
* Rework the 1.2.0 has-access/no-access correction to use a real, properly enqueued script (`wp_enqueue_script()` + `wp_add_inline_script()` for the late-known data) instead of echoing a `<script>` tag with embedded logic directly from PHP. The comparison logic now lives in `src/post-access-correction/view.js`, a browser-cacheable asset identical on every page, following the same independent-loading pattern already used for Block Variations.

= 1.2.0 =
* Correct `has-access`/`no-access` client-side for posts rendered as teasers inside a query loop (homepage, archives) — the one remaining gap after the Login Status rework, confirmed in production to actually matter since these classes drive real CSS (hiding the paywall label / featured-category label on article teasers). A new `wp_footer` script compares each recorded post's paywall ID(s) against the visitor's own `iteraspass` cookie client-side, replicating the same "sub, or user when that paywall allows it" check `iteras_user_has_access()` does server-side. Only emitted when at least one such post was actually rendered, and skipped entirely for WordPress editors (whose `has-access` is already unconditionally correct server-side, and who a client-side check has no way to recognize as such).
* `is-paywalled`/`is-not-paywalled` and `has-access`/`no-access` on a singular page needed no equivalent fix — both are either visitor-independent or already protected from caching by Iteras' own `DONOTCACHEPAGE`, see `inc/functions-iteras.php` and `potentially_prevent_caching()` in `iteras-public.php`.

= 1.1.0 =
* Rework the Iteras Login Status block to be cache-friendly: it now always renders its content (hidden by default, tagged `data-iteras-show-when`), and a new frontend `view.js` reveals the correct state client-side via Iteras' own `Iteras.paywallPass()` — instead of deciding server-side, which previously meant a page using this block could never be served from a full-page cache without risking stale, wrong content for other visitors. Also corrects the "Iteras Customer ID" paragraph's text client-side for the same reason, piggybacking on this block's script since block variations can't have their own.
* This is a deliberate, narrower alternative to bypassing full-page caching entirely for every logged-in visitor on every page — see `inc/custom.php`'s `bypass_dynamic_cache_for_iteras_login()` in the Olfi theme, which this is meant to make unnecessary once deployed and confirmed.

= 1.0.0 =
* Add a third, independent `body_class`: `iteras-logged-in` / `iteras-not-logged-in` — whether the current visitor holds a valid Iteras pass for *anything at all* (same question the Iteras Login Status block answers), regardless of whether they have access to any specific post. Fires on every page (not just singular posts) since it's a fact about the visitor, not about a post — unlike the existing `has-access`/`no-access` and `is-paywalled`/`is-not-paywalled` pairs. Prefixed with `iteras-` specifically to avoid any ambiguity with a WordPress account login, which this has nothing to do with.

= 0.12.0 =
* Add `iteras_is_post_paywalled()` helper — a plain post-level fact ("does this post have any paywall assigned at all"), independent of the current visitor's access, built on the existing `iteras_get_post_paywall_ids()`.
* Move the `is-paywalled` / `is-not-paywalled` `body_class`/`post_class` additions here from the Olfi theme's `inc/custom.php` (now using the new helper instead of a hardcoded `get_post_meta()` call) — Iteras-specific logic, not a theme concern, and a natural pair alongside this plugin's existing `has-access`/`no-access` classes.

= 0.11.0 =
* Add the "Iteras Paywall Login" block — a block equivalent of the `[iteras-paywall-login]` shortcode, embedding Iteras' own login/subscribe iframe with a per-paywall checkbox list and an access-level (subscribers / registered users) choice in the editor, as a safer alternative to hand-written inline JavaScript on a login page.
* Discovered and worked around a bug in Iteras' own `paywall_shortcode()`: its `paywallid` attribute check (`in_array('paywallid', $attrs)`) tests for the string as a value rather than a key, so it's always false and the shortcode's `paywallid` attribute has never actually restricted anything. The new block builds its own iframe call with that logic fixed, rather than delegating to the buggy method.

= 0.10.0 =
* Add a "Registered users" checkbox per paywall on the Iteras Paywall CTA settings screen (Settings → Iteras Paywall CTA). When checked, visitors with a registered-but-not-subscribed ("user" level) Iteras pass for that paywall are treated as having access — by this plugin's own blocks (Iteras Login Status, Iteras Paywall) and, via a new `iteras_override_content_paywall` filter callback, by Iteras' own article content truncation too. Off by default — every paywall keeps requiring a paying ("sub" level) subscription unless explicitly opted in, matching Iteras' own built-in behaviour.
* Fix: the theme's `disable_paywall()` filter (`inc/custom.php`) declared no parameters and unconditionally returned `true` for any page other than the front/home/archive/search, silently discarding whatever any other plugin's `iteras_override_content_paywall` filter had already decided — including this plugin's new "Registered users" override, and (pre-existing, unrelated to this release) Go:Publish Essentials' Talker TTS paywall bypass. Both now work correctly on single posts.

= 0.9.0 =
* Add the "Iteras Logout Button" block — a real button (click its toolbar link icon to set where it redirects to, same as any other button) that logs a subscriber out via Iteras' own client-side `Iteras.logoutFromWall()`. The plugin's first use of a block `viewScript` (a frontend-only script, auto-loaded only on pages containing the block).
* The button always passes an explicit redirect destination rather than relying on Iteras' documented "reload by default" behaviour, which testing showed doesn't reliably fire on pages with no active paywall script (e.g. a plain login page).

= 0.8.0 =
* Add a `gopublish-iteras-block/login-url` Block Bindings source and an "Iteras Login Button" core/button variation — a login link to Iteras' "User landing page", with a return-to parameter appended, usable anywhere the block editor is used (including inside synced patterns, unlike Iteras' own `[iteras-return-to-page]` shortcode).
* Reveal Iteras' own hidden "User landing page" field (`user_url`) on its settings screen (Settings → ITERAS), via a small CSS override — the field was already fully functional, just never shown.

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
