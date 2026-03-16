import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const rootDir = path.resolve( path.dirname( fileURLToPath( import.meta.url ) ), '..' );
const packageJsonPath = path.join( rootDir, 'package.json' );
const workflowPaths = [
	path.join( rootDir, '.github', 'workflows', 'theme-quality.yml' ),
	path.join( rootDir, '.github', 'workflows', 'wporg-release-preflight.yml' ),
];

const requiredQaFragments = [
	'composer run lint:php',
	'npm run lint:node',
	'npm run test:node',
	'npm run check:docs',
	'npm run check:qa-parity',
	'npm run test:phpunit:coverage',
	'npm run test:phpunit:coverage:check',
	'npm run wporg:check',
	'npm run test:smoke',
];

const requiredWorkflowCommands = [
	'composer lint:php',
	'npm run lint:node',
	'npm run test:node',
	'npm run check:docs',
	'npm run check:qa-parity',
	'npm run test:phpunit:coverage',
	'npm run test:phpunit:coverage:check',
	'npm run wporg:check',
];

const workflowSpecificCommands = {
	'Theme Quality': [ 'npm run test:smoke' ],
	'WP.org Release Preflight': [ 'npm run wporg:dry-run' ],
};

function resolveWorkflowName( workflowPath, content ) {
	const nameMatch = content.match( /^name:\s*(.+)$/mu );
	if ( nameMatch ) {
		return nameMatch[ 1 ].trim();
	}

	return path.basename( workflowPath );
}

export function validateQaParity( { qaScript, workflows } ) {
	const errors = [];

	for ( const fragment of requiredQaFragments ) {
		if ( ! qaScript.includes( fragment ) ) {
			errors.push( `qa script must include "${ fragment }".` );
		}
	}

	for ( const workflow of workflows ) {
		for ( const command of requiredWorkflowCommands ) {
			if ( ! workflow.content.includes( command ) ) {
				errors.push( `${ workflow.name} must include "${ command }".` );
			}
		}

		for ( const command of workflowSpecificCommands[ workflow.name ] ?? [] ) {
			if ( ! workflow.content.includes( command ) ) {
				errors.push( `${ workflow.name} must include "${ command }".` );
			}
		}
	}

	return errors;
}

function main() {
	const packageJson = JSON.parse( fs.readFileSync( packageJsonPath, 'utf8' ) );
	const qaScript = packageJson.scripts?.qa ?? '';
	const workflows = workflowPaths.map( ( workflowPath ) => {
		const content = fs.readFileSync( workflowPath, 'utf8' );
		return {
			name: resolveWorkflowName( workflowPath, content ),
			content,
		};
	} );
	const errors = validateQaParity( { qaScript, workflows } );

	if ( errors.length > 0 ) {
		console.error( 'QA parity check failed:' );
		for ( const error of errors ) {
			console.error( `- ${ error }` );
		}
		process.exit( 1 );
	}

	console.log( 'QA parity check passed.' );
}

const isDirectRun = process.argv[ 1 ] && path.resolve( process.argv[ 1 ] ) === fileURLToPath( import.meta.url );
if ( isDirectRun ) {
	main();
}
