/* global Tabulator updateTableHeaders getLetterType removeItemAjax axios Swal ajaxUrl */

const keywordsSwal = {
    confirmSave: {
        buttonsStyling: false,
        cancelButtonClass: 'btn btn-secondary btn-lg ml-1',
        cancelButtonText: 'Zrušit',
        confirmButtonClass: 'btn btn-primary btn-lg mr-1',
        confirmButtonText: 'Uložit',
        showCancelButton: true,
        showLoaderOnConfirm: true,
        type: 'question',
    },
    saveSuccess: {
        title: 'Klíčové slovo bylo úspěšně upraveno',
        type: 'success',
        buttonsStyling: false,
        confirmButtonText: 'OK',
        confirmButtonClass: 'btn btn-primary btn-lg',
    },
}

const letterTypes = getLetterType()

var table

function deleteKeyword(id, index) {
    removeItemAjax(id, 'keyword', letterTypes['path'], () => {
        table.deleteRow(index)
    })
}

function addKeyword(
    type,
    action,
    id,
    oldKeyword = '',
    oldKeywordCZ = '',
    oldCategory = false
) {
    let swalConfig = keywordsSwal.confirmSave

    swalConfig.title = (id ? 'Upravit' : 'Nové ') + ' klíčové slovo'
    swalConfig.allowOutsideClick = () => !Swal.isLoading()
    swalConfig.html = getKeywordForm(oldKeyword, oldKeywordCZ, oldCategory)
    swalConfig.focusConfirm = false

    swalConfig.preConfirm = () => {
        const nameen = document.getElementById('nameen').value
        const namecz = document.getElementById('namecz').value
        const category = document.getElementById('category').checked

        if (nameen.length < 2) {
            return Swal.showValidationMessage(
                'Zadané hodnoty nejsou zadané v požadovaném formátu (2-255 znaků)'
            )
        }

        return axios
            .post(
                ajaxUrl + '?action=insert_keyword',
                {
                    ['type']: type,
                    ['nameen']: nameen,
                    ['namecz']: namecz,
                    ['category']: category,
                    ['action']: action,
                    ['id']: id,
                },
                {
                    headers: {
                        'Content-Type': 'application/json;charset=utf-8',
                    },
                }
            )
            .then(function () {
                table.replaceData(
                    ajaxUrl +
                        '?action=keywords_table_data&type=' +
                        letterTypes['keyword']
                )
            })
            .catch(function (error) {
                Swal.showValidationMessage(
                    `Při ukládání došlo k chybě: ${error}`
                )
            })
    }

    Swal.fire(swalConfig).then((result) => {
        if (result.value) {
            Swal.fire(keywordsSwal.saveSuccess)
        }
    })
}

function getKeywordForm(en, cs, category) {
    category = category ? 'checked="checked"' : ''

    return `
    <div class="form-group">
    <label for="nameen">EN</label>
    <input value="${en}" id="nameen" class="form-control" pattern=".{2,255}" required title="2 to 255 characters">
    </div>
    <div class="form-group">
    <label for="namecz">CZ</label>
    <input value="${cs}" id="namecz" class="form-control" pattern=".{2,255}" required title="2 to 255 characters">
    </div>
    <div class="form-check">
    <input type="checkbox" class="form-check-input" id="category" ${category} autocomplete="off">
    <label class="form-check-label" id="category" for="category">Category</label>
    </div>
    `
}

if (document.getElementById('datatable-keywords')) {
    table = new Tabulator('#datatable-keywords', {
        columns: [
            {
                field: 'id',
                formatter: function (cell) {
                    const rowData = cell.getRow().getData()
                    const rowIndex = cell.getRow().getIndex()
                    return `
                    <ul class="list-unstyled mb-0">
                        <li>
                            <span onclick="addKeyword('${letterTypes['keyword']}', 'edit', ${rowData.id}, '${rowData.name}', '${rowData.namecz}, ${rowData.category}')" class="text-info is-link py-1">
                                Upravit
                            </span>
                        </li>
                        <li>
                            <span onclick="deleteKeyword(${rowData.id}, ${rowIndex})" class="text-danger is-link py-1">
                                Odstranit
                            </span>
                        </li>
                    </ul>
                    `
                },
                headerSort: false,
                title: '',
                width: 67,
            },
            {
                field: 'name',
                formatter: 'textarea',
                headerFilter: 'input',
                title: 'EN',
            },
            {
                field: 'namecz',
                formatter: 'textarea',
                headerFilter: 'input',
                title: 'CZ',
            },
        ],
        dataFiltered: function (filters, rows) {
            document.getElementById('search-count').innerHTML = rows.length
        },
        dataLoaded: function (data) {
            document.getElementById('total-count').innerHTML = data.length
        },
        groupBy: 'category',
        groupHeader: function (value, count) {
            value = value ? 'Category' : 'Keyword'

            return `
            ${value} <span class="text-danger">${count} items</span>
            `
        },
        groupStartOpen: false,
        footerElement:
            '<span>Showing <span id="search-count"></span> items from <span id="total-count"></span> total items</span>',
        height: '600px',
        layout: 'fitColumns',
        pagination: 'local',
        paginationSize: 25,
        selectable: false,
        tooltips: true,
    })

    table.setData(
        ajaxUrl + '?action=keywords_table_data&type=' + letterTypes['keyword']
    )

    updateTableHeaders()
}
