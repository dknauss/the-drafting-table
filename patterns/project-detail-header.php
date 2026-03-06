<?php
/**
 * Title: Project Detail Header
 * Slug: the-drafting-table/project-detail-header
 * Description: Full-width cover image with blueprint frame treatment and project metadata row for project detail pages.
 * Categories: the-drafting-table
 * Keywords: project, header, cover, blueprint
 * Inserter: true
 *
 * @package The_Drafting_Table
 */

?>
<!-- wp:group {"align":"full","className":"blueprint-frame","style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"0","bottom":"0","left":"0","right":"0"},"blockGap":"0"}},"layout":{"type":"default"}} -->
<div class="wp-block-group alignfull blueprint-frame" style="margin-top:0;margin-bottom:0;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
	<!-- wp:group {"className":"vellum-overlay","style":{"spacing":{"margin":{"top":"0","bottom":"0"},"padding":{"top":"0","bottom":"0","left":"0","right":"0"},"blockGap":"0"}},"layout":{"type":"default"}} -->
	<div class="wp-block-group vellum-overlay" style="margin-top:0;margin-bottom:0;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
		<!-- wp:cover {"url":"","dimRatio":40,"minHeight":480,"minHeightUnit":"px","isDark":false,"align":"full","style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"}}}} -->
		<div class="wp-block-cover alignfull is-light" style="min-height:480px;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-40 has-background-dim"></span>
		<div class="wp-block-cover__inner-container">
		</div></div>
		<!-- /wp:cover -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"margin":{"top":"var:preset|spacing|50","bottom":"0"},"padding":{"top":"0","bottom":"0","left":"2rem","right":"2rem"}}},"layout":{"type":"constrained","contentSize":"860px"}} -->
<div class="wp-block-group" style="margin-top:var(--wp--preset--spacing--50);margin-bottom:0;padding-top:0;padding-right:2rem;padding-bottom:0;padding-left:2rem">

	<!-- wp:group {"className":"terra-rule","layout":{"type":"constrained","contentSize":"860px"}} -->
	<div class="wp-block-group terra-rule">
		<!-- wp:post-title {"textAlign":"center","level":1,"fontFamily":"bodoni","style":{"typography":{"letterSpacing":"0.02em"}}} /-->
	</div>
	<!-- /wp:group -->

	<!-- wp:spacer {"height":"2rem"} -->
	<div style="height:2rem" aria-hidden="true" class="wp-block-spacer"></div>
	<!-- /wp:spacer -->

	<!-- wp:group {"style":{"spacing":{"padding":{"top":"1.25rem","bottom":"1.25rem"},"blockGap":"1.5rem"},"border":{"top":{"color":"var:preset|color|ink-ghost","width":"1px"},"bottom":{"color":"var:preset|color|ink-ghost","width":"1px"}}},"layout":{"type":"flex","justifyContent":"space-between","flexWrap":"wrap"}} -->
	<div class="wp-block-group" style="border-top-color:var(--wp--preset--color--ink-ghost);border-top-width:1px;border-bottom-color:var(--wp--preset--color--ink-ghost);border-bottom-width:1px;padding-top:1.25rem;padding-bottom:1.25rem">

		<!-- wp:group {"className":"meta-pair","layout":{"type":"flex","orientation":"vertical","flexWrap":"nowrap"}} -->
		<div class="wp-block-group meta-pair">
			<!-- wp:paragraph {"textColor":"ink-light","fontFamily":"josefin","style":{"typography":{"fontStyle":"normal","fontWeight":"600","fontSize":"0.5625rem","letterSpacing":"0.35em","textTransform":"uppercase"}}} -->
			<p class="has-ink-light-color has-text-color has-josefin-font-family" style="font-size:0.5625rem;font-style:normal;font-weight:600;letter-spacing:0.35em;text-transform:uppercase">Project No.</p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"textColor":"ink-faint","fontFamily":"courier-prime","style":{"typography":{"fontSize":"0.6875rem","letterSpacing":"0.1em"}}} -->
			<p class="has-ink-faint-color has-text-color has-courier-prime-font-family" style="font-size:0.6875rem;letter-spacing:0.1em">2024-08</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"className":"meta-pair","layout":{"type":"flex","orientation":"vertical","flexWrap":"nowrap"}} -->
		<div class="wp-block-group meta-pair">
			<!-- wp:paragraph {"textColor":"ink-light","fontFamily":"josefin","style":{"typography":{"fontStyle":"normal","fontWeight":"600","fontSize":"0.5625rem","letterSpacing":"0.35em","textTransform":"uppercase"}}} -->
			<p class="has-ink-light-color has-text-color has-josefin-font-family" style="font-size:0.5625rem;font-style:normal;font-weight:600;letter-spacing:0.35em;text-transform:uppercase">Year</p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"textColor":"ink-faint","fontFamily":"courier-prime","style":{"typography":{"fontSize":"0.6875rem","letterSpacing":"0.1em"}}} -->
			<p class="has-ink-faint-color has-text-color has-courier-prime-font-family" style="font-size:0.6875rem;letter-spacing:0.1em">2024</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"className":"meta-pair","layout":{"type":"flex","orientation":"vertical","flexWrap":"nowrap"}} -->
		<div class="wp-block-group meta-pair">
			<!-- wp:paragraph {"textColor":"ink-light","fontFamily":"josefin","style":{"typography":{"fontStyle":"normal","fontWeight":"600","fontSize":"0.5625rem","letterSpacing":"0.35em","textTransform":"uppercase"}}} -->
			<p class="has-ink-light-color has-text-color has-josefin-font-family" style="font-size:0.5625rem;font-style:normal;font-weight:600;letter-spacing:0.35em;text-transform:uppercase">Type</p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"textColor":"ink-faint","fontFamily":"courier-prime","style":{"typography":{"fontSize":"0.6875rem","letterSpacing":"0.1em"}}} -->
			<p class="has-ink-faint-color has-text-color has-courier-prime-font-family" style="font-size:0.6875rem;letter-spacing:0.1em">Residential</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"className":"meta-pair","layout":{"type":"flex","orientation":"vertical","flexWrap":"nowrap"}} -->
		<div class="wp-block-group meta-pair">
			<!-- wp:paragraph {"textColor":"ink-light","fontFamily":"josefin","style":{"typography":{"fontStyle":"normal","fontWeight":"600","fontSize":"0.5625rem","letterSpacing":"0.35em","textTransform":"uppercase"}}} -->
			<p class="has-ink-light-color has-text-color has-josefin-font-family" style="font-size:0.5625rem;font-style:normal;font-weight:600;letter-spacing:0.35em;text-transform:uppercase">Location</p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"textColor":"ink-faint","fontFamily":"courier-prime","style":{"typography":{"fontSize":"0.6875rem","letterSpacing":"0.1em"}}} -->
			<p class="has-ink-faint-color has-text-color has-courier-prime-font-family" style="font-size:0.6875rem;letter-spacing:0.1em">Madison, WI</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

	</div>
	<!-- /wp:group -->

</div>
<!-- /wp:group -->
