/* global Swal axios ajaxUrl decodeHTML */
const commonSwalOptions = {
    buttonsStyling: false,
    confirmButtonClass: 'btn btn-primary btn-lg mr-1',
    cancelButtonClass: 'btn btn-link text-danger btn-sm ml-1',
    confirmButtonText: 'OK',
    cancelButtonText: 'Zrušit',
}

window.locationForm = function () {
    return {
        loading: true,
        repositories: [],
        collections: [],
        archives: [],

        fetch: function () {
            const context = this

            context.repositories = []
            context.collections = []
            context.archives = []

            axios
                .get(ajaxUrl + '?action=list_locations')
                .then((response) => {
                    response.data.data.map((location) => {
                        if (location.type === 'repository') {
                            context.repositories.push(location)
                        } else if (location.type === 'collection') {
                            context.collections.push(location)
                        } else if (location.type === 'archive') {
                            context.archives.push(location)
                        }
                    })
                })
                .catch((error) => {
                    console.log(error)
                })
                .then(() => {
                    context.loading = false
                })
        },

        insertItem: function (type, title, action, defaultValue, id) {
            const context = this
            const swalConfig = Object.assign({}, commonSwalOptions)
            swalConfig.showCancelButton = true
            swalConfig.showLoaderOnConfirm = true
            swalConfig.type = 'question'
            swalConfig.title = title
            swalConfig.input = 'text'
            swalConfig.inputValue = decodeHTML(defaultValue)
            swalConfig.inputValidator = (value) => {
                if (value.length < 3) {
                    return 'Zadejte hodnotu'
                }
            }
            swalConfig.preConfirm = (value) => {
                return axios
                    .post(
                        ajaxUrl + '?action=insert_location_data',
                        {
                            type: type,
                            item: value,
                            action: action,
                            id: id,
                        },
                        {
                            headers: {
                                'Content-Type':
                                    'application/json;charset=utf-8',
                            },
                        }
                    )
                    .then((response) => {
                        return response.data
                    })
                    .catch((error) => {
                        Swal.showValidationMessage(
                            `Při ukládání došlo k chybě: ${error}`
                        )
                    })
            }

            Swal.fire(swalConfig).then((result) => {
                if (!result.value) {
                    return
                }
                Swal.fire(
                    Object.assign(commonSwalOptions, {
                        title: 'Položka byla úspěšně přidána',
                        type: 'success',
                    })
                )
                context.fetch()
            })
        },

        deleteItem: function (name, id) {
            const context = this

            const swalConfig = Object.assign(commonSwalOptions, {
                showCancelButton: true,
                title: 'Opravdu chcete odstranit tuto položku?',
                type: 'warning',
                text: decodeHTML(name),
            })

            Swal.fire(swalConfig).then((result) => {
                if (!result.value) {
                    return
                }

                axios
                    .post(
                        ajaxUrl + '?action=delete_location_data',
                        {
                            id: id,
                        },
                        {
                            headers: {
                                'Content-Type':
                                    'application/json;charset=utf-8',
                            },
                        }
                    )
                    .then(() => {
                        Swal.fire(
                            Object.assign(commonSwalOptions, {
                                title: 'Položka byla úspěšně odstraněna',
                                type: 'success',
                            })
                        )
                        context.fetch()
                    })
                    .catch((error) => {
                        Swal.showValidationMessage(
                            `Při odstraňování došlo k chybě: ${error}`
                        )
                    })
            })
        },
    }
}
