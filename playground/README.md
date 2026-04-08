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

- The blueprint currently installs from the repository's `main` branch.
- It is intentionally lightweight and does not try to mirror the full `wp-env` fixture/import pipeline.
