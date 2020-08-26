/* global Tabulator updateTableHeaders homeUrl getLetterType removeItemAjax */

var table

function deletePlace(id, index) {
    const letterTypes = getLetterType()

    removeItemAjax(id, 'place', letterTypes['path'], () => {
        table.deleteRow(index)
    })
}

if (document.getElementById('datatable-places')) {
    const letterTypes = getLetterType()

    table = new Tabulator('#datatable-places', {
        columns: [
            {
                field: 'id',
                formatter: function (cell) {
                    const rowData = cell.getRow().getData()
                    const rowIndex = cell.getRow().getIndex()
                    const placeId = cell.getValue()

                    let actions = ''

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

                    return `<ul class="list-unstyled mb-0">${actions}</ul>`
                },
                headerSort: false,
                title: '',
                width: 67,
            },
            {
                field: 'city',
                formatter: 'textarea',
                headerFilter: 'input',
                title: 'City',
            },
            {
                field: 'country',
                formatter: 'textarea',
                headerFilter: 'input',
                title: 'Country',
            },
            {
                field: 'latlong',
                formatter: function (cell) {
                    cell.getElement().style.whiteSpace = 'normal'

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
        layout: 'fitColumns',
        pagination: 'local',
        paginationSize: 25,
        selectable: false,
        tooltips: true,
    })

    table.setData(JSON.parse(document.querySelector('#places-data').innerHTML))

    updateTableHeaders()
}
