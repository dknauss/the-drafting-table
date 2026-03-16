import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { execSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import {
	assertContainerRunning,
	createDockerExecProxyServer,
	defaultWpEnvWordpressContainer,
	isBaseUrlReachable,
	parseWpEnvProjectId,
	shouldAutoProxy,
} from './playwright-wp-env-proxy.mjs';

const rootDir = path.resolve( path.dirname( fileURLToPath( import.meta.url ) ), '..' );
const wpEnvBin = path.join( rootDir, 'node_modules', '.bin', 'wp-env' );
const zipSource = path.join( rootDir, 'dist', 'wporg', 'the-drafting-table-wporg.zip' );
const FRONTEND_FETCH_TIMEOUT_MS = 5_000;
const errors = [];

function pass( message ) {
	console.log( `PASS: ${ message }` );
}

function fail( message ) {
	errors.push( message );
	console.error( `FAIL: ${ message }` );
}

function runCommand( command, options = {} ) {
	execSync( command, {
		stdio: 'inherit',
		...options,
	} );
}

function runCommandCapture( command, options = {} ) {
	return execSync( command, {
		encoding: 'utf8',
		stdio: [ 'ignore', 'pipe', 'pipe' ],
		...options,
	} );
}

function safeCommand( command, options = {} ) {
	try {
		runCommand( command, options );
	} catch ( error ) {
		const detail = error instanceof Error ? error.message : String( error );
		fail( `Command failed: ${ command }\n${ detail }` );
	}
}

function captureCommand( command, options = {} ) {
	try {
		return runCommandCapture( command, options );
	} catch ( error ) {
		const detail = error instanceof Error ? error.message : String( error );
		fail( `Command failed: ${ command }\n${ detail }` );
		return '';
	}
}

export async function fetchFrontPageWithAutoProxy(
	baseUrl,
	{
		wpEnvBinPath = wpEnvBin,
		wpEnvCwd,
		fetchFn = fetch,
		isBaseUrlReachableFn = isBaseUrlReachable,
		captureCommandFn = captureCommand,
		assertContainerRunningFn = assertContainerRunning,
		createProxyServerFn = createDockerExecProxyServer,
	} = {}
) {
	let proxy;

	try {
		if ( shouldAutoProxy( baseUrl ) ) {
			const baseUrlReachable = await isBaseUrlReachableFn( baseUrl, {
				fetchFn,
				timeoutMs: 1_500,
			} );

			if ( ! baseUrlReachable ) {
				const installPathOutput = captureCommandFn( `${ wpEnvBinPath } install-path`, { cwd: wpEnvCwd } );
				const projectId = parseWpEnvProjectId( installPathOutput );
				const containerName = defaultWpEnvWordpressContainer( projectId );
				const url = new URL( baseUrl );

				await assertContainerRunningFn( containerName );

				proxy = createProxyServerFn( {
					containerName,
					host: url.hostname,
					port: Number.parseInt( url.port, 10 ),
				} );
				await proxy.listen();
			}
		}

		return await fetchFn( baseUrl, {
			signal: AbortSignal.timeout( FRONTEND_FETCH_TIMEOUT_MS ),
		} );
	} finally {
		if ( proxy ) {
			await proxy.close().catch( () => {} );
		}
	}
}

async function main() {
	runCommand( 'bash ./scripts/build-wporg.sh', { cwd: rootDir } );

	if ( ! fs.existsSync( zipSource ) ) {
		fail( `WP.org zip was not generated: ${ zipSource }` );
		process.exit( 1 );
	}
	pass( 'WP.org zip generated successfully.' );

	if ( ! fs.existsSync( wpEnvBin ) ) {
		fail( 'wp-env binary was not found in node_modules. Run npm ci first.' );
		process.exit( 1 );
	}

	const tempDir = fs.mkdtempSync( path.join( os.tmpdir(), 'the-drafting-table-wporg-dry-run-' ) );
	const baseUrl = 'http://localhost:8898';
	const zipTarget = path.join( tempDir, 'the-drafting-table-wporg.zip' );

	const wpEnvConfig = {
		core: 'WordPress/WordPress#6.9',
		port: 8898,
		testsPort: 8899,
		config: {
			WP_DEBUG: true,
			WP_DEBUG_LOG: true,
		},
	};

	fs.writeFileSync( path.join( tempDir, '.wp-env.json' ), JSON.stringify( wpEnvConfig, null, 4 ) + '\n', 'utf8' );
	fs.copyFileSync( zipSource, zipTarget );

	try {
		safeCommand( `${ wpEnvBin } start --update`, { cwd: tempDir } );
		safeCommand( `${ wpEnvBin } clean all`, { cwd: tempDir } );
		safeCommand(
			`${ wpEnvBin } run cli wp core install --url=${ baseUrl } --title="WP.org Dry Run" --admin_user=admin --admin_password=password --admin_email=admin@example.com --skip-email`,
			{ cwd: tempDir }
		);
		safeCommand(
			`cat "${ zipTarget }" | ${ wpEnvBin } run cli bash -lc 'cat > /tmp/the-drafting-table-wporg.zip'`,
			{ cwd: tempDir }
		);
		safeCommand(
			`${ wpEnvBin } run cli wp theme install /tmp/the-drafting-table-wporg.zip --activate --force`,
			{ cwd: tempDir }
		);

		const themeStatus = captureCommand( `${ wpEnvBin } run cli wp theme status the-drafting-table`, { cwd: tempDir } );
		if ( /Status:\s+Active/iu.test( themeStatus ) ) {
			pass( 'Packaged theme installed and activated in a fresh environment.' );
		} else {
			fail( 'Packaged theme did not report as active after install.' );
		}

		const response = await fetchFrontPageWithAutoProxy( baseUrl, {
			wpEnvCwd: tempDir,
		} );
		const responseBody = await response.text();
		if ( 200 !== response.status ) {
			fail( `Front-end request failed after activation (status ${ response.status }).` );
		} else {
			pass( 'Front-end request returned HTTP 200 after activation.' );
		}

		if ( /Fatal error/iu.test( responseBody ) ) {
			fail( 'Front-end response contains a fatal error string.' );
		} else {
			pass( 'Front-end response does not contain fatal error text.' );
		}

		const logs = captureCommand( `${ wpEnvBin } logs development --no-watch`, { cwd: tempDir } );
		if ( /PHP Fatal error|Fatal error:/iu.test( logs ) ) {
			fail( 'PHP fatal error detected in development container logs.' );
		} else {
			pass( 'No PHP fatal errors detected in development container logs.' );
		}
	} finally {
		safeCommand( `${ wpEnvBin } stop`, { cwd: tempDir } );
		fs.rmSync( tempDir, { recursive: true, force: true } );
	}

	if ( errors.length > 0 ) {
		console.error( `\nwporg:dry-run failed with ${ errors.length } issue(s).` );
		process.exit( 1 );
	}

	console.log( '\nwporg:dry-run passed.' );
}

if ( import.meta.url === `file://${ process.argv[ 1 ] }` ) {
	await main();
}
