/* global Tabulator updateTableHeaders homeUrl axios ajaxUrl getLetterType removeItemAjax */

var table, professions

function deletePerson(id, index) {
    const letterTypes = getLetterType()

    removeItemAjax(id, 'person', letterTypes['path'], () => {
        table.deleteRow(index)
    })
}

function getProfessionsNames(data) {
    const names = data.getValue()
    const rowData = data.getRow().getData()

    if (rowData.type != 'person' || names == '') {
        return ''
    }

    let result = ''

    names.split(';').map((name) => {
        if (name != '') {
            result += `<li>${professions[name]}</li>`
        }
    })

    return `<ul class="list-unstyled">${result}</ul>`
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
            {
                field: 'profession_detailed',
                headerFilter: 'input',
                formatter: function (cell) {
                    return getProfessionsNames(cell)
                },
                title: 'Professions',
            },
            {
                field: 'profession_short',
                headerFilter: 'input',
                formatter: function (cell) {
                    return getProfessionsNames(cell)
                },
                title: 'Palladio',
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
