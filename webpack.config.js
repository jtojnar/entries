const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');
const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const TerserPlugin = require('terser-webpack-plugin');

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
							postcssOptions: {
								plugins: [
									require('autoprefixer'),
								],
							},
						},
					},
					'sass-loader',
				],
			},
		]
	},
	optimization: {
		minimizer: [
			new TerserPlugin(),
			new CssMinimizerPlugin(),
		]
	},
	plugins: [
		new MiniCssExtractPlugin({
			filename: '[name].css',
			chunkFilename: '[id].css'
		})
	],
};
