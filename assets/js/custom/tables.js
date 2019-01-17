/* global Vue VueTables */

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
    }
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
                ]
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
                texts: defaultTablesOptions.texts
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
                texts: defaultTablesOptions.texts
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
