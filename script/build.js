#!/usr/bin/env node

/**
 * Cross-platform build script to minify .dev.js files to .js using terser.
 *
 * Usage: node script/build.js
 */

const fs = require('fs');
const path = require('path');
const terser = require('terser');

const JS_DIR = path.resolve(__dirname, '..', 'js');

(async () => {
	const devFiles = fs.readdirSync(JS_DIR).filter((f) => f.endsWith('.dev.js'));

	if (devFiles.length === 0) {
		console.error('No .dev.js files found in js/');
		process.exit(1);
	}

	for (const file of devFiles) {
		const inputPath = path.join(JS_DIR, file);
		const outputPath = path.join(JS_DIR, file.replace(/\.dev\.js$/, '.js'));
		const code = fs.readFileSync(inputPath, 'utf8');

		const result = await terser.minify(code, {
			compress: true,
			mangle: true,
			format: { comments: /^!/ },
		});

		if (result.error) {
			throw result.error;
		}

		fs.writeFileSync(outputPath, result.code, 'utf8');
		console.log(`Built: ${file} -> ${path.basename(outputPath)}`);
	}
})().catch((error) => {
	console.error(error);
	process.exit(1);
});
