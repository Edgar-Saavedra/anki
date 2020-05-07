const webpack = require('webpack');
const TerserPlugin = require('terser-webpack-plugin');

module.exports = (env, argv) => {
  const prod = process.argv.indexOf('-p') !== -1;

  const config = {
    entry: {
      'anki': './js/src/index.js',
    },
    output: {
      path: __dirname + '/dist',
      filename: '[name].js'
    },
    externals: {
      Drupal: 'Drupal',
    },
    module: {
      rules: [
        {
          test: /\.(js|jsx)$/,
          exclude: /node_modules/,
          use: ['babel-loader'],
        }
      ]
    },
    resolve: {
      extensions: ['*', '.js', '.jsx']
    },
    watch: !prod,
  };

  if (prod) {
    config.mode = 'production';
    config.optimization = {
      minimizer: [new TerserPlugin({ /* additional options here */ })],
    };
  }

  return config;
}
