<?php
/**
 * Title: Highlight Pull Quote
 * Slug: the-drafting-table/highlight-pullquote
 * Description: A full-width featured quote section with parchment background and terra-rule accent, suitable for use between page sections or within long-form content.
 * Categories: the-drafting-table
 * Keywords: quote, pullquote, highlight, featured
 * Inserter: true
 *
 * @package The_Drafting_Table
 */

?>
<!-- wp:group {"align":"full","backgroundColor":"parchment","ariaLabel":"Featured quote","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"2rem","right":"2rem"}},"border":{"top":{"color":"var(--wp--preset--color--ink-ghost)","width":"1px"},"bottom":{"color":"var(--wp--preset--color--ink-ghost)","width":"1px"}}},"layout":{"type":"constrained","contentSize":"680px"}} -->
<div class="wp-block-group alignfull has-parchment-background-color has-background" aria-label="Featured quote" style="border-top-color:var(--wp--preset--color--ink-ghost);border-top-width:1px;border-bottom-color:var(--wp--preset--color--ink-ghost);border-bottom-width:1px;padding-top:var(--wp--preset--spacing--50);padding-right:2rem;padding-bottom:var(--wp--preset--spacing--50);padding-left:2rem">

	<!-- wp:group {"className":"terra-rule","layout":{"type":"default"}} -->
	<div class="wp-block-group terra-rule">
		<!-- wp:pullquote {"fontFamily":"bodoni"} -->
		<figure class="wp-block-pullquote has-bodoni-font-family"><blockquote><p>The measure of a building is not in its image but in the quality of life it engenders — how it holds light in the morning, how it gathers people at its hearth.</p><cite>From the Drafting Table, Autumn 1931</cite></blockquote></figure>
		<!-- /wp:pullquote -->
	</div>
	<!-- /wp:group -->

	<!-- wp:paragraph {"textColor":"ink-faint","fontFamily":"josefin","style":{"typography":{"fontStyle":"normal","fontWeight":"300","fontSize":"0.5625rem","letterSpacing":"0.5em","textTransform":"uppercase","textAlign":"center"},"spacing":{"margin":{"top":"1.25rem","bottom":"0"}}}} -->
	<p class="has-text-align-center has-ink-faint-color has-text-color has-josefin-font-family" style="margin-top:1.25rem;margin-bottom:0;font-size:0.5625rem;font-style:normal;font-weight:300;letter-spacing:0.5em;text-transform:uppercase">Drawn from the field notebooks</p>
	<!-- /wp:paragraph -->

</div>
<!-- /wp:group -->
