<?php
/**
 * Title: Post Meta Row
 * Slug: the-drafting-table/post-meta-row
 * Categories: the-drafting-table
 * Description: Post metadata row showing categories, tags, author, and last-updated date. Intended for single post templates.
 * Inserter: false
 *
 * @package The_Drafting_Table
 */

?>
<!-- wp:group {"style":{"spacing":{"margin":{"top":"0"},"padding":{"top":"var:preset|spacing|large","bottom":"var:preset|spacing|large","left":"2rem","right":"2rem"}}},"layout":{"type":"constrained","contentSize":"720px"}} -->
<div class="wp-block-group" style="margin-top:0;padding-top:var(--wp--preset--spacing--large);padding-right:2rem;padding-bottom:var(--wp--preset--spacing--large);padding-left:2rem"><!-- wp:group {"className":"meta-row","layout":{"type":"flex","justifyContent":"space-between","flexWrap":"nowrap"}} -->
<div class="wp-block-group meta-row"><!-- wp:group {"className":"meta-pair meta-categories","layout":{"type":"default"}} -->
<div class="wp-block-group meta-pair meta-categories"><!-- wp:paragraph {"textColor":"secondary","fontFamily":"condensed","style":{"typography":{"fontStyle":"normal","fontWeight":"600","fontSize":"0.5625rem","letterSpacing":"0.35em","textTransform":"uppercase"}}} -->
<p class="has-secondary-color has-text-color has-condensed-font-family" style="font-size:0.5625rem;font-style:normal;font-weight:600;letter-spacing:0.35em;text-transform:uppercase">Categories</p>
<!-- /wp:paragraph -->

<!-- wp:post-terms {"term":"category","textColor":"primary","fontFamily":"primary","style":{"typography":{"fontSize":"0.6875rem","letterSpacing":"0.1em"}}} /--></div>
<!-- /wp:group -->

<!-- wp:group {"className":"meta-pair meta-tags","layout":{"type":"default"}} -->
<div class="wp-block-group meta-pair meta-tags"><!-- wp:paragraph {"textColor":"secondary","fontFamily":"condensed","style":{"typography":{"fontStyle":"normal","fontWeight":"600","fontSize":"0.5625rem","letterSpacing":"0.35em","textTransform":"uppercase"}}} -->
<p class="has-secondary-color has-text-color has-condensed-font-family" style="font-size:0.5625rem;font-style:normal;font-weight:600;letter-spacing:0.35em;text-transform:uppercase">Tags</p>
<!-- /wp:paragraph -->

<!-- wp:post-terms {"term":"post_tag","textColor":"border-dark","fontFamily":"primary","style":{"typography":{"fontSize":"0.6875rem","letterSpacing":"0.1em"}}} /--></div>
<!-- /wp:group -->

<!-- wp:group {"className":"meta-pair meta-author","layout":{"type":"default"}} -->
<div class="wp-block-group meta-pair meta-author"><!-- wp:paragraph {"textColor":"secondary","fontFamily":"condensed","style":{"typography":{"fontStyle":"normal","fontWeight":"600","fontSize":"0.5625rem","letterSpacing":"0.35em","textTransform":"uppercase"}}} -->
<p class="has-secondary-color has-text-color has-condensed-font-family" style="font-size:0.5625rem;font-style:normal;font-weight:600;letter-spacing:0.35em;text-transform:uppercase">Author</p>
<!-- /wp:paragraph -->

<!-- wp:post-author-name {"textColor":"border-dark","fontFamily":"primary","style":{"typography":{"fontSize":"0.6875rem","letterSpacing":"0.1em"}}} /--></div>
<!-- /wp:group -->

<!-- wp:group {"className":"meta-pair meta-updated","layout":{"type":"default"}} -->
<div class="wp-block-group meta-pair meta-updated"><!-- wp:paragraph {"textColor":"secondary","fontFamily":"condensed","style":{"typography":{"fontStyle":"normal","fontWeight":"600","fontSize":"0.5625rem","letterSpacing":"0.35em","textTransform":"uppercase"}}} -->
<p class="has-secondary-color has-text-color has-condensed-font-family" style="font-size:0.5625rem;font-style:normal;font-weight:600;letter-spacing:0.35em;text-transform:uppercase">Updated</p>
<!-- /wp:paragraph -->

<!-- wp:post-date {"format":"M j, Y","textColor":"border-dark","className":"wp-block-post-date__modified-date","fontFamily":"primary","style":{"typography":{"fontSize":"0.6875rem","letterSpacing":"0.1em"}},"metadata":{"bindings":{"datetime":{"source":"core/post-data","args":{"field":"modified"}}}}} /--></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
