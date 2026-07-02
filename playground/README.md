# WordPress Playground

This directory contains a lightweight WordPress Playground blueprint for previewing **The Drafting Table** without Docker.

## What it does

- installs the theme from this GitHub repository
- installs and activates the companion plugin
- seeds the companion plugin's demo content
- adds a small nested `Journal > Sub > Sub Two` page path for navigation testing

## What it is for

Use this blueprint for:

- quick browser demos
- contributor onboarding
- visual review of the theme in a disposable environment

It is **not** intended to replace the repo's higher-fidelity `wp-env` QA setup.

## Run locally

```bash
npx @wp-playground/cli@latest run-blueprint --blueprint=./playground/blueprint.json
```

## Open in the browser Playground

Host the blueprint JSON somewhere public and pass it as a `blueprint-url`, or use the Playground Blueprint editor to paste in the file contents.

## Notes

- The blueprint installs the current `main` branch **without** Playground's `git:directory` resource: the theme installs from a `main` archive `.zip` (`resource: "url"` → `…/archive/refs/heads/main.zip`), and the companion plugin's files are written from `raw.githubusercontent` (also `main`). This avoids Playground's `git:directory` install path, which currently fails with `createHash is not a function`; plain `.zip`/raw-file fetches are unaffected. (If the companion plugin gains files, add matching `writeFile` steps.)
- It is intentionally lightweight and does not try to mirror the full `wp-env` fixture/import pipeline.
