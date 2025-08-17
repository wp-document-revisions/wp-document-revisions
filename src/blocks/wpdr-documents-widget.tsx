/**
 * Documents Widget Block
 * Modern TypeScript conversion with proper typing
 */

import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { createElement } from '@wordpress/element';
import { InspectorControls } from '@wordpress/block-editor';
import { 
  PanelBody, 
  RangeControl, 
  TextControl, 
  ToggleControl 
} from '@wordpress/components';

import { DocumentsWidgetAttributes, WPDRBlockProps } from '../types/blocks';

// Mock server-side render for TypeScript
const ServerSideRender = (window as any).wp?.serverSideRender || (() => createElement('div', {}, 'Server-side rendering...'));

interface DocumentsWidgetBlockProps extends WPDRBlockProps<DocumentsWidgetAttributes> {}

const DocumentsWidgetEdit = ({ attributes, setAttributes }: DocumentsWidgetBlockProps) => {
  const {
    header,
    numberposts,
    post_stat_publish,
    post_stat_private,
    post_stat_draft,
    show_thumb,
    show_descr,
    show_author,
    show_pdf,
    new_tab
  } = attributes;

  const inspectorControls = createElement(InspectorControls, {},
    createElement(PanelBody, {
      title: __('Widget Settings', 'wp-document-revisions'),
      initialOpen: true
    },
      createElement(TextControl, {
        label: __('Header', 'wp-document-revisions'),
        value: header || '',
        onChange: (value: string) => setAttributes({ header: value }),
        help: __('Optional header text for the widget', 'wp-document-revisions')
      }),
      createElement(RangeControl, {
        label: __('Number of Documents', 'wp-document-revisions'),
        value: numberposts,
        onChange: (value?: number) => setAttributes({ numberposts: value ?? 1 }),
        min: 1,
        max: 20,
        help: __('How many documents to display', 'wp-document-revisions')
      })
    ),
    createElement(PanelBody, {
      title: __('Post Status', 'wp-document-revisions'),
      initialOpen: false
    },
      createElement(ToggleControl, {
        label: __('Show Published Documents', 'wp-document-revisions'),
        checked: post_stat_publish,
        onChange: (value: boolean) => setAttributes({ post_stat_publish: value })
      }),
      createElement(ToggleControl, {
        label: __('Show Private Documents', 'wp-document-revisions'),
        checked: post_stat_private,
        onChange: (value: boolean) => setAttributes({ post_stat_private: value })
      }),
      createElement(ToggleControl, {
        label: __('Show Draft Documents', 'wp-document-revisions'),
        checked: post_stat_draft,
        onChange: (value: boolean) => setAttributes({ post_stat_draft: value })
      })
    ),
    createElement(PanelBody, {
      title: __('Display Options', 'wp-document-revisions'),
      initialOpen: false
    },
      createElement(ToggleControl, {
        label: __('Show Thumbnails', 'wp-document-revisions'),
        checked: show_thumb,
        onChange: (value: boolean) => setAttributes({ show_thumb: value }),
        help: __('Display document thumbnails if available', 'wp-document-revisions')
      }),
      createElement(ToggleControl, {
        label: __('Show Descriptions', 'wp-document-revisions'),
        checked: show_descr,
        onChange: (value: boolean) => setAttributes({ show_descr: value }),
        help: __('Display document descriptions/excerpts', 'wp-document-revisions')
      }),
      createElement(ToggleControl, {
        label: __('Show Authors', 'wp-document-revisions'),
        checked: show_author,
        onChange: (value: boolean) => setAttributes({ show_author: value }),
        help: __('Display document authors', 'wp-document-revisions')
      }),
      createElement(ToggleControl, {
        label: __('Show PDF Link', 'wp-document-revisions'),
        checked: show_pdf,
        onChange: (value: boolean) => setAttributes({ show_pdf: value }),
        help: __('Show direct PDF download link if available', 'wp-document-revisions')
      }),
      createElement(ToggleControl, {
        label: __('Open in New Tab', 'wp-document-revisions'),
        checked: new_tab,
        onChange: (value: boolean) => setAttributes({ new_tab: value }),
        help: __('Open document links in a new tab/window', 'wp-document-revisions')
      })
    )
  );

  const blockContent = createElement(ServerSideRender, {
    block: 'wp-document-revisions/documents-widget',
    attributes
  });

  return createElement('div', {},
    inspectorControls,
    blockContent
  );
};

registerBlockType('wp-document-revisions/documents-widget', {
  title: __('Latest Documents', 'wp-document-revisions'),
  description: __('Display a list of your most recent documents.', 'wp-document-revisions'),
  category: 'wpdr-category',
  icon: 'admin-page',
  keywords: [
    __('documents', 'wp-document-revisions'),
    __('latest', 'wp-document-revisions'),
    __('recent', 'wp-document-revisions'),
    __('widget', 'wp-document-revisions')
  ],
  attributes: {
    header: {
      type: 'string',
      default: ''
    },
    numberposts: {
      type: 'number',
      default: 5
    },
    post_stat_publish: {
      type: 'boolean',
      default: true
    },
    post_stat_private: {
      type: 'boolean',
      default: true
    },
    post_stat_draft: {
      type: 'boolean',
      default: false
    },
    show_thumb: {
      type: 'boolean',
      default: false
    },
    show_descr: {
      type: 'boolean',
      default: true
    },
    show_author: {
      type: 'boolean',
      default: true
    },
    show_pdf: {
      type: 'boolean',
      default: false
    },
    new_tab: {
      type: 'boolean',
      default: true
    },
    align: {
      type: 'string'
    },
    backgroundColor: {
      type: 'string'
    },
    linkColor: {
      type: 'string'
    },
    textColor: {
      type: 'string'
    },
    gradient: {
      type: 'string'
    },
    fontSize: {
      type: 'string'
    },
    style: {
      type: 'object'
    }
  },
  supports: {
    align: true,
    color: {
      background: true,
      text: true,
      link: true,
      gradients: true
    },
    typography: {
      fontSize: true
    },
    spacing: {
      padding: true,
      margin: true
    }
  },
  edit: DocumentsWidgetEdit,
  save: () => null // Server-side rendered
});
