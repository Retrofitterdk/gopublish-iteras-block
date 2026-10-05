import { useBlockProps, RichText } from '@wordpress/block-editor';

export default function save( { attributes } ) {
	const { text, url } = attributes;

	return (
		<button
			{ ...useBlockProps.save() }
			type="button"
			data-redirect={ url || undefined }
		>
			<RichText.Content value={ text } />
		</button>
	);
}
