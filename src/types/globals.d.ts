// WordPress block editor modules (for TypeScript)
declare module '@wordpress/blocks';
declare module '@wordpress/block-editor';
declare module '@wordpress/components';
declare module '@wordpress/element';
declare module '@wordpress/i18n';

declare var wp: any;

declare interface Window {
	jQuery: JQueryStatic;
	Plupload: any;
	uploader?: any;
	processed?: string;
	user?: string;
	nonce?: string;
}

declare type PluploadFile = {
	id: string;
	name: string;
	size: number;
	percent: number;
	status: number;
};
