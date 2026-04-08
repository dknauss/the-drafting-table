# The Drafting Table Companion

Optional companion plugin for the **The Drafting Table** theme.

It provides two things that are intentionally kept out of the WordPress.org-safe theme package:

- demo content onboarding and cleanup
- lightweight SEO/meta helpers

This plugin is designed to be used **with the theme active**. If a different theme is active, its runtime features do nothing.

## What it adds

### 1. Demo content tools

When **The Drafting Table** is active, the plugin can:

- show an admin notice in `Appearance > Themes`
- install starter pages and journal posts
- assign bundled demo media and the site logo
- set a static front page and posts page
- mark the lead demo journal entry as the featured entry
- later remove installer-created demo content and restore the previous site state

The install/remove flow is nonce-protected and restricted to users who can `edit_theme_options`.

### 2. SEO/meta helpers

The plugin adds a small set of automatic front-end SEO helpers:

- `<meta name="description">` output for:
  - singular content, using the excerpt when available
  - the site tagline on the front page / posts page
  - archive descriptions on archive views
- JSON-LD structured data for:
  - `WebSite` on the front page / posts page
  - `Article` on single blog posts
- `<meta name="robots" content="noindex, follow">` on paginated archive views

These helpers are intentionally lightweight. They are not a replacement for a full SEO plugin.

## SEO plugin compatibility

To avoid duplicate meta descriptions, the companion plugin defers to common dedicated SEO plugins. Its description output is skipped when it detects plugins such as:

- Yoast SEO
- Rank Math
- All in One SEO
- All in One SEO Pack

This detection is also filterable in code via:

- `the_drafting_table_has_external_seo_plugin`

## Installation

### Manual/local install

1. Install and activate **The Drafting Table** theme.
2. Install this plugin from the `companion-plugin/the-drafting-table-companion` directory.
3. Activate the plugin.
4. Go to `Appearance > Themes` to use the demo content prompt if desired.

### In local development

The plugin is activated automatically in the repo's local `wp-env` setup.

## Demo content behavior

### Install flow

When you activate the theme, the plugin can present a one-time demo prompt in the admin. Choosing **Install Demo Content** creates starter content intended to make the theme immediately previewable.

That includes:

- the main theme pages:
  - About
  - Projects
  - Journal
  - Principles
- sample journal posts
- demo categories and tags
- bundled demo media
- a bundled custom logo
- front-page/posts-page reading settings

Before installation, the plugin captures mutable site state so it can restore it later. That includes:

- `show_on_front`
- `page_on_front`
- `page_for_posts`
- the current custom logo
- existing featured-entry markers used by the theme

### Removal flow

After demo content is installed, the plugin shows a management notice with **Remove Demo Content**.

Removal attempts to delete only installer-managed content by tracking:

- demo posts and pages
- demo media attachments
- demo categories and tags
- featured-entry markers

It then restores the previously captured site state.

The remover also includes a fallback recovery path for older/manual local setups where a complete install manifest may be missing.

## Important limitations

- This plugin is **optional**. The theme works without it.
- It does **not** provide a settings screen of its own.
- Its front-end SEO/meta helpers are intentionally basic.
- It is not meant to be bundled in the WordPress.org theme package.

## Packaging / distribution notes

- This plugin is intentionally excluded from the WordPress.org directory build profile for the theme.
- The theme's public `readme.txt` documents it as an optional add-on rather than a required dependency.

## Development notes

Relevant source files:

- plugin bootstrap:
  - `the-drafting-table-companion.php`
- demo install/remove logic:
  - `inc/create-pages.php`
- SEO/meta helpers:
  - `inc/seo-meta.php`

The repository's PHPUnit suite loads and tests companion plugin behavior alongside the theme runtime.
