# The Ollie port (1.0.0)

From 1.0.0, The Drafting Table is a child theme of [Ollie](https://github.com/OllieWP/ollie). The last standalone version is [v0.6.4](https://github.com/dknauss/the-drafting-table/releases/tag/v0.6.4), kept on the `standalone` branch.

The goal of the port was to share the Ollie Child Kit with the other Ollie sites without changing how the theme looks. Rendered side by side in WordPress Playground (WordPress 7.1.4, PHP 8.3, Ollie 1.6.6, Ollie Child Kit active, companion plugin demo content), the child theme is pixel-identical to v0.6.4. That holds at 1280px and at a 390px phone width, on the front page, journal, a single entry, projects, principles, a category archive, search and the 404 page.

## How it is put together

`style.css` names `Template: ollie`; `Requires at least` is 7.0 (the Kit's floor).

All 17 templates, both template parts, all 11 patterns and the four style variations are this theme's own. Ollie's templates only act as a fallback. An early version let Ollie's archive, search, 404 and index templates show through, and they looked like Ollie, not like this theme.

### Preset slugs

A child theme's palette, font and spacing lists replace Ollie's outright, while Ollie's theme.json, block styles and patterns refer to Ollie's slugs. So the presets use Ollie's slugs, with this theme's names and values, and every reference in templates, parts, patterns, CSS and style variations uses the new slugs.

| Before 1.0.0 | From 1.0.0 | Name in the editor |
|---|---|---|
| `terra` | `primary` | Terra Cotta |
| — | `primary-accent` | Terra Wash (new: 14% terra over parchment) |
| `terra-muted` | `primary-alt` | Terra Muted |
| `charcoal` | `primary-alt-accent` | Charcoal |
| `ink` | `main` | Ink |
| — | `main-accent` | Ink Wash (new: 12% ink over parchment) |
| `parchment` | `base` | Parchment |
| `ink-light` | `secondary` | Ink Light |
| `parchment-dark` | `tertiary` | Parchment Dark |
| `ink-ghost` | `border-light` | Ink Ghost |
| `ink-faint` | `border-dark` | Ink Faint |
| `white` | (removed) | — |
| `courier-prime` | font `primary` (also `monospace`) | Courier Prime |
| `bodoni` | font `expanded` | Bodoni Moda |
| `josefin` | font `condensed` | Josefin Sans |
| `caveat` | font `narrow` | Caveat |
| spacing `10`, `20` / `30` / `40` / `50` / `60` / `70` / `80` | `small` / `medium` / `large` / `x-large` / `xx-large` / `xxx-large` / `xxxx-large` | — |

Content saved before 1.0.0 with the old slugs (for example `"textColor":"ink-faint"` in a post) loses that colour, font or spacing until it is chosen again.

### Spacing values

Up to 0.6.4, theme.json declared spacing sizes `10`–`80` (0.5rem to 12rem), but WordPress's default spacing sizes use the same slugs and won, so the site rendered core's values. The design was tuned against those. 1.0.0 declares the values that actually rendered: `small` 0.5rem, `medium` 0.67rem, `large` 1rem, `x-large` 1.5rem, `xx-large` 2.25rem, `xxx-large` 3.38rem, `xxxx-large` 5.06rem.

### Keeping Ollie's styling out

Ollie styles things this theme already styles, so four layers of it are switched off:

- Ollie's `style.css` (a reset, rounded white form fields, sticky-header and helper rules) is dequeued, and its per-block stylesheets (`assets/styles/core-*.css`: underlined current menu item, rounded drop-downs, excerpt and pullquote tweaks) are not loaded. Return true from the `the_drafting_table_keep_ollie_styles` filter to load both again.
- Ollie's theme.json settings that change rendering are reset here: fluid typography is off, `custom.lineHeight` and `custom.fontWeight` use this theme's values, the root block gap is 1.5rem and the root side padding is 1rem.
- Every value in Ollie's theme.json `styles` that this theme doesn't set itself is set here to an empty string, which WordPress skips when it generates CSS. The theme then falls back to the core defaults it was designed against. Button padding is the exception: core's default padding is set explicitly, because blanking it removes core's value too.
- `npm run ollie:overrides -- --ollie <path to Ollie's theme.json>` lists any Ollie style value that isn't covered; add `--write` to blank them. Run it whenever the Ollie version in `.wp-env.json` changes.

## Ollie Child Kit

When the Kit plugin is active, the theme asks for `limit-variations` (only this theme's four variations show), `hide-parent-patterns` (Ollie's patterns leave the inserter), `hide-shop-templates`, `template-titles`, `table-cell-align` and `footnotes-indent`. The theme does not need the Kit. Without it, Ollie's variations and patterns also show in the editor, and nothing else changes.

The theme keeps its own featured-image caption filter. The Kit's `featured-image-caption` module stays off, so the theme works the same without the plugin.

The companion plugin still provides demo content and the SEO helpers. It recognises the theme by its `the-drafting-table` folder name, which hasn't changed.

## Development

`.wp-env.json` runs WordPress 7.1.4 with Ollie 1.6.6 from WordPress.org. `wporg:dry-run` installs the same Ollie version before installing the packaged theme. To change Ollie versions, update both, then run the overrides check.
