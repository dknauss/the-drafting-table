import net from 'node:net';
import test from 'node:test';
import assert from 'node:assert/strict';

import { isPortAvailable, recoveryActions, runPreflightChecks } from '../../scripts/preflight-wp-env.mjs';

function healthyRunCommand() {
	return { ok: true, output: '29.2.1', errorOutput: '' };
}

test( 'passes with healthy docker, disk, and available ports', async () => {
	const result = await runPreflightChecks( {
		minFreeGiB: 2,
		warnFreeGiB: 4,
		env: { WP_ENV_PORT: '8892', WP_ENV_TESTS_PORT: '8893' },
		statfsFn: () => ( { bavail: 10 * 1024 * 1024, bsize: 1024 } ),
		runCommandFn: healthyRunCommand,
		isPortAvailableFn: async () => true,
	} );

	assert.deepEqual( result.failures, [] );
	assert.ok( result.passes.some( ( pass ) => pass.includes( 'Port 8892 available' ) ) );
	assert.ok( result.passes.some( ( pass ) => pass.includes( 'Docker daemon reachable' ) ) );
} );

test( 'fails when free disk is below minimum', async () => {
	const result = await runPreflightChecks( {
		minFreeGiB: 2,
		warnFreeGiB: 4,
		env: {},
		statfsFn: () => ( { bavail: 512 * 1024, bsize: 1024 } ),
		runCommandFn: healthyRunCommand,
		isPortAvailableFn: async () => true,
	} );

	assert.ok( result.failures.some( ( failure ) => failure.includes( 'Free disk space is' ) ) );
} );

test( 'fails when docker daemon is not reachable', async () => {
	const result = await runPreflightChecks( {
		env: {},
		statfsFn: () => ( { bavail: 10 * 1024 * 1024, bsize: 1024 } ),
		runCommandFn: () => ( { ok: false, output: '', errorOutput: 'Cannot connect to the Docker daemon' } ),
		isPortAvailableFn: async () => true,
	} );

	assert.ok( result.failures.some( ( failure ) => failure.includes( 'Docker daemon is not reachable' ) ) );
} );

test( 'fails when ports are invalid or occupied', async () => {
	const result = await runPreflightChecks( {
		env: { WP_ENV_PORT: 'invalid', WP_ENV_TESTS_PORT: '8889' },
		statfsFn: () => ( { bavail: 10 * 1024 * 1024, bsize: 1024 } ),
		runCommandFn: healthyRunCommand,
		isPortAvailableFn: async ( port ) => 8889 !== port,
	} );

	assert.ok( result.failures.some( ( failure ) => failure.includes( 'WP_ENV_PORT must be a valid TCP port' ) ) );
	assert.ok( result.failures.some( ( failure ) => failure.includes( 'Port 8889 is already in use' ) ) );
} );

test( 'recovery actions describe the configured minimum disk requirement', () => {
	const actions = recoveryActions( { minFreeGiB: 2 } );

	assert.ok( actions.some( ( action ) => action.includes( 'at least 2 GiB' ) ) );
} );

test( 'detects a real occupied TCP port', async () => {
	const server = net.createServer();
	await new Promise( ( resolve ) => server.listen( 0, '0.0.0.0', resolve ) );

	try {
		const address = server.address();
		assert.ok( address && 'object' === typeof address && 'port' in address );
		assert.equal( await isPortAvailable( address.port ), false );
	} finally {
		await new Promise( ( resolve ) => server.close( resolve ) );
	}
} );
