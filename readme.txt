=== The Drafting Table ===
Contributors: dpknauss
Tags: block-patterns, blog, custom-colors, custom-logo, custom-menu, editor-style, featured-images, full-site-editing, one-column, portfolio, threaded-comments, translation-ready, wide-blocks
Requires at least: 6.4
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 0.6.1
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
* Eleven block patterns: Hero, Portfolio Grid, Services Overview, Testimonial, Process Workflow, Contact Inquiry, Latest Journal Entries, Post Meta Row, Project Detail Header, Bio Card, Highlight Pull Quote
* Custom page templates: About, Projects, Journal, Principles, Page (Wide), Page (No Title)
* Threaded comments support
* Accessibility: skip link, WCAG 2.1 AA focus styles, reduced-motion support, ARIA labels

== Installation ==

1. In your WordPress dashboard, go to Appearance > Themes > Add New.
2. Search for "The Drafting Table" and click Install, then Activate.
3. Go to Appearance > Editor to customize your site using the Site Editor.
4. (Optional) Install and activate the companion plugin if you want demo-content onboarding and built-in SEO/meta helpers.
5. Use the included block patterns (under Patterns in the inserter) to build pages quickly.

== Frequently Asked Questions ==

= Does this theme require any plugins? =

No plugins are required. The theme is fully functional without any additional plugins.

= Where can I customize colors and fonts? =

Go to Appearance > Editor > Styles. You can switch between the four built-in style variations (Drafting Blue, Charcoal Blueprint, Sandstone Warm, Vellum) or customize any individual color, font, or spacing value.

= How do I add navigation menus? =

The bundled header and footer use a Page List navigation block, so newly created pages appear automatically. If you need a custom menu structure, go to Appearance > Editor, click on the header or footer, and replace the Page List with a custom Navigation block.

= How do I install the demo content? =

Demo-content onboarding is provided by the optional companion plugin. After activating both the theme and companion plugin, visit Appearance > Themes and use the "Install Demo Content" action in the admin notice. The installer creates starter pages, sample journal posts, featured images, the site logo, and static front page / posts page reading settings.

= Can I use this theme for a non-architecture site? =

Yes. While the aesthetic is inspired by architectural drafting, the theme suits any portfolio, creative studio, consultant, or journal-style site that appreciates refined, tactile design.

= How do I change the placeholder content in the About and Projects templates? =

Go to Appearance > Editor > Templates and select the template you want to edit. All content is editable directly in the Site Editor. You can also use the included patterns as starting points for your own pages.

== Development ==

* `composer install` then `composer run lint:php` for WordPress Coding Standards checks
* `npm install`
* `npm run preflight:env` to validate local disk and Docker health before wp-env startup
* `npm run lint:node` and `npm run test:node` for Node-based tooling checks
* `npm run check:docs` and `npm run check:qa-parity` for docs/workflow consistency
* `npm run env:start` and `npm run env:setup` to provision a local wp-env instance with demo content
* `npm run test:phpunit` for the WordPress PHPUnit suite against theme and companion logic
* `npm run test:phpunit:coverage` and `npm run test:phpunit:coverage:check` for enforced coverage thresholds
* `npm run themecheck` to run the Theme Check plugin against the active theme
* `npm run wporg:check` for WordPress.org preflight checks (headers, screenshot, package profile, Theme Check in CI)
* `npm run build:wporg` to build the directory-safe package profile
* `npm run test:smoke` to run the Playwright smoke suite against the local wp-env site
* `npm run test:smoke:local` to auto-proxy a local wp-env site when Docker publishes the custom port but the host cannot reach it
* `npm run playwright:proxy` to keep that proxy open for headed/manual Playwright sessions on the same wp-env port
* `npm run qa` to run the full local release gate sequence

== Local Playwright troubleshooting ==

If `npm run test:smoke` stalls or times out against a custom `WP_ENV_PORT` even though `wp-env` reports the stack as running, Docker may have published the port without making it reachable from the host. In that case, run `WP_ENV_PORT=8894 npm run test:smoke:local` instead. For headed or manual Playwright sessions, start `WP_ENV_PORT=8894 npm run playwright:proxy` in one terminal and point Playwright at the same `http://localhost:8894` base URL.

