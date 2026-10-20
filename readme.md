# Go:Publish Iteras Block

A WordPress block plugin that reveals or hides inner content based on Iteras subscription access.

## Requirements

| Requirement | Version |
|---|---|
| WordPress | 6.5+ |
| PHP | 7.4+ |
| Iteras plugin | any (active) |
| Node.js | 18+ (build only) |

WordPress 6.5 is the minimum because the `Requires Plugins` dependency header — which prevents activation without Iteras — was introduced in that release. WordPress 6.7+ additionally enables the block metadata manifest for more efficient registration (see [Block metadata manifest](#block-metadata-manifest)); the plugin degrades gracefully on older versions.

## How it works

The plugin ships four real blocks — **Iteras Paywall**, **Iteras Login Status**, **Iteras Logout Button**, and **Iteras Paywall Login** — plus three Block Bindings-powered variations, a settings screen, a shortcode, and a Block Bindings source for driving Iteras' own inline paywall (see further down).

The first two are dynamic container blocks that work the same way: editors place any inner blocks (text, images, embeds, etc.) inside them, and on the frontend `render.php` runs an access check on every page load — if it passes, the inner blocks render; if not, the block outputs nothing. They differ in *what* they check: Iteras Paywall gates by a specific paywall (defaulting to the current post's own assigned paywall(s)); Iteras Login Status answers the simpler, unscoped question "does this visitor hold a valid Iteras pass for anything at all". That access check is enforced entirely in PHP using the visitor's `iteraspass` cookie — no JavaScript involved.

Iteras Logout Button is different in kind: logging a visitor out isn't a value to check or render, it's an action that has to run in the browser on click, via Iteras' own client-side `Iteras.logoutFromWall()` JavaScript API. It's the plugin's first use of a block `viewScript` (a frontend-only script, auto-loaded by WordPress only on pages that actually contain the block) — see its own section below.

Iteras Paywall Login is a block equivalent of the `[iteras-paywall-login]` shortcode — it embeds Iteras' own login/subscribe iframe, with which paywalls it applies to and whether a free "registered user" pass is accepted as editor settings, instead of hand-written shortcode attributes (and instead of hand-written inline JavaScript, which has no editor UI at all and is easy to get subtly wrong — see its own section below).

In the block editor, inner content of the two access-gated blocks is always visible. Editors with the `edit_pages` capability are unconditionally granted access, consistent with the `[iteras-if-logged-in]` shortcode's own behaviour.

## Plugin structure

```
gopublish-iteras-block/
├── gopublish-iteras-block.php        # Plugin entry point
├── inc/
│   ├── functions-iteras.php          # Iteras access-check + CTA/label resolver helpers
│   ├── admin-settings.php            # "Iteras Paywall CTA" settings screen + reveals Iteras' hidden user_url field (admin-only)
│   ├── shortcode-paywall-cta.php     # [iteras-paywall-cta] shortcode
│   ├── block-bindings.php            # "paywall-label" + "customer-id" + "login-url" Block Bindings sources
│   ├── content-paywall-override.php  # Unwalls full articles for "user"-level-allowed paywalls, via Iteras' own override filter
│   └── post-content-layout-fix.php   # Keeps Iteras' auto paywall wrapper inside block-theme layout bounds
├── src/
│   ├── blocks/
│   │   ├── iteras-paywall/
│   │   │   ├── block.json            # Block metadata
│   │   │   ├── index.js              # Block registration entry point
│   │   │   ├── edit.js               # Editor component
│   │   │   ├── save.js               # Save function
│   │   │   ├── render.php            # Server-side render (access gate)
│   │   │   ├── editor.scss           # Editor-only styles
│   │   │   └── style.scss            # Frontend styles
│   │   ├── iteras-logged-in/         # Same file layout as iteras-paywall/ above
│   │   │   └── ...                   # (block.json, index.js, edit.js, save.js, render.php, editor.scss, style.scss)
│   │   ├── iteras-logout/
│   │   │   ├── block.json            # Declares both editorScript and viewScript
│   │   │   ├── index.js              # Block registration entry point
│   │   │   ├── edit.js               # Editor component (RichText label + toolbar Link UI)
│   │   │   ├── save.js               # Save function — fully static output, no render.php needed
│   │   │   ├── view.js               # Frontend-only script — wires up Iteras.logoutFromWall() on click
│   │   │   ├── editor.scss           # Editor-only styles
│   │   │   └── style.scss            # Frontend styles
│   │   └── iteras-paywall-login/
│   │       ├── block.json            # No "style" field — output is a <script> tag, no frontend CSS needed
│   │       ├── index.js              # Block registration entry point
│   │       ├── edit.js               # Editor component (paywall checkboxes + access-level radio + Notice)
│   │       ├── save.js               # Save function — returns null, render.php builds everything
│   │       ├── render.php            # Server-side render — builds Iteras.paywalliframe() call directly
│   │       └── editor.scss           # Editor-only styles
│   ├── block-variations/             # See "Block Variations" below — not a real block
│   │   ├── block.json                # Build-tooling marker only, never register_block_type()'d
│   │   ├── index.js                  # Shared entry point — imports every variation file below
│   │   ├── paywall-label.js          # "Iteras Paywall Label" core/paragraph variation
│   │   ├── customer-id.js            # "Iteras Customer ID" core/paragraph variation
│   │   └── login-button.js           # "Iteras Login Button" core/button variation
│   └── post-access-correction/       # Same independent-loading pattern as block-variations/ — not a real block
│       ├── block.json                # Build-tooling marker only, never register_block_type()'d
│       └── view.js                   # Corrects has-access/no-access client-side on any page with these classes
├── build/                            # Compiled assets (generated, do not edit)
│   ├── blocks-manifest.php           # PHP block metadata manifest (WP 6.7+)
│   ├── blocks/
│   │   ├── iteras-paywall/
│   │   │   ├── block.json
│   │   │   ├── index.js
│   │   │   ├── index.css             # Compiled editor styles
│   │   │   ├── index-rtl.css
│   │   │   ├── style-index.css       # Compiled frontend styles
│   │   │   ├── style-index-rtl.css
│   │   │   ├── index.asset.php       # Dependency manifest
│   │   │   └── render.php
│   │   ├── iteras-logged-in/         # Same file layout as iteras-paywall/ above
│   │   ├── iteras-logout/
│   │   │   ├── block.json
│   │   │   ├── index.js              # Editor bundle
│   │   │   ├── view.js               # Frontend bundle — separate entry, separate asset.php
│   │   │   ├── index.asset.php
│   │   │   ├── view.asset.php
│   │   │   ├── index.css
│   │   │   ├── style-index.css
│   │   │   └── style-index-rtl.css
│   │   └── iteras-paywall-login/
│   │       ├── block.json
│   │       ├── index.js              # Editor bundle
│   │       ├── index.asset.php
│   │       ├── index.css
│   │       └── render.php
│   ├── block-variations/
│   │   ├── block.json                # Copied from src/ as-is, still inert
│   │   ├── index.js                  # Compiled bundle of all variation files
│   │   └── index.asset.php           # Dependency manifest, read manually (see "Block Variations")
│   └── post-access-correction/
│       ├── block.json                # Copied from src/ as-is, still inert
│       ├── view.js                   # Compiled script
│       └── view.asset.php            # Dependency manifest, read manually (same pattern as block-variations/)
├── languages/
│   └── gopublish-iteras-block.pot   # Translation template
├── package.json
└── readme.txt                        # WordPress.org readme
```

## The Iteras Paywall block

**Block name:** `gopublish-iteras-block/iteras-paywall`  
**Category:** Design  
**Type:** Dynamic (server-side rendered via `render.php`)

### Attributes

| Attribute | Type | Default | Description |
|---|---|---|---|
| `paywallIds` | `string[]` | `[]` | Array of paywall IDs that grant access. Falls back to the post's configured paywall IDs when empty. |

### Editor controls

The block's sidebar panel shows one **CheckboxControl** per paywall configured in the Iteras plugin settings. Ticking a paywall adds its ID to `paywallIds`; unticking removes it. Access is granted when the visitor holds a valid pass for **any one** of the selected paywalls (OR logic). If no paywalls are configured in Iteras a warning notice is shown instead. Leaving all boxes unchecked delegates to the paywall IDs set on the current post via the Iteras plugin meta.

### Block supports

- Color (text, background)
- Spacing (margin, padding)
- Align (wide, full)
- Layout: constrained

### Save / render pattern

`save.js` serialises the block wrapper and inner blocks into the post content:

```jsx
<div {...useBlockProps.save()}>
    <InnerBlocks.Content />
</div>
```

On the frontend, `render.php` takes over entirely. It receives `$content` (the serialised inner-blocks HTML) and either echoes it or returns early:

```php
if ( ! $has_access ) {
    return; // Output nothing
}
echo $content;
```

## The Iteras Login Status block

**Block name:** `gopublish-iteras-block/iteras-logged-in`  
**Category:** Design  
**Type:** Dynamic (`render.php`), with a frontend `view.js` that does the actual show/hide decision

A block equivalent of Iteras' own `[iteras-if-logged-in]` / `[iteras-if-not-logged-in]` shortcodes (`Iteras::content_by_login_status()` in `iteras/public/iteras-public.php`), for use in places shortcodes aren't usable — template parts, synced patterns used outside `the_content`, etc.

"Logged in" means the visitor holds a valid Iteras subscription pass (the `iteraspass` cookie), not a WordPress account login — matching the shortcodes' own terminology.

### Attributes

| Attribute | Type | Default | Description |
|---|---|---|---|
| `showWhen` | `string` | `'logged-in'` | Either `'logged-in'` or `'not-logged-in'` — which visitors see the inner content. |

Deliberately has no paywall-selection attribute. An earlier version let editors optionally restrict the check to specific paywalls (like the Iteras Paywall block's `paywallIds`), but combined with `showWhen: 'not-logged-in'` that produced a negated-OR condition — "shown to everyone *except* visitors who qualify for at least one of the checked paywalls" — that's hard to reason about correctly, and duplicated what the Iteras Paywall block already does for the `'logged-in'` case. This block only ever checks for a pass against **any** paywall configured in Iteras (`iteras_user_has_access()` with no restriction). Use the Iteras Paywall block instead for paywall-specific gating.

### Editor controls

A single **RadioControl** in the sidebar: "Show content to" → *visitors who are logged in* / *visitors who are NOT logged in*. The block's canvas label reflects the current mode (`Iteras: Logged in` / `Iteras: Not logged in`) so blocks are distinguishable at a glance without opening the sidebar.

### Render logic and edge cases

`render.php` deliberately does **not** delegate to `iteras_user_has_access_for_post()` or rely solely on `iteras_user_has_access()`'s own internal bypasses — it reimplements two checks explicitly, in the same order `content_by_login_status()` does, because they must apply identically to *both* branches of `showWhen`:

1. **No server-side validation configured** → content is always shown (fail open), in either mode. There's no way to check the pass at all in this state.
2. **Current user has `edit_pages`** → content is always shown, in either mode. This is the subtle one: naively calling `iteras_user_has_access()` (which has its own internal editor bypass returning `true`) and then inverting that for `'not-logged-in'` mode would incorrectly *hide* content from editors in that mode. Editors must see the content regardless of which mode is selected, exactly like the shortcodes.

Both of those branches are resolved **server-side**, unconditionally — they're the same for every visitor (or never actually reach a cached page in practice, since a logged-in WordPress session already forces a cache bypass at the hosting layer on its own), so baking them into a cached response is safe.

Once both are ruled out, the real per-visitor case is handled completely differently — not by computing `iteras_user_has_access()` and conditionally echoing. Doing that would mean a page using this block could never be served from a full-page cache without risking stale, wrong content for other visitors (confirmed in production: a page cached while actually logged in was later served, unchanged, to logged-out visitors, showing them as logged in). Instead `render.php` always outputs the content, hidden by default, tagged with `data-iteras-show-when` — set directly on the block's *own* wrapper via `WP_HTML_Tag_Processor`, not a new div wrapped around it:

```php
$processor = new WP_HTML_Tag_Processor( $content );

if ( $processor->next_tag() ) {
    $processor->set_attribute( 'data-iteras-show-when', $show_when );

    $existing_style = $processor->get_attribute( 'style' );
    $processor->set_attribute(
        'style',
        ( $existing_style ? rtrim( (string) $existing_style, ';' ) . ';' : '' ) . 'display:none'
    );
}

echo $processor->get_updated_html();
```

An earlier version wrapped `$content` in a new outer div instead, which broke this block's "constrained" layout support: that layout type's CSS applies `max-width`/`alignwide`/`alignfull` rules to *direct children* of the block's own wrapper, so the extra DOM level meant the actual inner blocks were no longer direct children of it, and all fell back to the default constrained width regardless of their own alignment — confirmed in production as a real, visible layout regression. Reading any existing `style` attribute first (rather than overwriting it outright) also matters: this block supports color/spacing, so an editor-configured background color or padding would otherwise be silently discarded.

`view.js` (a frontend-only `viewScript`, loaded only on pages containing this block) is what actually decides, client-side, reading the visitor's own `iteraspass` cookie via Iteras' `Iteras.paywallPass()`:

```js
var pass = Iteras.paywallPass();
var isLoggedIn = !! pass && pass.expires instanceof Date && pass.expires.getTime() > Date.now();

document.querySelectorAll( '[data-iteras-show-when]' ).forEach( function ( el ) {
    var showWhen = el.getAttribute( 'data-iteras-show-when' );
    if ( ( showWhen === 'not-logged-in' ) ? ! isLoggedIn : isLoggedIn ) {
        el.style.display = '';
    }
} );
```

This means a page using this block renders *identically* for every visitor — fully cacheable — and only a small client-side step personalizes it afterwards. It's a deliberately narrower fix than bypassing full-page caching entirely for every logged-in visitor on every page (see the Olfi theme's `inc/custom.php`, `bypass_dynamic_cache_for_iteras_login()`) — most of a typical page has nothing visitor-dependent on it at all; this only makes the actual visitor-dependent fragment safe to cache around, rather than paying the cost of never caching the whole page for anyone who happens to be logged in.

**A weaker check, on purpose.** `Iteras.paywallPass()` parses the cookie and exposes its expiry, but — unlike this plugin's own `iteras_user_has_access()` — performs no HMAC signature verification; the signing key is a server-only secret, so that's not something any client-side check can do. That's fine here specifically because this block only ever controls a status *display*; actual paywalled content access is still fully, correctly enforced server-side (Iteras' own truncation, and this plugin's `iteras_user_has_access_for_post()`), regardless of what this block happens to show.

**The "Iteras Customer ID" paragraph gets corrected too.** Its server-resolved value (via the `gopublish-iteras-block/customer-id` Block Binding) would otherwise freeze into a cached page the exact same way this block's visibility used to. `view.js` also selects `.iteras-customer-id` (the variation's own stable class, see [Block Variations](#block-variations-srcblock-variations)) and overwrites its text with `pass.customer`. Block variations can't have their own `viewScript`, so this piggybacks on this block's — consistent with this plugin's own documented convention that the Customer ID variation should always be wrapped inside an Iteras Login Status block anyway.

## The Iteras Logout Button block

**Block name:** `gopublish-iteras-block/iteras-logout`
**Category:** Design
**Type:** Static (save-only — no `render.php`; unlike the other two blocks, this one's output doesn't depend on an access check, so there's nothing for PHP to gate)

Logs the current visitor out of their Iteras subscription pass, via Iteras' own client-side `Iteras.logoutFromWall()` JavaScript API ([docs](https://app.iteras.dk/api/docs/js/paywall/)). This block doesn't fit the pattern of the other two — logging out isn't a value to render or check, it's an action that has to run in the browser on click — so it's the plugin's first use of a block `viewScript`, a frontend-only script WordPress auto-loads only on pages that actually contain the block (confirmed via `WP_Block_Type::view_script_handles` / `register_block_script_handle()` in `wp-includes/blocks.php` — standard core mechanism, no custom enqueue code needed, unlike Block Variations' `editorScript`).

### Attributes

| Attribute | Type | Default | Description |
|---|---|---|---|
| `text` | `string` | `'Log out'` | Button label, editable in-canvas via `RichText`. |
| `url` | `string` | `''` | Optional redirect destination after logging out. |

### Editor controls

No Inspector panel. `url` is set via the same toolbar Link button + `LinkControl` popover `core/button` itself uses (`__experimentalLinkControl` from `@wordpress/block-editor`) — deliberately reused rather than a custom sidebar field, so editing it uses UI muscle memory people already have from every other button/link in WordPress, instead of a field that's easy to miss. Icons are plain dashicon slug strings (`"admin-links"` / `"editor-unlink"`), not `@wordpress/icons` imports — that package isn't installed as a local devDependency in this plugin (unlike `@wordpress/block-editor`, which `wp-scripts`' dependency-extraction can resolve as a build-time external without the package being present locally), so importing from it fails the build.

### `view.js` and the fallback redirect

```js
button.addEventListener( 'click', function () {
    if ( typeof window.Iteras === 'undefined' || typeof window.Iteras.logoutFromWall !== 'function' ) {
        return;
    }
    var redirect = button.dataset.redirect || window.location.href;
    window.Iteras.logoutFromWall( { redirect: redirect } );
} );
```

Always passes an explicit `redirect` — never calls `logoutFromWall()` with no options. Iteras' own docs say omitting `redirect` reloads the current page by default, but that was tested directly (calling it manually from the browser console on a real page with no active `Iteras.wall()` instance — e.g. a plain login page): the cookie cleared correctly, but the promised reload never fired, with no error and no visible feedback. Passing an explicit `redirect` — even just the current page's own URL — was confirmed reliable in the same test. So: use the link set via the toolbar if there is one, otherwise fall back to `window.location.href`, which reproduces the "stay here, just refreshed" behaviour the undocumented default was supposed to provide, without depending on it.

The `typeof window.Iteras === 'undefined'` check is a runtime guard, not a build-time script dependency on Iteras' own `iteras-api-script` handle (enqueued by the Iteras plugin itself, not something `wp-scripts` can auto-detect for this file's `view.asset.php`). In practice there's no real race to guard against: the check only matters at the moment of *click*, and a visitor can't click the button before the page — and every script on it, including Iteras' externally-hosted one — has finished loading.

### Composability

No built-in visibility logic, same reasoning as Iteras Customer ID: wrap it in an **Iteras Login Status** block (mode: logged in) if it should only appear for visitors who are actually logged in, rather than duplicating that check into every new block.

## The Iteras Paywall Login block

**Block name:** `gopublish-iteras-block/iteras-paywall-login`
**Category:** Design
**Type:** Dynamic (`render.php` — output depends on editor-configured settings, not an access check)

A block equivalent of the `[iteras-paywall-login paywallid="…"]` shortcode — embeds Iteras' own login/subscribe iframe via its client-side `Iteras.paywalliframe()` ([docs](https://app.iteras.dk/api/docs/js/paywall/)), with which paywalls it applies to and the access level it requests as editor settings. Meant for a dedicated login page (e.g. `/log-ind/`), as a safer alternative to hand-writing the same JavaScript inline in an HTML block — fragile (no validation, easy to forget the `document.write(...)` wrapper Iteras' own shortcodes all use, silently renders nothing if missed) and invisible to anyone editing the page later.

### Attributes

| Attribute | Type | Default | Description |
|---|---|---|---|
| `paywallIds` | `string[]` | `[]` | Which paywalls this login form applies to. Empty means every configured paywall — matching the `[iteras-paywall-login]` shortcode's own default. |
| `accessLevel` | `string` | `''` | `''` (subscribers, Iteras' own default) or `'user'` (also accepts a free, non-paying registered-user sign-up). |

### Editor controls

Inspector panel: a checkbox per configured paywall (same `CheckboxControl` list pattern as the Iteras Paywall block, reading the same localized `window.gopublishIterasBlockData.paywalls`), and a `RadioControl` for access level. When "Registered users" is selected, a `Notice` lists any selected paywall that doesn't have "Registered users" enabled on the [Iteras Paywall CTA settings](#iteras-paywall-cta-settings-incadmin-settingsphp) screen — a visitor signing up as a registered user through this form still won't be granted access anywhere until that's turned on for the relevant paywall(s), and this is the most direct way to notice that before publishing rather than after a confused support request.

### Why `render.php` doesn't call `Iteras::paywall_shortcode()` directly

This plugin otherwise prefers calling Iteras' own public shortcode methods directly rather than reimplementing them — see `return_to_page_shortcode()` reuse in [Block Bindings](#block-bindings-incblock-bindingsphp). That wasn't possible here: `paywall_shortcode()` (the method backing `[iteras-paywall-login]`) has a genuine bug —

```php
if (!empty($attrs) && in_array('paywallid', $attrs)) {
  $attrs['paywallid'] = $this->parse_paywall_ids($attrs['paywallid']);
}
```

`in_array('paywallid', $attrs)` checks whether the *string* `"paywallid"` appears as a *value* in `$attrs` — but a shortcode's `paywallid` attribute is a *key*, never a value (this should be `array_key_exists()`). Confirmed directly (`studio wp eval`): calling `paywall_shortcode(['paywallid' => 'abc123'])` produces the exact same output as calling it with no `paywallid` at all — it always falls through to every configured paywall. The raw `[iteras-paywall-login paywallid="…"]` shortcode has the same bug, for the same reason — its `paywallid` attribute has never actually worked. This is a bug in Iteras' own code, not introduced here, and per this plugin's rule against editing Iteras' files, not something to patch there either.

So `render.php` builds the same `<script>document.write(Iteras.paywalliframe(…))` output Iteras' own shortcode produces, with that one line's logic fixed, reusing `Iteras::combine_attributes()` — a generic, bug-free helper — for everything else.

## Helper functions (`inc/functions-iteras.php`)

These functions are copied from Go:Publish Essentials so this plugin can operate independently. All three are guarded with `if ( ! function_exists() )`, so they coexist safely if Essentials is also active — whichever plugin loads first wins, and the implementations are identical.

---

### `iteras_user_has_access( $paywall_ids = '' )`

Returns `true` if the current visitor holds a valid Iteras subscription pass for at least one of the given paywall IDs.

```php
// Check against specific paywall IDs
if ( iteras_user_has_access( 'abc123,def456' ) ) { ... }
if ( iteras_user_has_access( [ 'abc123', 'def456' ] ) ) { ... }

// Fall back to the paywall IDs configured in the Iteras plugin settings
if ( iteras_user_has_access() ) { ... }
```

**Parameters**

| Parameter | Type | Default | Description |
|---|---|---|---|
| `$paywall_ids` | `string\|string[]` | `""` | Comma-separated string or array of paywall IDs. Falls back to the plugin's globally configured paywalls when empty. |

**Returns** `bool`

**Notes**
- Returns `false` immediately if the `Iteras` class does not exist.
- Returns `false` if `paywall_server_side_validation` is not enabled in Iteras settings.
- Returns `true` unconditionally for users with the `edit_pages` capability.
- Validates the `iteraspass` cookie using HMAC (see `_iteras_pass_authorized()`).

---

### `iteras_user_has_access_for_post( $post_id = null )`

Returns `true` if the current visitor has access to a specific post's paywalled content. Looks up the paywall IDs stored in the post's Iteras meta and delegates to `iteras_user_has_access()`.

```php
// Current post in the loop
if ( iteras_user_has_access_for_post() ) { ... }

// Specific post
if ( iteras_user_has_access_for_post( $post->ID ) ) { ... }
```

**Parameters**

| Parameter | Type | Default | Description |
|---|---|---|---|
| `$post_id` | `int\|null` | `null` | Post ID. Defaults to the current global post (`get_the_ID()`). |

**Returns** `bool`

**Notes**
- Returns `true` (free access) when no paywall is assigned to the post.
- Handles the legacy single-value meta format (`"user"` or `"sub"`) by mapping it to all configured paywalls.

---

### `iteras_get_post_paywall_ids( $post_id = null )`

Returns the paywall IDs assigned to a post via its Iteras post meta, normalised to an array (including the legacy `"user"`/`"sub"` mapping). The shared building block behind `iteras_user_has_access_for_post()` and the CTA/label resolvers below.

**Returns** `string[]|null` — `null` when there's no Iteras plugin active or no post to resolve, which is distinct from an empty array (post resolved fine, just has no paywall assigned).

---

### `iteras_get_paywall_cta_pattern_id_for_post( $post_id = null )` / `iteras_get_paywall_label_for_post( $post_id = null )`

Resolve, respectively, the synced-pattern ID and label text configured for a post — driven by the `paywalls` map in the `gopublish_iteras_paywall_cta_settings` option (see "Iteras Paywall CTA settings" below). A post can carry several paywall IDs; if they resolve to zero or to more than one *distinct* non-empty value, there's no single correct answer, so both return `null` rather than guessing. Two paywall IDs that happen to share the same configured pattern/label still count as one unambiguous match.

Both are thin wrappers around the shared `iteras_get_paywall_cta_field_for_post( $post_id, $field )` helper (`$field` is `'pattern_id'` or `'label'`).

**Returns** `int|null` / `string|null`

---

### `iteras_get_default_paywall_cta_pattern_id()` / `iteras_get_default_paywall_cta_label()`

Read the "Default" tab's pattern ID / label from `gopublish_iteras_paywall_cta_settings['default']` — the fallback used whenever the functions above return `null`.

**Returns** `int` (`0` when unset) / `string` (`''` when unset)

---

### `_iteras_pass_authorized( $pass, $restriction, $signing_key )` _(internal)_

Validates the raw `iteraspass` cookie value against a list of paywall IDs. Replicates the private `Iteras::pass_authorized()` method. Use `iteras_user_has_access()` instead of calling this directly.

**Parameters**

| Parameter | Type | Description |
|---|---|---|
| `$pass` | `string` | Raw value of the `iteraspass` cookie. |
| `$restriction` | `string[]` | Array of paywall IDs to check against. |
| `$signing_key` | `string` | HMAC signing key from Iteras plugin settings. |

**Returns** `bool`

**Validation steps**
1. Splits the cookie into a data segment and an HMAC signature (`sha1:…` or `sha256:…`).
2. Verifies the HMAC against the signing key (skipped when no key is configured).
3. Checks the pass expiry timestamp.
4. Confirms at least one paywall ID in `$restriction` has `access_level = "sub"` — **or** `access_level = "user"` when that specific paywall ID has its "Registered users" checkbox ticked on the Iteras Paywall CTA settings screen (see `iteras_paywall_allows_user_level()` and [Iteras Paywall CTA settings](#iteras-paywall-cta-settings-incadmin-settingsphp) below). This is an addition on top of what Iteras' own private `pass_authorized()` checks — it never recognises `"user"` level, with no way to extend it directly.

---

### `iteras_paywall_allows_user_level( $paywall_id )`

Returns `true` if the given paywall ID is configured (via its "Registered users" checkbox) to treat a registered-but-not-subscribed (`"user"` level) Iteras pass as sufficient access, not just a paying subscription (`"sub"` level). Reads the same option as `iteras_get_paywall_cta_field_for_post()`. Off by default for every paywall.

---

### `iteras_get_customer_id()`

Returns the current visitor's Iteras customer ID (their account number), read directly from the `iteraspass` cookie — no API call needed.

The cookie's real format, confirmed from a live captured value, is
`{access_levels}|{paywall_ids}|{expiry}|{customer_id}|{client_ip}/{algo}:{hmac}` — a superset of what both Iteras' own `pass_authorized()` and `_iteras_pass_authorized()` above actually read (they only use indices 0–2). This function runs the same signature and expiry checks as `_iteras_pass_authorized()`, then returns index 3 instead of doing the paywall-restriction check.

This is the customer's *account-level* ID, not a per-subscription ID — one customer can have more than one active subscription. Getting a specific subscription ID would need a real API call to `GET https://app.iteras.dk/api/customers/?id=…` ([docs](https://app.iteras.dk/api/docs/server/retrievingcustomers/)), which this function deliberately avoids.

**Returns** `string|null` — `null` when there's no cookie, the signature doesn't verify, or the pass has expired.

**Usage**

```php
if ( function_exists( 'iteras_get_customer_id' ) ) {
    $customer_id = iteras_get_customer_id(); // string or null
}
```

---

### `iteras_is_post_paywalled( $post_id = null )`

Returns `true` if the given post has any paywall(s) assigned at all — a plain post-level fact, independent of the current visitor's access. Built on `iteras_get_post_paywall_ids()` (`! empty( iteras_get_post_paywall_ids( $post_id ) )`), so it shares that function's null-safety: when Iteras isn't active, this returns `false`, consistent with this plugin's fail-open behaviour.

Distinct from `iteras_user_has_access_for_post()`: use this to mark "this is premium content" the same way for every visitor (e.g. a "Premium" badge on an article teaser); use `iteras_user_has_access_for_post()` instead to decide what the *current* visitor is specifically allowed to see. Backs the `is-paywalled` / `is-not-paywalled` body/post classes below.

**Returns** `bool`

## `body_class`/`post_class`: access-related classes

Three independent class pairs, all added by `gopublish-iteras-block.php` itself (the first two moved there from the Olfi theme's `inc/custom.php` — these are Iteras facts, not theme/presentation concerns):

| Classes | Filter | Based on | Meaning |
|---|---|---|---|
| `has-access` / `no-access` | `body_class` (singular only), `post_class` | `iteras_user_has_access_for_post()` | Can *this visitor* read this specific post right now? |
| `is-paywalled` / `is-not-paywalled` | `body_class` (singular, not the front page), `post_class` | `iteras_is_post_paywalled()` | Does this post have *any* paywall assigned at all — the same for every visitor? |
| `iteras-logged-in` / `iteras-not-logged-in` | `body_class` (every page) | `iteras_user_has_access()` (no paywall restriction) | Does this visitor hold a valid Iteras pass for *anything at all* — the same question the Iteras Login Status block answers? |

The first two are about a specific *post*; the third is purely about the *visitor*, which is why it has no `post_class` sibling (it wouldn't vary per post in a query loop) and no `is_singular()` guard (it fires on every page — archives, the front page, anywhere a header login link might need it). It's deliberately prefixed `iteras-` (unlike the other two pairs) because "logged in" alone would be ambiguous with a WordPress account login, which this has nothing to do with.

These can all disagree on the same request: a post can be `is-paywalled` and `has-access` at once (a subscriber reading gated content), or `is-paywalled` and `no-access` (an anonymous visitor hitting the same gate), independently of whatever `iteras-logged-in`/`iteras-not-logged-in` says about the visitor generally. Use `is-paywalled`/`is-not-paywalled` for a universal "this is premium content" marker (e.g. a lock badge shown to everyone); use `has-access`/`no-access` for anything that should differ per visitor on that specific post; use `iteras-logged-in`/`iteras-not-logged-in` for anything that only cares whether the visitor is logged in at all (e.g. showing a login link vs. an account link in the header).

`body_class_for_paywalled_content()` specifically excludes the front page (`is_front_page()`, not just `is_singular()` — a static front page still satisfies `is_singular()`) since the homepage itself was never meant to be tagged this way; `body_class_for_paywall_access()` has no such exclusion (a pre-existing difference between the two, kept as-is when the paywalled-content pair moved here from the theme, rather than silently unifying behaviour nobody asked to change).

### Caching: `has-access`/`no-access` on a cached page

`has-access`/`no-access` is visitor-specific, which is exactly what makes it unsafe to bake into a cached page — and this site's Dynamic Caching layer doesn't vary by the `iteraspass` cookie on its own (see the Olfi theme's `inc/custom.php`). Two of the three places this class pair can appear turn out to already be safe, and one genuinely isn't:

- **`body_class` on a singular, actually-paywalled post** — safe. That page is never cached at all; Iteras' own `potentially_prevent_caching()` sends `DONOTCACHEPAGE` for it.
- **`body_class` on a singular, non-paywalled post** — safe. `has-access` is `true` for every visitor there (no paywall assigned), so there's nothing visitor-specific to go stale.
- **`post_class` on a post rendered as a teaser inside a query loop** (the homepage, an archive) — **not safe**, and confirmed in production to actually matter: the Olfi theme's `archive.scss` hides the featured-category label specifically via `.no-access .is-featured-category`, so a stale class here is visibly wrong, not just cosmetic.

`post_class_for_paywall_access()` handles this by recording the post's own paywall ID(s) into a request-scoped global (`$gopublish_iteras_rendered_post_paywalls`) whenever it computes `has-access`/`no-access` for an actually-paywalled post — skipped for editors, since their value is already unconditionally correct via `iteras_user_has_access()`'s own `edit_pages` bypass, and a client-side check has no way to tell "this visitor is a WordPress editor" to avoid wrongly reversing that.

The comparison logic itself lives in `src/post-access-correction/view.js` — a real, browser-cacheable script, not inlined into the page, since it's identical on every page and only the data differs. It isn't tied to any one block's `viewScript` (it needs to run on any page with these classes, not just pages containing a particular block), so it follows the same independent-loading pattern as [Block Variations](#block-variations-srcblock-variations) below: a `block.json` that exists purely as a wp-scripts build marker, never passed to `register_block_type()`, with the compiled script enqueued manually instead.

`gopublish_iteras_enqueue_post_access_correction()` does that enqueuing — hooked to `wp_footer` at an early priority (`5`, before core's own `wp_print_footer_scripts()` at its default priority `20`) rather than the usual `wp_enqueue_scripts`, because the data it needs is only complete at the very end of the request. It's still correctly printed in the same `wp_footer` pass; the tradeoff is that this can't use the conditional per-block-presence loading a real block's `viewScript` gets for free, so it checks the same condition manually — only enqueuing when `$gopublish_iteras_rendered_post_paywalls` is non-empty — and attaches the late-known data via `wp_add_inline_script()` (the same pattern `wp_localize_script()` uses under the hood), comparing each recorded post's paywall ID(s) against the visitor's own pass (`Iteras.paywallPass()`, which exposes the full per-paywall access-level map, not just presence) and toggling the classes to match, replicating `iteras_user_has_access()`'s own "sub, or user when that paywall allows it" logic (`iteras_paywall_allows_user_level()`).

## Plugin dependency

`gopublish-iteras-block.php` declares:

```
Requires Plugins: iteras
```

This is the WordPress 6.5+ plugin dependency header. Its effects:

- **Activation blocked** — WordPress will not activate this plugin if the Iteras plugin is inactive.
- **Deactivation blocked** — WordPress will not allow Iteras to be deactivated while this plugin is active.

The slug `iteras` matches the Iteras plugin's directory name (`wp-content/plugins/iteras/`).

## Iteras Paywall CTA settings (`inc/admin-settings.php`)

The Iteras Paywall block above only gates blocks placed *outside* `the_content`. The paywall a reader actually hits inline in an article — the "cut text and call-to-action box" Iteras itself inserts via `Iteras::potentially_paywall_content()` — is driven by a single, global TinyMCE field in Iteras' own settings (**Settings → ITERAS → Call-to-action content**), rendered through `do_shortcode()` for every paywalled post. There is no hook in Iteras' admin page or save handler to extend that screen directly (`Iteras_Admin::display_plugin_admin_page()` / `save_settings_form()` are hardcoded, no `do_action`/`apply_filters` anywhere in that path), so this plugin adds its own screen instead and lets a shortcode do the substitution — see below.

**Screen:** Settings → Iteras Paywall CTA (`add_options_page()`, slug `gopublish-iteras-paywall-cta`).

**Layout:** a single form, tabbed client-side (no page reloads — all tabs' fields exist in the DOM at once, toggled by a small inline script, so switching tabs never risks losing another tab's unsaved input). One tab per paywall from `Iteras::get_instance()->settings['paywalls']`, plus a "Default" tab first.

**Per tab (except Default):**
- **Label** — plain text, feeds `iteras_get_paywall_label_for_post()` / the block bindings source below.
- **Pattern** — a `<select>` of synced patterns (`wp_block` posts), filtered to the **"Paywall"** pattern category (auto-registered in the `wp_pattern_category` taxonomy on `init`) once at least one pattern is tagged with it; falls back to listing every published pattern until then, so the picker is never empty.
- **Registered users** — a checkbox, off by default. When checked, a visitor holding a registered-but-not-subscribed (`"user"` level) Iteras pass for *this* paywall is treated as having access, both by this plugin's own checks (`iteras_user_has_access()` and anything built on it) and, via the content-paywall override below, by Iteras' own article truncation. Every paywall otherwise keeps requiring a paying (`"sub"` level) subscription, matching Iteras' own built-in behaviour exactly.

**Storage:** a single option, `gopublish_iteras_paywall_cta_settings`:

```php
[
    'default'  => [ 'pattern_id' => 31736, 'label' => '' ],
    'paywalls' => [
        'jb4e3e8v9ajr' => [ 'label' => 'Privat', 'pattern_id' => 123, 'allow_user_level' => false ],
        // one entry per paywall someone has actually configured
    ],
]
```
Sanitized via `gopublish_iteras_cta_sanitize_settings()` (`absint()` on pattern IDs, `sanitize_text_field()` on labels and paywall-ID keys, cast to `bool` for `allow_user_level`).

**Migration:** a one-time `admin_init` check (`gopublish_iteras_cta_maybe_migrate()`) seeds `default.pattern_id` from whatever synced-pattern ID the *previous* "Iteras Ordering" screen's free-text fallback CTA already referenced (parsed out of `[synced_pattern id="…"]`), so upgrading doesn't blank out an existing fallback. It's a no-op once the new option exists. The old option (`gopublish_iteras_ordering_settings`) is left in place, untouched and unused.

## Content paywall override (`inc/content-paywall-override.php`)

Checking the "Registered users" box above only extends *this plugin's own* access checks (`iteras_user_has_access()`). It does nothing on its own about the actual article content — Iteras' own truncation (`Iteras::resolve_paywall_state()` / `potentially_paywall_content()` in `iteras-public.php`) is driven by its *private* `pass_authorized()` method, which only ever recognises `"sub"` level, with no hook to change how that specific check evaluates.

The only exposed extension point is Iteras' own `iteras_override_content_paywall` filter — `apply_filters( 'iteras_override_content_paywall', $paywall_ids, $post, $user_authorized, $settings )` — which can force `post_is_paywalled` to `false` outright (or swap which paywall IDs apply), but can't make Iteras' check itself accept `"user"` level. So instead of trying to fix that check, `gopublish_iteras_user_level_override_paywall()` unwalls the post entirely for the current visitor once `iteras_user_has_access( $paywall_ids )` (now itself "user"-level aware per paywall, see above) says they qualify — same OR-across-paywall-IDs semantics as everywhere else in this plugin, so a post with several paywall IDs only needs *one* of them to match.

**Caution if adding another filter on the same hook:** this filter chains — each callback receives the *previous* callback's return value as its first argument, not the original. A callback that ignores that argument and returns a hardcoded value (as `disable_paywall()` in the theme's `inc/custom.php` used to, before being fixed alongside this feature) silently discards every other plugin's override.

## `[iteras-paywall-cta]` shortcode (`inc/shortcode-paywall-cta.php`)

Registered on `init`. Meant to be dropped **once** into Iteras' own "Call-to-action content" field (see above) in place of any static text — it resolves per-post at render time:

1. Returns `''` immediately if Iteras isn't active, or if the current visitor already has access (`iteras_user_has_access_for_post()`) — nothing to sell.
2. Resolves `iteras_get_paywall_cta_pattern_id_for_post()`; if `null` (ambiguous/unmapped), falls back to `iteras_get_default_paywall_cta_pattern_id()`.
3. If a pattern ID was resolved either way, loads that `wp_block` post directly (`get_post()`, after checking it's a published `wp_block`) and renders its content with `do_shortcode( do_blocks( $pattern->post_content ) )` — `do_blocks()` alone won't process a shortcode embedded inside the pattern (e.g. `[iteras-ordering]`), hence the `do_shortcode()` wrapper. Otherwise returns `''`.

   (An earlier version routed this through the theme's own `[synced_pattern id="…"]` shortcode instead of rendering directly — that shortcode has since been removed as unused, since this was its only remaining caller.)

Supersedes an earlier `[iteras-ordering-for-post]` shortcode, which resolved a plain Iteras "ordering ID" string instead of a whole pattern. If that tag is still present in Iteras' call-to-action field, replace it with `[iteras-paywall-cta]`.

## Block Bindings (`inc/block-bindings.php`)

Two `register_block_bindings_source()` sources, both registered on `init` — the same mechanism (and the same pattern already used by `gopublish/featured-category` in Go:Publish Essentials) for binding a block's `content` attribute to dynamic text instead of a static string.

### `gopublish-iteras-block/paywall-label`

`uses_context: [ 'postId' ]`, since the resolved text depends on *which post* the bound block is on. Typical use is a paragraph inside a reusable "post meta" pattern:

```
<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"gopublish-iteras-block/paywall-label"}}}} -->
<p></p>
<!-- /wp:paragraph -->
```

Resolution order, via `gopublish_iteras_paywall_label_binding_callback()`:
1. `iteras_get_paywall_label_for_post( $post_id )` — the specific label for this post's paywall(s), if unambiguous.
2. `iteras_get_default_paywall_cta_label()` — the Default tab's label, if set.
3. `__( 'Subscription', 'olfi' )` — a hardcoded built-in fallback, deliberately using the `olfi` theme's own text domain/string so it picks up whatever translation ("Abonnement" in Danish) that theme already ships, with no new translation entries needed.

This binding always resolves to *some* text regardless of access status — it doesn't check `iteras_user_has_access_for_post()` itself. Whether the bound label is actually visible to a given reader is left to the theme/CSS (e.g. hiding it for posts the current visitor already has access to), matching how the original static label behaved.

### `gopublish-iteras-block/customer-id`

No `uses_context` — unlike the paywall label, this is purely visitor-specific, not post-specific; the same value applies regardless of which post/page the bound block is on. Callback is `gopublish_iteras_customer_id_binding_callback()`, a thin wrapper returning `iteras_get_customer_id() ?? ''`.

Unlike the paywall-label source, this one resolves to `''` (not a fallback string) when the visitor isn't logged in with a valid pass — see "Block Variations" below for how the "Iteras Customer ID" variation expects to be composed with static text around it.

### `gopublish-iteras-block/login-url`

No `uses_context` either. Callback is `gopublish_iteras_login_url_binding_callback()`, which resolves the URL to Iteras' own login page with a return-to parameter appended, so a visitor who logs in there lands back on whichever page they came from:

1. Reads `Iteras::get_instance()->settings['user_url']` — the **"User landing page"** setting, deliberately *not* `subscribe_url`. Iteras documents `subscribe_url` as serving both "logging in" and "becoming a paying subscriber"; sending an already-subscribed visitor there means landing on another subscribe pitch right after the paywall call-to-action they just saw. `user_url` is a separate, login-only destination for exactly this case — see the next point.
2. `user_url` is hidden by default on Iteras' own settings screen (an inline `style="display: none"` on its `<tr>`, `iteras/admin/views/admin.php:92`) even though it's fully functional — its value is already read and saved by Iteras' own `save_settings_form()`. `gopublish_iteras_reveal_user_url_field()` in `inc/admin-settings.php` reveals it with a small CSS override (`tr:has(#userurl) { display: table-row !important; }`) scoped to Iteras' settings screen only, via the `admin_head-settings_page_iteras` action — rather than editing Iteras' own file, which would get wiped on its next update.
3. If `user_url` is empty, returns `''` — no fallback to `subscribe_url`, since that would quietly reintroduce the "lands on a subscribe pitch" problem this is meant to avoid.
4. Otherwise calls `Iteras::return_to_page_shortcode( [ 'url' => $login_url ] )` directly — the exact public method backing Iteras' own `[iteras-return-to-page]` shortcode, reused rather than reimplementing its `iterasnext`/`urlencode( REQUEST_URI )` logic.

Note: calling that method on a URL with no existing query string triggers a harmless PHP "Undefined array key 'query'" warning inside Iteras' *own* code (debug-log only, the returned URL is still correct) — tried pre-empting it by appending a bare `?` first, but that flips a different branch of Iteras' own logic and produces a real bug (`?&iterasnext=...`), so it's left alone.

## Block Variations (`src/block-variations/`)

Three variations — editor-inserter-visible, pre-bound blocks, for dropping any Block Bindings source above into real post/page content, widgets, or anywhere else the block editor is used, not just theme patterns wired up in PHP.

### Why they're loaded independently

Block variations aren't tied to any one block's own lifecycle — registering them isn't the Iteras Paywall block's or Iteras Login Status block's concern, so they don't ride along with either block's compiled script. Instead:

- `src/block-variations/block.json` exists purely as a **build-tooling marker**. `wp-scripts` discovers build entry points by scanning for files literally named `block.json` (hardcoded in `@wordpress/scripts/utils/config.js`, not configurable) — this file gives it an `editorScript` entry to bundle from, but is **never passed to `register_block_type()`**, so it's not a real block as far as WordPress itself is concerned. (Verified: `WP_Block_Type_Registry::get_instance()->is_registered( 'gopublish-iteras-block/block-variations' )` is `false`.)
- `src/block-variations/index.js` is the real entry point — it just imports every variation file in the directory (currently `paywall-label.js`, `customer-id.js`, and `login-button.js`). Add a variation by creating a new file here and importing it from `index.js`.
- `gopublish_iteras_enqueue_block_variations()` in `gopublish-iteras-block.php` enqueues the compiled `build/block-variations/index.js` directly on `enqueue_block_editor_assets`, reading `index.asset.php` manually for its dependencies/version — since this never goes through `register_block_type()`, there's no automatic enqueue to rely on.

### "Iteras Paywall Label"

Bound to `gopublish-iteras-block/paywall-label`. Defaults to the `paywalled` className so it inherits the same CSS that hides it for readers who already have access (`.has-access .paywalled { display: none }`, see the theme's `src/scss/pages/post.scss`) — without that class, the resolved label would show to everyone, access or not, since the binding itself always resolves to *some* text.

### "Iteras Customer ID"

Bound to `gopublish-iteras-block/customer-id`. Resolves to the bare customer ID (e.g. `251848`) or an empty string — no fallback text, no auto-composed sentence. Since a block binding replaces a block's entire `content` attribute, it can't fill in just one word inside a sentence typed around it — to build something like "You're logged in with customer number 251848", wrap this block together with your own static text paragraph inside an **Iteras Login Status** block (set to "logged in"). That hides the whole group as one unit for logged-out visitors, rather than leaving the static text stranded with nothing after it.

### "Iteras Login Button"

A `core/button` variation, not a paragraph — binds only the `url` attribute to `gopublish-iteras-block/login-url`, leaving `text` completely free to edit. `core/button` is one of the block/attribute pairs WordPress core itself supports for bindings (`wp-includes/block-bindings.php`), confirmed before building on it. Because the dynamic part (the link) and the static part (the label) are two separate attributes here, there's none of the "bare value vs. full sentence" tradeoff the paragraph-based variations above have to deal with — type whatever button text makes sense ("Log in", "Already a subscriber?", etc.) and the link resolves independently.

## Building

```bash
cd wp-content/plugins/gopublish-iteras-block
npm install
npm run build   # production build → build/
npm run start   # watch mode for development
```

The build uses `@wordpress/scripts` ^32 with its zero-config block discovery. `npm run build` runs two steps in sequence:

1. `wp-scripts build` — compiles JavaScript and SCSS, copies `block.json` and PHP files from `src/` to `build/`.
2. `wp-scripts build-blocks-manifest` — scans `build/` for `block.json` files and emits `build/blocks-manifest.php` as a native PHP array (see [Block metadata manifest](#block-metadata-manifest)).

Both `build/` artefacts should be committed to the repository so the plugin can be installed without a build step.

## Block metadata manifest

WordPress 6.7 introduced `wp_register_block_metadata_collection()`, which allows a plugin to register all its block metadata from a pre-built PHP array instead of having WordPress open and JSON-parse each `block.json` file on every request.

`gopublish_iteras_block_init()` calls this before `register_block_type()`:

```php
if ( function_exists( 'wp_register_block_metadata_collection' ) ) {
    wp_register_block_metadata_collection(
        __DIR__ . '/build/blocks',
        __DIR__ . '/build/blocks-manifest.php'
    );
}
register_block_type( __DIR__ . '/build/blocks/iteras-paywall' );
```

The manifest file is generated by `wp-scripts build-blocks-manifest` and contains the full `block.json` contents as a PHP array. The `function_exists` guard means the call is safely skipped on WordPress < 6.7.

## Translations

All editor strings are in the `gopublish-iteras-block` text domain. The POT template is at `languages/gopublish-iteras-block.pot`.

To regenerate the POT file:

```bash
wp i18n make-pot . languages/gopublish-iteras-block.pot \
  --domain=gopublish-iteras-block \
  --exclude=node_modules \
  --skip-block-json
```

JavaScript translations are served at runtime via `wp_set_script_translations()`, which is called in `gopublish_iteras_block_init()` with the script handle `gopublish-iteras-block-iteras-paywall-editor-script`.

The `block.json` fields `title` and `description` are translatable via WordPress's built-in block metadata i18n mechanism, controlled by `"textdomain": "gopublish-iteras-block"` in `block.json`.
