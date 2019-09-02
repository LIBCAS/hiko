/* global Vue Swal axios ajaxUrl */

const locationSwal = {
    saveSuccess: {
        title: 'Položka byla úspěšně přidána',
        type: 'success',
        buttonsStyling: false,
        confirmButtonText: 'OK',
        confirmButtonClass: 'btn btn-primary btn-lg',
    },
    removeInfo: {
        title: 'Položka byla úspěšně odstraněna',
        type: 'success',
        buttonsStyling: false,
        confirmButtonText: 'OK',
        confirmButtonClass: 'btn btn-primary btn-lg',
    },
    confirmSave: {
        buttonsStyling: false,
        cancelButtonClass: 'btn btn-secondary btn-lg ml-1',
        cancelButtonText: 'Zrušit',
        confirmButtonClass: 'btn btn-primary btn-lg mr-1',
        confirmButtonText: 'Uložit',
        input: 'text',
        showCancelButton: true,
        showLoaderOnConfirm: true,
        type: 'question',
    },
    confirmDelete: {
        buttonsStyling: false,
        cancelButtonClass: 'btn btn-secondary btn-lg ml-1',
        cancelButtonText: 'Zrušit',
        confirmButtonClass: 'btn btn-primary btn-lg mr-1',
        confirmButtonText: 'Ano',
        showCancelButton: true,
        title: 'Opravdu chcete odstranit tuto položku?',
        type: 'warning',
    },
}

if (document.getElementById('repository')) {
    new Vue({
        el: '#location',
        data: {
            data: [],
            loading: true,
            error: false,
        },
        computed: {
            repositories: function() {
                let self = this
                return self.data.filter(function(loc) {
                    if (loc.type == 'repository') {
                        return true
                    }
                })
            },
            collections: function() {
                let self = this
                return self.data.filter(function(loc) {
                    if (loc.type == 'collection') {
                        return true
                    }
                })
            },
            archives: function() {
                let self = this
                return self.data.filter(function(loc) {
                    if (loc.type == 'archive') {
                        return true
                    }
                })
            },
        },

        mounted: function() {
            this.getData()
        },

        methods: {
            insertItem: function(type, title, action, id) {
                let self = this
                self.insertLocationItem(type, title, action, id, function() {
                    self.getData()
                })
            },
            insertLocationItem: function(type, title, action, id, callback) {
                let swalConfig = locationSwal.confirmSave
                swalConfig.title = title
                swalConfig.allowOutsideClick = () => !Swal.isLoading()
                swalConfig.inputValidator = value => {
                    if (value.length < 3) {
                        return 'Zadejte hodnotu'
                    }
                }
                swalConfig.preConfirm = value => {
                    return axios
                        .post(
                            ajaxUrl + '?action=insert_location_data',
                            {
                                ['type']: type,
                                ['item']: value,
                                ['action']: action,
                                ['id']: id,
                            },
                            {
                                headers: {
                                    'Content-Type':
                                        'application/json;charset=utf-8',
                                },
                            }
                        )
                        .then(function(response) {
                            return response.data
                        })
                        .catch(function(error) {
                            Swal.showValidationMessage(
                                `Při ukládání došlo k chybě: ${error}`
                            )
                        })
                }
                Swal.fire(swalConfig).then(result => {
                    if (result.value) {
                        Swal.fire(locationSwal.saveSuccess)
                        callback()
                    }
                })
            },
            deleteItem: function(name, id) {
                let self = this
                self.deleteLocationItem(name, id, function() {
                    self.data = self.data.filter(function(item) {
                        return item.id !== id
                    })
                })
            },

            deleteLocationItem: function(name, id, callback) {
                let swalConfig = locationSwal.confirmDelete
                swalConfig.text = name
                Swal.fire(swalConfig).then(result => {
                    if (result.value) {
                        axios
                            .post(
                                ajaxUrl + '?action=delete_location_data',
                                {
                                    ['id']: id,
                                },
                                {
                                    headers: {
                                        'Content-Type':
                                            'application/json;charset=utf-8',
                                    },
                                }
                            )
                            .then(function() {
                                Swal.fire(locationSwal.removeInfo)
                                callback()
                            })
                            .catch(function(error) {
                                Swal.showValidationMessage(
                                    `Při odstraňování došlo k chybě: ${error}`
                                )
                            })
                    }
                })
            },

            getData: function() {
                let self = this
                self.loading = true
                axios
                    .get(ajaxUrl + '?action=list_locations')
                    .then(function(response) {
                        self.data = response.data.data
                    })
                    .catch(function(error) {
                        self.error = error
                    })
                    .then(function() {
                        self.loading = false
                    })
            },
        },
    })
}
