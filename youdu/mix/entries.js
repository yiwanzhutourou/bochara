const glob = require('glob')

function createEntries(source, target, fileReq) {
    const entries = glob.sync(source + fileReq)
    return Object.keys(entries).map(key => {
        const entry = entries[key]
        const output = entry.replace(source, target).replace(/styl$/, 'css')
        return {entry, output}
    })
}


module.exports = (program) => {

    let jsKeys = {
        assets: createEntries('./resources/assets/', './public/dist/', '!(component)/*.js'),
        m: createEntries('./resources/assets/m/', './public/dist/m/', '!(component)/*.js'),
    }
    let cssKeys = {
        assets: createEntries('./resources/assets/', './public/dist/', '!(component)/*.styl'),
        m: createEntries('./resources/assets/m/', './public/dist/m/', '!(component)/*.styl'),
    }
    console.log( 'module:' + program.module )

    switch (program.module) {
        case 'm':
            return {
                jsEntries: jsKeys[program.module].concat(jsKeys['assets']),
                cssEntries: cssKeys[program.module].concat(cssKeys['assets'])
            }
        default:
            return {
                jsEntries: Object.keys(jsKeys).reduce((sum, key) => {
                    return sum.concat(jsKeys[key])
                }, []),
                cssEntries: Object.keys(cssKeys).reduce((sum, key) => {
                    return sum.concat(cssKeys[key])
                }, [])
            }
    }
}
