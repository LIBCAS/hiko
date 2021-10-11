import TomSelect from 'tom-select/dist/esm/tom-select.complete'

window.select = function (data) {
    return {
        initSelect: function () {
            new TomSelect(data.element, {
                plugins: ['checkbox_options'],
                allowEmptyOption: true,
                create: false,
            })
        },
    }
}
