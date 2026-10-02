<?php
/**
 * "Iteras Paywall CTA" settings screen.
 *
 * One tab per synced Iteras paywall (plus a "Default" tab), each with a
 * Label field (drives the eyebrow label, see inc/block-bindings.php) and a
 * dropdown of synced patterns (wp_block posts) whose rendered content
 * replaces the default paywall content for posts walled by that paywall.
 * Consumed by [iteras-paywall-cta], see inc/shortcode-paywall-cta.php.
 *
 * Supersedes the earlier "Iteras Ordering" screen (raw ordering-ID text
 * field + free-text fallback CTA), which required hand-written shortcode
 * syntax. A subscription manager now only ever picks an existing synced
 * pattern — built visually in the block editor, which can itself contain
 * an [iteras-ordering] shortcode, a pricing table, or anything else.
 *
 * @package GopublishIterasBlock
 * @since   0.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const GOPUBLISH_ITERAS_CTA_OPTION           = 'gopublish_iteras_paywall_cta_settings';
const GOPUBLISH_ITERAS_CTA_OPTION_GROUP     = 'gopublish_iteras_paywall_cta_group';
const GOPUBLISH_ITERAS_CTA_PAGE_SLUG        = 'gopublish-iteras-paywall-cta';
const GOPUBLISH_ITERAS_CTA_PATTERN_CAT_SLUG = 'paywall';

/**
 * Registers a "Paywall" synced-pattern category (the same wp_pattern_category
 * taxonomy the block editor's pattern Categories sidebar already uses), so
 * it's available to assign to patterns without an admin having to type it in
 * first. Idempotent — does nothing once the term exists.
 */
if ( ! function_exists( 'gopublish_iteras_cta_register_pattern_category' ) ) {
	function gopublish_iteras_cta_register_pattern_category() {
		if ( ! taxonomy_exists( 'wp_pattern_category' ) ) {
			return;
		}

		if ( ! term_exists( GOPUBLISH_ITERAS_CTA_PATTERN_CAT_SLUG, 'wp_pattern_category' ) ) {
			wp_insert_term(
				__( 'Paywall', 'gopublish-iteras-block' ),
				'wp_pattern_category',
				[ 'slug' => GOPUBLISH_ITERAS_CTA_PATTERN_CAT_SLUG ]
			);
		}
	}
}
add_action( 'init', 'gopublish_iteras_cta_register_pattern_category', 20 );

/**
 * One-time migration from the old "Iteras Ordering" screen: seeds the new
 * option's Default pattern from whatever synced pattern the old free-text
 * fallback CTA already pointed at (e.g. "[synced_pattern id="31736"]" —
 * historical format only; that shortcode itself no longer exists, this
 * just parses the leftover text to recover the pattern ID), so the site
 * doesn't lose its fallback CTA mid-rollout. No-ops once the new option
 * exists — this never overwrites deliberate configuration.
 */
if ( ! function_exists( 'gopublish_iteras_cta_maybe_migrate' ) ) {
	function gopublish_iteras_cta_maybe_migrate() {
		if ( get_option( GOPUBLISH_ITERAS_CTA_OPTION, false ) !== false ) {
			return;
		}

		$old                 = get_option( 'gopublish_iteras_ordering_settings', [] );
		$default_pattern_id = 0;

		if ( ! empty( $old['fallback_cta'] ) && preg_match( '/id="(\d+)"/', $old['fallback_cta'], $matches ) ) {
			$default_pattern_id = (int) $matches[1];
		}

		update_option(
			GOPUBLISH_ITERAS_CTA_OPTION,
			[
				'default'  => [
					'pattern_id' => $default_pattern_id,
					'label'      => '',
				],
				'paywalls' => [],
			]
		);
	}
}
add_action( 'admin_init', 'gopublish_iteras_cta_maybe_migrate', 5 );

if ( ! function_exists( 'gopublish_iteras_cta_register_settings' ) ) {
	function gopublish_iteras_cta_register_settings() {
		register_setting(
			GOPUBLISH_ITERAS_CTA_OPTION_GROUP,
			GOPUBLISH_ITERAS_CTA_OPTION,
			[
				'type'              => 'array',
				'sanitize_callback' => 'gopublish_iteras_cta_sanitize_settings',
			]
		);
	}
}
add_action( 'admin_init', 'gopublish_iteras_cta_register_settings' );

if ( ! function_exists( 'gopublish_iteras_cta_sanitize_settings' ) ) {
	function gopublish_iteras_cta_sanitize_settings( $input ): array {
		$output = [
			'default'  => [
				'pattern_id' => absint( $input['default']['pattern_id'] ?? 0 ),
				'label'      => sanitize_text_field( (string) ( $input['default']['label'] ?? '' ) ),
			],
			'paywalls' => [],
		];

		if ( ! empty( $input['paywalls'] ) && is_array( $input['paywalls'] ) ) {
			foreach ( $input['paywalls'] as $paywall_id => $config ) {
				$paywall_id = sanitize_text_field( (string) $paywall_id );

				if ( $paywall_id === '' || ! is_array( $config ) ) {
					continue;
				}

				$label      = sanitize_text_field( (string) ( $config['label'] ?? '' ) );
				$pattern_id = absint( $config['pattern_id'] ?? 0 );

				if ( $label !== '' || $pattern_id !== 0 ) {
					$output['paywalls'][ $paywall_id ] = [
						'label'      => $label,
						'pattern_id' => $pattern_id,
					];
				}
			}
		}

		return $output;
	}
}

