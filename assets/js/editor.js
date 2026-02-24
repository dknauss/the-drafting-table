/* global wp */
/**
 * Featured Image Caption — Block Editor Preview
 *
 * Appends a read-only caption below core/post-featured-image in the block
 * editor canvas so authors can confirm the caption set in the Media Library
 * without leaving the post editor.
 *
 * The caption is sourced from the attachment's caption field (Media > Library
 * > [image] > Caption). It cannot be edited here; all edits happen in the
 * Media Library. The front end renders the same text via the PHP render_block
 * filter in functions.php.
 *
 * No build step required — uses wp.* globals already loaded by the editor.
 *
 * @package The_Drafting_Table
 */
( function ( wp ) {
	'use strict';

	var el                         = wp.element.createElement;
	var Fragment                   = wp.element.Fragment;
	var addFilter                  = wp.hooks.addFilter;
	var createHigherOrderComponent = wp.compose.createHigherOrderComponent;
	var useSelect                  = wp.data.useSelect;

	/**
	 * Higher-order component wrapping core/post-featured-image.
	 *
	 * Fetches the attached media record from the REST API via the editor data
	 * store and, when a caption is set, renders a <figcaption> below the block.
	 * The element is purely presentational inside the editor; the published page
	 * uses the PHP-injected <figcaption> inside the <figure> instead.
	 */
	var withFeaturedImageCaption = createHigherOrderComponent(
		function ( BlockEdit ) {
			return function ( props ) {

				// Only act on the featured image block.
				if ( 'core/post-featured-image' !== props.name ) {
					return el( BlockEdit, props );
				}

				var caption = useSelect(
					function ( select ) {
						var featuredId = select( 'core/editor' )
							.getEditedPostAttribute( 'featured_media' );

						if ( ! featuredId ) {
							return '';
						}

						/*
						 * Fetch the attachment with context:'view' so that
						 * caption.rendered contains the full HTML string,
						 * including any links added in the Media Library.
						 */
						var media = select( 'core' ).getMedia(
							featuredId,
							{ context: 'view' }
						);

						return ( media && media.caption && media.caption.rendered )
							? media.caption.rendered
							: '';
					},
					[]
				);

				return el(
					Fragment,
					null,
					el( BlockEdit, props ),
					caption
						? el(
							'figcaption',
							{ className: 'wp-element-caption' },
							/*
							 * "Fig. 1" label: real DOM element matching the PHP
							 * filter output so the editor preview is identical
							 * to the front end. Featured image is always Fig. 1.
							 */
							el( 'span', { className: 'featured-image-fig-label' }, 'Fig. 1' ),
							/*
							 * caption.rendered is WordPress-sanitized HTML from
							 * the REST API. A wrapper span is needed because
							 * React does not allow mixing children with
							 * dangerouslySetInnerHTML on the same element.
							 */
							el( 'span', { dangerouslySetInnerHTML: { __html: caption } } )
						  )
						: null
				);
			};
		},
		'withFeaturedImageCaption'
	);

	addFilter(
		'editor.BlockEdit',
		'the-drafting-table/featured-image-caption',
		withFeaturedImageCaption
	);

} )( window.wp );
