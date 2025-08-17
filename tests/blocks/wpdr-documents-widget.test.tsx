/**
 * @jest-environment jsdom
 */

import React from 'react';
import { render, screen } from '@testing-library/react';
import '@testing-library/jest-dom';

// Mock WordPress dependencies
jest.mock('@wordpress/i18n', () => ({
  __: jest.fn((text) => text),
}));

jest.mock('@wordpress/blocks', () => ({
  registerBlockType: jest.fn(),
}));

jest.mock('@wordpress/element', () => ({
  createElement: React.createElement,
}));

jest.mock('@wordpress/block-editor', () => ({
  InspectorControls: ({ children }: { children: React.ReactNode }) => (
    <div data-testid="inspector-controls">{children}</div>
  ),
}));

jest.mock('@wordpress/components', () => ({
  PanelBody: ({ title, children }: { title: string; children: React.ReactNode }) => (
    <div data-testid="panel-body" data-title={title}>
      {children}
    </div>
  ),
  RangeControl: jest.fn(({ label, value }) => (
    <div data-testid="range-control">
      {label}: {value}
    </div>
  )),
  TextControl: jest.fn(({ label, value }) => (
    <div data-testid="text-control">
      {label}: {value}
    </div>
  )),
  ToggleControl: jest.fn(({ label, checked }) => (
    <div data-testid="toggle-control">
      {label}: {checked ? 'Yes' : 'No'}
    </div>
  )),
}));

describe('Documents Widget Block', () => {
  let mockSetAttributes: jest.Mock;
  let mockAttributes: any;

  // Helper function to get block config after requiring the module
  const loadAndGet = () => {
    jest.resetModules();
    const mockBlocks = require('@wordpress/blocks');
    require('../../src/blocks/wpdr-documents-widget');
    return mockBlocks.registerBlockType.mock;
  };

  // Helper function to get the registerBlockType mock
  const getRegisterBlockTypeMock = () => {
    const mockBlocks = jest.requireMock('@wordpress/blocks');
    jest.clearAllMocks();
    require('../../src/blocks/wpdr-documents-widget');
    return mockBlocks.registerBlockType;
  };

  beforeEach(() => {
    mockSetAttributes = jest.fn();
    mockAttributes = {
      header: 'Recent Documents',
      numberposts: 5,
      post_stat_publish: true,
      post_stat_private: false,
      post_stat_draft: false,
      show_thumb: false,
      show_descr: false,
      show_author: false,
      show_pdf: false,
      new_tab: true,
    };

    jest.clearAllMocks();
  });

  describe('Block Registration', () => {
    it('should register the documents widget block', () => {
      // Import the mock first to get a reference to it
      const mock = loadAndGet();
      expect(mock.calls[0][0]).toBe('wp-document-revisions/documents-widget');
      expect(mock.calls[0][1]).toEqual(
        expect.objectContaining({
          title: 'Latest Documents',
          description: 'Display a list of your most recent documents.',
          category: 'wpdr-category',
          icon: 'admin-page',
        })
      );
    });

    it('should have correct widget attributes', () => {
      const mock = loadAndGet();
      const blockConfig = mock.calls[0][1];
      expect(blockConfig.attributes).toEqual(
        expect.objectContaining({
          header: { type: 'string', default: '' },
          numberposts: { type: 'number', default: 5 },
          post_stat_publish: { type: 'boolean', default: true },
          post_stat_private: { type: 'boolean', default: true },
          post_stat_draft: { type: 'boolean', default: false },
          show_thumb: { type: 'boolean', default: false },
          show_descr: { type: 'boolean', default: true },
          show_author: { type: 'boolean', default: true },
          show_pdf: { type: 'boolean', default: false },
          new_tab: { type: 'boolean', default: true },
        })
      );
    });

    it('should support block customization features', () => {
      const mock = loadAndGet();
      const blockConfig = mock.calls[0][1];
      expect(blockConfig.supports).toEqual(
        expect.objectContaining({
          align: true,
          color: expect.objectContaining({
            background: true,
            text: true,
            link: true,
            gradients: true,
          }),
          typography: expect.objectContaining({ fontSize: true }),
          spacing: expect.objectContaining({ padding: true, margin: true }),
        })
      );
    });

    it('should include appropriate keywords', () => {
      const mock = loadAndGet();
      const blockConfig = mock.calls[0][1];
      expect(blockConfig.keywords).toEqual(
        expect.arrayContaining(['documents', 'latest', 'recent', 'widget'])
      );
    });
  });

  // Remove duplicated sections below – using revised tests above.
});
