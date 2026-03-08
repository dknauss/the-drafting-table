import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const rootDir = path.resolve( path.dirname( fileURLToPath( import.meta.url ) ), '..' );
const reportPath = path.join( rootDir, 'test-results', 'phpunit', 'clover.xml' );

function asPercent( covered, total ) {
	if ( total <= 0 ) {
		return 100;
	}

	return ( covered / total ) * 100;
}

function readCoverage() {
	if ( ! fs.existsSync( reportPath ) ) {
		throw new Error( `Coverage report not found at ${ reportPath }` );
	}

	const xml = fs.readFileSync( reportPath, 'utf8' );
	const fileCoverage = new Map();

	for ( const match of xml.matchAll( /<file name="([^"]+)">([\s\S]*?)<\/file>/gu ) ) {
		const fileName = match[ 1 ];
		const fileBody = match[ 2 ];
		const metrics = fileBody.match( /<metrics[^>]*statements="(\d+)"[^>]*coveredstatements="(\d+)"/u );

		if ( ! metrics ) {
			continue;
		}

		const statements = Number.parseInt( metrics[ 1 ], 10 );
		const covered = Number.parseInt( metrics[ 2 ], 10 );
		fileCoverage.set( fileName.replaceAll( '\\', '/' ), {
			statements,
			covered,
			percent: asPercent( covered, statements ),
		} );
	}

	const projectMetrics = xml.match( /<metrics files="\d+"[^>]*statements="(\d+)"[^>]*coveredstatements="(\d+)"/u );
	if ( ! projectMetrics ) {
		throw new Error( 'Project-level metrics were not found in clover.xml.' );
	}

	const projectStatements = Number.parseInt( projectMetrics[ 1 ], 10 );
	const projectCovered = Number.parseInt( projectMetrics[ 2 ], 10 );

	return {
		fileCoverage,
		project: {
			statements: projectStatements,
			covered: projectCovered,
			percent: asPercent( projectCovered, projectStatements ),
		},
	};
}

function findFileEntry( fileCoverage, relativePath ) {
	for ( const [ fileName, entry ] of fileCoverage.entries() ) {
		if ( fileName.endsWith( relativePath ) ) {
			return {
				fileName,
				...entry,
			};
		}
	}

	return null;
}

const checks = {
	projectMin: 65,
	fileMinimums: [
		{ path: 'functions.php', min: 50 },
		{ path: 'companion-plugin/the-drafting-table-companion/inc/create-pages.php', min: 70 },
		{ path: 'companion-plugin/the-drafting-table-companion/inc/seo-meta.php', min: 80 },
	],
};

const errors = [];

try {
	const coverage = readCoverage();
	const projectPercent = coverage.project.percent;

	console.log( `Coverage total: ${ projectPercent.toFixed( 2 ) }%` );

	if ( projectPercent < checks.projectMin ) {
		errors.push( `Overall line coverage ${ projectPercent.toFixed( 2 ) }% is below ${ checks.projectMin }%.` );
	}

	for ( const fileCheck of checks.fileMinimums ) {
		const fileEntry = findFileEntry( coverage.fileCoverage, fileCheck.path );
		if ( ! fileEntry ) {
			errors.push( `Coverage entry missing for ${ fileCheck.path }.` );
			continue;
		}

		console.log( `${ fileCheck.path }: ${ fileEntry.percent.toFixed( 2 ) }%` );
		if ( fileEntry.percent < fileCheck.min ) {
			errors.push( `${ fileCheck.path } coverage ${ fileEntry.percent.toFixed( 2 ) }% is below ${ fileCheck.min }%.` );
		}
	}
} catch ( error ) {
	const message = error instanceof Error ? error.message : String( error );
	errors.push( message );
}

if ( errors.length > 0 ) {
	console.error( '\nCoverage gate failed:' );
	for ( const error of errors ) {
		console.error( `- ${ error }` );
	}
	process.exit( 1 );
}

console.log( '\nCoverage gate passed.' );
