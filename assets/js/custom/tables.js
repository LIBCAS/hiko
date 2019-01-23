/* global Vue VueTables Swal axios ajaxUrl */

var columns;

var defaultTablesOptions = {
    skin: 'table table-bordered table-hover table-striped table-sm',
    sortIcon: {
        base: 'oi pl-1',
        up: 'oi-arrow-top',
        down: 'oi-arrow-bottom',
        is: 'oi-elevator'
    },
    texts: {
        count: 'Zobrazena položka {from} až {to} z celkového počtu {count} položek |{count} položky|Jedna položka',
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
        columns: 'Columns'
    },
};

if (document.getElementById('datatable-letters')) {
    let tabledata;
    if (document.querySelector('#letters-data') !== null) {
        tabledata = JSON.parse(document.querySelector('#letters-data').innerHTML);
    } else {
        tabledata = null;
    }
    Vue.use(VueTables.ClientTable, false, false, 'bootstrap4');
    columns = [
        'edit', 'number', 'date', 'author', 'recipient', 'origin', 'dest', 'status'
    ];

    new Vue({
        el: '#datatable-letters',
        data: {
            columns: columns,
            tableData: tabledata,
            options: {
                headings: {
                    edit: 'Akce',
                    dest: 'Destination'
                },
                skin: defaultTablesOptions.skin,
                sortable: removeElFromArr('edit', columns),
                filterable: removeElFromArr('edit', columns),
                sortIcon: defaultTablesOptions.sortIcon,
                texts: defaultTablesOptions.texts,
                dateColumns: [
                    'date'
                ],
                rowClassCallback: function(row) {
                    return 'row-' + row.id;
                }
            }
        },
        methods: {
            deleteLetter: function(id) {
                let self = this;
                removeItemAjax(id, 'delete_bl_letter', function() {
                    self.deleteRow(id, self.tableData);
                });
            },
            deleteRow: function(id, data) {
                this.tableData = data.filter(function(item) {
                    return item.id !== id;
                });
            }
        }
    });
}


if (document.getElementById('datatable-persons')) {
    Vue.use(VueTables.ClientTable, false, false, 'bootstrap4');

    columns = [
        'edit', 'name', 'dates'
    ];

    new Vue({
        el: '#datatable-persons',
        data: {
            columns: columns,
            tableData: JSON.parse(document.querySelector('#persons-data').innerHTML),
            options: {
                headings: {
                    edit: 'Akce'
                },
                skin: defaultTablesOptions.skin,
                sortable: removeElFromArr('edit', columns),
                filterable: removeElFromArr('edit', columns),
                sortIcon: defaultTablesOptions.sortIcon,
                texts: defaultTablesOptions.texts,
                rowClassCallback: function(row) {
                    return 'row-' + row.id;
                }
            }
        },
        methods: {
            deletePerson: function(id) {
                let self = this;
                removeItemAjax(id, 'delete_bl_person', function() {
                    self.deleteRow(id, self.tableData);
                });
            },
            deleteRow: function(id, data) {
                this.tableData = data.filter(function(item) {
                    return item.id !== id;
                });
            }
        }
    });
}

if (document.getElementById('datatable-places')) {
    Vue.use(VueTables.ClientTable, false, false, 'bootstrap4');
    columns = [
        'edit', 'city', 'country'
    ];
    new Vue({
        el: '#datatable-places',
        data: {
            columns: columns,
            tableData: JSON.parse(document.querySelector('#places-data').innerHTML),
            options: {
                headings: {
                    edit: 'Akce'
                },
                skin: defaultTablesOptions.skin,
                sortable: removeElFromArr('edit', columns),
                filterable: removeElFromArr('edit', columns),
                sortIcon: defaultTablesOptions.sortIcon,
                texts: defaultTablesOptions.texts,
                rowClassCallback: function(row) {
                    return 'row-' + row.id;
                }
            }
        },
        methods: {
            deletePlace: function(id) {
                let self = this;
                removeItemAjax(id, 'delete_bl_place', function() {
                    self.deleteRow(id, self.tableData);
                });
            },
            deleteRow: function(id, data) {
                this.tableData = data.filter(function(item) {
                    return item.id !== id;
                });
            }
        }
    });
}

function removeElFromArr(el, array) {
    var filtered = array.filter(function(value) {
        return value != el;
    });
    return filtered;
}


function removeItemAjax(id, action, callback) {
    Swal.fire({
        title: 'Opravdu chcete smazat tuto položku?',
        type: 'warning',
        buttonsStyling: false,
        showCancelButton: true,
        confirmButtonText: 'Ano!',
        cancelButtonText: 'Zrušit',
        confirmButtonClass: 'btn btn-primary btn-lg mr-1',
        cancelButtonClass: 'btn btn-secondary btn-lg ml-1',
    }).then((result) => {
        if (result.value) {
            axios.get(ajaxUrl + '?action=' + action + '&pods_id=' + id)
                .then(function() {
                    Swal.fire({
                        title: 'Odstraněno.',
                        type: 'success',
                        buttonsStyling: false,
                        confirmButtonText: 'OK',
                        confirmButtonClass: 'btn btn-primary btn-lg',
                    });
                    callback();
                })
                .catch(function (error) {
                    Swal.fire({
                        title: 'Při odstraňování došlo k chybě.',
                        text: error,
                        type: 'error',
                        buttonsStyling: false,
                        confirmButtonText: 'OK',
                        confirmButtonClass: 'btn btn-primary btn-lg',
                    });
                });
        }
    });
}
