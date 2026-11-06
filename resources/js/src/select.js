import Choices from 'choices.js'

window.ajaxChoicesSelectors = {};
window.choicesSelectors = {};

window.choices = (data) => {
    let instance;
    return {
        initSelect: () => {
            const select = new Choices(data.element, {
                allowHTML: data.allowHTML ?? true,
                removeItemButton: true,
                searchResultLimit: 1000,
            })
            instance = select;
            if (data.change) data.element.addEventListener('change', data.change);

            if (data.element.id) {
                window.choicesSelectors[data.element.id] = select
            }

            return select
        },
        destroy: () => {
            instance?.destroy();
            if (window.choicesSelectors[data.element.id] === instance) delete window.choicesSelectors[data.element.id];
        },
    }
}

window.ajaxChoices = function (data) {
    let instance;
    let searchVersion = 0;
    return {
        initSelect: () => {
            const select = new Choices(data.element, {
                allowHTML: data.allowHTML ?? true,
                removeItemButton: true,
                searchResultLimit: 1000,
                duplicateItemsAllowed: false,
                shouldSort: data.shouldSort ?? true,
            })
            instance = select;

            window.ajaxChoicesSelectors[data.element.id] = select

            data.element.addEventListener(
                'search',
                debounce(
                    this,
                    (e) => {
                        if (!instance) return;
                        const version = ++searchVersion;
                        const params = new URLSearchParams({ search: e.detail.value });

                        if (data.extraParams) {
                            Object.entries(data.extraParams).forEach(([key, val]) =>
                                params.append(key, val)
                            );
                        }

                        const url = data.url + '?' + params.toString();

                        fetch(url)
                            .then(function (response) {
                                return response.json()
                            })
                            .then(function (json) {
                                if (version !== searchVersion || !instance || !Array.isArray(json)) return;
                                select.clearChoices()
                                select.setChoices(json)
                            })
                            .catch(() => { /* Keep existing selections when a lookup is unavailable. */ })
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
        destroy: () => {
            searchVersion++;
            instance?.destroy();
            if (window.ajaxChoicesSelectors[data.element.id] === instance) delete window.ajaxChoicesSelectors[data.element.id];
            instance = null;
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
