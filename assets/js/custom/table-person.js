/* global Tabulator updateTableHeaders homeUrl axios ajaxUrl getLetterType removeItemAjax arrayToList */

var table, professions

function deletePerson(id, index) {
    const letterTypes = getLetterType()

    removeItemAjax(id, 'person', letterTypes['path'], () => {
        table.deleteRow(index)
    })
}

function getProfessionsNames(professionList, rowData) {
    const type = rowData.type

    if (type != 'person' || professionList == '') {
        return []
    }

    let result = []

    professionList.split(';').map((name) => {
        if (name != '') {
            result.push(professions[name])
        }
    })

    return result
}

function removeEmptyNameAlternatives(personID) {
    const spinner = event.target.querySelector('.spinner')
    const letterTypes = getLetterType()

    spinner.classList.remove('d-none')

    axios
        .get(
            ajaxUrl +
                '?action=count_alternate_name&id=' +
                personID +
                '&l_type=' +
                letterTypes['path']
        )
        .then(() => {
            table.replaceData(
                ajaxUrl +
                    '?action=persons_table_data&type=' +
                    letterTypes['personType']
            )
        })
        .catch((error) => {
            console.log(error)
        })
        .then(() => {
            spinner.classList.add('d-none')
        })
}

if (document.getElementById('datatable-persons')) {
    const letterTypes = getLetterType()
    professions = JSON.parse(document.querySelector('#professions').innerHTML)

    table = new Tabulator('#datatable-persons', {
        columns: [
            {
                field: 'id',
                formatter: function (cell) {
                    const rowData = cell.getRow().getData()
                    const rowIndex = cell.getRow().getIndex()
                    const personId = cell.getValue()

                    let actions = ''

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

                    return `<ul class="list-unstyled mb-0">${actions}</ul>`
                },
                headerSort: false,
                title: '',
                width: 67,
            },
            {
                field: 'name',
                headerFilter: 'input',
                formatter: function (cell) {
                    cell.getElement().style.whiteSpace = 'normal'

                    const name = cell.getValue()
                    const rowData = cell.getRow().getData()

                    if (rowData.type != 'person') {
                        return `<strong>${name}</strong>`
                    }

                    let resultName = `<strong>${name}</strong> `
                    if (rowData.birth || rowData.death) {
                        resultName += `(${rowData.birth ? rowData.birth : ''}–${
                            rowData.death ? rowData.death : ''
                        })`
                    }

                    return resultName
                },
                title: 'Name',
                variableHeight: true,
            },
            {
                field: 'alternatives',
                formatter: function (cell) {
                    cell.getElement().style.whiteSpace = 'normal'

                    const names = cell.getValue()
                    const rowIndex = cell.getRow().getIndex()

                    if (!Array.isArray(names) || names.length == 0) {
                        return ''
                    }

                    let actions = ''

                    names.forEach((name) => {
                        actions += `<li>${name}</li>`
                    })

                    actions += `
                    <li onclick="removeEmptyNameAlternatives(${rowIndex})">
                        <button type="button" class="btn btn-sm btn-link px-0 py-1 is-info text-left">
                            <span class="spinner spinner-border spinner-border-sm d-none"></span>
                            Odstranit nepoužité varianty jména
                        </button>
                    </li>
                    `

                    return `<ul class="list-unstyled mb-0">${actions}</ul>`
                },
                headerFilter: 'input',
                title: 'Name as marked',
                variableHeight: true,
            },
            {
                field: 'profession_detailed',
                headerFilter: 'input',

                formatter: function (cell) {
                    return arrayToList(cell.getValue())
                },
                mutator: function (value, data) {
                    return getProfessionsNames(value, data)
                },
                sorter: 'array',
                title: 'Professions',
                variableHeight: true,
            },
            {
                field: 'profession_short',
                headerFilter: 'input',
                formatter: function (cell) {
                    return arrayToList(cell.getValue())
                },
                mutator: function (value, data) {
                    return getProfessionsNames(value, data)
                },
                sorter: 'array',
                title: 'Palladio',
                variableHeight: true,
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
