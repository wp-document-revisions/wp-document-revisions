const { createElement } = require('./element');

const PanelBody = ({ children, title, initialOpen = true }) => 
  createElement('div', { 'data-testid': 'panel-body', title }, children);
const PanelRow = ({ children }) => 
  createElement('div', { 'data-testid': 'panel-row' }, children);
const RangeControl = ({ value, onChange, min, max, label }) => 
  createElement('input', { 
    type: 'range', 
    value, 
    onChange: (e) => onChange && onChange(e.target.value),
    min,
    max,
    'data-testid': 'range-control',
    'aria-label': label
  });
const SelectControl = ({ value, onChange, options, label }) =>
  createElement('select', {
    value,
    onChange: (e) => onChange && onChange(e.target.value),
    'data-testid': 'select-control',
    'aria-label': label
  }, options && options.map(opt => createElement('option', { key: opt.value, value: opt.value }, opt.label)));
const ToggleControl = ({ checked, onChange, label }) =>
  createElement('input', {
    type: 'checkbox',
    checked,
    onChange: (e) => onChange && onChange(e.target.checked),
    'data-testid': 'toggle-control',
    'aria-label': label
  });
const TextControl = ({ value, onChange, label, placeholder }) =>
  createElement('input', {
    type: 'text',
    value,
    onChange: (e) => onChange && onChange(e.target.value),
    placeholder,
    'data-testid': 'text-control',
    'aria-label': label
  });
const TextareaControl = ({ value, onChange, label, placeholder }) =>
  createElement('textarea', {
    value,
    onChange: (e) => onChange && onChange(e.target.value),
    placeholder,
    'data-testid': 'textarea-control',
    'aria-label': label
  });
const Button = ({ children, onClick, isPrimary, isSecondary, ...props }) =>
  createElement('button', { 
    onClick, 
    'data-testid': 'button',
    className: isPrimary ? 'is-primary' : isSecondary ? 'is-secondary' : '',
    ...props 
  }, children);
const Panel = ({ children, header }) =>
  createElement('div', { 'data-testid': 'panel' }, [header, children]);
const Placeholder = ({ children, icon, label, instructions }) =>
  createElement('div', { 'data-testid': 'placeholder' }, [icon, label, instructions, children]);
const Spinner = () => createElement('div', { 'data-testid': 'spinner' });
const Notice = ({ children, status, isDismissible, onRemove }) =>
  createElement('div', { 
    'data-testid': 'notice',
    className: `notice notice-${status}`,
    'data-dismissible': isDismissible
  }, children);

module.exports = {
  PanelBody,
  PanelRow,
  RangeControl,
  SelectControl,
  ToggleControl,
  TextControl,
  TextareaControl,
  Button,
  Panel,
  Placeholder,
  Spinner,
  Notice
};
