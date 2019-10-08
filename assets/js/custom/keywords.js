/* global Vue VueTables defaultTablesOptions getLetterType removeItemAjax axios Swal ajaxUrl */

const keywordsSwal = {
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
    saveSuccess: {
        title: 'Položka byla úspěšně přidána',
        type: 'success',
        buttonsStyling: false,
        confirmButtonText: 'OK',
        confirmButtonClass: 'btn btn-primary btn-lg',
    },
}

if (document.getElementById('datatable-keywords')) {
    Vue.use(VueTables.ClientTable, false, false, 'bootstrap4')
    new Vue({
        el: '#datatable-keywords',
        data: {
            columns: ['name', 'edit'],
            tableData: [],
            error: false,
            options: {
                filterable: ['name'],
                headings: {
                    edit: 'Akce',
                },
                pagination: defaultTablesOptions.pagination,
                perPage: defaultTablesOptions.perPage,
                perPageValues: defaultTablesOptions.perPageValues,
                skin: defaultTablesOptions.skin,
                sortIcon: defaultTablesOptions.sortIcon,
                sortable: ['name'],
                texts: defaultTablesOptions.texts,
            },
            path: '',
            type: '',
        },

        mounted: function() {
            let letterTypes = getLetterType()
            if (
                typeof letterTypes === 'string' ||
                letterTypes instanceof String
            ) {
                self.error = letterTypes
                return
            }
            this.type = letterTypes['keyword']
            this.path = letterTypes['path']

            this.getData()
        },
        methods: {
            getData: function() {
                let self = this
                axios
                    .get(
                        ajaxUrl +
                            '?action=keywords_table_data&type=' +
                            self.type
                    )
                    .then(function(response) {
                        self.tableData = response.data
                    })
                    .catch(function(error) {
                        self.error = error
                    })
            },
            deleteKeyword: function(id) {
                let self = this
                removeItemAjax(id, 'keyword', self.path, function() {
                    self.deleteRow(id, self.tableData)
                })
            },
            deleteRow: function(id, data) {
                this.tableData = data.filter(function(item) {
                    return item.id !== id
                })
            },
            addKeyword: function(type, action, id, oldKeyword = false) {
                let self = this
                let swalConfig = keywordsSwal.confirmSave
                swalConfig.title = (id ? 'Upravit' : 'Nové ') + ' klíčové slovo'

                if (oldKeyword) {
                    swalConfig.inputValue = oldKeyword
                }
                swalConfig.allowOutsideClick = () => !Swal.isLoading()
                swalConfig.inputValidator = value => {
                    if (value.length < 3) {
                        return 'Zadejte hodnotu'
                    }
                }
                swalConfig.preConfirm = value => {
                    return axios
                        .post(
                            ajaxUrl + '?action=insert_keyword',
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
                        Swal.fire(keywordsSwal.saveSuccess)
                    }

                    self.getData()
                })
            },
        },
    })
}
