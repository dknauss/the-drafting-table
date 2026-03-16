import test from 'node:test';
import assert from 'node:assert/strict';

import {
	defaultWpEnvWordpressContainer,
	normalizePort,
	parseWpEnvProjectId,
	shouldAutoProxy,
} from '../../scripts/playwright-wp-env-proxy.mjs';

test( 'normalizePort accepts valid TCP ports', () => {
	assert.equal( normalizePort( '8894' ), 8894 );
	assert.equal( normalizePort( undefined, '8888' ), 8888 );
} );

test( 'normalizePort rejects invalid TCP ports', () => {
	assert.throws( () => normalizePort( '0' ) );
	assert.throws( () => normalizePort( '70000' ) );
	assert.throws( () => normalizePort( 'abc' ) );
} );

test( 'parseWpEnvProjectId extracts the project id from wp-env install-path output', () => {
	const output = '/Users/danknauss/.wp-env/ba6fe3aa4d2bfc5b73f417198e1a5998\n✔  (in 0s 360ms)\n';

	assert.equal( parseWpEnvProjectId( output ), 'ba6fe3aa4d2bfc5b73f417198e1a5998' );
} );

test( 'defaultWpEnvWordpressContainer builds the expected container name', () => {
	assert.equal(
		defaultWpEnvWordpressContainer( 'ba6fe3aa4d2bfc5b73f417198e1a5998' ),
		'ba6fe3aa4d2bfc5b73f417198e1a5998-wordpress-1'
	);
} );

test( 'shouldAutoProxy only enables the helper for localhost targets', () => {
	assert.equal( shouldAutoProxy( 'http://localhost:8894' ), true );
	assert.equal( shouldAutoProxy( 'http://127.0.0.1:8894' ), true );
	assert.equal( shouldAutoProxy( 'https://example.com' ), false );
} );
