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

The **Iteras Paywall** block is a dynamic container block. Editors place any inner blocks (text, images, embeds, etc.) inside it. On the frontend, `render.php` runs an access check on every page load:

- If the visitor holds a valid Iteras subscription pass → the inner blocks are rendered.
- If the visitor does not have access → the block outputs nothing.

Access is enforced entirely in PHP using the visitor's `iteraspass` cookie. No JavaScript is involved in the access check.

In the block editor, inner content is always visible. Editors with the `edit_pages` capability are unconditionally granted access, consistent with the `[iteras-if-logged-in]` shortcode's own behaviour.

## Plugin structure

```
gopublish-iteras-block/
├── gopublish-iteras-block.php        # Plugin entry point
├── inc/
│   └── functions-iteras.php          # Iteras access-check helpers
├── src/
│   └── blocks/
│       └── iteras-paywall/
│           ├── block.json            # Block metadata
│           ├── index.js              # Block registration entry point
│           ├── edit.js               # Editor component
│           ├── save.js               # Save function
│           ├── render.php            # Server-side render (access gate)
│           ├── editor.scss           # Editor-only styles
│           └── style.scss            # Frontend styles
├── build/                            # Compiled assets (generated, do not edit)
│   ├── blocks-manifest.php           # PHP block metadata manifest (WP 6.7+)
│   └── blocks/
│       └── iteras-paywall/
│           ├── block.json
│           ├── index.js
│           ├── index.css             # Compiled editor styles
│           ├── index-rtl.css
│           ├── style-index.css       # Compiled frontend styles
│           ├── style-index-rtl.css
│           ├── index.asset.php       # Dependency manifest
│           └── render.php
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

## Plugin dependency

`gopublish-iteras-block.php` declares:

```
Requires Plugins: iteras
```

This is the WordPress 6.5+ plugin dependency header. Its effects:

- **Activation blocked** — WordPress will not activate this plugin if the Iteras plugin is inactive.
- **Deactivation blocked** — WordPress will not allow Iteras to be deactivated while this plugin is active.

The slug `iteras` matches the Iteras plugin's directory name (`wp-content/plugins/iteras/`).

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