/**
 * Published synced patterns (wp_block posts) tagged with the "Paywall"
 * pattern category, so the dropdown stays short as the site accumulates
 * unrelated patterns. Falls back to *all* published patterns (the same
 * query the theme's Tools -> Synced Patterns lookup page uses) when none
 * are tagged yet, so the picker is never empty during rollout.
 *
 * @return WP_Post[]
 */
if ( ! function_exists( 'gopublish_iteras_cta_get_patterns' ) ) {
	function gopublish_iteras_cta_get_patterns(): array {
		$base_args = [
			'post_type'      => 'wp_block',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		];

		if ( taxonomy_exists( 'wp_pattern_category' ) ) {
			$tagged = get_posts(
				$base_args + [
					'tax_query' => [
						[
							'taxonomy' => 'wp_pattern_category',
							'field'    => 'slug',
							'terms'    => GOPUBLISH_ITERAS_CTA_PATTERN_CAT_SLUG,
						],
					],
				]
			);

			if ( ! empty( $tagged ) ) {
				return $tagged;
			}
		}

		return get_posts( $base_args );
	}
}

if ( ! function_exists( 'gopublish_iteras_cta_render_pattern_select' ) ) {
	function gopublish_iteras_cta_render_pattern_select( string $name, int $selected_id ) {
		$patterns = gopublish_iteras_cta_get_patterns();
		?>
		<select name="<?php echo esc_attr( $name ); ?>">
			<option value="0"><?php esc_html_e( '— None —', 'gopublish-iteras-block' ); ?></option>
			<?php foreach ( $patterns as $pattern ) : ?>
				<option value="<?php echo esc_attr( $pattern->ID ); ?>" <?php selected( $selected_id, $pattern->ID ); ?>>
					<?php echo esc_html( $pattern->post_title !== '' ? $pattern->post_title : ( '#' . $pattern->ID ) ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php if ( empty( $patterns ) ) : ?>
			<p class="description">
				<?php esc_html_e( 'No synced patterns found yet. Create one in the block editor (Synced patterns), or check Tools -> Synced Patterns.', 'gopublish-iteras-block' ); ?>
			</p>
		<?php else : ?>
			<p class="description">
				<?php esc_html_e( 'Only showing patterns tagged with the "Paywall" pattern category, once at least one exists — otherwise every synced pattern is listed. Tag a pattern with "Paywall" (in its Categories panel, in the block editor) to keep this list short as more patterns get added for other purposes.', 'gopublish-iteras-block' ); ?>
			</p>
		<?php endif; ?>
		<?php
	}
}

if ( ! function_exists( 'gopublish_iteras_cta_add_menu' ) ) {
	function gopublish_iteras_cta_add_menu() {
		add_options_page(
			__( 'Iteras Paywall CTA', 'gopublish-iteras-block' ),
			__( 'Iteras Paywall CTA', 'gopublish-iteras-block' ),
			'manage_options',
			GOPUBLISH_ITERAS_CTA_PAGE_SLUG,
			'gopublish_iteras_cta_render_page'
		);
	}
}
add_action( 'admin_menu', 'gopublish_iteras_cta_add_menu' );

if ( ! function_exists( 'gopublish_iteras_cta_render_page' ) ) {
	function gopublish_iteras_cta_render_page() {
		$paywalls = class_exists( 'Iteras' ) ? ( Iteras::get_instance()->settings['paywalls'] ?? [] ) : [];
		$settings = get_option( GOPUBLISH_ITERAS_CTA_OPTION, [] );
		$default  = $settings['default'] ?? [];
		$configs  = $settings['paywalls'] ?? [];
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Iteras Paywall CTA', 'gopublish-iteras-block' ); ?></h1>
			<p>
				<?php esc_html_e( 'For each paywall, pick the synced pattern whose content should replace the default paywall content, and the label shown on the "ABONNEMENT" eyebrow above paywalled posts. Add the [iteras-paywall-cta] shortcode to Iteras\' own "Call-to-action content" box (once) to enable this.', 'gopublish-iteras-block' ); ?>
			</p>

			<?php if ( ! class_exists( 'Iteras' ) ) : ?>
				<p><em><?php esc_html_e( 'The Iteras plugin is not active — only the Default tab is usable until it is.', 'gopublish-iteras-block' ); ?></em></p>
			<?php elseif ( empty( $paywalls ) ) : ?>
				<p><em><?php esc_html_e( 'No paywalls found. Configure and synchronize paywalls in the Iteras plugin settings to get per-paywall tabs here.', 'gopublish-iteras-block' ); ?></em></p>
			<?php endif; ?>

			<h2 class="nav-tab-wrapper">
				<a href="#giteras-cta-tab-default" class="nav-tab nav-tab-active" data-giteras-cta-tab>
					<?php esc_html_e( 'Default', 'gopublish-iteras-block' ); ?>
				</a>
				<?php foreach ( $paywalls as $paywall ) :
					$paywall_id = $paywall['paywall_id'] ?? '';
					if ( $paywall_id === '' ) {
						continue;
					}
					?>
					<a href="#giteras-cta-tab-<?php echo esc_attr( $paywall_id ); ?>" class="nav-tab" data-giteras-cta-tab>
						<?php echo esc_html( $paywall['name'] ?: $paywall_id ); ?>
					</a>
				<?php endforeach; ?>
			</h2>

			<form method="post" action="options.php">
				<?php settings_fields( GOPUBLISH_ITERAS_CTA_OPTION_GROUP ); ?>

				<div id="giteras-cta-tab-default" class="giteras-cta-tab-panel" style="display:block;">
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><label><?php esc_html_e( 'Label', 'gopublish-iteras-block' ); ?></label></th>
							<td>
								<input
									type="text"
									class="regular-text"
									name="<?php echo esc_attr( GOPUBLISH_ITERAS_CTA_OPTION ); ?>[default][label]"
									value="<?php echo esc_attr( $default['label'] ?? '' ); ?>"
									placeholder="<?php esc_attr_e( 'Abonnement', 'gopublish-iteras-block' ); ?>"
								>
								<p class="description"><?php esc_html_e( 'Leave blank to use the built-in "Abonnement" text.', 'gopublish-iteras-block' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label><?php esc_html_e( 'Pattern', 'gopublish-iteras-block' ); ?></label></th>
							<td>
								<?php
								gopublish_iteras_cta_render_pattern_select(
									GOPUBLISH_ITERAS_CTA_OPTION . '[default][pattern_id]',
									(int) ( $default['pattern_id'] ?? 0 )
								);
								?>
								<p class="description"><?php esc_html_e( 'Shown whenever a post\'s paywalls don\'t resolve to exactly one specific pattern below (none configured, or more than one that disagree).', 'gopublish-iteras-block' ); ?></p>
							</td>
						</tr>
					</table>
				</div>

				<?php foreach ( $paywalls as $paywall ) :
					$paywall_id = $paywall['paywall_id'] ?? '';
					if ( $paywall_id === '' ) {
						continue;
					}
					$config = $configs[ $paywall_id ] ?? [];
					?>
					<div id="giteras-cta-tab-<?php echo esc_attr( $paywall_id ); ?>" class="giteras-cta-tab-panel" style="display:none;">
						<p class="description">
							<?php
							printf(
								/* translators: %s: paywall ID */
								esc_html__( 'Paywall ID: %s', 'gopublish-iteras-block' ),
								'<code>' . esc_html( $paywall_id ) . '</code>'
							);
							?>
						</p>
						<table class="form-table" role="presentation">
							<tr>
								<th scope="row"><label><?php esc_html_e( 'Label', 'gopublish-iteras-block' ); ?></label></th>
								<td>
									<input
										type="text"
										class="regular-text"
										name="<?php echo esc_attr( GOPUBLISH_ITERAS_CTA_OPTION ); ?>[paywalls][<?php echo esc_attr( $paywall_id ); ?>][label]"
										value="<?php echo esc_attr( $config['label'] ?? '' ); ?>"
									>
								</td>
							</tr>
							<tr>
								<th scope="row"><label><?php esc_html_e( 'Pattern', 'gopublish-iteras-block' ); ?></label></th>
								<td>
									<?php
									gopublish_iteras_cta_render_pattern_select(
										GOPUBLISH_ITERAS_CTA_OPTION . '[paywalls][' . $paywall_id . '][pattern_id]',
										(int) ( $config['pattern_id'] ?? 0 )
									);
									?>
								</td>
							</tr>
						</table>
					</div>
				<?php endforeach; ?>

				<?php submit_button(); ?>
			</form>
		</div>
		<script>
		( function () {
			var tabs = document.querySelectorAll( '[data-giteras-cta-tab]' );
			tabs.forEach( function ( tab ) {
				tab.addEventListener( 'click', function ( event ) {
					event.preventDefault();
					var targetId = tab.getAttribute( 'href' ).slice( 1 );

					tabs.forEach( function ( t ) {
						t.classList.remove( 'nav-tab-active' );
					} );
					tab.classList.add( 'nav-tab-active' );

					document.querySelectorAll( '.giteras-cta-tab-panel' ).forEach( function ( panel ) {
						panel.style.display = ( panel.id === targetId ) ? 'block' : 'none';
					} );
				} );
			} );
		} )();
		</script>
		<?php
	}
}
