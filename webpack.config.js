const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = (env, argv) => {
  const isProduction = argv.mode === 'production';

  return {
    entry: {
      'wp-document-revisions': './src/admin/wp-document-revisions.ts',
      'wp-document-revisions-validate': './src/admin/wp-document-revisions-validate.ts',
      'wpdr-documents-shortcode': './src/blocks/wpdr-documents-shortcode.tsx',
      'wpdr-documents-widget': './src/blocks/wpdr-documents-widget.tsx',
      'wpdr-revisions-shortcode': './src/blocks/wpdr-revisions-shortcode.tsx',
    },
    output: {
      path: path.resolve(__dirname, 'dist'),
      filename: '[name].js',
      clean: true,
    },
    resolve: {
      extensions: ['.ts', '.tsx', '.js', '.jsx'],
    },
    module: {
      rules: [
        {
          test: /\.tsx?$/,
          use: {
            loader: 'ts-loader',
            options: {
              transpileOnly: true,
              compilerOptions: {
                noEmitOnError: false
              }
            }
          },
          exclude: /node_modules/,
        },
        {
          test: /\.s?css$/,
          use: [
            isProduction ? MiniCssExtractPlugin.loader : 'style-loader',
            'css-loader',
            'sass-loader',
          ],
        },
      ],
    },
    plugins: [
      ...(isProduction
        ? [
            new MiniCssExtractPlugin({
              filename: '[name].css',
            }),
          ]
        : []),
    ],
    devtool: isProduction ? 'source-map' : 'eval-source-map',
    externals: {
      jquery: 'jQuery',
      '@wordpress/blocks': ['wp', 'blocks'],
      '@wordpress/components': ['wp', 'components'],
      '@wordpress/element': ['wp', 'element'],
      '@wordpress/i18n': ['wp', 'i18n'],
      '@wordpress/block-editor': ['wp', 'blockEditor'],
      '@wordpress/server-side-render': ['wp', 'serverSideRender'],
    },
    optimization: {
      splitChunks: {
        chunks: 'all',
        cacheGroups: {
          vendor: {
            test: /[\\/]node_modules[\\/]/,
            name: 'vendors',
            chunks: 'all',
          },
        },
      },
    },
  };
};
