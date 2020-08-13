/* global Tabulator updateTableHeaders homeUrl Swal axios ajaxUrl getLetterType removeItemAjax getTimestampFromDate */

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

function deleteLetter(id, index) {
    removeItemAjax(id, 'letter', letterTypes['path'], () => {
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

function listLetterMultiData(data) {
    if (!Array.isArray(data)) {
        return data
    }

    let list = '<ul class="list-unstyled">'
    data.forEach((author) => {
        list += `<li>${author}</li>`
    })
    list += '</ul>'

    return list
}

function sortLetterMultiData(aData, bData) {
    let a = aData // if is string
    let b = bData // if is string

    if (!aData) {
        a = ''
    } else if (Array.isArray(aData) && aData[0]) {
        a = aData[0]
    }

    if (!bData) {
        b = ''
    } else if (Array.isArray(bData) && bData[0]) {
        b = bData[0]
    }

    return a.localeCompare(b)
}

function showHistory(id, event) {
    const spinner = event.querySelector('.spinner')
    spinner.classList.remove('d-none')

    axios
        .get(
            ajaxUrl +
                '?action=list_letter_history&l_id=' +
                id +
                '&l_type=' +
                letterTypes['path']
        )
        .then(function (result) {
            Swal.fire({
                title: 'Historie úprav',
                html: result.data.data.replace(/\n/g, '<br>'),
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
}

if (document.getElementById('datatable-letters')) {
    table = new Tabulator('#datatable-letters', {
        columns: [
            {
                field: 'actions',
                formatter: function (cell) {
                    const rowIndex = cell.getRow().getIndex()
                    const letterId = cell.getRow().getData().ID

                    return `
                    <ul class="list-unstyled">
                    <li>
                        <a href="${homeUrl}/${letterTypes['path']}/letters-add/?edit=${letterId}" class="text-info py-1">Upravit</a>
                    </li>
                    <li>
                        <a href="${homeUrl}/${letterTypes['path']}/letters-media/?l_type=${letterTypes['letterType']}&letter=${letterId}" class="py-1 text-primary">Obrazové přílohy</a>
                    </li>
                    <li>
                        <a href="${homeUrl}/letter-preview/?l_type=${letterTypes['letterType']}&letter=${letterId}" class="py-1 text-primary">Náhled</a>
                    </li>
                    <li>
                        <span onclick="showHistory(${letterId}, this)" class="is-link text-primary py-1">
                            <span class="spinner spinner-border spinner-border-sm d-none"></span>
                            Historie úprav
                        </span>
                    </li>
                    <li>
                        <span onclick="deleteLetter(${letterId}, ${rowIndex})" class="text-danger is-link">
                            Odstranit
                        </span>
                    </li>
                    </ul>
                    `
                },
                headerSort: false,
                title: '',
            },
            {
                field: 'ID',
                headerFilter: 'input',
                title: 'ID',
            },
            {
                field: 'signature',
                headerFilter: 'input',
                title: 'Signature',
            },
            {
                field: 'date',
                headerFilter: 'input',
                mutator: function (value, data) {
                    let year = data.date_year ? data.date_year : 0
                    let month = data.date_month ? data.date_month : 0
                    let day = data.date_day ? data.date_day : 0
                    return `${year}/${month}/${day}`
                },
                sorter: function (a, b, aRow, bRow) {
                    let aRowData = aRow.getData()
                    let bRowData = bRow.getData()

                    a = getTimestampFromDate(
                        aRowData.date_year,
                        aRowData.date_month,
                        aRowData.date_day
                    )

                    b = getTimestampFromDate(
                        bRowData.date_year,
                        bRowData.date_month,
                        bRowData.date_day
                    )

                    return a - b
                },
                title: 'Date',
            },
            {
                field: 'author',
                headerFilter: 'input',
                formatter: function (cell) {
                    return listLetterMultiData(cell.getValue())
                },
                sorter: function (a, b) {
                    return sortLetterMultiData(a, b)
                },
                title: 'Author',
            },
            {
                field: 'recipient',
                headerFilter: 'input',
                formatter: function (cell) {
                    return listLetterMultiData(cell.getValue())
                },
                sorter: function (a, b) {
                    return sortLetterMultiData(a, b)
                },
                title: 'Recipient',
            },
            {
                field: 'origin',
                headerFilter: 'input',
                formatter: function (cell) {
                    return listLetterMultiData(cell.getValue())
                },
                sorter: function (a, b) {
                    return sortLetterMultiData(a, b)
                },
                title: 'Origin',
            },
            {
                field: 'dest',
                headerFilter: 'input',
                formatter: function (cell) {
                    return listLetterMultiData(cell.getValue())
                },
                sorter: function (a, b) {
                    return sortLetterMultiData(a, b)
                },
                title: 'Destination',
            },
            {
                field: 'images',
                headerFilter: 'input',
                formatter: function (cell) {
                    if (cell.getValue()) {
                        return 'ano'
                    }

                    return ''
                },
                title: 'Images',
            },
            {
                field: 'status',
                headerFilter: 'input',
                title: 'Status',
            },
            {
                field: 'my_letter',
                headerFilter: 'input',
                title: 'My Letter',
                visible: false,
            },
        ],
        dataFiltered: function (filters, rows) {
            document.getElementById('search-count').innerHTML = rows.length
        },
        dataLoaded: function (data) {
            document.getElementById('total-count').innerHTML = data.length
        },
        footerElement:
            '<span>Showing <span id="search-count"></span> items from <span id="total-count"></span> total items</span>',
        height: '600px',
        layout: 'fitDataFill',
        index: 'ID',
        pagination: 'local',
        paginationSize: 25,
        selectable: false,
        tooltips: true,
    })

    table.setData(
        ajaxUrl + '?action=list_all_letters_short&type=' + letterTypes['path']
    )

    updateTableHeaders()

    document.querySelectorAll('#my-letters-filter input').forEach((radio) => {
        radio.addEventListener('change', () => {
            let selected = document.querySelector(
                'input[name="letters-filter"]:checked'
            ).value

            if (selected == 'my') {
                table.addFilter('my_letter', '!=', '0')
            } else {
                table.removeFilter('my_letter', '!=', '0')
            }
        })
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
        dataFiltered: function (filters, rows) {
            document.getElementById('search-count').innerHTML = rows.length
        },
        dataLoaded: function (data) {
            document.getElementById('total-count').innerHTML = data.length
        },
        footerElement:
            '<span>Showing <span id="search-count"></span> items from <span id="total-count"></span> total items</span>tooltips: true',
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
        tooltips: true,
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
        dataFiltered: function (filters, rows) {
            document.getElementById('search-count').innerHTML = rows.length
        },
        dataLoaded: function (data) {
            document.getElementById('total-count').innerHTML = data.length
        },
        footerElement:
            '<span>Showing <span id="search-count"></span> items from <span id="total-count"></span> total items</span>',
        height: '600px',
        layout: 'fitColumns',
        pagination: 'local',
        paginationSize: 25,
        selectable: false,
        tooltips: true,
    })

    table.setData(JSON.parse(document.querySelector('#places-data').innerHTML))

    updateTableHeaders()
}
