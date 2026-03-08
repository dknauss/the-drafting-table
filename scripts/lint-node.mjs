import fs from 'node:fs';
import path from 'node:path';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const rootDir = path.resolve( path.dirname( fileURLToPath( import.meta.url ) ), '..' );
const scriptDir = path.join( rootDir, 'scripts' );
const smokeDir = path.join( rootDir, 'tests', 'smoke' );

const filesToCheck = [
	path.join( rootDir, 'playwright.config.mjs' ),
	...fs
		.readdirSync( scriptDir )
		.filter( ( fileName ) => /\.(?:mjs|js|cjs)$/u.test( fileName ) )
		.map( ( fileName ) => path.join( scriptDir, fileName ) ),
	...fs
		.readdirSync( smokeDir )
		.filter( ( fileName ) => /\.(?:mjs|js|cjs)$/u.test( fileName ) )
		.map( ( fileName ) => path.join( smokeDir, fileName ) ),
];

const errors = [];

for ( const filePath of filesToCheck ) {
	try {
		execFileSync( process.execPath, [ '--check', filePath ], {
			stdio: 'pipe',
		} );
		console.log( `PASS: ${ path.relative( rootDir, filePath ) }` );
	} catch ( error ) {
		const message = error instanceof Error ? error.message : String( error );
		errors.push( `FAIL: ${ path.relative( rootDir, filePath ) }\n${ message }` );
	}
}

if ( errors.length > 0 ) {
	console.error( '\nNode syntax lint failed.' );
	for ( const error of errors ) {
		console.error( error );
	}
	process.exit( 1 );
}

console.log( '\nNode syntax lint passed.' );
