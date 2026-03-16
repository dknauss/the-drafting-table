import test from 'node:test';
import assert from 'node:assert/strict';

import { validateQaParity } from '../../scripts/check-qa-parity.mjs';

test( 'passes when qa script and workflows include required parity checks', () => {
	const errors = validateQaParity( {
		qaScript:
			'npm run env:start && npm run env:setup && composer run lint:php && npm run lint:node && npm run test:node && npm run check:docs && npm run check:qa-parity && npm run test:phpunit:coverage && npm run test:phpunit:coverage:check && npm run wporg:check && npm run test:smoke',
		workflows: [
			{
				name: 'Theme Quality',
				content: `
      - name: Run PHP lint rules
        run: composer lint:php
      - name: Run Node syntax lint
        run: npm run lint:node
      - name: Run Node tests
        run: npm run test:node
      - name: Validate documentation commands
        run: npm run check:docs
      - name: Validate QA parity
        run: npm run check:qa-parity
      - name: Run PHPUnit coverage suite
        run: npm run test:phpunit:coverage
      - name: Validate PHPUnit coverage thresholds
        run: npm run test:phpunit:coverage:check
      - name: Run WP.org preflight checks
        run: npm run wporg:check
      - name: Run smoke tests
        run: npm run test:smoke
`,
			},
			{
				name: 'WP.org Release Preflight',
				content: `
      - name: Run PHP lint rules
        run: composer lint:php
      - name: Run Node syntax lint
        run: npm run lint:node
      - name: Run Node tests
        run: npm run test:node
      - name: Validate documentation commands
        run: npm run check:docs
      - name: Validate QA parity
        run: npm run check:qa-parity
      - name: Run PHPUnit coverage suite
        run: npm run test:phpunit:coverage
      - name: Validate PHPUnit coverage thresholds
        run: npm run test:phpunit:coverage:check
      - name: Run WP.org preflight checks
        run: npm run wporg:check
      - name: Run isolated package install dry run
        run: npm run wporg:dry-run
`,
			},
		],
	} );

	assert.deepEqual( errors, [] );
} );

test( 'fails when qa script misses PHP lint', () => {
	const errors = validateQaParity( {
		qaScript: 'npm run env:start && npm run env:setup && npm run lint:node',
		workflows: [],
	} );

	assert.ok(
		errors.some( ( error ) => error.includes( 'qa script must include "composer run lint:php"' ) )
	);
} );

test( 'fails when qa script misses Node tests', () => {
	const errors = validateQaParity( {
		qaScript:
			'npm run env:start && npm run env:setup && composer run lint:php && npm run lint:node && npm run check:docs && npm run check:qa-parity && npm run test:phpunit:coverage && npm run test:phpunit:coverage:check && npm run wporg:check && npm run test:smoke',
		workflows: [],
	} );

	assert.ok(
		errors.some( ( error ) => error.includes( 'qa script must include "npm run test:node"' ) )
	);
} );

test( 'fails when release preflight workflow misses required workflow gates', () => {
	const errors = validateQaParity( {
		qaScript:
			'npm run env:start && npm run env:setup && composer run lint:php && npm run lint:node && npm run test:node && npm run check:docs && npm run check:qa-parity && npm run test:phpunit:coverage && npm run test:phpunit:coverage:check && npm run wporg:check && npm run test:smoke',
		workflows: [
			{
				name: 'WP.org Release Preflight',
				content: `
      - name: Run Node syntax lint
        run: npm run lint:node
      - name: Validate QA parity
        run: npm run check:qa-parity
      - name: Validate documentation commands
        run: npm run check:docs
      - name: Run PHPUnit coverage suite
        run: npm run test:phpunit:coverage
      - name: Validate PHPUnit coverage thresholds
        run: npm run test:phpunit:coverage:check
      - name: Run WP.org preflight checks
        run: npm run wporg:check
`,
			},
		],
	} );

	assert.ok(
		errors.some(
			( error ) =>
				error.includes( 'WP.org Release Preflight' ) &&
				error.includes( 'composer lint:php' )
		)
	);
	assert.ok(
		errors.some(
			( error ) =>
				error.includes( 'WP.org Release Preflight' ) &&
				error.includes( 'npm run test:node' )
		)
	);
	assert.ok(
		errors.some(
			( error ) =>
				error.includes( 'WP.org Release Preflight' ) &&
				error.includes( 'npm run wporg:dry-run' )
		)
	);
} );
