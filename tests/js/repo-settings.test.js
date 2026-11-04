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

/**
 * The "main" ruleset in .github/settings.yml requires CI jobs by name. Renaming
 * a job without updating the ruleset blocks every merge on a check that never
 * reports, and dropping a ruleset from the file makes the Settings app delete it.
 */
describe('repository rulesets', () => {
	const yaml = require('js-yaml');
	const settings = yaml.load(read('.github/settings.yml'));
	const ci = yaml.load(read('.github/workflows/ci.yml'));
	const rulesets = settings.rulesets || [];

	test('keeps the rulesets the Settings app would otherwise delete', () => {
		const names = rulesets.map((ruleset) => ruleset.name);
		expect(names).toEqual(expect.arrayContaining(['main', 'release tags']));
	});

	test('every required status check names a CI job that runs on every pull request', () => {
		const main = rulesets.find((ruleset) => ruleset.name === 'main');
		const rule = main.rules.find(({ type }) => type === 'required_status_checks');
		const contexts = rule.parameters.required_status_checks.map(({ context }) => context);
		expect(contexts.length).toBeGreaterThan(0);

		// A job's check is named by its `name`, or its ID when it has none. Jobs with an
		// `if:` or a matrix-templated name can't be required safely, so they don't count.
		const jobs = Object.entries(ci.jobs)
			.filter(([, job]) => !job.if && !String(job.name || '').includes('${{'))
			.map(([id, job]) => job.name || id);

		expect(jobs).toEqual(expect.arrayContaining(contexts));
	});
});
