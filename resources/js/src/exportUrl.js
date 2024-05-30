window.updateExportUrl = (filters, el) => {
    const queryString = Object.keys(filters)
        .map((key) => {
            return (
                encodeURIComponent(key) + '=' + encodeURIComponent(filters[key])
            )
        })
        .join('&')

    el.href = new URL(el.href).href.split('?')[0] + '?' + queryString
}
