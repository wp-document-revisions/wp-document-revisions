const useSelect = jest.fn(() => ({}));
const useDispatch = jest.fn(() => ({}));
const select = jest.fn(() => ({}));
const dispatch = jest.fn(() => ({}));
const createReduxStore = jest.fn();
const register = jest.fn();

module.exports = {
  useSelect,
  useDispatch,
  select,
  dispatch,
  createReduxStore,
  register,
};
