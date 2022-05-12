window.similarItems = function (params) {
    return {
        search: '',
        similarNames: [],
        findSimilarNames(context) {
            if (context.id !== '' || context.search.length < 4) {
                context.similarNames = []
            }

            fetch(params.similarNamesUrl + '&search=' + this.search)
                .then((response) => response.json())
                .then((data) => {
                    context.similarNames = data
                })
        },
    }
}
