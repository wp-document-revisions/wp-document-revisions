// Debug jQuery mock issue
const jestFn = require('jest').fn;

// Create a simple test similar to our mock
const createMockElement = () => ({
  prop: jestFn().mockReturnThis(),
  show: jestFn().mockReturnThis(),
  hide: jestFn().mockReturnThis(),
});

const mockJQuery = jestFn((selector, context) => {
  console.log('mockJQuery called with:', selector, context);
  const element = createMockElement();
  console.log('Element created, has prop?', typeof element.prop);
  return element;
});

// Test the mock
try {
  const result = mockJQuery(':button, :submit', '#submitpost');
  console.log('Result:', result);
  console.log('Result has prop?', typeof result.prop);

  // Try calling prop
  const propResult = result.prop('disabled', true);
  console.log('Prop call result:', propResult);
} catch (error) {
  console.error('Error:', error.message);
}
