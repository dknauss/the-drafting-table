# WP.org Release Checklist

Use this checklist before publishing a release for the WordPress.org theme directory.

1. Verify local QA gates.
   - `npm run env:start`
   - `npm run env:setup`
   - `npm run test:phpunit:coverage`
   - `npm run test:phpunit:coverage:check`
   - `CI=true npm run wporg:check`
   - `npm run test:smoke`
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
