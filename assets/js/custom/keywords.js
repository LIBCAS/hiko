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
        title: 'Úspěšně upraveno',
        type: 'success',
        buttonsStyling: false,
        confirmButtonText: 'OK',
        confirmButtonClass: 'btn btn-primary btn-lg',
    },
}

const letterTypes = getLetterType()

var keywordTable
var categoriesTable

function deleteKeyword(id, index, table) {
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
    oldCategory = ''
) {
    let swalConfig = keywordsSwal.confirmSave

    swalConfig.title = (id ? 'Upravit' : 'Nové ') + ' klíčové slovo'
    swalConfig.allowOutsideClick = () => !Swal.isLoading()
    swalConfig.html = getKeywordForm(oldKeyword, oldKeywordCZ, oldCategory)
    swalConfig.focusConfirm = false

    swalConfig.preConfirm = () => {
        const nameen = document.getElementById('nameen').value
        const namecz = document.getElementById('namecz').value

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
                    ['action']: action,
                    ['id']: id,
                    ['categories']: document.getElementById('categories').value,
                },
                {
                    headers: {
                        'Content-Type': 'application/json;charset=utf-8',
                    },
                }
            )
            .then(function () {
                keywordTable.replaceData(
                    ajaxUrl +
                        '?action=keywords_table_data&type=' +
                        letterTypes['keyword'] +
                        '&categories=0'
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

// TODO:
// REFACTOR
function addCategory(type, action, id, oldKeyword = '', oldKeywordCZ = '') {
    let swalConfig = keywordsSwal.confirmSave

    swalConfig.title = id ? 'Upravit kategorii' : 'Nová kategorie'
    swalConfig.allowOutsideClick = () => !Swal.isLoading()
    swalConfig.html = getCategoryForm(oldKeyword, oldKeywordCZ)
    swalConfig.focusConfirm = false

    swalConfig.preConfirm = () => {
        const nameen = document.getElementById('nameen').value
        const namecz = document.getElementById('namecz').value

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
                    ['action']: action,
                    ['id']: id,
                    ['is_category']: 1,
                },
                {
                    headers: {
                        'Content-Type': 'application/json;charset=utf-8',
                    },
                }
            )
            .then(function () {
                categoriesTable.replaceData(
                    ajaxUrl +
                        '?action=keywords_table_data&type=' +
                        letterTypes['keyword'] +
                        '&categories=1'
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

function getKeywordForm(en, cs, oldCategory) {
    const categories = categoriesTable.getData()
    const categoriesNameField =
        getLetterType()['defaultLanguage'] === 'en' ? 'name' : 'namecz'

    return `
    <div class="form-group">
    <label for="nameen">EN</label>
    <input value="${en}" id="nameen" class="form-control" pattern=".{2,255}" required title="2 to 255 characters">
    </div>
    <div class="form-group">
    <label for="namecz">CZ</label>
    <input value="${cs}" id="namecz" class="form-control" pattern=".{2,255}" required title="2 to 255 characters">
    </div>
    <div class="form-group">
    <label for="categories">Kategorie</label>
    <select id="categories" class="form-control">
        <option selected value>---</option>
        ${categories.map(
            (category) =>
                `<option value="${category.id}" ${
                    category.id == oldCategory ? 'selected' : ''
                }>
            ${category[categoriesNameField]}
            </option>`
        )}
    </select>
    </div>
    `
}

function getCategoryForm(en, cs) {
    return `
    <div class="form-group">
    <label for="nameen">EN</label>
    <input value="${en}" id="nameen" class="form-control" pattern=".{2,255}" required title="2 to 255 characters">
    </div>
    <div class="form-group">
    <label for="namecz">CZ</label>
    <input value="${cs}" id="namecz" class="form-control" pattern=".{2,255}" required title="2 to 255 characters">
    </div>
    `
}

if (document.getElementById('datatable-keywords')) {
    keywordTable = new Tabulator('#datatable-keywords', {
        columns: [
            {
                download: false,
                field: 'id',
                formatter: function (cell) {
                    const rowData = cell.getRow().getData()
                    const rowIndex = cell.getRow().getIndex()
                    return `
                    <ul class="list-unstyled mb-0">
                        <li>
                            <span onclick="addKeyword('${letterTypes['keyword']}', 'edit', ${rowData.id}, '${rowData.name}', '${rowData.namecz}', '${rowData.categories}')" class="text-info is-link py-1">
                                Upravit
                            </span>
                        </li>
                        <li>
                            <span onclick="deleteKeyword(${rowData.id}, ${rowIndex}, keywordTable)" class="text-danger is-link py-1">
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
                download: true,
                field: 'name',
                formatter: 'textarea',
                headerFilter: 'input',
                title: 'EN',
            },
            {
                download: true,
                field: 'namecz',
                formatter: 'textarea',
                headerFilter: 'input',
                title: 'CZ',
            },
            {
                download: true,
                field: 'categories',
                mutator: function (categoryId) {
                    const categoriesNameField =
                        getLetterType()['defaultLanguage'] === 'en'
                            ? 'name'
                            : 'namecz'
                    const matchingCategories = categoriesTable
                        .getData()
                        .filter((category) => {
                            return categoryId == category.id
                        })

                    if (matchingCategories.length === 0) {
                        return ''
                    }
                    return matchingCategories[0][categoriesNameField]
                },
                headerFilter: 'input',
                title: 'Categories',
            },
        ],
        dataFiltered: function (filters, rows) {
            document.getElementById('search-count').innerHTML = rows.length
        },
        dataLoaded: function (data) {
            document.getElementById('total-count').innerHTML = data.length
        },
        downloadRowRange: 'all',
        footerElement:
            '<span>Showing <span id="search-count"></span> items from <span id="total-count"></span> total items</span>',
        height: '600px',
        layout: 'fitColumns',
        pagination: 'local',
        paginationSize: 25,
        selectable: false,
        tooltips: true,
    })

    keywordTable.setData(
        ajaxUrl +
            '?action=keywords_table_data&type=' +
            letterTypes['keyword'] +
            '&categories=0'
    )

    updateTableHeaders()

    document.getElementById('export-keywords').addEventListener('click', () => {
        keywordTable.download('csv', 'keywords.csv', {
            bom: true,
            delimiter: ';',
        })
    })
}

if (document.getElementById('datatable-categories')) {
    categoriesTable = new Tabulator('#datatable-categories', {
        columns: [
            {
                download: false,
                field: 'id',
                formatter: function (cell) {
                    const rowData = cell.getRow().getData()
                    const rowIndex = cell.getRow().getIndex()
                    return `
                    <ul class="list-unstyled mb-0">
                        <li>
                            <span onclick="addCategory('${letterTypes['keyword']}', 'edit', ${rowData.id}, '${rowData.name}', '${rowData.namecz}')" class="text-info is-link py-1">
                                Upravit
                            </span>
                        </li>
                        <li>
                            <span onclick="deleteKeyword(${rowData.id}, ${rowIndex}, categoriesTable)" class="text-danger is-link py-1">
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
                download: true,
                field: 'name',
                formatter: 'textarea',
                headerFilter: 'input',
                title: 'EN',
            },
            {
                download: true,
                field: 'namecz',
                formatter: 'textarea',
                headerFilter: 'input',
                title: 'CZ',
            },
        ],
        data: JSON.parse(document.getElementById('categories-data').innerHTML),
        dataFiltered: function (filters, rows) {
            document.getElementById('category-search-count').innerHTML =
                rows.length
        },
        dataLoaded: function (data) {
            document.getElementById('category-total-count').innerHTML =
                data.length
        },
        downloadRowRange: 'all',
        footerElement:
            '<span>Showing <span id="category-search-count"></span> items from <span id="category-total-count"></span> total items</span>',
        height: '600px',
        layout: 'fitColumns',
        pagination: 'local',
        paginationSize: 25,
        selectable: false,
        tooltips: true,
    })

    updateTableHeaders()

    document
        .getElementById('export-categories')
        .addEventListener('click', () => {
            categoriesTable.download('csv', 'keywords-categories.csv', {
                bom: true,
                delimiter: ';',
            })
        })
}
