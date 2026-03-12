import test from 'node:test';
import assert from 'node:assert/strict';

import { validateQaParity } from '../../scripts/check-qa-parity.mjs';

test( 'passes when qa script and workflows include required parity checks', () => {
	const errors = validateQaParity( {
		qaScript:
			'npm run env:start && npm run env:setup && composer run lint:php && npm run lint:node && npm run check:docs && npm run test:phpunit:coverage && npm run test:phpunit:coverage:check && npm run wporg:check && npm run test:smoke',
		workflows: [
			{
				name: 'Theme Quality',
				content: `
      - name: Run PHP lint rules
        run: composer lint:php
      - name: Validate documentation commands
        run: npm run check:docs
`,
			},
			{
				name: 'WP.org Release Preflight',
				content: `
      - name: Run PHP lint rules
        run: composer lint:php
      - name: Validate documentation commands
        run: npm run check:docs
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

test( 'fails when release preflight workflow misses PHP lint', () => {
	const errors = validateQaParity( {
		qaScript: 'composer run lint:php && npm run lint:node',
		workflows: [
			{
				name: 'WP.org Release Preflight',
				content: `
      - name: Run Node syntax lint
        run: npm run lint:node
      - name: Validate documentation commands
        run: npm run check:docs
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
} );
