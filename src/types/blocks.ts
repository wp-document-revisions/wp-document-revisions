/**
 * Block types and interfaces for WordPress Document Revisions
 */

export interface BaseBlockAttributes {
	align?: string;
	backgroundColor?: string;
	linkColor?: string;
	textColor?: string;
	gradient?: string;
	fontSize?: string;
	style?: Record<string, any>;
}

export interface DocumentsShortcodeAttributes extends BaseBlockAttributes {
	header: string;
	taxonomy_0: string;
	term_0: number;
	taxonomy_1: string;
	term_1: number;
	taxonomy_2: string;
	term_2: number;
	numberposts: number;
	orderby?: string;
	order: string;
	show_edit: string;
	show_thumb: boolean;
	show_descr: boolean;
	show_pdf: boolean;
	new_tab: boolean;
	freeform: string;
}

export interface DocumentsWidgetAttributes extends BaseBlockAttributes {
	header?: string;
	numberposts: number;
	post_stat_publish: boolean;
	post_stat_private: boolean;
	post_stat_draft: boolean;
	show_thumb: boolean;
	show_descr: boolean;
	show_author: boolean;
	show_pdf: boolean;
	new_tab: boolean;
}

export interface RevisionsShortcodeAttributes extends BaseBlockAttributes {
	id: number;
	numberposts: number;
	summary: boolean;
	show_pdf: boolean;
	new_tab: boolean;
}

export interface TaxonomyOption {
	label: string;
	value: string;
}

export interface TermOption {
	label: string;
	value: number;
}

export interface WPDRBlockProps<T = any> {
	attributes: T;
	setAttributes: (attributes: Partial<T>) => void;
	isSelected?: boolean;
	className?: string;
}
