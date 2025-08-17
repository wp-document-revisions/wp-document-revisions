/**
 * Documents Shortcode Block
 * Modern TypeScript conversion with proper typing
 */

import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { createElement } from '@wordpress/element';
import { InspectorControls } from '@wordpress/block-editor';
import {
  PanelBody,
  RadioControl,
  RangeControl,
  TextControl,
  TextareaControl,
  ToggleControl,
} from '@wordpress/components';

import { DocumentsShortcodeAttributes, WPDRBlockProps } from '../types/blocks';

// Mock server-side render for TypeScript
const ServerSideRender =
  (window as any).wp?.serverSideRender ||
  (() => createElement('div', {}, 'Server-side rendering...'));

const TAXONOMY_OPTIONS = [
  { label: __('None', 'wp-document-revisions'), value: '' },
  { label: __('Workflow State', 'wp-document-revisions'), value: 'workflow_state' },
  { label: __('Document Type', 'wp-document-revisions'), value: 'document_type' },
];

const ORDER_OPTIONS = [
  { label: __('Ascending', 'wp-document-revisions'), value: 'ASC' },
  { label: __('Descending', 'wp-document-revisions'), value: 'DESC' },
];

const ORDERBY_OPTIONS = [
  { label: __('Date', 'wp-document-revisions'), value: 'date' },
  { label: __('Title', 'wp-document-revisions'), value: 'title' },
  { label: __('Menu Order', 'wp-document-revisions'), value: 'menu_order' },
  { label: __('Random', 'wp-document-revisions'), value: 'rand' },
];

const SHOW_EDIT_OPTIONS = [
  { label: __('Default', 'wp-document-revisions'), value: '' },
  { label: __('Show', 'wp-document-revisions'), value: 'true' },
  { label: __('Hide', 'wp-document-revisions'), value: 'false' },
];

interface DocumentsShortcodeBlockProps extends WPDRBlockProps<DocumentsShortcodeAttributes> {}

const DocumentsShortcodeEdit = ({ attributes, setAttributes }: DocumentsShortcodeBlockProps) => {
  const {
    header,
    taxonomy_0,
    term_0,
    taxonomy_1,
    term_1,
    taxonomy_2,
    term_2,
    numberposts,
    orderby,
    order,
    show_edit,
    show_thumb,
    show_descr,
    show_pdf,
    new_tab,
    freeform,
  } = attributes;

  const inspectorControls = createElement(
    InspectorControls,
    {},
    createElement(
      PanelBody,
      {
        title: __('Documents Settings', 'wp-document-revisions'),
        initialOpen: true,
      },
      createElement(TextControl, {
        label: __('Header', 'wp-document-revisions'),
        value: header,
        onChange: (value: string) => setAttributes({ header: value }),
        help: __('Optional header text for the documents list', 'wp-document-revisions'),
      }),
      createElement(RangeControl, {
        label: __('Number of Documents', 'wp-document-revisions'),
        value: numberposts,
        onChange: (value?: number) => setAttributes({ numberposts: value ?? 1 }),
        min: 1,
        max: 50,
      }),
      createElement(RadioControl, {
        label: __('Order By', 'wp-document-revisions'),
        selected: orderby || 'date',
        options: ORDERBY_OPTIONS,
        onChange: (value: string) => setAttributes({ orderby: value }),
      }),
      createElement(RadioControl, {
        label: __('Order', 'wp-document-revisions'),
        selected: order,
        options: ORDER_OPTIONS,
        onChange: (value: string) => setAttributes({ order: value }),
      })
    ),
    createElement(
      PanelBody,
      {
        title: __('Taxonomy Filters', 'wp-document-revisions'),
        initialOpen: false,
      },
      createElement(RadioControl, {
        label: __('First Taxonomy', 'wp-document-revisions'),
        selected: taxonomy_0,
        options: TAXONOMY_OPTIONS,
        onChange: (value: string) => setAttributes({ taxonomy_0: value }),
      }),
      taxonomy_0 &&
        createElement(RangeControl, {
          label: __('First Term ID', 'wp-document-revisions'),
          value: term_0,
          onChange: (value?: number) => setAttributes({ term_0: value ?? 0 }),
          min: 0,
          max: 1000,
        }),
      createElement(RadioControl, {
        label: __('Second Taxonomy', 'wp-document-revisions'),
        selected: taxonomy_1,
        options: TAXONOMY_OPTIONS,
        onChange: (value: string) => setAttributes({ taxonomy_1: value }),
      }),
      taxonomy_1 &&
        createElement(RangeControl, {
          label: __('Second Term ID', 'wp-document-revisions'),
          value: term_1,
          onChange: (value?: number) => setAttributes({ term_1: value ?? 0 }),
          min: 0,
          max: 1000,
        }),
      createElement(RadioControl, {
        label: __('Third Taxonomy', 'wp-document-revisions'),
        selected: taxonomy_2,
        options: TAXONOMY_OPTIONS,
        onChange: (value: string) => setAttributes({ taxonomy_2: value }),
      }),
      taxonomy_2 &&
        createElement(RangeControl, {
          label: __('Third Term ID', 'wp-document-revisions'),
          value: term_2,
          onChange: (value?: number) => setAttributes({ term_2: value ?? 0 }),
          min: 0,
          max: 1000,
        })
    ),
    createElement(
      PanelBody,
      {
        title: __('Display Options', 'wp-document-revisions'),
        initialOpen: false,
      },
      createElement(RadioControl, {
        label: __('Show Edit Link', 'wp-document-revisions'),
        selected: show_edit,
        options: SHOW_EDIT_OPTIONS,
        onChange: (value: string) => setAttributes({ show_edit: value }),
      }),
      createElement(ToggleControl, {
        label: __('Show Thumbnails', 'wp-document-revisions'),
        checked: show_thumb,
        onChange: (value: boolean) => setAttributes({ show_thumb: value }),
      }),
      createElement(ToggleControl, {
        label: __('Show Descriptions', 'wp-document-revisions'),
        checked: show_descr,
        onChange: (value: boolean) => setAttributes({ show_descr: value }),
      }),
      createElement(ToggleControl, {
        label: __('Show PDF Link', 'wp-document-revisions'),
        checked: show_pdf,
        onChange: (value: boolean) => setAttributes({ show_pdf: value }),
      }),
      createElement(ToggleControl, {
        label: __('Open in New Tab', 'wp-document-revisions'),
        checked: new_tab,
        onChange: (value: boolean) => setAttributes({ new_tab: value }),
      })
    ),
    createElement(
      PanelBody,
      {
        title: __('Advanced', 'wp-document-revisions'),
        initialOpen: false,
      },
      createElement(TextareaControl, {
        label: __('Additional Shortcode Parameters', 'wp-document-revisions'),
        value: freeform,
        onChange: (value: string) => setAttributes({ freeform: value }),
        help: __('Additional shortcode parameters in key="value" format', 'wp-document-revisions'),
        rows: 3,
      })
    )
  );

  const blockContent = createElement(ServerSideRender, {
    block: 'wp-document-revisions/documents-shortcode',
    attributes,
  });

  return createElement('div', {}, inspectorControls, blockContent);
};

