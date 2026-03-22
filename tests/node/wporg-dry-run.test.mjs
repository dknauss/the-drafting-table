import test from 'node:test';
import assert from 'node:assert/strict';
import http from 'node:http';

import { fetchFrontPageWithAutoProxy } from '../../scripts/wporg-dry-run.mjs';

test( 'fetchFrontPageWithAutoProxy proxies unreachable localhost dry runs', async () => {
	let listenCalls = 0;
	let closeCalls = 0;
	let fetchCalls = 0;
	let inspectedContainer = '';

	const { response, close } = await fetchFrontPageWithAutoProxy( 'http://localhost:8898', {
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
	assert.equal( closeCalls, 0 );
	assert.equal( fetchCalls, 1 );
	assert.equal( response.status, 200 );

	await close();
	assert.equal( closeCalls, 1 );
} );

test( 'fetchFrontPageWithAutoProxy skips proxy when localhost base URL is already reachable', async () => {
	let createProxyCalls = 0;
	let fetchCalls = 0;

	const { response, close } = await fetchFrontPageWithAutoProxy( 'http://localhost:8898', {
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

	await close();
} );

test( 'fetchFrontPageWithAutoProxy returns before proxy teardown blocks streaming responses', async () => {
	let closeCalls = 0;
	let releaseProxyClose;

	const proxyCloseGate = new Promise( ( resolve ) => {
		releaseProxyClose = resolve;
	} );
	const server = http.createServer( ( _request, response ) => {
		response.writeHead( 200, { 'Content-Type': 'text/plain' } );
		response.write( 'hello' );
		setTimeout( () => response.end( ' world' ), 50 );
	} );

	await new Promise( ( resolve ) => server.listen( 0, '127.0.0.1', resolve ) );
	const address = server.address();
	assert.notEqual( address, null );
	assert.equal( typeof address, 'object' );

	try {
		const { response, close } = await Promise.race( [
			fetchFrontPageWithAutoProxy( `http://127.0.0.1:${ address.port }`, {
				fetchFn: fetch,
				isBaseUrlReachableFn: async () => false,
				assertContainerRunningFn: async () => {},
				createProxyServerFn: () => ( {
					listen: async () => {},
					close: async () => {
						closeCalls += 1;
						await proxyCloseGate;
					},
				} ),
				wordpressContainerName: 'custom-wordpress-1',
			} ),
			new Promise( ( _resolve, reject ) => {
				setTimeout( () => reject( new Error( 'Helper did not return before proxy shutdown.' ) ), 250 );
			} ),
		] );

		assert.equal( closeCalls, 0 );
		assert.equal( await response.text(), 'hello world' );

		releaseProxyClose();
		await close();
		assert.equal( closeCalls, 1 );
	} finally {
		await new Promise( ( resolve ) => server.close( resolve ) );
	}
} );

test( 'fetchFrontPageWithAutoProxy prefers the explicit WordPress container override', async () => {
	let installPathLookups = 0;
	let inspectedContainer = '';
	let proxiedContainer = '';

	const { response, close } = await fetchFrontPageWithAutoProxy( 'http://localhost:8898', {
		fetchFn: async () => ( {
			status: 200,
			text: async () => '<html></html>',
		} ),
		isBaseUrlReachableFn: async () => false,
		captureCommandFn: () => {
			installPathLookups += 1;
			return '';
		},
		assertContainerRunningFn: async ( containerName ) => {
			inspectedContainer = containerName;
		},
		createProxyServerFn: ( { containerName } ) => {
			proxiedContainer = containerName;

			return {
				listen: async () => {},
				close: async () => {},
			};
		},
		wordpressContainerName: 'custom-wordpress-1',
	} );

	assert.equal( installPathLookups, 0 );
	assert.equal( inspectedContainer, 'custom-wordpress-1' );
	assert.equal( proxiedContainer, 'custom-wordpress-1' );
	assert.equal( response.status, 200 );

	await close();
} );
