import TomSelect from 'tom-select/dist/esm/tom-select.complete'

window.ajaxSelect = function (data) {
    return {
        initSelect: function () {
            const select = new TomSelect(data.element, {
                plugins: ['checkbox_options'],
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
                options: data.options ? [data.options] : [],
            })

            if (data.options) {
                select.setValue(data.options.id)
            }
        },
    }
}
