import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import {
	useBlockProps,
	RichText,
	BlockControls,
	__experimentalLinkControl as LinkControl,
} from '@wordpress/block-editor';
import { ToolbarGroup, ToolbarButton, Popover } from '@wordpress/components';
import './editor.scss';

// Plain dashicon slugs, not @wordpress/icons imports: that package isn't
// installed as a local devDependency in this plugin (unlike @wordpress/
// block-editor and friends, which wp-scripts' dependency-extraction can
// resolve as externals without the package being present locally),
// so importing from it fails at build time. ToolbarButton's `icon` prop
// accepts a dashicon slug string directly, avoiding the need for it.

export default function Edit( { attributes, setAttributes } ) {
	const { text, url } = attributes;
	const [ isEditingURL, setIsEditingURL ] = useState( false );

	const blockProps = useBlockProps();

	return (
		<>
			<BlockControls group="block">
				<ToolbarGroup>
					<ToolbarButton
						icon="admin-links"
						title={ __(
							'Redirect after logout',
							'gopublish-iteras-block'
						) }
						isActive={ !! url }
						onClick={ () => setIsEditingURL( true ) }
					/>
					{ !! url && (
						<ToolbarButton
							icon="editor-unlink"
							title={ __( 'Remove link', 'gopublish-iteras-block' ) }
							onClick={ () => setAttributes( { url: '' } ) }
						/>
					) }
				</ToolbarGroup>
			</BlockControls>
			{ isEditingURL && (
				<Popover
					placement="bottom"
					onClose={ () => setIsEditingURL( false ) }
				>
					<LinkControl
						value={ { url } }
						onChange={ ( newValue ) =>
							setAttributes( { url: newValue.url || '' } )
						}
						onRemove={ () => {
							setAttributes( { url: '' } );
							setIsEditingURL( false );
						} }
					/>
				</Popover>
			) }
			<RichText
				{ ...blockProps }
				tagName="button"
				type="button"
				allowedFormats={ [] }
				value={ text }
				onChange={ ( value ) => setAttributes( { text: value } ) }
				placeholder={ __( 'Log out', 'gopublish-iteras-block' ) }
			/>
		</>
	);
}
