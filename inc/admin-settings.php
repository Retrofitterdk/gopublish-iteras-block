<?php
/**
 * "Iteras Ordering" settings screen.
 *
 * Lets an admin map each Iteras paywall ID to the Iteras ordering-form ID
 * that sells the subscription unlocking it, plus a fallback call-to-action
 * for posts whose paywall IDs don't resolve to exactly one ordering ID.
 * Consumed by [iteras-ordering-for-post], see inc/shortcode-ordering.php.
 *
 * @package GopublishIterasBlock
 * @since   0.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const GOPUBLISH_ITERAS_ORDERING_OPTION      = 'gopublish_iteras_ordering_settings';
const GOPUBLISH_ITERAS_ORDERING_OPTION_GROUP = 'gopublish_iteras_ordering_group';
const GOPUBLISH_ITERAS_ORDERING_PAGE_SLUG    = 'gopublish-iteras-ordering';

if ( ! function_exists( 'gopublish_iteras_ordering_register_settings' ) ) {
	function gopublish_iteras_ordering_register_settings() {
		register_setting(
			GOPUBLISH_ITERAS_ORDERING_OPTION_GROUP,
			GOPUBLISH_ITERAS_ORDERING_OPTION,
			[
				'type'              => 'array',
				'sanitize_callback' => 'gopublish_iteras_ordering_sanitize_settings',
			]
		);

		add_settings_section(
			'gopublish_iteras_ordering_main',
			'',
			'__return_false',
			GOPUBLISH_ITERAS_ORDERING_PAGE_SLUG
		);

		add_settings_field(
			'mapping',
			__( 'Paywall → Ordering ID mapping', 'gopublish-iteras-block' ),
			'gopublish_iteras_ordering_render_mapping_field',
			GOPUBLISH_ITERAS_ORDERING_PAGE_SLUG,
			'gopublish_iteras_ordering_main'
		);

		add_settings_field(
			'fallback_cta',
			__( 'Fallback call-to-action', 'gopublish-iteras-block' ),
			'gopublish_iteras_ordering_render_fallback_field',
			GOPUBLISH_ITERAS_ORDERING_PAGE_SLUG,
			'gopublish_iteras_ordering_main'
		);
	}
}
add_action( 'admin_init', 'gopublish_iteras_ordering_register_settings' );

if ( ! function_exists( 'gopublish_iteras_ordering_sanitize_settings' ) ) {
	function gopublish_iteras_ordering_sanitize_settings( $input ): array {
		$mapping = [];

		if ( ! empty( $input['mapping'] ) && is_array( $input['mapping'] ) ) {
			foreach ( $input['mapping'] as $paywall_id => $ordering_id ) {
				$paywall_id  = sanitize_text_field( (string) $paywall_id );
				$ordering_id = sanitize_text_field( (string) $ordering_id );

				if ( $paywall_id !== '' && $ordering_id !== '' ) {
					$mapping[ $paywall_id ] = $ordering_id;
				}
			}
		}

		return [
			'mapping'      => $mapping,
			'fallback_cta' => wp_kses_post( $input['fallback_cta'] ?? '' ),
		];
	}
}

if ( ! function_exists( 'gopublish_iteras_ordering_render_mapping_field' ) ) {
	function gopublish_iteras_ordering_render_mapping_field() {
		if ( ! class_exists( 'Iteras' ) ) {
			echo '<p>' . esc_html__( 'The Iteras plugin is not active.', 'gopublish-iteras-block' ) . '</p>';
			return;
		}

		$paywalls = Iteras::get_instance()->settings['paywalls'] ?? [];
		$mapping  = get_option( GOPUBLISH_ITERAS_ORDERING_OPTION, [] )['mapping'] ?? [];

		if ( empty( $paywalls ) ) {
			echo '<p>' . esc_html__( 'No paywalls found. Configure and synchronize paywalls in the Iteras plugin settings first.', 'gopublish-iteras-block' ) . '</p>';
			return;
		}
		?>
		<table class="widefat" style="max-width: 640px;">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Paywall', 'gopublish-iteras-block' ); ?></th>
					<th><?php esc_html_e( 'Ordering ID', 'gopublish-iteras-block' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $paywalls as $paywall ) :
					$paywall_id = $paywall['paywall_id'] ?? '';
					if ( $paywall_id === '' ) {
						continue;
					}
					?>
					<tr>
						<td>
							<?php echo esc_html( $paywall['name'] ?? '' ); ?>
							<br><span class="description">ID: <?php echo esc_html( $paywall_id ); ?></span>
						</td>
						<td>
							<input
								type="text"
								class="regular-text"
								name="<?php echo esc_attr( GOPUBLISH_ITERAS_ORDERING_OPTION ); ?>[mapping][<?php echo esc_attr( $paywall_id ); ?>]"
								value="<?php echo esc_attr( $mapping[ $paywall_id ] ?? '' ); ?>"
							>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p class="description">
			<?php esc_html_e( 'The ordering-form ID from Iteras — the "orderingid" you\'d pass to [iteras-ordering] — that sells the subscription granting access via this paywall. Leave blank if none.', 'gopublish-iteras-block' ); ?>
		</p>
		<?php
	}
}

if ( ! function_exists( 'gopublish_iteras_ordering_render_fallback_field' ) ) {
	function gopublish_iteras_ordering_render_fallback_field() {
		$fallback_cta = get_option( GOPUBLISH_ITERAS_ORDERING_OPTION, [] )['fallback_cta'] ?? '';
		?>
		<textarea
			name="<?php echo esc_attr( GOPUBLISH_ITERAS_ORDERING_OPTION ); ?>[fallback_cta]"
			rows="6"
			class="large-text code"
		><?php echo esc_textarea( $fallback_cta ); ?></textarea>
		<p class="description">
			<?php esc_html_e( 'Shown by [iteras-ordering-for-post] when a post\'s paywalls map to zero or more than one ordering ID, so a single correct offer can\'t be determined automatically. HTML and shortcodes are allowed.', 'gopublish-iteras-block' ); ?>
		</p>
		<?php
	}
}

if ( ! function_exists( 'gopublish_iteras_ordering_add_menu' ) ) {
	function gopublish_iteras_ordering_add_menu() {
		add_options_page(
			__( 'Iteras Ordering', 'gopublish-iteras-block' ),
			__( 'Iteras Ordering', 'gopublish-iteras-block' ),
			'manage_options',
			GOPUBLISH_ITERAS_ORDERING_PAGE_SLUG,
			'gopublish_iteras_ordering_render_page'
		);
	}
}
add_action( 'admin_menu', 'gopublish_iteras_ordering_add_menu' );

if ( ! function_exists( 'gopublish_iteras_ordering_render_page' ) ) {
	function gopublish_iteras_ordering_render_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Iteras Ordering', 'gopublish-iteras-block' ); ?></h1>
			<p>
				<?php esc_html_e( 'Connects each Iteras paywall to the ordering form that sells the subscription unlocking it. Used by the [iteras-ordering-for-post] shortcode — add that shortcode to the Iteras "Call-to-action content" paywall box to show the right offer automatically.', 'gopublish-iteras-block' ); ?>
			</p>
			<form method="post" action="options.php">
				<?php
				settings_fields( GOPUBLISH_ITERAS_ORDERING_OPTION_GROUP );
				do_settings_sections( GOPUBLISH_ITERAS_ORDERING_PAGE_SLUG );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
