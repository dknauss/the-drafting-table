import fs from 'node:fs';
import path from 'node:path';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const rootDir = path.resolve( path.dirname( fileURLToPath( import.meta.url ) ), '..' );
const minFreeGiB = Number( process.env.WP_ENV_MIN_FREE_GIB ?? '2' );
const warnFreeGiB = Number( process.env.WP_ENV_WARN_FREE_GIB ?? '4' );
const minFreeBytes = minFreeGiB * 1024 * 1024 * 1024;
const warnFreeBytes = warnFreeGiB * 1024 * 1024 * 1024;

const failures = [];
const warnings = [];

function formatBytes( bytes ) {
	const units = [ 'B', 'KiB', 'MiB', 'GiB', 'TiB' ];
	let value = Number( bytes );
	let unitIndex = 0;

	while ( value >= 1024 && unitIndex < units.length - 1 ) {
		value /= 1024;
		unitIndex += 1;
	}

	return `${ value.toFixed( 2 ) } ${ units[ unitIndex ] }`;
}

function runCommand( command, args ) {
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

function checkDiskSpace() {
	const filesystem = fs.statfsSync( rootDir );
	const freeBytes = Number( filesystem.bavail ) * Number( filesystem.bsize );

	if ( freeBytes < minFreeBytes ) {
		failures.push(
			`Free disk space is ${ formatBytes( freeBytes ) }, below required ${ formatBytes( minFreeBytes ) } for wp-env startup/build operations.`
		);
		return;
	}

	if ( freeBytes < warnFreeBytes ) {
		warnings.push(
			`Free disk space is ${ formatBytes( freeBytes ) }; recommended headroom is at least ${ formatBytes( warnFreeBytes ) } for stable Docker rebuilds.`
		);
	}

	console.log( `PASS: Free disk space ${ formatBytes( freeBytes ) }.` );
}

function checkDockerHealth() {
	const versionCheck = runCommand( 'docker', [ 'version', '--format', '{{.Server.Version}}' ] );
	if ( ! versionCheck.ok ) {
		const output = `${ versionCheck.output }\n${ versionCheck.errorOutput }`.trim();
		failures.push(
			`Docker daemon is not reachable.\n${ output || 'No diagnostic output from docker version.' }`
		);
		return;
	}

	console.log( `PASS: Docker daemon reachable (server ${ versionCheck.output.trim() }).` );

	const storageCheck = runCommand( 'docker', [ 'system', 'df' ] );
	if ( ! storageCheck.ok ) {
		const output = `${ storageCheck.output }\n${ storageCheck.errorOutput }`.trim();
		const hasIoSignature = /input\/output error|meta\.db|failed to retrieve image list/iu.test( output );

		if ( hasIoSignature ) {
			failures.push(
				`Docker storage metadata appears unhealthy (containerd I/O error).\n${ output }`
			);
		} else {
			failures.push(
				`Docker storage check failed.\n${ output || 'No diagnostic output from docker system df.' }`
			);
		}
		return;
	}

	console.log( 'PASS: Docker storage metadata query succeeded.' );
}

checkDiskSpace();
checkDockerHealth();

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
	console.error( '1. Free disk space to at least 4 GiB available on the Docker host filesystem.' );
	console.error( '2. Restart Docker Desktop/daemon and re-run `npm run preflight:env`.' );
	console.error( '3. If Docker is healthy, run `docker system prune -af --volumes` to clear stale build data.' );
	console.error( '4. If metadata I/O errors persist, reset Docker\'s disk image/store and retry.' );
	process.exit( 1 );
}

console.log( '\nwp-env preflight passed.' );
