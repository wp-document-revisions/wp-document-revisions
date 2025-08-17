const { createElement } = require('./element');

const InspectorControls = ({ children }) => createElement('div', { 'data-testid': 'inspector-controls' }, children);
const BlockControls = ({ children }) => createElement('div', { 'data-testid': 'block-controls' }, children);
const useBlockProps = jest.fn(() => ({}));
const RichText = ({ value, onChange, tagName = 'div', ...props }) => 
  createElement(tagName, { ...props, 'data-testid': 'rich-text' }, value);
const MediaUpload = ({ onSelect, render }) => render({ open: jest.fn() });
const MediaUploadCheck = ({ children }) => children;
const PlainText = ({ value, onChange, ...props }) => 
  createElement('textarea', { ...props, value, onChange, 'data-testid': 'plain-text' });
const ColorPalette = ({ value, onChange }) => 
  createElement('div', { 'data-testid': 'color-palette' });
const ContrastChecker = ({ children }) => children;

module.exports = {
  InspectorControls,
  BlockControls,
  useBlockProps,
  RichText,
  MediaUpload,
  MediaUploadCheck,
  PlainText,
  ColorPalette,
  ContrastChecker
};
