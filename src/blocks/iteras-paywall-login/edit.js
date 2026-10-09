import { __, sprintf } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	CheckboxControl,
	RadioControl,
	Notice,
} from '@wordpress/components';
import './editor.scss';

const { paywalls = [] } = window.gopublishIterasBlockData || {};

export default function Edit( { attributes, setAttributes } ) {
	const { paywallIds, accessLevel } = attributes;

	const blockProps = useBlockProps();

	function togglePaywall( id, checked ) {
		if ( checked ) {
			setAttributes( { paywallIds: [ ...paywallIds, id ] } );
		} else {
			setAttributes( {
				paywallIds: paywallIds.filter( ( p ) => p !== id ),
			} );
		}
	}

	// Which paywalls this block actually applies to, matching render.php's
	// own fallback: none selected means every configured paywall.
	const effectivePaywalls =
		paywallIds.length > 0
			? paywalls.filter( ( p ) => paywallIds.includes( p.paywall_id ) )
			: paywalls;

	const missingUserLevel = effectivePaywalls.filter(
		( p ) => ! p.allow_user_level
	);

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __(
						'Iteras Paywall Login',
						'gopublish-iteras-block'
					) }
				>
					{ paywalls.length === 0 ? (
						<Notice status="warning" isDismissible={ false }>
							{ __(
								'No paywalls found. Configure paywalls in the Iteras plugin settings.',
								'gopublish-iteras-block'
							) }
						</Notice>
					) : (
						<>
							<p className="components-base-control__help">
								{ __(
									'Select which paywalls this login/subscribe form applies to. Leave all unchecked to apply to every configured paywall (matches the [iteras-paywall-login] shortcode\'s own default).',
									'gopublish-iteras-block'
								) }
							</p>
							{ paywalls.map( ( { paywall_id, name } ) => (
								<CheckboxControl
									key={ paywall_id }
									__nextHasNoMarginBottom
									label={ name }
									checked={ paywallIds.includes(
										paywall_id
									) }
									onChange={ ( checked ) =>
										togglePaywall( paywall_id, checked )
									}
								/>
							) ) }
						</>
					) }
					<RadioControl
						label={ __( 'Access level', 'gopublish-iteras-block' ) }
						help={ __(
							'"Subscribers" matches Iteras\' own default. "Registered users" also accepts a free, non-paying sign-up — only useful for a paywall that has "Registered users" enabled on the Iteras Paywall CTA settings screen.',
							'gopublish-iteras-block'
						) }
						selected={ accessLevel }
						options={ [
							{
								label: __(
									'Subscribers (default)',
									'gopublish-iteras-block'
								),
								value: '',
							},
							{
								label: __(
									'Registered users (free)',
									'gopublish-iteras-block'
								),
								value: 'user',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { accessLevel: value } )
						}
					/>
					{ accessLevel === 'user' &&
						missingUserLevel.length > 0 && (
							<Notice status="warning" isDismissible={ false }>
								{ sprintf(
									/* translators: %s: comma-separated list of paywall names */
									__(
										'"Registered users" isn\'t enabled for: %s. Visitors who sign up here as a registered user still won\'t be granted access, until that\'s turned on for those paywalls on the Iteras Paywall CTA settings screen.',
										'gopublish-iteras-block'
									),
									missingUserLevel
										.map( ( p ) => p.name || p.paywall_id )
										.join( ', ' )
								) }
							</Notice>
						) }
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<div className="gp-iteras-paywall-login__label">
					{ __( 'Iteras Paywall Login', 'gopublish-iteras-block' ) }
				</div>
				<p className="gp-iteras-paywall-login__summary">
					{ accessLevel === 'user'
						? __(
								'Registered users and subscribers',
								'gopublish-iteras-block'
						  )
						: __( 'Subscribers only', 'gopublish-iteras-block' ) }
					{ paywallIds.length > 0
						? sprintf(
								/* translators: %s: comma-separated list of paywall names */
								__( ' — %s', 'gopublish-iteras-block' ),
								effectivePaywalls
									.map( ( p ) => p.name || p.paywall_id )
									.join( ', ' )
						  )
						: __(
								' — every configured paywall',
								'gopublish-iteras-block'
						  ) }
				</p>
			</div>
		</>
	);
}
