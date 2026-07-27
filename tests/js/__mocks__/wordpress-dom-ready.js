/**
 * Jest mock for `@wordpress/dom-ready` (mapped via jest.moduleNameMapper).
 *
 * The real package is a webpack external and is not installed in
 * node_modules. Under Jest the DOM is already "complete", so — matching the
 * real package's behaviour in that state — this mock invokes the callback
 * synchronously. That preserves the previous test contract where requiring
 * src/admin/wp-document-revisions.js instantiates the class immediately.
 */

const domReady = ( callback ) => {
	callback();
};

module.exports = domReady;
module.exports.default = domReady;
module.exports.__esModule = true;
