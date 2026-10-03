/**
 * The plugin version is restated in several files that each have a consumer:
 * WordPress reads the "Version:" header, runtime code reads WPDR_VERSION,
 * WordPress.org serves whatever "Stable tag" says, and npm reads package.json.
 * Release prep bumps them by hand, so this fails CI when one gets missed
 * (package.json sat at 4.0.0 through every 5.x release before this test).
 */

const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '../..');
const read = (file) => fs.readFileSync(path.join(root, file), 'utf8');

function match(file, pattern) {
	const found = read(file).match(pattern);
	if (!found) {
		throw new Error(`No version matching ${pattern} in ${file}`);
	}
	return found[1];
}

const plugin = read('wp-document-revisions.php');
const header = plugin.match(/^Version:\s*(\S+)/m);

describe('plugin version', () => {
	test('the plugin header declares a version', () => {
		expect(header).not.toBeNull();
	});

	const expected = header && header[1];

	test.each([
		['wp-document-revisions.php @version', 'wp-document-revisions.php', /@version\s+(\S+)/],
		[
			'WPDR_VERSION constant',
			'wp-document-revisions.php',
			/define\(\s*'WPDR_VERSION',\s*'([^']+)'/,
		],
		['docs/header.md Stable tag', 'docs/header.md', /^Stable tag:\s*(\S+)/m],
		['package.json version', 'package.json', /"version":\s*"([^"]+)"/],
	])('%s matches the plugin header', (_label, file, pattern) => {
		expect(match(file, pattern)).toBe(expected);
	});

	test('package-lock.json matches package.json', () => {
		const lock = JSON.parse(read('package-lock.json'));
		expect(lock.version).toBe(expected);
		expect(lock.packages[''].version).toBe(expected);
	});
});
