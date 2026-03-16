# WP.org Release Checklist

Use this checklist before publishing a release for the WordPress.org theme directory.

1. Verify local QA gates.
   - `npm run qa`
   - If Docker publishes the custom wp-env port but the host cannot reach it on your machine, run `WP_ENV_PORT=8894 npm run qa:local` instead.
2. Run a package installation dry run in an isolated wp-env instance.
   - `npm run wporg:dry-run`
3. Build and inspect the release artifact.
   - Confirm `dist/wporg/the-drafting-table-wporg.zip` exists.
   - Confirm package excludes dev/test files and companion plugin code.
4. Tag the release after all checks pass.
   - `git tag -a vX.Y.Z -m "Release vX.Y.Z"`
   - `git push origin vX.Y.Z`
5. Create the GitHub release.
   - Upload `dist/wporg/the-drafting-table-wporg.zip`.
   - Copy the changelog entry from `readme.txt`.
   - Note that demo onboarding and SEO/meta utilities are companion-plugin functionality, not theme runtime.
