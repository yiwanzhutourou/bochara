const { mix } = require('laravel-mix')
const program = require('./mix/program')
require('./mix/webpack.config')(mix, program)
const {cssEntries, jsEntries} = require('./mix/entries')(program)

jsEntries.forEach((oneItem) => {
	mix.js(oneItem.entry, oneItem.output)
})

cssEntries.forEach((oneItem) => {
	mix.stylus(oneItem.entry, oneItem.output)
})

