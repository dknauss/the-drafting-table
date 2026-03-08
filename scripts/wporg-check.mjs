import fs from 'node:fs';
import path from 'node:path';
import { execSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const rootDir = path.resolve( path.dirname( fileURLToPath( import.meta.url ) ), '..' );
const errors = [];

function pass( message ) {
	console.log( `PASS: ${ message }` );
}

function fail( message ) {
	errors.push( message );
	console.error( `FAIL: ${ message }` );
}

function parseColonHeaders( filePath ) {
	const headers = {};
	const contents = fs.readFileSync( filePath, 'utf8' );

	for ( const line of contents.split( /\r?\n/u ) ) {
		const match = line.match( /^([A-Za-z][A-Za-z\s]+):\s*(.+)$/u );
		if ( match ) {
			headers[ match[ 1 ].trim() ] = match[ 2 ].trim();
		}
	}

	return headers;
}

function readPngDimensions( filePath ) {
	const buffer = fs.readFileSync( filePath );
	if ( buffer.length < 24 ) {
		throw new Error( 'PNG header is incomplete.' );
	}

	const signature = buffer.subarray( 0, 8 ).toString( 'hex' );
	if ( '89504e470d0a1a0a' !== signature ) {
		throw new Error( 'Invalid PNG signature.' );
	}

	return {
		width: buffer.readUInt32BE( 16 ),
		height: buffer.readUInt32BE( 20 ),
	};
}

function runCommand( command, options = {} ) {
	execSync( command, {
		cwd: rootDir,
		stdio: 'inherit',
		...options,
	} );
}

const styleHeaders = parseColonHeaders( path.join( rootDir, 'style.css' ) );
const readmeHeaders = parseColonHeaders( path.join( rootDir, 'readme.txt' ) );

for ( const header of [ 'Theme Name', 'Version', 'Requires at least', 'Tested up to', 'Requires PHP', 'License', 'License URI', 'Text Domain' ] ) {
	if ( styleHeaders[ header ] ) {
		pass( `style.css header present: ${ header }` );
	} else {
		fail( `style.css header missing: ${ header }` );
	}
}

for ( const header of [ 'Requires at least', 'Tested up to', 'Requires PHP', 'Stable tag', 'License', 'License URI' ] ) {
	if ( readmeHeaders[ header ] ) {
		pass( `readme.txt header present: ${ header }` );
	} else {
		fail( `readme.txt header missing: ${ header }` );
	}
}

if ( styleHeaders.Version && readmeHeaders[ 'Stable tag' ] ) {
	if ( styleHeaders.Version === readmeHeaders[ 'Stable tag' ] ) {
		pass( 'Version parity: style.css Version matches readme Stable tag.' );
	} else {
		fail( `Version mismatch: style.css=${ styleHeaders.Version } readme=${ readmeHeaders[ 'Stable tag' ] }` );
	}
}

for ( const sharedHeader of [ 'Requires at least', 'Tested up to', 'Requires PHP' ] ) {
	if ( styleHeaders[ sharedHeader ] && readmeHeaders[ sharedHeader ] && styleHeaders[ sharedHeader ] !== readmeHeaders[ sharedHeader ] ) {
		fail( `${ sharedHeader } mismatch between style.css and readme.txt.` );
	}
}

try {
	const screenshot = readPngDimensions( path.join( rootDir, 'screenshot.png' ) );
	if ( 1200 === screenshot.width && 900 === screenshot.height ) {
		pass( 'screenshot.png dimensions are 1200x900.' );
	} else {
		fail( `screenshot.png must be 1200x900; found ${ screenshot.width }x${ screenshot.height }.` );
	}
} catch ( error ) {
	fail( `Unable to validate screenshot.png: ${ error.message }` );
}

runCommand( 'bash ./scripts/build-wporg.sh' );

const packageDir = path.join( rootDir, 'dist', 'wporg', 'the-drafting-table' );
const requiredPackageFiles = [ 'style.css', 'readme.txt', 'theme.json', 'screenshot.png', 'functions.php' ];
const forbiddenPackagePaths = [
	'.github',
	'companion-plugin',
	'demo-content',
	'tests',
	'scripts',
	'package.json',
	'package-lock.json',
	'composer.json',
	'composer.lock',
	'phpunit.xml.dist',
	'.distignore.wporg',
	'.wp-env.json',
	'playwright.config.mjs',
];

for ( const requiredPath of requiredPackageFiles ) {
	if ( fs.existsSync( path.join( packageDir, requiredPath ) ) ) {
		pass( `Package includes ${ requiredPath }.` );
	} else {
		fail( `Package is missing ${ requiredPath }.` );
	}
}

for ( const forbiddenPath of forbiddenPackagePaths ) {
	if ( fs.existsSync( path.join( packageDir, forbiddenPath ) ) ) {
		fail( `Package contains forbidden path: ${ forbiddenPath }.` );
	} else {
		pass( `Package excludes ${ forbiddenPath }.` );
	}
}

const packagedFunctions = fs.readFileSync( path.join( packageDir, 'functions.php' ), 'utf8' );
if ( packagedFunctions.includes( 'inc/create-pages.php' ) || packagedFunctions.includes( 'the_drafting_table_seo_meta' ) ) {
	fail( 'Theme runtime still contains onboarding/SEO bootstrap references in functions.php.' );
} else {
	pass( 'Theme runtime excludes onboarding/SEO bootstrap references.' );
}

if ( process.env.CI === 'true' ) {
	runCommand( 'npm run themecheck' );
	pass( 'Theme Check executed in CI mode.' );
} else {
	console.log( 'INFO: Skipping Theme Check locally (set CI=true to enforce).' );
}

if ( errors.length > 0 ) {
	console.error( `\nwporg:check failed with ${ errors.length } issue(s).` );
	process.exit( 1 );
}

console.log( '\nwporg:check passed.' );
