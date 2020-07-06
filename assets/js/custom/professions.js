/* global Vue VueTables defaultTablesOptions getLetterType removeItemAjax axios Swal ajaxUrl isString */

const professionsSwal = {
    confirmSave: {
        buttonsStyling: false,
        cancelButtonClass: 'btn btn-secondary btn-lg ml-1',
        cancelButtonText: 'Zrušit',
        confirmButtonClass: 'btn btn-primary btn-lg mr-1',
        confirmButtonText: 'Uložit',
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

function getProfessionForm(val1, val2) {
    return `
    <div class="form-group">
    <label for="nameen">EN</label>
    <input value="${val1}" id="nameen" class="form-control" pattern=".{2,255}" required title="2 to 255 characters">
    </div>
    <div class="form-group">
    <label for="namecz">CZ</label>
    <input value="${val2}" id="namecz" class="form-control" pattern=".{2,255}" required title="2 to 255 characters">
    </div>
    `
}

if (document.getElementById('datatable-profession')) {
    Vue.use(VueTables.ClientTable, false, false, 'bootstrap4')
    new Vue({
        el: '#datatable-profession',
        data: {
            columns: ['name', 'namecz', 'edit'],
            tableData: [],
            error: false,
            options: {
                filterable: ['name', 'namecz'],
                headings: {
                    name: 'EN',
                    namecz: 'CZ',
                    edit: 'Akce',
                },
                pagination: defaultTablesOptions.pagination,
                perPage: defaultTablesOptions.perPage,
                perPageValues: defaultTablesOptions.perPageValues,
                skin: defaultTablesOptions.skin,
                sortIcon: defaultTablesOptions.sortIcon,
                sortable: ['name', 'namecz'],
                texts: defaultTablesOptions.texts,
            },
            path: '',
            type: '',
        },

        mounted: function () {
            let letterTypes = getLetterType()
            if (isString(letterTypes)) {
                self.error = letterTypes
                return
            }
            this.type = letterTypes['profession']
            this.path = letterTypes['path']

            this.getData()
        },
        methods: {
            getData: function () {
                let self = this
                axios
                    .get(
                        ajaxUrl +
                            '?action=professions_table_data&type=' +
                            self.type
                    )
                    .then(function (response) {
                        self.tableData = response.data
                    })
                    .catch(function (error) {
                        self.error = error
                    })
            },
            deleteProfession: function (id) {
                let self = this
                removeItemAjax(id, 'profession', self.path, function () {
                    self.deleteRow(id, self.tableData)
                })
            },
            deleteRow: function (id, data) {
                this.tableData = data.filter(function (item) {
                    return item.id !== id
                })
            },
            addProfession: function (
                type,
                action,
                id,
                oldProfession = '',
                oldProfessionCZ = ''
            ) {
                let self = this
                let swalConfig = professionsSwal.confirmSave
                swalConfig.title = id ? 'Upravit profesi' : 'Nová profese '

                swalConfig.allowOutsideClick = () => !Swal.isLoading()

                swalConfig.html = getProfessionForm(
                    oldProfession,
                    oldProfessionCZ
                )

                swalConfig.focusConfirm = false

                swalConfig.preConfirm = () => {
                    let nameen = document.getElementById('nameen').value
                    let namecz = document.getElementById('namecz').value

                    if (nameen.length < 2) {
                        return Swal.showValidationMessage(
                            'Zadané hodnoty nejsou zadané v požadovaném formátu (2-255 znaků)'
                        )
                    }

                    return axios
                        .post(
                            ajaxUrl + '?action=insert_profession',
                            {
                                ['type']: type,
                                ['nameen']: nameen,
                                ['namecz']: namecz,
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
                        .then(function (response) {
                            return response.data
                        })
                        .catch(function (error) {
                            Swal.showValidationMessage(
                                `Při ukládání došlo k chybě: ${error}`
                            )
                        })
                }

                Swal.fire(swalConfig).then((result) => {
                    if (result.value) {
                        Swal.fire(professionsSwal.saveSuccess)
                        self.getData()
                    }
                })
            },
        },
    })
}