registerBlockType('wp-document-revisions/documents-shortcode', {
  title: __('Documents List', 'wp-document-revisions'),
  description: __('Display a list of documents.', 'wp-document-revisions'),
  category: 'wpdr-category',
  icon: 'editor-ul',
  keywords: [
    __('documents', 'wp-document-revisions'),
    __('list', 'wp-document-revisions'),
    __('files', 'wp-document-revisions'),
  ],
  attributes: {
    header: {
      type: 'string',
      default: '',
    },
    taxonomy_0: {
      type: 'string',
      default: '',
    },
    term_0: {
      type: 'number',
      default: 0,
    },
    taxonomy_1: {
      type: 'string',
      default: '',
    },
    term_1: {
      type: 'number',
      default: 0,
    },
    taxonomy_2: {
      type: 'string',
      default: '',
    },
    term_2: {
      type: 'number',
      default: 0,
    },
    numberposts: {
      type: 'number',
      default: 5,
    },
    orderby: {
      type: 'string',
      default: 'date',
    },
    order: {
      type: 'string',
      default: 'ASC',
    },
    show_edit: {
      type: 'string',
      default: '',
    },
    show_thumb: {
      type: 'boolean',
      default: false,
    },
    show_descr: {
      type: 'boolean',
      default: true,
    },
    show_pdf: {
      type: 'boolean',
      default: false,
    },
    new_tab: {
      type: 'boolean',
      default: true,
    },
    freeform: {
      type: 'string',
      default: '',
    },
    align: {
      type: 'string',
    },
    backgroundColor: {
      type: 'string',
    },
    linkColor: {
      type: 'string',
    },
    textColor: {
      type: 'string',
    },
    gradient: {
      type: 'string',
    },
    fontSize: {
      type: 'string',
    },
    style: {
      type: 'object',
    },
  },
  supports: {
    align: true,
    color: {
      background: true,
      text: true,
      link: true,
      gradients: true,
    },
    typography: {
      fontSize: true,
    },
    spacing: {
      padding: true,
      margin: true,
    },
  },
  edit: DocumentsShortcodeEdit,
  save: () => null, // Server-side rendered
});
