/**
 * The GitHub repository description in .github/settings.yml (synced by the
 * Repository Settings app) restates the WordPress.org short description from
 * docs/header.md, since GitHub can't read it from there. This fails CI when
 * one is edited without the other.
 */

const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '../..');
const read = (file) => fs.readFileSync(path.join(root, file), 'utf8');

// The short description is the first paragraph after the readme header fields.
const tagline = read('docs/header.md').match(/^Stable tag:[\s\S]*?\n\n(.+)$/m);
const description = read('.github/settings.yml').match(/^ {4}description:\s*(.+)$/m);

describe('repository settings', () => {
	test('the GitHub description matches the WordPress.org short description', () => {
		expect(tagline).not.toBeNull();
		expect(description).not.toBeNull();
		expect(description[1].trim()).toBe(tagline[1].trim());
	});
});
