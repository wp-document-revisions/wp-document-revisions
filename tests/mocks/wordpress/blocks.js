const registerBlockType = jest.fn();
const createBlock = jest.fn();
const getBlockType = jest.fn();
const getBlockTypes = jest.fn();
const hasBlockSupport = jest.fn();
const isReusableBlock = jest.fn();
const isTemplatePart = jest.fn();

module.exports = {
  registerBlockType,
  createBlock,
  getBlockType,
  getBlockTypes,
  hasBlockSupport,
  isReusableBlock,
  isTemplatePart
};
