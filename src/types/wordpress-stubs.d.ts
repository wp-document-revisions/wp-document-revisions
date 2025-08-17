/**
 * WordPress Global Variables and Types
 * These are minimal type stubs for WordPress globals and basic component interfaces
 */

// Extend the existing window interface with WordPress-specific properties
declare global {
  // For any window-scoped WordPress objects not covered in globals.ts
  interface Window {
    wp?: {
      serverSideRender?: React.ComponentType<any>;
      [key: string]: any;
    };
  }
}

// Basic type stubs for WordPress packages
// These provide just enough typing to satisfy TypeScript without conflicting

declare module '@wordpress/i18n' {
  function __(text: string, domain?: string): string;
  function _x(text: string, context: string, domain?: string): string;
  function _n(single: string, plural: string, number: number, domain?: string): string;
  function sprintf(format: string, ...args: any[]): string;
}

declare module '@wordpress/blocks' {
  interface BlockConfiguration {
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

  function registerBlockType(name: string, settings: BlockConfiguration): void;
}

declare module '@wordpress/element' {
  const createElement: any;
  const Fragment: any;
  const Component: any;
  const useState: any;
  const useEffect: any;
  const useCallback: any;
  const useMemo: any;
  const useRef: any;
}

declare module '@wordpress/block-editor' {
  const InspectorControls: any;
  const BlockControls: any;
  const useBlockProps: any;
  const RichText: any;
  const MediaUpload: any;
  const ColorPalette: any;
}

declare module '@wordpress/components' {
  const PanelBody: any;
  const RangeControl: any;
  const TextControl: any;
  const SelectControl: any;
  const ToggleControl: any;
  const TextareaControl: any;
  const Button: any;
  const Panel: any;
  const Placeholder: any;
}

declare module '@wordpress/server-side-render' {
  const ServerSideRender: any;
  export default ServerSideRender;
}

export {};
