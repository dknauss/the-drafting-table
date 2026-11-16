#!/usr/bin/env node
/**
 * Keep Ollie's theme.json styles from leaking into this child theme.
 *
 * A child theme's theme.json is merged over Ollie's, so any style Ollie sets
 * that this theme does not set still applies (rounded buttons, comment-form
 * font sizes, pullquote borders...). This script walks Ollie's `styles` tree
 * and lists every value this theme does not set. With --write it adds each one
 * here as an empty string, which WordPress skips when it generates CSS, so the
 * core defaults this theme was designed against apply instead.
 *
 * Usage:
 *   node scripts/ollie-overrides.mjs --ollie ../ollie/theme.json          # check (exit 1 if any)
 *   node scripts/ollie-overrides.mjs --ollie ../ollie/theme.json --write  # add the blanks
 *
 * Run it after updating the Ollie version pinned in .wp-env.json.
 */
import fs from 'node:fs';
import path from 'node:path';

const args = process.argv.slice( 2 );
const ollieIndex = args.indexOf( '--ollie' );
if ( ollieIndex === -1 || ! args[ ollieIndex + 1 ] ) {
	console.error( 'Usage: node scripts/ollie-overrides.mjs --ollie <path to Ollie theme.json> [--write]' );
	process.exit( 2 );
}
const write = args.includes( '--write' );
const childPath = path.resolve( 'theme.json' );
const ollie = JSON.parse( fs.readFileSync( args[ ollieIndex + 1 ], 'utf8' ) ).styles ?? {};
const child = JSON.parse( fs.readFileSync( childPath, 'utf8' ) );
child.styles ??= {};

const missing = [];
const isObject = ( value ) => value !== null && typeof value === 'object' && ! Array.isArray( value );

function walk( source, target, trail ) {
	for ( const [ key, value ] of Object.entries( source ) ) {
		if ( trail.length === 1 && trail[ 0 ] === 'blocks' && key.startsWith( 'woocommerce/' ) ) {
			continue;
		}
		if ( isObject( value ) ) {
			if ( key in target && ! isObject( target[ key ] ) ) {
				continue;
			}
			const created = ! ( key in target );
			target[ key ] ??= {};
			walk( value, target[ key ], [ ...trail, key ] );
			if ( created && Object.keys( target[ key ] ).length === 0 ) {
				delete target[ key ];
			}
		} else if ( ! ( key in target ) ) {
			missing.push( [ ...trail, key ].join( '.' ) );
			target[ key ] = '';
		}
	}
}

walk( ollie, child.styles, [] );

if ( missing.length === 0 ) {
	console.log( 'Every Ollie style value is set or blanked in theme.json.' );
	process.exit( 0 );
}
console.log( `${ missing.length } Ollie style value(s) not set in theme.json:\n  ${ missing.join( '\n  ' ) }` );
if ( write ) {
	fs.writeFileSync( childPath, JSON.stringify( child, null, 4 ) + '\n', 'utf8' );
	console.log( 'Added them to theme.json as empty strings.' );
	process.exit( 0 );
}
process.exit( 1 );
