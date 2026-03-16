import process from 'node:process';
import { spawn } from 'node:child_process';

import {
	assertContainerRunning,
	createDockerExecProxyServer,
	defaultWpEnvWordpressContainer,
	isBaseUrlReachable,
	parseWpEnvProjectId,
	runCommand,
	normalizePort,
	shouldAutoProxy,
} from './playwright-wp-env-proxy.mjs';

async function runInteractive( command, args, { env = process.env } = {} ) {
	return new Promise( ( resolve, reject ) => {
		const child = spawn( command, args, {
			env,
			stdio: 'inherit',
		} );

		child.on( 'error', reject );
		child.on( 'exit', ( code ) => resolve( code ?? 1 ) );
	} );
}

async function main() {
	const port = normalizePort( process.env.WP_ENV_PORT );
	const baseURL = process.env.PLAYWRIGHT_BASE_URL || `http://localhost:${ port }`;
	let proxy;

	if ( shouldAutoProxy( baseURL ) && ! ( await isBaseUrlReachable( baseURL ) ) ) {
		const installPathOutput = await runCommand( 'npx', [ 'wp-env', 'install-path' ] );
		const projectId = parseWpEnvProjectId( installPathOutput );
		const containerName = process.env.WP_ENV_WORDPRESS_CONTAINER || defaultWpEnvWordpressContainer( projectId );

		await assertContainerRunning( containerName );

		proxy = createDockerExecProxyServer( { containerName, port } );
		await proxy.listen();

		process.stderr.write(
			`Playwright local proxy enabled for ${ baseURL } via ${ containerName }:80 because the host port was unreachable.\n`
		);

		if ( ! ( await isBaseUrlReachable( baseURL, { timeoutMs: 3_000 } ) ) ) {
			throw new Error( `Started a local proxy for ${ baseURL }, but the site is still unreachable.` );
		}
	}

	const exitCode = await runInteractive( 'npx', [ 'playwright', 'test', ...process.argv.slice( 2 ) ] );

	if ( proxy ) {
		await proxy.close();
	}

	process.exit( exitCode );
}

main().catch( async ( error ) => {
	process.stderr.write( `${ error.message }\n` );
	process.exit( 1 );
} );
