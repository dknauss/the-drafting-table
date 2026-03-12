import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const rootDir = path.resolve( path.dirname( fileURLToPath( import.meta.url ) ), '..' );
const docsToValidate = [
	path.join( rootDir, 'docs', 'wporg-release-checklist.md' ),
	path.join( rootDir, 'readme.txt' ),
];
const packageJsonPath = path.join( rootDir, 'package.json' );
const composerJsonPath = path.join( rootDir, 'composer.json' );

const packageJson = JSON.parse( fs.readFileSync( packageJsonPath, 'utf8' ) );
const composerJson = JSON.parse( fs.readFileSync( composerJsonPath, 'utf8' ) );

const npmScripts = new Set( Object.keys( packageJson.scripts ?? {} ) );
const composerScripts = new Set( Object.keys( composerJson.scripts ?? {} ) );
const errors = [];

function extractInlineCommands( markdown ) {
	return Array.from( markdown.matchAll( /`([^`]+)`/gu ), ( match ) => match[ 1 ].trim() );
}

function checkNpmCommand( command, sourcePath ) {
	const npmMatch = command.match( /(?:^|\s)npm run ([A-Za-z0-9:_-]+)/u );
	if ( ! npmMatch ) {
		return;
	}

	const scriptName = npmMatch[ 1 ];
	if ( ! npmScripts.has( scriptName ) ) {
		errors.push( `Unknown npm script in ${ sourcePath }: "${ scriptName }" (from "${ command }").` );
	}
}

function checkComposerCommand( command, sourcePath ) {
	const composerRunMatch = command.match( /(?:^|\s)composer run ([A-Za-z0-9:_-]+)/u );
	if ( composerRunMatch ) {
		const scriptName = composerRunMatch[ 1 ];
		if ( ! composerScripts.has( scriptName ) ) {
			errors.push( `Unknown Composer script in ${ sourcePath }: "${ scriptName }" (from "${ command }").` );
		}
		return;
	}

	const composerMatch = command.match( /(?:^|\s)composer ([A-Za-z0-9:_-]+)/u );
	if ( ! composerMatch ) {
		return;
	}

	const candidate = composerMatch[ 1 ];
	if ( composerScripts.has( candidate ) ) {
		errors.push( `Use "composer run ${ candidate }" in ${ sourcePath } (found "${ command }").` );
	}
}

for ( const sourcePath of docsToValidate ) {
	const source = fs.readFileSync( sourcePath, 'utf8' );
	const sourceName = path.relative( rootDir, sourcePath );

	for ( const command of extractInlineCommands( source ) ) {
		checkNpmCommand( command, sourceName );
		checkComposerCommand( command, sourceName );
	}
}

if ( errors.length > 0 ) {
	console.error( 'Documentation command check failed:' );
	for ( const error of errors ) {
		console.error( `- ${ error }` );
	}
	process.exit( 1 );
}

console.log( 'Documentation command check passed.' );
