window.updateExportUrl = (filters, el) => {
    if (!el) {
        return
    }

    // Livewire event parameters may arrive wrapped as either [{ filters: ... }]
    // or { filters: ... }, depending on the caller/version.
    if (Array.isArray(filters) && filters.length === 1) {
        filters = filters[0]
    }

    if (filters && !Array.isArray(filters) && typeof filters.filters === 'object') {
        filters = filters.filters
    }

    filters = filters && typeof filters === 'object' ? filters : {}

    const url = new URL(el.href, window.location.origin)
    url.search = ''

    Object.entries(filters).forEach(([key, value]) => {
        if (Array.isArray(value)) {
            value.forEach((item) => url.searchParams.append(`filters[${key}][]`, item))
            return
        }

        if (value !== null && value !== '') {
            url.searchParams.set(`filters[${key}]`, value)
        }
    })

    el.href = url.toString()
}
