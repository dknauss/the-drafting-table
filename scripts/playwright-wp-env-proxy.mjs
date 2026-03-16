import net from 'node:net';
import path from 'node:path';
import process from 'node:process';
import { spawn } from 'node:child_process';

const DEFAULT_HOST = '127.0.0.1';
const DEFAULT_CONTAINER_PORT = 80;
const INSTALL_PATH_SUCCESS_PREFIX = '✔';

export function normalizePort( value, fallback = '8888' ) {
	const resolvedValue = value ?? fallback;
	const port = Number.parseInt( `${ resolvedValue }`, 10 );

	if ( ! Number.isInteger( port ) || port < 1 || port > 65_535 ) {
		throw new Error( `Expected a valid TCP port, received "${ resolvedValue }".` );
	}

	return port;
}

export function parseWpEnvProjectId( installPathOutput ) {
	const firstNonEmptyLine = `${ installPathOutput }`
		.split( /\r?\n/u )
		.map( ( line ) => line.trim() )
		.find( Boolean );

	if ( ! firstNonEmptyLine ) {
		throw new Error( 'wp-env install-path did not return a usable path.' );
	}

	const sanitizedPath = firstNonEmptyLine.startsWith( INSTALL_PATH_SUCCESS_PREFIX )
		? firstNonEmptyLine.slice( INSTALL_PATH_SUCCESS_PREFIX.length ).trim()
		: firstNonEmptyLine;
	const projectId = path.basename( sanitizedPath );

	if ( ! /^[a-f0-9]{32}$/u.test( projectId ) ) {
		throw new Error( `Could not derive a wp-env project id from "${ sanitizedPath }".` );
	}

	return projectId;
}

export function defaultWpEnvWordpressContainer( projectId ) {
	return `${ projectId }-wordpress-1`;
}

export function shouldAutoProxy( baseURL ) {
	const url = new URL( baseURL );
	return [ 'localhost', '127.0.0.1' ].includes( url.hostname );
}

export async function isBaseUrlReachable( baseURL, { fetchFn = fetch, timeoutMs = 1_500 } = {} ) {
	try {
		const url = new URL( '/wp-login.php', baseURL );
		const response = await fetchFn( url, {
			redirect: 'manual',
			signal: AbortSignal.timeout( timeoutMs ),
		} );

		return response.ok || response.status >= 300;
	} catch {
		return false;
	}
}

export async function runCommand( command, args, { cwd = process.cwd(), env = process.env } = {} ) {
	return new Promise( ( resolve, reject ) => {
		const child = spawn( command, args, {
			cwd,
			env,
			stdio: [ 'ignore', 'pipe', 'pipe' ],
		} );

		let stdout = '';
		let stderr = '';

		child.stdout.on( 'data', ( chunk ) => {
			stdout += chunk.toString();
		} );

		child.stderr.on( 'data', ( chunk ) => {
			stderr += chunk.toString();
		} );

		child.on( 'error', reject );
		child.on( 'exit', ( code ) => {
			if ( 0 === code ) {
				resolve( stdout );
				return;
			}

			reject(
				new Error(
					`${ command } ${ args.join( ' ' ) } exited with code ${ code }.\n${ stderr || stdout }`.trim()
				)
			);
		} );
	} );
}

export async function assertContainerRunning( containerName, { runCommandFn = runCommand } = {} ) {
	const output = await runCommandFn( 'docker', [ 'inspect', '--format', '{{.State.Running}}', containerName ] );
	if ( 'true' !== `${ output }`.trim() ) {
		throw new Error( `Container "${ containerName }" is not running.` );
	}

	return containerName;
}

export function createDockerExecProxyServer( {
	containerName,
	host = DEFAULT_HOST,
	port,
	containerPort = DEFAULT_CONTAINER_PORT,
	spawnFn = spawn,
	stderr = process.stderr,
} ) {
	const tunnelCommand = `exec 3<>/dev/tcp/127.0.0.1/${ containerPort }; cat <&3 & cat >&3`;
	const server = net.createServer( ( socket ) => {
		const child = spawnFn( 'docker', [ 'exec', '-i', containerName, 'bash', '-lc', tunnelCommand ], {
			stdio: [ 'pipe', 'pipe', 'pipe' ],
		} );

		socket.pipe( child.stdin );
		child.stdout.pipe( socket );
		child.stderr.pipe( stderr, { end: false } );

		const stopChild = () => {
			if ( ! child.killed ) {
				child.kill();
			}
		};

		socket.on( 'error', stopChild );
		socket.on( 'close', stopChild );
		child.on( 'exit', () => socket.end() );
	} );

	return {
		host,
		port,
		server,
		listen() {
			return new Promise( ( resolve, reject ) => {
				server.once( 'error', reject );
				server.listen( port, host, () => {
					server.off( 'error', reject );
					resolve();
				} );
			} );
		},
		close() {
			return new Promise( ( resolve, reject ) => {
				server.close( ( error ) => {
					if ( error ) {
						reject( error );
						return;
					}

					resolve();
				} );
			} );
		},
	};
}

async function main() {
	const port = normalizePort( process.env.WP_ENV_PORT );
	const installPathOutput = await runCommand( 'npx', [ 'wp-env', 'install-path' ] );
	const projectId = parseWpEnvProjectId( installPathOutput );
	const containerName = process.env.WP_ENV_WORDPRESS_CONTAINER || defaultWpEnvWordpressContainer( projectId );
	await assertContainerRunning( containerName );

	const proxy = createDockerExecProxyServer( { containerName, port } );
	await proxy.listen();

	process.stdout.write( `Proxying http://${ DEFAULT_HOST }:${ port } -> ${ containerName }:80\n` );

	const shutdown = async () => {
		await proxy.close().catch( () => {} );
		process.exit( 0 );
	};

	process.on( 'SIGINT', shutdown );
	process.on( 'SIGTERM', shutdown );
}

if ( import.meta.url === `file://${ process.argv[ 1 ] }` ) {
	main().catch( ( error ) => {
		process.stderr.write( `${ error.message }\n` );
		process.exit( 1 );
	} );
}
