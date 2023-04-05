module.exports = {
    env: {
        browser: true,
        commonjs: true,
        es6: true,
    },
    extends: ['eslint:recommended'],
    parserOptions: {
        sourceType: 'module',
    },
    rules: {
        indent: ['error', 4],
        'linebreak-style': ['error', 'unix'],
        quotes: ['error', 'single'],
        'space-in-parens': ['error', 'never'],
        yoda: ['error', 'never'],
        semi: 0,
        'no-console': 1,
    },
};
