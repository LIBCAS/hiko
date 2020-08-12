/* global Tabulator updateTableHeaders homeUrl Vue VueTables Swal axios ajaxUrl defaultTablesOptions getLetterType removeItemAjax removeElFromArr getCustomSorting getTimestampFromDate isString */

const letterTypes = getLetterType()

var table

function deletePlace(id, index) {
    removeItemAjax(id, 'place', letterTypes['path'], () => {
        table.deleteRow(index)
    })
}

function deletePerson(id, index) {
    removeItemAjax(id, 'person', letterTypes['path'], () => {
        table.deleteRow(index)
    })
}

function removeEmptyNameAlternatives(personID) {
    const spinner = event.target.querySelector('.spinner')
    spinner.classList.remove('d-none')

    axios
        .get(
            ajaxUrl +
                '?action=count_alternate_name&id=' +
                personID +
                '&l_type=' +
                letterTypes['path']
        )
        .then(function () {
            table.replaceData(
                ajaxUrl +
                    '?action=persons_table_data&type=' +
                    letterTypes['personType']
            )
        })
        .catch(function (error) {
            console.log(error)
        })
        .then(function () {
            spinner.classList.add('d-none')
        })
}

var columns

if (document.getElementById('datatable-letters')) {
    Vue.use(VueTables.ClientTable, false, false, 'bootstrap4')
    columns = [
        'edit',
        'id',
        'signature',
        'date',
        'author',
        'recipient',
        'origin',
        'dest',
        'images',
        'status',
    ]

    let customSortingLetters = getCustomSorting([
        'signature',
        'date',
        'author',
        'recipient',
        'origin',
        'dest',
        'status',
        'images',
        'id',
    ])

    customSortingLetters.id = function (ascending) {
        return function (a, b) {
            a = parseInt(a.id)
            b = parseInt(b.id)

            if (ascending) return a >= b ? 1 : -1

            return a <= b ? 1 : -1
        }
    }

    customSortingLetters.date = function (ascending) {
        return function (a, b) {
            a = getTimestampFromDate(a.date_year, a.date_month, a.date_day)
            b = getTimestampFromDate(b.date_year, b.date_month, b.date_day)

            if (ascending) return a >= b ? 1 : -1

            return a <= b ? 1 : -1
        }
    }

    new Vue({
        el: '#datatable-letters',
        data: {
            columns: columns,
            error: false,
            loading: true,
            path: '',
            tableData: [],
            options: {
                customSorting: customSortingLetters,
                filterable: removeElFromArr('edit', columns),
                headings: {
                    dest: 'Destination',
                    edit: 'Akce',
                    id: 'ID',
                    images: 'Obrázky',
                },
                pagination: defaultTablesOptions.pagination,
                perPage: defaultTablesOptions.perPage,
                perPageValues: defaultTablesOptions.perPageValues,
                skin: defaultTablesOptions.skin,
                sortIcon: defaultTablesOptions.sortIcon,
                sortable: removeElFromArr('edit', columns),
                texts: defaultTablesOptions.texts,
            },
        },
        mounted: function () {
            let letterTypes = getLetterType()
            if (isString(letterTypes)) {
                self.error = letterTypes
                return
            }
            this.path = letterTypes['path']

            this.getData()
        },
        methods: {
            showHistory: function (id, event) {
                let self = this
                let spinner = event.target.querySelector('.spinner')
                spinner.classList.remove('d-none')
                axios
                    .get(
                        ajaxUrl +
                            '?action=list_letter_history&l_id=' +
                            id +
                            '&l_type=' +
                            self.path
                    )
                    .then(function (result) {
                        spinner.classList.add('d-none')
                        let r = result.data.data
                        r = r.replace(/\n/g, '<br>')
                        Swal.fire({
                            title: 'Historie úprav',
                            html: r,
                            buttonsStyling: false,
                            confirmButtonText: 'Zavřít',
                            confirmButtonClass: 'btn btn-primary btn-lg mr-1',
                        })
                    })
                    .catch(function (error) {
                        Swal.fire({
                            title:
                                'Historii úprav se nepodařilo načíst nebo nebo neexistuje',
                            text: error,
                            type: 'error',
                            buttonsStyling: false,
                            confirmButtonText: 'Zavřít',
                            confirmButtonClass: 'btn btn-primary btn-lg mr-1',
                        })
                    })
                    .then(function () {
                        spinner.classList.add('d-none')
                    })
            },
            deleteLetter: function (id) {
                let self = this
                removeItemAjax(id, 'letter', self.path, function () {
                    self.deleteRow(id, self.tableData)
                })
            },
            deleteRow: function (id, data) {
                this.tableData = data.filter(function (item) {
                    return item.id !== id
                })
            },
            getData: function () {
                let self = this
                axios
                    .get(
                        ajaxUrl +
                            '?action=list_all_letters_short&type=' +
                            self.path
                    )
                    .then(function (result) {
                        self.tableData = result.data
                    })
                    .catch(function (error) {
                        self.error = error
                    })
                    .then(function () {
                        self.loading = false
                    })
            },
        },
    })
}

