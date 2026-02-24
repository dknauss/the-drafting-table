/**
 * Theme Assets Editor Rewrite
 *
 * This script rewrites URLs in block attributes that start with 'theme:./'
 * to point to the correct theme assets.
 * It allows blocks in templates to reference theme assets using relative paths in the editor.
**/
( function ( wp ) {
	const base = window.THEME_ASSETS_BASE_URL || '';
	const prefix = 'theme:./';

	if ( ! wp?.hooks || ! wp?.compose ) return;

	const rewrite = ( url ) => {
		if ( typeof url === 'string' && url.startsWith( prefix ) ) {
			const path = url.slice( prefix.length );
			return base + '/' + path;
		}
		return null;
	};

	wp.hooks.addFilter(
		'editor.BlockEdit',
		'theme-assets/rewrite-image-urls',
		wp.compose.createHigherOrderComponent( ( BlockEdit ) => ( props ) => {
			const { attributes } = props;
			const updates = {};

			// Check common image URL attributes
			if ( rewrite( attributes?.url ) ) {
				updates.url = rewrite( attributes.url );
			}
			if ( rewrite( attributes?.mediaUrl ) ) {
				updates.mediaUrl = rewrite( attributes.mediaUrl );
			}
			if ( rewrite( attributes?.backgroundImage ) ) {
				updates.backgroundImage = rewrite( attributes.backgroundImage );
			}

			// Pass rewritten URLs to BlockEdit for display only, without persisting changes
			if ( Object.keys( updates ).length > 0 ) {
				const modifiedProps = {
					...props,
					attributes: {
						...attributes,
						...updates,
					},
				};
				return wp.element.createElement( BlockEdit, modifiedProps );
			}

			return wp.element.createElement( BlockEdit, props );
		}, 'withThemeAssetsRewrite' )
	);

} )( window.wp );
