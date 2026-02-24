=== The Drafting Table ===
Contributors: dpknauss
Tags: block-patterns, blog, custom-colors, custom-logo, custom-menu, editor-style, featured-images, full-site-editing, one-column, portfolio, threaded-comments, translation-ready, wide-blocks
Requires at least: 6.4
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 0.3.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A Frank Lloyd Wright-era architect's notebook theme with aged parchment, dot-grid overlays, blueprint borders, and refined architectural typography.

== Description ==

The Drafting Table is a full-site-editing block theme inspired by the architect's studio — the drafting table, the parchment sheet, the precise annotation. It is built for designers, architects, illustrators, and anyone who approaches their work with craft and intention.

The theme features:

* Aged parchment background with dot-grid and blueprint-grid overlays
* Blueprint-style double-border frames for images
* Vellum overlay effect with subtle sepia treatment on photography
* Three-font typographic system: Bodoni Moda (headings), Courier Prime (body), Josefin Sans (labels)
* Sheet-annotation metadata layout for journal entries
* Terra cotta accent color drawn from Wright's palette of earth and fire
* Fade-in-up entry animations with reduced-motion support
* Four style variations: Drafting Blue, Charcoal Blueprint, Sandstone Warm, Vellum
* Seven block patterns: Hero, Portfolio Grid, Services Overview, Testimonial, Process Workflow, Contact Inquiry, Latest Journal Entries
* Custom page templates: About, Projects, Journal, Principles, Page (Wide), Page (No Title)
* Threaded comments support
* Accessibility: skip link, WCAG 2.1 AA focus styles, reduced-motion support, ARIA labels

== Installation ==

1. In your WordPress dashboard, go to Appearance > Themes > Add New.
2. Search for "The Drafting Table" and click Install, then Activate.
3. Go to Appearance > Editor to customize your site using the Site Editor.
4. Use the included block patterns (under Patterns in the inserter) to build pages quickly.

== Frequently Asked Questions ==

= Does this theme require any plugins? =

No plugins are required. The theme is fully functional without any additional plugins.

= Where can I customize colors and fonts? =

Go to Appearance > Editor > Styles. You can switch between the four built-in style variations (Drafting Blue, Charcoal Blueprint, Sandstone Warm, Vellum) or customize any individual color, font, or spacing value.

= How do I add navigation menus? =

In a block theme, navigation is managed through the Site Editor. Go to Appearance > Editor, click on the header, and edit the Navigation block directly.

= Can I use this theme for a non-architecture site? =

Yes. While the aesthetic is inspired by architectural drafting, the theme suits any portfolio, creative studio, consultant, or journal-style site that appreciates refined, tactile design.

= How do I change the placeholder content in the About and Projects templates? =

Go to Appearance > Editor > Templates and select the template you want to edit. All content is editable directly in the Site Editor. You can also use the included patterns as starting points for your own pages.

== Changelog ==

= 0.3.1 =
* Fixed dot-grid and blueprint-line overlays hidden by accumulated styles.css in DB global styles record
* Fixed Charcoal Blueprint logo filter — converted styles.css to settings.custom to prevent DB accumulation
* Bumped Tested up to 6.9

= 0.3.0 =
* Added Fallingwater SVG site logo with embedded dark-mode colour inversion via Charcoal Blueprint style variation
* Fixed sticky primary navigation — applied position:sticky to the outer template-part wrapper
* Added admin toolbar offset for sticky header at all three breakpoints
* Added Vellum style variation — clean no-overlay parchment alternative (replaces Customizer toggle)
* Replaced anonymous filter/action callbacks with named functions (WordPress coding standards)
* Moved styles.php and theme-assets-rewrite.php into inc/ for cleaner theme structure
* Removed empty fonts.php file
* Added full-site-editing tag

= 0.2.0 =
* Added three style variations: Drafting Blue, Charcoal Blueprint, Sandstone Warm
* Added seven block patterns
* Added custom page templates: About, Projects, Journal, Principles
* Added threaded comments support to single post template
* Improved accessibility: skip link, WCAG 2.1 AA focus outlines
* Performance: optimized theme asset URL rewriting

= 0.1.0 =
* Initial release

== Credits ==

* Bodoni Moda (v28) — https://github.com/googlefonts/bodonimoda
  Copyright: 2019 The Bodoni Moda Project Authors
  License: SIL Open Font License 1.1 — https://scripts.sil.org/OFL

* Courier Prime (v11) — https://github.com/quoteunquoteapps/CourierPrime
  Copyright: 2015 Quote-Unquote Apps
  License: SIL Open Font License 1.1 — https://scripts.sil.org/OFL

* Josefin Sans (v34) — https://github.com/ThomasJockin/JosefinSansFont
  Copyright: 2010 The Josefin Sans Project Authors
  License: SIL Open Font License 1.1 — https://scripts.sil.org/OFL

* No bundled images — templates and patterns use block editor placeholders.
  Add your own images via the WordPress Site Editor.
