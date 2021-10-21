import TomSelect from 'tom-select/dist/esm/tom-select.complete'

window.ajaxSelect = function (data) {
    let options = []

    if (Array.isArray(data.options) && data.options.length > 0) {
        options = data.options
    } else if (data.options) {
        options = [data.options]
    }

    return {
        initSelect: function () {
            const select = new TomSelect(data.element, {
                plugins: [
                    'checkbox_options',
                    'caret_position',
                    'input_autogrow',
                ],
                allowEmptyOption: true,
                create: false,
                sortField: {
                    field: 'name',
                    direction: 'asc',
                },
                valueField: 'id',
                labelField: 'name',
                searchField: 'name',
                load: (query, callback) => {
                    const url =
                        data.url + '?search=' + encodeURIComponent(query)
                    fetch(url)
                        .then((response) => response.json())
                        .then((json) => {
                            callback(json)
                        })
                        .catch(() => {
                            callback()
                        })
                },
                options: options,
            })

            select.setValue(options.map((obj) => obj.id))
        },
    }
}
