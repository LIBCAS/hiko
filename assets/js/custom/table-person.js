/* global Tabulator updateTableHeaders homeUrl axios ajaxUrl getLetterType removeItemAjax arrayToList normalize */

var table

function deletePerson(id, index) {
    const letterTypes = getLetterType()

    removeItemAjax(id, 'person', letterTypes['path'], () => {
        table.deleteRow(index)
    })
}

function removeEmptyNames(personID) {
    const spinner = event.target.querySelector('.spinner')
    const letterTypes = getLetterType()
    spinner.classList.remove('d-none')

    axios
        .get(
            ajaxUrl +
                '?action=regenerate_alternate_name&id=' +
                personID +
                '&l_type=' +
                letterTypes['path']
        )
        .then(() => {
            table.replaceData(
                ajaxUrl +
                    '?action=persons_table_data&type=' +
                    letterTypes['path']
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
                headerFilterFunc: (headerValue, rowValue) => {
                    return normalize(rowValue.name).includes(
                        normalize(headerValue)
                    )
                },
                tooltip: (cell) => {
                    return cell.getValue().name
                },
                formatter: (cell) => {
                    let resultName =
                        '<strong>' + cell.getValue()['name'] + '</strong>'

                    if (cell.getValue()['dates']) {
                        resultName += ' ' + cell.getValue()['dates']
                    }

                    return resultName
                },
                sorter: (a, b) => {
                    return a.name.localeCompare(b.name)
                },
                title: 'Name',
                variableHeight: true,
            },
            {
                field: 'alternatives',
                formatter: (cell) => {
                    cell.getElement().style.whiteSpace = 'normal'

                    let actions = ''

                    cell.getValue().forEach((name) => {
                        actions += `<li>${name}</li>`
                    })

                    actions += `
                    <li onclick="removeEmptyNames(${cell.getRow().getIndex()})">
                        <button type="button" class="btn btn-sm btn-link px-0 py-1 is-info text-left">
                            <span class="spinner spinner-border spinner-border-sm d-none"></span>
                            Přegenerovat jména
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
                field: 'detailed',
                headerFilter: 'input',
                formatter: (cell) => {
                    return arrayToList(cell.getValue())
                },
                sorter: 'array',
                title: 'Professions',
                variableHeight: true,
            },
            {
                field: 'short',
                headerFilter: 'input',
                formatter: (cell) => {
                    return arrayToList(cell.getValue())
                },
                sorter: 'array',
                title: 'Palladio',
                variableHeight: true,
            },
        ],
        dataFiltered: (filters, rows) => {
            document.getElementById('search-count').innerHTML = rows.length
        },
        dataLoaded: (data) => {
            document.getElementById('total-count').innerHTML = data.length
        },
        footerElement:
            '<span>Showing <span id="search-count"></span> items from <span id="total-count"></span> total items</span>',
        height: '600px',
        groupBy: 'type',
        groupHeader: (value, count) => {
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
        ajaxUrl + '?action=persons_table_data&type=' + letterTypes['path']
    )

    updateTableHeaders()
}
