const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const OptimizeCSSAssetsPlugin = require('optimize-css-assets-webpack-plugin');
const path = require('path');
const postcss = require('postcss');
const UglifyJsPlugin = require('uglifyjs-webpack-plugin');

const removeWarningPseudoClasses = postcss.plugin('postcss-remove-warning-pseudoclasses', opts => {
	return root => {
		root.walkRules(rule => {
			if (rule.selector.includes(':warning')) {
				rule.selectors = rule.selectors.filter(selector => !selector.includes(':warning'));
			}
		});
	};
});

module.exports = {
	entry: './www/assets/index.js',
	output: {
		path: path.resolve(__dirname, 'www', 'dist'),
	},
	module: {
		rules: [
			{
				test: /\.js$/,
				exclude: /node_modules/,
				loader: 'babel-loader',
				options: {
					cacheDirectory: true,
					presets: [
						[
							'@babel/preset-env',
							{
								'targets': {
									'browsers': ['last 2 versions']
								}
							}
						]
					],
				},
			},
			{
				test: /\.(png|jpg|gif)$/,
				use: [
					'file-loader',
				],
			},
			{
				test: /\.woff2?$/,
				use: [
					{
						loader: 'url-loader',
						options: {
							limit: 10000,
						},
					},
				],
			},
			{
				test: /\.(ttf|eot|svg)$/,
				use: [
					'file-loader',
				],
			},
			{
				test: /\.scss$/,
				use: [
					MiniCssExtractPlugin.loader,
					{
						loader: 'css-loader',
						options: {
							importLoaders: 2,
						},
					},
					{
						loader: 'postcss-loader',
						options: {
							plugins: () => [
								require('autoprefixer'),
								// We are adding custom validation state to Bootstrap
								// but form-validation-state mixin uses :warning pseudo-class
								// which breaks the style.
								// Let’ strip it.
								removeWarningPseudoClasses,
							],
						},
					},
					'sass-loader',
				],
			},
		]
	},
	optimization: {
		minimizer: [
			new UglifyJsPlugin({
				cache: true,
				parallel: true,
				sourceMap: true // set to true if you want JS source maps
			}),
			new OptimizeCSSAssetsPlugin({})
		]
	},
	plugins: [
		new MiniCssExtractPlugin({
			filename: '[name].css',
			chunkFilename: '[id].css'
		})
	],
};
