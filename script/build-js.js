#!/usr/bin/env node

/**
 * Build script for WP Document Revisions JavaScript files
 * Minifies all .dev.js files to .js using terser
 */

const { execFile } = require('child_process');
const { promisify } = require('util');
const path = require('path');

const execFileAsync = promisify(execFile);

const files = [
	'wp-document-revisions',
	'wp-document-revisions-validate',
	'wpdr-documents-shortcode',
	'wpdr-documents-widget',
	'wpdr-revisions-shortcode',
];

async function minifyFile(filename) {
	const input = path.join('js', `${filename}.dev.js`);
	const output = path.join('js', `${filename}.js`);

	try {
		// Use execFile for better security - no shell interpretation
		await execFileAsync('npx', ['terser', input, '-o', output, '--compress', '--mangle']);
		console.log(`✓ Minified ${filename}`);
	} catch (error) {
		console.error(`✗ Error minifying ${filename}:`, error.message);
		throw error;
	}
}

async function buildAll() {
	console.log('Building JavaScript files...\n');

	try {
		for (const file of files) {
			await minifyFile(file);
		}
		console.log('\n✓ JavaScript files minified successfully');
	} catch (error) {
		console.error('\nBuild failed:', error.message);
		process.exit(1);
	}
}

buildAll();
