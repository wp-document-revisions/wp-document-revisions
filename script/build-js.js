#!/usr/bin/env node

/**
 * Build script for WP Document Revisions JavaScript files
 * Minifies all .dev.js files to .js using terser
 */

const { exec } = require('child_process');
const { promisify } = require('util');
const execAsync = promisify(exec);

const files = [
	'wp-document-revisions',
	'wp-document-revisions-validate',
	'wpdr-documents-shortcode',
	'wpdr-documents-widget',
	'wpdr-revisions-shortcode',
];

async function minifyFile(filename) {
	const input = `js/${filename}.dev.js`;
	const output = `js/${filename}.js`;
	const command = `npx terser ${input} -o ${output} --compress --mangle`;

	try {
		await execAsync(command);
		console.log(`✓ Minified ${filename}`);
	} catch (error) {
		console.error(`✗ Error minifying ${filename}:`, error.message);
		process.exit(1);
	}
}

async function buildAll() {
	console.log('Building JavaScript files...\n');

	for (const file of files) {
		await minifyFile(file);
	}

	console.log('\n✓ JavaScript files minified successfully');
}

buildAll().catch((error) => {
	console.error('Build failed:', error);
	process.exit(1);
});
