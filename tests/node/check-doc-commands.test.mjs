import test from 'node:test';
import assert from 'node:assert/strict';

import { validateDocCommands } from '../../scripts/check-doc-commands.mjs';

function buildInput( { docs } ) {
	return {
		docs,
		npmScripts: new Set( [ 'env:start', 'check:docs', 'test:smoke' ] ),
		composerScripts: new Set( [ 'lint:php', 'test:phpunit' ] ),
	};
}

test( 'passes for known npm and composer run commands', () => {
	const errors = validateDocCommands(
		buildInput( {
			docs: [
				{
					name: 'docs/a.md',
					content: '`npm run env:start` and `composer run lint:php`',
				},
			],
		} )
	);

	assert.deepEqual( errors, [] );
} );

test( 'fails for unknown npm script reference', () => {
	const errors = validateDocCommands(
		buildInput( {
			docs: [
				{
					name: 'docs/a.md',
					content: '`npm run does-not-exist`',
				},
			],
		} )
	);

	assert.ok( errors.some( ( error ) => error.includes( 'Unknown npm script' ) ) );
} );

test( 'fails for composer alias usage without "run"', () => {
	const errors = validateDocCommands(
		buildInput( {
			docs: [
				{
					name: 'docs/a.md',
					content: '`composer lint:php`',
				},
			],
		} )
	);

	assert.ok( errors.some( ( error ) => error.includes( 'Use "composer run lint:php"' ) ) );
} );
