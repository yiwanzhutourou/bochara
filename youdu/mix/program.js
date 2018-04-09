// 设置全局参数
module.exports = {
    MIX_PROXY : '',  // 'localhost:80'，需要 hotreload 可以添加
    production : process.env.NODE_ENV === 'production',
    module : process.env.MODULE || ''
}
