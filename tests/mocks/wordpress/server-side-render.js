const React = require('react');

/**
 * Minimal mock of @wordpress/server-side-render for Jest environment.
 * Returns a simple placeholder div with props serialized for debugging.
 */
function ServerSideRender(props) {
	return React.createElement(
		'div',
		{
			'data-mock': 'ServerSideRender',
			'data-block': props.block,
			style: { border: '1px dotted #ccc', padding: '4px', fontSize: '11px' },
		},
		`SSR: ${props.block}`
	);
}

module.exports = ServerSideRender;