if (document.getElementById('datatable-persons')) {
    table = new Tabulator('#datatable-persons', {
        columns: [
            {
                field: 'id',
                formatter: function (cell) {
                    const rowData = cell.getRow().getData()
                    const rowIndex = cell.getRow().getIndex()
                    const personId = cell.getValue()

                    let actions = '<ul class="list-unstyled">'

                    actions += `
                    <li>
                        <a href="${homeUrl}/${letterTypes['path']}/persons-add/?edit=${personId}" class="text-info is-link py-1">Upravit</a>
                    </li>
                    `

                    actions += rowData.relationships
                        ? ''
                        : `
                    <li>
                    <a onclick="deletePerson(${personId}, ${rowIndex})" class="text-danger is-link">Odstranit</a>
                    </li>
                    `
                    actions += '</ul>'

                    return actions
                },
                headerSort: false,
                title: '',
            },
            {
                field: 'name',
                headerFilter: 'input',
                formatter: function (cell) {
                    const name = cell.getValue()
                    const rowData = cell.getRow().getData()

                    if (rowData.type != 'person') {
                        return `<strong>${name}</strong>`
                    }

                    let resultName = `<strong>${name}</strong> `
                    resultName += `(${rowData.birth}–${rowData.death})`

                    return resultName
                },
                title: 'Name',
            },
            {
                field: 'alternatives',
                formatter: function (cell) {
                    const names = cell.getValue()
                    const rowIndex = cell.getRow().getIndex()

                    if (!Array.isArray(names) || names.length == 0) {
                        return ''
                    }

                    let actions = '<ul class="list-unstyled">'

                    names.forEach((name) => {
                        actions += `<li>${name}</li>`
                    })

                    actions += `
                    <li onclick="removeEmptyNameAlternatives(${rowIndex})">
                        <span class="is-link py-1 is-info">
                            <span class="spinner spinner-border spinner-border-sm d-none"></span>
                            Odstranit nepoužité varianty jména
                        </span>
                    </li>
                    `

                    actions += '</ul>'

                    return actions
                },
                headerFilter: 'input',
                title: 'Name as marked',
            },
        ],
        height: '600px',
        groupBy: 'type',
        groupHeader: function (value, count) {
            value = value == 'institution' ? 'Institution' : 'Person'

            return `
            ${value} <span class="text-danger">${count} items</span>
            `
        },
        groupStartOpen: false,
        layout: 'fitColumns',
        pagination: 'local',
        paginationSize: 25,
        selectable: false,
    })

    table.setData(
        ajaxUrl + '?action=persons_table_data&type=' + letterTypes['personType']
    )

    updateTableHeaders()
}

if (document.getElementById('datatable-places')) {
    table = new Tabulator('#datatable-places', {
        columns: [
            {
                field: 'id',
                formatter: function (cell) {
                    const rowData = cell.getRow().getData()
                    const rowIndex = cell.getRow().getIndex()
                    const placeId = cell.getValue()

                    let actions = '<ul class="list-unstyled">'

                    actions += `
                    <li>
                        <a href="${homeUrl}/${letterTypes['path']}/places-add/?edit=${placeId}" class="text-info is-link py-1">Upravit</a>
                    </li>
                    `

                    actions += rowData.relationships
                        ? ''
                        : `
                    <li>
                    <a onclick="deletePlace(${placeId}, ${rowIndex})" class="text-danger is-link">Odstranit</a>
                    </li>
                    `
                    actions += '</ul>'

                    return actions
                },
                headerSort: false,
                title: '',
            },
            {
                field: 'city',
                headerFilter: 'input',
                title: 'City',
            },
            {
                field: 'country',
                headerFilter: 'input',
                title: 'Country',
            },
            {
                field: 'latlong',
                formatter: function (cell) {
                    const latlong = cell.getValue()

                    if (!latlong) {
                        return ''
                    }

                    const coordinates = latlong.split(',')
                    const link = `https://www.openstreetmap.org/?mlat=${coordinates[0]}&mlon=${coordinates[1]}&zoom=12`

                    return `
                    <a href="${link}" target="_blank">
                        ${latlong}
                    </a>
                    `
                },
                headerFilter: 'input',
                title: 'Coordinates',
            },
        ],
        height: '600px',
        layout: 'fitColumns',
        pagination: 'local',
        paginationSize: 25,
        selectable: false,
    })

    table.setData(JSON.parse(document.querySelector('#places-data').innerHTML))

    updateTableHeaders()
}
