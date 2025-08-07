const path = require('path');
const fs = require('fs');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');
const TerserPlugin = require('terser-webpack-plugin');

/**
 * Automatically discover entry points from JS and SCSS directories
 */
function getEntryPoints() {
  const entries = {};
  
  // JavaScript files
  const jsDir = path.resolve(__dirname, 'client/src/js');
  if (fs.existsSync(jsDir)) {
    const jsFiles = fs.readdirSync(jsDir)
      .filter(file => file.endsWith('.js') && !file.includes('.backup') && !file.includes('.test'))
      .map(file => file.replace('.js', ''));
    
    jsFiles.forEach(file => {
      entries[file] = path.resolve(jsDir, `${file}.js`);
      console.log(`📄 JS: ${file}.js`);
    });
  }
  
  // SCSS files (with 'styles' suffix to avoid naming conflicts)
  const scssDir = path.resolve(__dirname, 'client/src/scss');
  if (fs.existsSync(scssDir)) {
    const scssFiles = fs.readdirSync(scssDir)
      .filter(file => file.endsWith('.scss') && !file.startsWith('_')) // Exclude partials
      .map(file => file.replace('.scss', ''));
    
    scssFiles.forEach(file => {
      entries[`${file}-styles`] = path.resolve(scssDir, `${file}.scss`);
      console.log(`🎨 SCSS: ${file}.scss`);
    });
  }
  
  console.log('📦 Total entry points discovered:', Object.keys(entries).length);
  return entries;
}

module.exports = (env, argv) => {
  const isProduction = argv.mode === 'production';
  
  return {
    mode: isProduction ? 'production' : 'development',
    devtool: isProduction ? 'source-map' : 'eval-source-map',
    
    entry: getEntryPoints(),
    
    output: {
      path: path.resolve(__dirname, 'client/dist'),
      filename: 'js/[name].js',
      clean: true,
      publicPath: ''
    },
    
    module: {
      rules: [
        {
          test: /\.js$/,
          exclude: /node_modules/,
          use: {
            loader: 'babel-loader',
            options: {
              presets: [
                ['@babel/preset-env', {
                  targets: {
                    browsers: ['> 1%', 'last 2 versions', 'not ie <= 11']
                  },
                  useBuiltIns: 'usage',
                  corejs: 3
                }]
              ]
            }
          }
        },
        {
          test: /\.scss$/,
          use: [
            MiniCssExtractPlugin.loader,
            {
              loader: 'css-loader',
              options: {
                sourceMap: true,
                importLoaders: 2
              }
            },
            {
              loader: 'postcss-loader',
              options: {
                sourceMap: true,
                postcssOptions: {
                  plugins: [
                    require('autoprefixer')({
                      grid: true
                    }),
                    ...(isProduction ? [require('cssnano')({ preset: 'default' })] : [])
                  ]
                }
              }
            },
            {
              loader: 'sass-loader',
              options: {
                sourceMap: true,
                api: 'modern-compiler', // Use modern Sass API instead of legacy
                sassOptions: {
                  outputStyle: isProduction ? 'compressed' : 'expanded',
                  includePaths: [
                    path.resolve(__dirname, 'client/src/scss'),
                    path.resolve(__dirname, 'node_modules')
                  ]
                }
              }
            }
          ]
        },
        {
          test: /\.(woff|woff2|eot|ttf|otf)$/,
          type: 'asset/resource',
          generator: {
            filename: 'fonts/[name][ext]'
          }
        },
        {
          test: /\.(png|svg|jpg|jpeg|gif)$/,
          type: 'asset/resource',
          generator: {
            filename: 'images/[name][ext]'
          }
        }
      ]
    },
    
    plugins: [
      new MiniCssExtractPlugin({
        filename: 'css/[name].css',
        chunkFilename: 'css/[id].css'
      })
    ],
    
    optimization: {
      minimize: isProduction,
      minimizer: [
        new TerserPlugin({
          terserOptions: {
            compress: {
              drop_console: isProduction
            }
          }
        }),
        new CssMinimizerPlugin()
      ],
      splitChunks: {
        cacheGroups: {
          styles: {
            name: 'styles',
            test: /\.scss$/,
            chunks: 'all',
            enforce: true
          }
        }
      }
    },
    
    resolve: {
      alias: {
        '@': path.resolve(__dirname, 'client/src'),
        '@js': path.resolve(__dirname, 'client/src/js'),
        '@scss': path.resolve(__dirname, 'client/src/scss')
      }
    },
    
    stats: {
      children: false,
      entrypoints: false,
      modules: false
    }
  };
};
