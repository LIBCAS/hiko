window.identityForm = function (params) {
    return {
        type: params.type,
        fullName: '',
        surname: params.surname,
        forename: params.forename,
        name: params.name,
        id: params.id,
        similarNames: [],
        findSimilarNames(context) {
            if (context.id !== '' || context.fullName.length < 4) {
                context.similarNames = []
            }

            fetch(params.similarNamesUrl + '?search=' + this.fullName)
                .then((response) => response.json())
                .then((data) => {
                    context.similarNames = data
                })
        },
    }
}
