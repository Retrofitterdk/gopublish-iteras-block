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

The plugin ships two dynamic container blocks — **Iteras Paywall** and **Iteras Login Status** — plus a settings screen, a shortcode, and a Block Bindings source for driving Iteras' own inline paywall (see further down). Both blocks work the same way: editors place any inner blocks (text, images, embeds, etc.) inside them, and on the frontend `render.php` runs an access check on every page load:

- If the check passes → the inner blocks are rendered.
- If it doesn't → the block outputs nothing.

They differ in *what* they check: Iteras Paywall gates by a specific paywall (defaulting to the current post's own assigned paywall(s)); Iteras Login Status answers the simpler, unscoped question "does this visitor hold a valid Iteras pass for anything at all" — see each block's own section below for the distinction and when to use which.

Access is enforced entirely in PHP using the visitor's `iteraspass` cookie. No JavaScript is involved in the access check.

In the block editor, inner content is always visible. Editors with the `edit_pages` capability are unconditionally granted access, consistent with the `[iteras-if-logged-in]` shortcode's own behaviour.

## Plugin structure

```
gopublish-iteras-block/
├── gopublish-iteras-block.php        # Plugin entry point
├── inc/
│   ├── functions-iteras.php          # Iteras access-check + CTA/label resolver helpers
│   ├── admin-settings.php            # "Iteras Paywall CTA" settings screen (admin-only)
│   ├── shortcode-paywall-cta.php     # [iteras-paywall-cta] shortcode
│   ├── block-bindings.php            # "paywall-label" + "customer-id" Block Bindings sources
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
│   │   └── iteras-logged-in/         # Same file layout as iteras-paywall/ above
│   │       └── ...                   # (block.json, index.js, edit.js, save.js, render.php, editor.scss, style.scss)
│   └── block-variations/             # See "Block Variations" below — not a real block
│       ├── block.json                # Build-tooling marker only, never register_block_type()'d
│       ├── index.js                  # Shared entry point — imports every variation file below
│       ├── paywall-label.js          # "Iteras Paywall Label" core/paragraph variation
│       └── customer-id.js            # "Iteras Customer ID" core/paragraph variation
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
│   │   └── iteras-logged-in/         # Same file layout as iteras-paywall/ above
│   └── block-variations/
│       ├── block.json                # Copied from src/ as-is, still inert
│       ├── index.js                  # Compiled bundle of all variation files
│       └── index.asset.php           # Dependency manifest, read manually (see "Block Variations")
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
**Type:** Dynamic (server-side rendered via `render.php`)

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

Only once both of those are ruled out does it evaluate `iteras_user_has_access()` and compare against `showWhen`.

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
4. Confirms at least one paywall ID in `$restriction` has `access_level = "sub"`.

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

**Per tab:**
- **Label** — plain text, feeds `iteras_get_paywall_label_for_post()` / the block bindings source below.
- **Pattern** — a `<select>` of synced patterns (`wp_block` posts), filtered to the **"Paywall"** pattern category (auto-registered in the `wp_pattern_category` taxonomy on `init`) once at least one pattern is tagged with it; falls back to listing every published pattern until then, so the picker is never empty.

**Storage:** a single option, `gopublish_iteras_paywall_cta_settings`:

```php
[
    'default'  => [ 'pattern_id' => 31736, 'label' => '' ],
    'paywalls' => [
        'jb4e3e8v9ajr' => [ 'label' => 'Privat', 'pattern_id' => 123 ],
        // one entry per paywall someone has actually configured
    ],
]
```
Sanitized via `gopublish_iteras_cta_sanitize_settings()` (`absint()` on pattern IDs, `sanitize_text_field()` on labels and paywall-ID keys).

**Migration:** a one-time `admin_init` check (`gopublish_iteras_cta_maybe_migrate()`) seeds `default.pattern_id` from whatever synced-pattern ID the *previous* "Iteras Ordering" screen's free-text fallback CTA already referenced (parsed out of `[synced_pattern id="…"]`), so upgrading doesn't blank out an existing fallback. It's a no-op once the new option exists. The old option (`gopublish_iteras_ordering_settings`) is left in place, untouched and unused.

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

## Block Variations (`src/block-variations/`)

Two `core/paragraph` variations — editor-inserter-visible, pre-bound paragraphs, for dropping either Block Bindings source above into real post/page content, widgets, or anywhere else the block editor is used, not just theme patterns wired up in PHP.

### Why they're loaded independently

Block variations aren't tied to any one block's own lifecycle — registering them isn't the Iteras Paywall block's or Iteras Login Status block's concern, so they don't ride along with either block's compiled script. Instead:

- `src/block-variations/block.json` exists purely as a **build-tooling marker**. `wp-scripts` discovers build entry points by scanning for files literally named `block.json` (hardcoded in `@wordpress/scripts/utils/config.js`, not configurable) — this file gives it an `editorScript` entry to bundle from, but is **never passed to `register_block_type()`**, so it's not a real block as far as WordPress itself is concerned. (Verified: `WP_Block_Type_Registry::get_instance()->is_registered( 'gopublish-iteras-block/block-variations' )` is `false`.)
- `src/block-variations/index.js` is the real entry point — it just imports every variation file in the directory (currently `paywall-label.js` and `customer-id.js`). Add a variation by creating a new file here and importing it from `index.js`.
- `gopublish_iteras_enqueue_block_variations()` in `gopublish-iteras-block.php` enqueues the compiled `build/block-variations/index.js` directly on `enqueue_block_editor_assets`, reading `index.asset.php` manually for its dependencies/version — since this never goes through `register_block_type()`, there's no automatic enqueue to rely on.

### "Iteras Paywall Label"

Bound to `gopublish-iteras-block/paywall-label`. Defaults to the `paywalled` className so it inherits the same CSS that hides it for readers who already have access (`.has-access .paywalled { display: none }`, see the theme's `src/scss/pages/post.scss`) — without that class, the resolved label would show to everyone, access or not, since the binding itself always resolves to *some* text.

### "Iteras Customer ID"

Bound to `gopublish-iteras-block/customer-id`. Resolves to the bare customer ID (e.g. `251848`) or an empty string — no fallback text, no auto-composed sentence. Since a block binding replaces a block's entire `content` attribute, it can't fill in just one word inside a sentence typed around it — to build something like "You're logged in with customer number 251848", wrap this block together with your own static text paragraph inside an **Iteras Login Status** block (set to "logged in"). That hides the whole group as one unit for logged-out visitors, rather than leaving the static text stranded with nothing after it.

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
