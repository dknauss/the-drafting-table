import fs from 'node:fs';
import net from 'node:net';
import path from 'node:path';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const defaultRootDir = path.resolve( path.dirname( fileURLToPath( import.meta.url ) ), '..' );

export function formatBytes( bytes ) {
	const units = [ 'B', 'KiB', 'MiB', 'GiB', 'TiB' ];
	let value = Number( bytes );
	let unitIndex = 0;

	while ( value >= 1024 && unitIndex < units.length - 1 ) {
		value /= 1024;
		unitIndex += 1;
	}

	return `${ value.toFixed( 2 ) } ${ units[ unitIndex ] }`;
}

export function runCommand( command, args ) {
	try {
		const output = execFileSync( command, args, {
			encoding: 'utf8',
			stdio: [ 'ignore', 'pipe', 'pipe' ],
		} );

		return {
			ok: true,
			output,
			errorOutput: '',
		};
	} catch ( error ) {
		return {
			ok: false,
			output:
				typeof error.stdout === 'string'
					? error.stdout
					: Buffer.isBuffer( error.stdout )
						? error.stdout.toString( 'utf8' )
						: '',
			errorOutput:
				typeof error.stderr === 'string'
					? error.stderr
					: Buffer.isBuffer( error.stderr )
						? error.stderr.toString( 'utf8' )
						: '',
		};
	}
}

export async function isPortAvailable( port ) {
	return new Promise( ( resolve ) => {
		const server = net.createServer();
		server.unref();

		server.on( 'error', ( error ) => {
			if ( error && 'EADDRINUSE' === error.code ) {
				resolve( false );
				return;
			}

			resolve( false );
		} );

		// Probe the wildcard interface so we catch ports already claimed for host-wide publishing.
		server.listen( port, '0.0.0.0', () => {
			server.close( () => resolve( true ) );
		} );
	} );
}

export function recoveryActions( options = {} ) {
	const minFreeGiB = Number( options.minFreeGiB ?? '2' );

	return [
		`Free disk space to at least ${ minFreeGiB } GiB available on the Docker host filesystem.`,
		'Free occupied wp-env ports (default 8888/8889) or set WP_ENV_PORT and WP_ENV_TESTS_PORT to open ports.',
		'Restart Docker Desktop/daemon and re-run `npm run preflight:env`.',
		'If Docker is healthy, run `docker system prune -af --volumes` to clear stale build data.',
		'If metadata I/O errors persist, reset Docker\'s disk image/store and retry.',
	];
}

export async function runPreflightChecks( options = {} ) {
	const rootDir = options.rootDir ?? defaultRootDir;
	const env = options.env ?? process.env;
	const minFreeGiB = Number( options.minFreeGiB ?? env.WP_ENV_MIN_FREE_GIB ?? '2' );
	const warnFreeGiB = Number( options.warnFreeGiB ?? env.WP_ENV_WARN_FREE_GIB ?? '4' );
	const statfsFn = options.statfsFn ?? fs.statfsSync;
	const runCommandFn = options.runCommandFn ?? runCommand;
	const isPortAvailableFn = options.isPortAvailableFn ?? isPortAvailable;

	const minFreeBytes = minFreeGiB * 1024 * 1024 * 1024;
	const warnFreeBytes = warnFreeGiB * 1024 * 1024 * 1024;

	const failures = [];
	const warnings = [];
	const passes = [];

	const filesystem = statfsFn( rootDir );
	const freeBytes = Number( filesystem.bavail ) * Number( filesystem.bsize );

	if ( freeBytes < minFreeBytes ) {
		failures.push(
			`Free disk space is ${ formatBytes( freeBytes ) }, below required ${ formatBytes( minFreeBytes ) } for wp-env startup/build operations.`
		);
	} else {
		passes.push( `Free disk space ${ formatBytes( freeBytes ) }.` );
		if ( freeBytes < warnFreeBytes ) {
			warnings.push(
				`Free disk space is ${ formatBytes( freeBytes ) }; recommended headroom is at least ${ formatBytes( warnFreeBytes ) } for stable Docker rebuilds.`
			);
		}
	}

	const versionCheck = runCommandFn( 'docker', [ 'version', '--format', '{{.Server.Version}}' ] );
	if ( ! versionCheck.ok ) {
		const output = `${ versionCheck.output }\n${ versionCheck.errorOutput }`.trim();
		failures.push(
			`Docker daemon is not reachable.\n${ output || 'No diagnostic output from docker version.' }`
		);
	} else {
		passes.push( `Docker daemon reachable (server ${ versionCheck.output.trim() }).` );

		const storageCheck = runCommandFn( 'docker', [ 'system', 'df' ] );
		if ( ! storageCheck.ok ) {
			const output = `${ storageCheck.output }\n${ storageCheck.errorOutput }`.trim();
			const hasIoSignature = /input\/output error|meta\.db|failed to retrieve image list/iu.test( output );

			if ( hasIoSignature ) {
				failures.push( `Docker storage metadata appears unhealthy (containerd I/O error).\n${ output }` );
			} else {
				failures.push(
					`Docker storage check failed.\n${ output || 'No diagnostic output from docker system df.' }`
				);
			}
		} else {
			passes.push( 'Docker storage metadata query succeeded.' );
		}
	}

	const expectedPorts = [
		{
			name: 'WP_ENV_PORT',
			value: Number( env.WP_ENV_PORT ?? '8888' ),
		},
		{
			name: 'WP_ENV_TESTS_PORT',
			value: Number( env.WP_ENV_TESTS_PORT ?? '8889' ),
		},
	];

	for ( const portConfig of expectedPorts ) {
		if ( ! Number.isInteger( portConfig.value ) || portConfig.value <= 0 || portConfig.value > 65535 ) {
			failures.push(
				`${ portConfig.name } must be a valid TCP port, received "${ String( env[ portConfig.name ] ?? portConfig.value ) }".`
			);
			continue;
		}

		const available = await isPortAvailableFn( portConfig.value );
		if ( ! available ) {
			failures.push(
				`Port ${ portConfig.value } is already in use. Free the port or set ${ portConfig.name } to an available value before running wp-env.`
			);
			continue;
		}

		passes.push( `Port ${ portConfig.value } available for ${ portConfig.name }.` );
	}

	return { passes, warnings, failures, minFreeGiB };
}

async function main() {
	const { passes, warnings, failures, minFreeGiB } = await runPreflightChecks();

	for ( const pass of passes ) {
		console.log( `PASS: ${ pass }` );
	}

	if ( warnings.length > 0 ) {
		console.warn( '\nPreflight warnings:' );
		for ( const warning of warnings ) {
			console.warn( `- ${ warning }` );
		}
	}

	if ( failures.length > 0 ) {
		console.error( '\nwp-env preflight failed:' );
		for ( const failure of failures ) {
			console.error( `- ${ failure }` );
		}

		console.error( '\nRecommended recovery actions:' );
		for ( const [ index, action ] of recoveryActions( { minFreeGiB } ).entries() ) {
			console.error( `${ index + 1 }. ${ action }` );
		}
		process.exit( 1 );
	}

	console.log( '\nwp-env preflight passed.' );
}

const isDirectRun = process.argv[ 1 ] && path.resolve( process.argv[ 1 ] ) === fileURLToPath( import.meta.url );
if ( isDirectRun ) {
	await main();
}
