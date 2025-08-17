const __ = (text) => text;
const _x = (text, context) => text;
const _n = (single, plural, number) => number === 1 ? single : plural;
const sprintf = (format, ...args) => format;

module.exports = { __, _x, _n, sprintf };
