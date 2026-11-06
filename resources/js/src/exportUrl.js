window.updateExportUrl = (filters, el) => {
    if (!el) {
        return
    }

    // Livewire event parameters may arrive wrapped as either [{ filters: ... }] or { filters: ... }, depending on the caller/version
    if (Array.isArray(filters) && filters.length === 1) {
        filters = filters[0]
    }

    if (filters && !Array.isArray(filters) && typeof filters.filters === 'object') {
        filters = filters.filters
    }

    filters = filters && typeof filters === 'object' ? filters : {}

    const url = new URL(el.href, window.location.origin)
    url.search = ''

    const append = (key, value) => {
        if (value !== null && typeof value === 'object') {
            Object.entries(value).forEach(([child, item]) => append(`${key}[${child}]`, item))
        } else if (value !== null && value !== '') {
            url.searchParams.set(key, value)
        }
    }
    Object.entries(filters).forEach(([key, value]) => append(`filters[${key}]`, value))

    el.href = url.toString()
}
