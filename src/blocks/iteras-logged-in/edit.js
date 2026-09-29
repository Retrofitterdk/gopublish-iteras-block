import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	useInnerBlocksProps,
	InspectorControls,
} from '@wordpress/block-editor';
import { PanelBody, RadioControl } from '@wordpress/components';
import './editor.scss';

export default function Edit( { attributes, setAttributes } ) {
	const { showWhen } = attributes;

	const blockProps = useBlockProps();
	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'gp-iteras-logged-in__content' },
		{
			template: [
				[
					'core/paragraph',
					{
						placeholder: __(
							'Add content for this login status…',
							'gopublish-iteras-block'
						),
					},
				],
			],
			templateLock: false,
		}
	);

	const modeLabel =
		showWhen === 'not-logged-in'
			? __( 'Not logged in', 'gopublish-iteras-block' )
			: __( 'Logged in', 'gopublish-iteras-block' );

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Iteras Login Status', 'gopublish-iteras-block' ) }
				>
					<RadioControl
						label={ __( 'Show content to', 'gopublish-iteras-block' ) }
						help={ __(
							'"Logged in" means the visitor holds a valid Iteras subscription pass of any kind — not a WordPress account login. To gate content by a specific paywall instead, use the Iteras Paywall block.',
							'gopublish-iteras-block'
						) }
						selected={ showWhen }
						options={ [
							{
								label: __(
									'Visitors who are logged in',
									'gopublish-iteras-block'
								),
								value: 'logged-in',
							},
							{
								label: __(
									'Visitors who are NOT logged in',
									'gopublish-iteras-block'
								),
								value: 'not-logged-in',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { showWhen: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<div className="gp-iteras-logged-in__label" aria-hidden="true">
					{ __( 'Iteras:', 'gopublish-iteras-block' ) } { modeLabel }
				</div>
				<div { ...innerBlocksProps } />
			</div>
		</>
	);
}
