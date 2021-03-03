/* global Tabulator updateTableHeaders homeUrl Swal axios ajaxUrl getLetterType removeItemAjax getTimestampFromDate arrayToList */

var table

function deleteLetter(id, index) {
    const letterTypes = getLetterType()

    removeItemAjax(id, 'letter', letterTypes['path'], () => {
        table.deleteRow(index)
    })
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
    const letterTypes = getLetterType()
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

const headerMenu = function () {
    const menu = []
    const columns = this.getColumns()

    for (let column of columns) {
        const hiddenFields = ['actions', 'editors', 'my_letter']
        let checkbox = document.createElement('input')
        let label = document.createElement('label')
        let container = document.createElement('div')

        checkbox.classList.add('form-check-input')
        checkbox.type = 'checkbox'
        checkbox.checked = column.isVisible()
        checkbox.value = column.getDefinition().title
        checkbox.id = column.getField()

        label.classList.add('form-check-label')
        label.appendChild(document.createTextNode(column.getDefinition().title))
        label.htmlFor = column.getField()

        container.classList.add('form-check')
        container.appendChild(checkbox)
        container.appendChild(label)

        if (hiddenFields.includes(column.getField())) {
            continue
        }

        menu.push({
            action: (e) => {
                e.stopPropagation()
            },
            label: container,
        })

        checkbox.addEventListener('change', () => {
            column.toggle()
            table.redraw()
        })
    }

    return menu
}

if (document.getElementById('datatable-letters')) {
    const letterTypes = getLetterType()
    const categoriesData = JSON.parse(
        document.getElementById('categories-data').innerHTML
    )
    const categoriesNameField =
        letterTypes['defaultLanguage'] === 'en' ? 'name' : 'namecz'

    table = new Tabulator('#datatable-letters', {
        ajaxResponse: function (url, params, response) {
            document.getElementById('custom-filters').classList.remove('d-none')
            return response
        },
        columns: [
            {
                field: 'actions',
                formatter: function (cell) {
                    const rowIndex = cell.getRow().getIndex()
                    const letterId = cell.getRow().getData().ID

                    return `
                    <ul class="list-unstyled mb-0">
                    <li>
                        <a href="${homeUrl}/${letterTypes['path']}/letters-add/?edit=${letterId}" class="text-info py-1">Upravit</a>
                    </li>
                    <li>
                        <a href="${homeUrl}/${letterTypes['path']}/letters-media/?l_type=${letterTypes['letterType']}&letter=${letterId}" class="py-1 text-primary">Obrazové přílohy</a>
                    </li>
                    <li>
                        <a href="${homeUrl}/letter-preview/?l_type=${letterTypes['letterType']}&letter=${letterId}&lang=${letterTypes['defaultLanguage']}" class="py-1 text-primary">Náhled</a>
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
                headerMenu: headerMenu,
                title: '',
            },
            {
                field: 'ID',
                headerFilter: 'input',
                title: 'ID',
                width: 41,
            },
            {
                field: 'signature',
                formatter: function (cell) {
                    cell.getElement().style.whiteSpace = 'normal'
                    return cell.getValue()
                },
                headerFilter: 'input',
                title: 'Signature',
                mutator: function (value, data) {
                    const signature = data.signature
                    const repository = data.repository

                    let result = signature

                    if (repository && signature) {
                        result += ' / '
                    }

                    if (repository) {
                        result += repository
                    }

                    return result
                },
            },
            {
                field: 'date',
                formatter: 'textarea',
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
                    cell.getElement().style.whiteSpace = 'normal'
                    return arrayToList(cell.getValue())
                },
                sorter: function (a, b) {
                    return sortLetterMultiData(a, b)
                },
                title: 'Author',
                variableHeight: true,
            },
            {
                field: 'recipient',
                headerFilter: 'input',
                formatter: function (cell) {
                    cell.getElement().style.whiteSpace = 'normal'
                    return arrayToList(cell.getValue())
                },
                sorter: function (a, b) {
                    return sortLetterMultiData(a, b)
                },
                title: 'Recipient',
                variableHeight: true,
            },
            {
                field: 'origin',
                headerFilter: 'input',
                formatter: function (cell) {
                    cell.getElement().style.whiteSpace = 'normal'
                    return arrayToList(cell.getValue())
                },
                sorter: function (a, b) {
                    return sortLetterMultiData(a, b)
                },
                title: 'Origin',
                variableHeight: true,
            },
            {
                field: 'dest',
                headerFilter: 'input',
                formatter: function (cell) {
                    cell.getElement().style.whiteSpace = 'normal'
                    return arrayToList(cell.getValue())
                },
                sorter: function (a, b) {
                    return sortLetterMultiData(a, b)
                },
                title: 'Destination',
                variableHeight: true,
            },
            {
                field: 'keyword',
                headerFilter: 'input',
                formatter: function (cell) {
                    cell.getElement().style.whiteSpace = 'normal'
                    return arrayToList(cell.getValue())
                },
                sorter: function (a, b) {
                    return sortLetterMultiData(a, b)
                },
                title: 'Keywords',
                variableHeight: true,
                visible: false,
            },
            {
                field: 'category',
                formatter: function (cell) {
                    cell.getElement().style.whiteSpace = 'normal'
                    return cell.getValue()
                },
                headerFilter: 'input',
                mutator: function (categories) {
                    if (typeof categories == 'string') categories = [categories]

                    const uniqueCategories = [...new Set(categories)]

                    let result = '<ul class="list-unstyled mb-0">'

                    uniqueCategories.forEach((category) => {
                        const categoryMeta = categoriesData.find((item) => {
                            return item.id == category
                        })

                        if (categoryMeta) {
                            result += `<li>${categoryMeta[categoriesNameField]}</li>`
                        }
                    })

                    return result + '</ul>'
                },
                title: 'Categories',
                visible: false,
            },
            {
                field: 'images',
                headerFilter: 'input',
                formatter: function (cell) {
                    cell.getElement().style.whiteSpace = 'normal'
                    if (cell.getValue()) {
                        return 'ano'
                    }

                    return ''
                },
                title: 'Images',
                variableHeight: true,
            },
            {
                field: 'status',
                formatter: 'textarea',
                headerFilter: 'input',
                title: 'Status',
            },
            {
                field: 'my_letter',
                headerFilter: 'input',
                title: 'My Letter',
                visible: false,
            },
            {
                field: 'editors',
                headerFilter: 'input',
                title: 'Editors',
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
        layout: 'fitColumns',
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

            table.removeFilter('my_letter', '!=', '0')

            if (selected == 'my') {
                table.addFilter('my_letter', '!=', '0')
            }
        })
    })

    if (document.getElementById('editors-letters-filter')) {
        document
            .getElementById('editors-letters-filter')
            .addEventListener('change', (e) => {
                table.clearFilter()

                let editor = e.target.value

                if (editor != 'all') {
                    table.addFilter('editors', 'like', editor)
                }
            })
    }
}
