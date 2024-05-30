import Choices from 'choices.js'

window.choices = (data) => {
    return {
        initSelect: () => {
            return new Choices(data.element, {
                removeItemButton: true,
                searchResultLimit: 10,
            })
        },
    }
}

window.ajaxChoices = function (data) {
    return {
        initSelect: () => {
            const select = new Choices(data.element, {
                removeItemButton: true,
                searchResultLimit: 10,
                duplicateItemsAllowed: false,
            })

            data.element.addEventListener(
                'search',
                debounce(
                    this,
                    (e) => {
                        const url =
                            data.url +
                            '?search=' +
                            encodeURIComponent(e.detail.value)

                        fetch(url)
                            .then(function (response) {
                                return response.json()
                            })
                            .then(function (json) {
                                select.clearChoices()
                                select.setChoices(json)
                            })
                    },
                    300
                )
            )

            if ('change' in data) {
                data.element.addEventListener('change', (event) => {
                    const option = data.element.querySelector(
                        'option[value="' + event.detail.value + '"]'
                    )

                    data.change({
                        value: event.detail.value,
                        label: option ? option.textContent : '',
                    })
                })
            }
        },
    }
}

function debounce(context, func, timeout = 300) {
    let timer
    return (...args) => {
        clearTimeout(timer)
        timer = setTimeout(() => {
            func.apply(context, args)
        }, timeout)
    }
}
