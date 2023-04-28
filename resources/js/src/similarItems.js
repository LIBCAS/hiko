window.similarItems = function (params) {
    return {
        search: '',
        similarNames: [],
        findSimilarNames(context) {
            if (context.id !== '' || context.search.length < 4) {
                context.similarNames = []
            }

            let url = new URL(params.similarNamesUrl)

            url.searchParams.append('search', context.search)

            fetch(url.href)
                .then((response) => response.json())
                .then((data) => {
                    context.similarNames = data
                })
        },
    }
}
