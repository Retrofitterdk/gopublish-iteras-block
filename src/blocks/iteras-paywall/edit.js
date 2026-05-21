import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	useInnerBlocksProps,
	InspectorControls,
} from '@wordpress/block-editor';
import { PanelBody, CheckboxControl, Notice } from '@wordpress/components';
import './editor.scss';

const { paywalls = [] } = window.gopublishIterasBlockData || {};

export default function Edit( { attributes, setAttributes } ) {
	const { paywallIds } = attributes;

	const blockProps = useBlockProps();
	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'gp-iteras-paywall__content' },
		{
			template: [
				[
					'core/paragraph',
					{
						placeholder: __(
							'Add content for Iteras subscribers…',
							'gopublish-iteras-block'
						),
					},
				],
			],
			templateLock: false,
		}
	);

	function togglePaywall( id, checked ) {
		if ( checked ) {
			setAttributes( { paywallIds: [ ...paywallIds, id ] } );
		} else {
			setAttributes( {
				paywallIds: paywallIds.filter( ( p ) => p !== id ),
			} );
		}
	}

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Iteras Paywall', 'gopublish-iteras-block' ) }
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
									'Select which paywalls grant access to this content. Leave all unchecked to use the paywalls assigned to the current post.',
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
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<div className="gp-iteras-paywall__label" aria-hidden="true">
					{ __( 'Iteras Paywall', 'gopublish-iteras-block' ) }
				</div>
				<div { ...innerBlocksProps } />
			</div>
		</>
	);
}
