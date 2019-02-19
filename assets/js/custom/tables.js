/* global Vue VueTables Swal axios ajaxUrl getLetterType */

var columns

var defaultTablesOptions = {
    skin: 'table table-bordered table-hover table-striped table-sm',
    sortIcon: {
        base: 'oi pl-1',
        up: 'oi-arrow-top',
        down: 'oi-arrow-bottom',
        is: 'oi-elevator',
    },
    texts: {
        count:
            'Zobrazena položka {from} až {to} z celkového počtu {count} položek |{count} položky|Jedna položka',
        first: 'První',
        last: 'Poslední',
        filter: 'Filtr: ',
        filterPlaceholder: 'Hledat',
        limit: 'Položky: ',
        page: 'Strana: ',
        noResults: 'Nenalezeno',
        filterBy: 'Filtrovat dle {column}',
        loading: 'Načítá se...',
        defaultOption: 'Vybrat {column}',
        columns: 'Columns',
    },
    pagination: {
        edge: true,
    },
    perPage: 10,
    perPageValues: [10, 25, 50, 100],
}

if (document.getElementById('datatable-letters')) {
    Vue.use(VueTables.ClientTable, false, false, 'bootstrap4')
    columns = [
        'edit',
        'l_number',
        'date',
        'author',
        'recipient',
        'origin',
        'dest',
        'status',
    ]

    new Vue({
        el: '#datatable-letters',
        data: {
            columns: columns,
            tableData: [],
            options: {
                headings: {
                    edit: 'Akce',
                    dest: 'Destination',
                    l_number: 'Number',
                },
                skin: defaultTablesOptions.skin,
                sortable: removeElFromArr('edit', columns),
                filterable: removeElFromArr('edit', columns),
                sortIcon: defaultTablesOptions.sortIcon,
                texts: defaultTablesOptions.texts,
                pagination: defaultTablesOptions.pagination,
                perPage: defaultTablesOptions.perPage,
                perPageValues: defaultTablesOptions.perPageValues,
                dateColumns: ['date'],
            },
            error: false,
            loading: true,
            path: '',
        },
        mounted: function() {
            let letterTypes = getLetterType()
            if (
                typeof letterTypes === 'string' ||
                letterTypes instanceof String
            ) {
                self.error = letterTypes
                return
            } else {
                this.path = letterTypes['path']
            }

            this.getData()
        },
        methods: {
            deleteLetter: function(id) {
                let self = this
                removeItemAjax(id, 'letter', self.path, function() {
                    self.deleteRow(id, self.tableData)
                })
            },
            deleteRow: function(id, data) {
                this.tableData = data.filter(function(item) {
                    return item.id !== id
                })
            },
            getData: function() {
                let self = this
                axios
                    .get(
                        ajaxUrl +
                            '?action=list_public_letters_short&type=' +
                            self.path
                    )
                    .then(function(result) {
                        self.tableData = result.data
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

if (document.getElementById('datatable-persons')) {
    Vue.use(VueTables.ClientTable, false, false, 'bootstrap4')

    columns = ['edit', 'name', 'dates']

    new Vue({
        el: '#datatable-persons',
        data: {
            columns: columns,
            tableData: JSON.parse(
                document.querySelector('#persons-data').innerHTML
            ),
            options: {
                headings: {
                    edit: 'Akce',
                },
                skin: defaultTablesOptions.skin,
                sortable: removeElFromArr('edit', columns),
                filterable: removeElFromArr('edit', columns),
                sortIcon: defaultTablesOptions.sortIcon,
                texts: defaultTablesOptions.texts,
                pagination: defaultTablesOptions.pagination,
                perPage: defaultTablesOptions.perPage,
                perPageValues: defaultTablesOptions.perPageValues,
            },
            path: '',
        },
        mounted: function() {
            let letterTypes = getLetterType()
            if (
                typeof letterTypes === 'string' ||
                letterTypes instanceof String
            ) {
                self.error = letterTypes
                return
            } else {
                this.path = letterTypes['path']
            }
        },
        methods: {
            deletePerson: function(id) {
                let self = this
                removeItemAjax(id, 'person', self.path, function() {
                    self.deleteRow(id, self.tableData)
                })
            },
            deleteRow: function(id, data) {
                this.tableData = data.filter(function(item) {
                    return item.id !== id
                })
            },
        },
    })
}

if (document.getElementById('datatable-places')) {
    Vue.use(VueTables.ClientTable, false, false, 'bootstrap4')
    columns = ['edit', 'city', 'country']
    new Vue({
        el: '#datatable-places',
        data: {
            columns: columns,
            tableData: JSON.parse(
                document.querySelector('#places-data').innerHTML
            ),
            options: {
                headings: {
                    edit: 'Akce',
                },
                skin: defaultTablesOptions.skin,
                sortable: removeElFromArr('edit', columns),
                filterable: removeElFromArr('edit', columns),
                sortIcon: defaultTablesOptions.sortIcon,
                texts: defaultTablesOptions.texts,
                pagination: defaultTablesOptions.pagination,
                perPage: defaultTablesOptions.perPage,
                perPageValues: defaultTablesOptions.perPageValues,
            },
            path: '',
        },
        mounted: function() {
            let letterTypes = getLetterType()
            if (
                typeof letterTypes === 'string' ||
                letterTypes instanceof String
            ) {
                self.error = letterTypes
                return
            } else {
                this.path = letterTypes['path']
            }
        },
        methods: {
            deletePlace: function(id) {
                let self = this
                removeItemAjax(id, 'place', self.path, function() {
                    self.deleteRow(id, self.tableData)
                })
            },
            deleteRow: function(id, data) {
                this.tableData = data.filter(function(item) {
                    return item.id !== id
                })
            },
        },
    })
}

function removeElFromArr(el, array) {
    var filtered = array.filter(function(value) {
        return value != el
    })
    return filtered
}

function removeItemAjax(id, podType, podName, callback) {
    Swal.fire({
        title: 'Opravdu chcete smazat tuto položku?',
        type: 'warning',
        buttonsStyling: false,
        showCancelButton: true,
        confirmButtonText: 'Ano!',
        cancelButtonText: 'Zrušit',
        confirmButtonClass: 'btn btn-primary btn-lg mr-1',
        cancelButtonClass: 'btn btn-secondary btn-lg ml-1',
    }).then(result => {
        if (result.value) {
            axios
                .post(
                    ajaxUrl + '?action=delete_hiko_pod',
                    {
                        ['pod_type']: podType,
                        ['pod_name']: podName,
                        ['id']: id,
                    },
                    {
                        headers: {
                            'Content-Type': 'application/json;charset=utf-8',
                        },
                    }
                )
                .then(function() {
                    Swal.fire({
                        title: 'Odstraněno.',
                        type: 'success',
                        buttonsStyling: false,
                        confirmButtonText: 'OK',
                        confirmButtonClass: 'btn btn-primary btn-lg',
                    })
                    callback()
                })
                .catch(function(error) {
                    Swal.fire({
                        title: 'Při odstraňování došlo k chybě.',
                        text: error,
                        type: 'error',
                        buttonsStyling: false,
                        confirmButtonText: 'OK',
                        confirmButtonClass: 'btn btn-primary btn-lg',
                    })
                })
        }
    })
}
