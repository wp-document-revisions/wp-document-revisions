/**
 * Jest mock for `@wordpress/api-fetch` (mapped via jest.moduleNameMapper).
 *
 * The real package is a webpack external (mapped to `wp.apiFetch` at build
 * time) and is not installed in node_modules, so the admin sources' new
 * `import apiFetch from '@wordpress/api-fetch'` cannot resolve under Jest.
 *
 * This mock delegates every call to `global.wp.apiFetch` — the same jest.fn
 * the admin tests already set up and assert against — so the import behaves
 * exactly like the previous `wp.apiFetch` global lookup and the existing
 * test suites keep working unchanged. The lookup is dynamic (per call), so
 * tests that reassign `global.wp.apiFetch` after loading the module still
 * intercept correctly.
 */

const apiFetch = ( ...args ) => global.wp.apiFetch( ...args );

module.exports = apiFetch;
module.exports.default = apiFetch;
module.exports.__esModule = true;
