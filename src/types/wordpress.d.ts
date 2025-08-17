// Type declarations for WordPress packages
// This file provides basic typing for WordPress packages to satisfy TypeScript

declare module '@wordpress/*' {
	const content: any;
	export = content;
}

// Specific module declarations for better type safety
declare module '@wordpress/i18n' {
	export function __(text: string, domain?: string): string;
	export function _x(text: string, context: string, domain?: string): string;
	export function _n(single: string, plural: string, number: number, domain?: string): string;
	export function sprintf(format: string, ...args: any[]): string;
}

declare module '@wordpress/blocks' {
	export interface BlockConfiguration {
		title: string;
		description?: string;
		category?: string;
		icon?: string | any;
		keywords?: string[];
		attributes?: Record<string, any>;
		supports?: Record<string, any>;
		edit: any;
		save: any;
	}

	export function registerBlockType(name: string, settings: BlockConfiguration): void;
}

declare module '@wordpress/element' {
	export const createElement: any;
	export const Fragment: any;
	export const Component: any;
	export const useState: any;
	export const useEffect: any;
	export const useCallback: any;
	export const useMemo: any;
	export const useRef: any;
}

declare module '@wordpress/block-editor' {
	export const InspectorControls: any;
	export const BlockControls: any;
	export const useBlockProps: any;
	export const RichText: any;
	export const MediaUpload: any;
	export const ColorPalette: any;
}

declare module '@wordpress/components' {
	export const PanelBody: any;
	export const RangeControl: any;
	export const TextControl: any;
	export const SelectControl: any;
	export const ToggleControl: any;
	export const TextareaControl: any;
	export const RadioControl: any;
	export const Button: any;
	export const Panel: any;
	export const Placeholder: any;
}

declare module '@wordpress/server-side-render' {
	const ServerSideRender: any;
	export default ServerSideRender;
}
