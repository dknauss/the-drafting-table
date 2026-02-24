<?php
/**
 * Title: Latest Journal Entries
 * Slug: the-drafting-table/latest-journal-entries
 * Categories: featured
 * Description: A dynamic query loop displaying the latest blog posts as journal entry cards.
 */
?>
<!-- wp:group {"style":{"spacing":{"margin":{"top":"0"},"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"2rem","right":"2rem"}}},"layout":{"type":"constrained","wideSize":"1280px"}} -->
<div class="wp-block-group" style="margin-top:0;padding-top:var(--wp--preset--spacing--60);padding-right:2rem;padding-bottom:var(--wp--preset--spacing--60);padding-left:2rem">
    <!-- wp:group {"style":{"spacing":{"padding":{"bottom":"1rem"}},"border":{"bottom":{"color":"#d5cfc6","width":"1px"}}},"layout":{"type":"flex","justifyContent":"space-between","flexWrap":"nowrap"}} -->
    <div class="wp-block-group" style="border-bottom-color:#d5cfc6;border-bottom-width:1px;padding-bottom:1rem">
        <!-- wp:paragraph {"textColor":"ink-light","fontFamily":"josefin","style":{"typography":{"fontStyle":"normal","fontWeight":"600","fontSize":"0.5625rem","letterSpacing":"0.35em","textTransform":"uppercase"}}} -->
        <p class="has-ink-light-color has-text-color has-josefin-font-family" style="font-size:0.5625rem;font-style:normal;font-weight:600;letter-spacing:0.35em;text-transform:uppercase">From the Journal</p>
        <!-- /wp:paragraph -->

        <!-- wp:paragraph {"textColor":"ink-faint","fontFamily":"courier-prime","style":{"typography":{"fontSize":"0.6875rem","letterSpacing":"0.1em"}}} -->
        <p class="has-ink-faint-color has-text-color has-courier-prime-font-family" style="font-size:0.6875rem;letter-spacing:0.1em">Latest Entries</p>
        <!-- /wp:paragraph -->
    </div>
    <!-- /wp:group -->

    <!-- wp:spacer {"height":"2.5rem"} -->
    <div style="height:2.5rem" aria-hidden="true" class="wp-block-spacer"></div>
    <!-- /wp:spacer -->

    <!-- wp:query {"queryId":10,"query":{"perPage":3,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","inherit":false}} -->
    <!-- wp:post-template {"layout":{"type":"grid","columnCount":3}} -->
        <!-- wp:group {"backgroundColor":"parchment","className":"journal-card","style":{"spacing":{"padding":{"top":"1.75rem","bottom":"1.75rem","left":"1.5rem","right":"1.5rem"}},"border":{"width":"1px","color":"#d5cfc680"}},"layout":{"type":"default"}} -->
        <div class="wp-block-group journal-card has-border-color has-parchment-background-color has-background" style="border-color:#d5cfc680;border-width:1px;padding-top:1.75rem;padding-right:1.5rem;padding-bottom:1.75rem;padding-left:1.5rem">
            <!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap"}} -->
            <div class="wp-block-group"><!-- wp:post-date {"format":"M j, Y","style":{"typography":{"fontSize":"0.6875rem","letterSpacing":"0.1em"}},"textColor":"ink-faint","fontFamily":"courier-prime"} /-->

            <!-- wp:post-date {"displayType":"modified","format":"(\\u\\p\\d. M j)","textColor":"ink-ghost","fontFamily":"courier-prime","style":{"typography":{"fontSize":"0.6875rem","letterSpacing":"0.05em"}}} /--></div>
            <!-- /wp:group -->

            <!-- wp:post-title {"level":3,"isLink":true,"style":{"spacing":{"margin":{"top":"0.75rem","bottom":"0.5rem"}},"typography":{"fontSize":"1.125rem"},"elements":{"link":{"color":{"text":"var:preset|color|ink"},":hover":{"color":{"text":"var:preset|color|terra"}}}}}} /-->

            <!-- wp:post-terms {"term":"category","textColor":"terra","fontFamily":"courier-prime","style":{"typography":{"fontSize":"0.625rem","letterSpacing":"0.1em"},"spacing":{"margin":{"bottom":"0.5rem"}}}} /-->

            <!-- wp:post-excerpt {"moreText":"Read Entry","excerptLength":20,"style":{"typography":{"fontSize":"0.8125rem","lineHeight":"1.7"}},"textColor":"ink-light","fontFamily":"courier-prime"} /-->
        </div>
        <!-- /wp:group -->
    <!-- /wp:post-template -->

    <!-- wp:query-no-results -->
        <!-- wp:paragraph {"align":"center","textColor":"ink-faint","fontFamily":"courier-prime","style":{"typography":{"fontSize":"0.875rem"}}} -->
        <p class="has-text-align-center has-ink-faint-color has-text-color has-courier-prime-font-family" style="font-size:0.875rem">No journal entries have been published yet.</p>
        <!-- /wp:paragraph -->
    <!-- /wp:query-no-results -->
    <!-- /wp:query -->
</div>
<!-- /wp:group -->