== Changelog ==

= 0.6.1 =
* Stabilized Playwright smoke tests in CI by disabling canonical redirects for query-based smoke routes in the test-only MU plugin
* Added smoke-only REST state endpoint for deterministic assertions around demo installer lifecycle checks
* Reworked installer rollback smoke assertions to validate stable user-visible outcomes across remove/reinstall flows
* Hardened single/archive smoke route targeting and removed rewrite-environment assumptions that caused GitHub Actions flakes

= 0.6.0 =
* Added hardened GitHub Actions QA workflow defaults (CI mode, npm cache, npm ci, concurrency guard, timeout)
* Expanded PHPUnit coverage for demo installer edge cases and query marker behavior
* Expanded Playwright coverage with installer lifecycle behavior (remove and reinstall demo content + reading settings rollback/restore)
* Updated Theme Check package runner to validate against an isolated slug-matched theme root (removes directory-name warning)
* Added `npm run wporg:dry-run` script for isolated packaged-theme install/activate verification with fatal-log checks
* Added manual `WP.org Release Preflight` workflow and a release checklist document

= 0.5.0 =
* Replaced broken database-bound navigation references with portable Page List navigation in the header and footer
* Expanded the companion demo installer to create featured artwork, assign the bundled logo, and mark the lead journal post with a dedicated featured-entry meta marker (not sticky posts)
* Moved demo onboarding and optional SEO/meta runtime to the companion plugin for WordPress.org-safe theme packaging
* Loaded `editor-style.css` in the block editor so the editor reflects the parchment/grid treatment
* Replaced hard-coded template and pattern border colors with theme tokens so style variations recolor consistently
* Added local QA tooling: Composer + PHPCS, WordPress PHPUnit bootstrap, Theme Check runner, wp-env bootstrap, and expanded Playwright smoke coverage
* Fixed `index.html` landmark structure so the footer renders outside `<main>`

= 0.4.0 =
* Added static front-page template (hero, journal grid, principles strip, project studies)
* Added Blog Home template (index/home with 3-col journal grid)
* Added three new block patterns: Project Detail Header, Bio Card, Highlight Pull Quote (eleven total)
* Added Principles page to demo installer with four numbered design principles
* Added static front page / posts page reading settings to demo installer
* Added font preload hints for four critical woff2 files (LCP improvement)
* Added spacing scale tokens 70 (9rem) and 80 (12rem) for large section spacing
* Fixed modified-date accent color to use textColor:terra so each style variation auto-adapts
* Fixed footer template-part placement: moved outside </main> in front-page and home templates
* Fixed all archive/tag/category/date/search templates: padding-top, grid layout, terra modified-date
* Fixed principle-number border to adapt per style variation via CSS custom property
* Refined style variation color palettes: deduplicated charcoal/ink in Drafting Blue and Sandstone Warm; corrected ink-faint distinction in Drafting Blue and Sandstone Warm; corrected terra-muted direction in Charcoal Blueprint
* Improved Charcoal Blueprint logo filter: brightness(0) invert(1) reliably whites out any logo on dark background
* Replaced screenshot with headless 1200x900 capture of default style front page
* Code quality: phpcbf auto-fixed 468 formatting violations across all pattern files; 0 errors/warnings

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

* Original bundled image assets created for this theme package by Dan Knauss.
  Files: screenshot.png, assets/images/fallingwater-logo.svg,
  assets/images/demo-board-formed-concrete.svg,
  assets/images/demo-ridgeline-dwelling.svg,
  assets/images/demo-copper-roof-study.svg,
  assets/images/demo-drawing-hand-study.svg,
  assets/images/demo-timber-joinery-study.svg,
  assets/images/demo-glass-transparency-study.svg
  License: GPLv2 or later

* Test fixtures for automated QA:
  WordPress Theme Test Data — https://github.com/WordPress/theme-test-data
  Accessibility Theme Unit Test Data — https://github.com/wpaccessibility/a11y-theme-unit-test
