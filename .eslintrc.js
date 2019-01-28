module.exports = {
    env: {
        browser: true,
        commonjs: true,
        es6: true,
        node: true,
    },
    extends: ['eslint:recommended', 'plugin:vue/recommended'],
    parserOptions: {
        sourceType: 'module',
    },
    rules: {
        indent: ['error', 4],
        'linebreak-style': ['error', 'unix'],
        quotes: ['error', 'single'],
        semi: 0,
        'space-in-parens': ['error', 'never'],
        yoda: ['error', 'never'],
        'no-console': 'off',
    },
}
