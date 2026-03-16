import test from 'node:test';
import assert from 'node:assert/strict';

import { fetchFrontPageWithAutoProxy } from '../../scripts/wporg-dry-run.mjs';

test( 'fetchFrontPageWithAutoProxy proxies unreachable localhost dry runs', async () => {
	let listenCalls = 0;
	let closeCalls = 0;
	let fetchCalls = 0;
	let inspectedContainer = '';

	const response = await fetchFrontPageWithAutoProxy( 'http://localhost:8898', {
		wpEnvBinPath: '/fake/wp-env',
		wpEnvCwd: '/tmp/fake-dry-run',
		fetchFn: async () => {
			fetchCalls += 1;

			return {
				status: 200,
				text: async () => '<html></html>',
			};
		},
		isBaseUrlReachableFn: async () => false,
		captureCommandFn: () => '/Users/danknauss/.wp-env/ea8e5d1f07e454707de9b52f8aa44ff0\n',
		assertContainerRunningFn: async ( containerName ) => {
			inspectedContainer = containerName;
		},
		createProxyServerFn: ( options ) => {
			assert.equal( options.containerName, 'ea8e5d1f07e454707de9b52f8aa44ff0-wordpress-1' );
			assert.equal( options.host, 'localhost' );
			assert.equal( options.port, 8898 );

			return {
				listen: async () => {
					listenCalls += 1;
				},
				close: async () => {
					closeCalls += 1;
				},
			};
		},
	} );

	assert.equal( inspectedContainer, 'ea8e5d1f07e454707de9b52f8aa44ff0-wordpress-1' );
	assert.equal( listenCalls, 1 );
	assert.equal( closeCalls, 1 );
	assert.equal( fetchCalls, 1 );
	assert.equal( response.status, 200 );
} );

test( 'fetchFrontPageWithAutoProxy skips proxy when localhost base URL is already reachable', async () => {
	let createProxyCalls = 0;
	let fetchCalls = 0;

	const response = await fetchFrontPageWithAutoProxy( 'http://localhost:8898', {
		fetchFn: async () => {
			fetchCalls += 1;

			return {
				status: 200,
				text: async () => '<html></html>',
			};
		},
		isBaseUrlReachableFn: async () => true,
		createProxyServerFn: () => {
			createProxyCalls += 1;
			throw new Error( 'Proxy should not be created for reachable localhost base URLs.' );
		},
	} );

	assert.equal( createProxyCalls, 0 );
	assert.equal( fetchCalls, 1 );
	assert.equal( response.status, 200 );
} );
