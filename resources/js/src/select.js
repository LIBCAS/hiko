import TomSelect from 'tom-select/dist/esm/tom-select.complete'

window.select = function (data) {
    return {
        initSelect: function () {
            new TomSelect(data.element, {
                plugins: ['checkbox_options', 'input_autogrow'],
                allowEmptyOption: true,
                create: false,
            })
        },
    }
}
