/* global Vue VeeValidate */

if (window.hasOwnProperty('VueMultiselect')) {
    Vue.component('multiselect', window.VueMultiselect.default)
}

if (typeof VeeValidate !== 'undefined') {
    Vue.use(VeeValidate)
}

const defaultTablesOptions = {
    pagination: {
        edge: true,
    },
    perPage: 25,
    perPageValues: [10, 25, 50, 100],
    skin: 'table table-bordered table-hover table-striped table-sm',
    sortIcon: {
        base: 'oi pl-1',
        up: 'oi-arrow-top',
        down: 'oi-arrow-bottom',
        is: 'oi-elevator',
    },
    texts: {
        columns: 'Columns',
        count:
            'Zobrazena položka {from} až {to} z celkového počtu {count} položek |{count} položky|Jedna položka',
        defaultOption: 'Vybrat {column}',
        filter: 'Filtr: ',
        filterBy: 'Filtrovat dle {column}',
        filterPlaceholder: 'Hledat',
        first: 'První',
        last: 'Poslední',
        limit: 'Položky: ',
        loading: 'Načítá se...',
        noResults: 'Nenalezeno',
        page: 'Strana: ',
    },
}
