/* global Vue Swal axios ajaxUrl */

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
                let result = self.data.filter(function(loc) {
                    if (loc.type == 'repository') {
                        return true
                    }
                })
                return result
            },
            collections: function() {
                let self = this
                let result = self.data.filter(function(loc) {
                    if (loc.type == 'collection') {
                        return true
                    }
                })
                return result
            },
            archives: function() {
                let self = this
                let result = self.data.filter(function(loc) {
                    if (loc.type == 'archive') {
                        return true
                    }
                })
                return result
            },
        },
        mounted: function() {
            this.getData()
        },
        methods: {
            insertItem: function(type, title, action, id) {
                let self = this
                insertLocationItem(type, title, action, id, function() {
                    self.getData()
                })
            },
            deleteItem: function(id) {
                console.log(id)
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

function insertLocationItem(type, title, action, id, callback) {
    Swal.fire({
        title: title,
        type: 'question',
        input: 'text',
        inputValidator: value => {
            if (value.length < 3) {
                return 'Zadejte hodnotu'
            }
        },
        buttonsStyling: false,
        showCancelButton: true,
        confirmButtonText: 'Uložit',
        cancelButtonText: 'Zrušit',
        confirmButtonClass: 'btn btn-primary btn-lg mr-1',
        cancelButtonClass: 'btn btn-secondary btn-lg ml-1',
        showLoaderOnConfirm: true,
        preConfirm: function(value) {
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
                            'Content-Type': 'application/json;charset=utf-8',
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
        },
        allowOutsideClick: () => !Swal.isLoading(),
    }).then(result => {
        if (result.value) {
            Swal.fire({
                title: 'Položka byla úspěšně přidána',
                type: 'success',
                buttonsStyling: false,
                confirmButtonText: 'OK',
                confirmButtonClass: 'btn btn-primary btn-lg',
            })
            callback()
        }
    })
}
