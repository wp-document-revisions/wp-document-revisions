const createElement = jest.fn((type, props, ...children) => ({ type, props, children }));
const Fragment = ({ children }) => children;
const Component = class {};
const PureComponent = class {};
const useState = jest.fn();
const useEffect = jest.fn();
const useContext = jest.fn();
const useRef = jest.fn();
const useMemo = jest.fn();
const useCallback = jest.fn();

module.exports = {
  createElement,
  Fragment,
  Component,
  PureComponent,
  useState,
  useEffect,
  useContext,
  useRef,
  useMemo,
  useCallback
};
