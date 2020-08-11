/* global Tabulator getLetterType removeItemAjax axios Swal ajaxUrl */

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

function addKeyword(type, action, id, oldKeyword = '', oldKeywordCZ = '') {
    let swalConfig = keywordsSwal.confirmSave

    swalConfig.title = (id ? 'Upravit' : 'Nové ') + ' klíčové slovo'
    swalConfig.allowOutsideClick = () => !Swal.isLoading()
    swalConfig.html = getKeywordForm(oldKeyword, oldKeywordCZ)
    swalConfig.focusConfirm = false

    swalConfig.preConfirm = () => {
        let nameen = document.getElementById('nameen').value
        let namecz = document.getElementById('namecz').value

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

function getKeywordForm(en, cs) {
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
    table = new Tabulator('#datatable-keywords', {
        columns: [
            {
                field: 'name',
                headerFilter: 'input',
                title: 'EN',
            },
            {
                field: 'namecz',
                headerFilter: 'input',
                title: 'CZ',
            },
            {
                field: 'id',
                formatter: function (cell) {
                    const rowData = cell.getRow().getData()
                    const rowIndex = cell.getRow().getIndex()
                    return `
                    <ul class="list-unstyled mb-0">
                        <li>
                            <span onclick="addKeyword('${letterTypes['keyword']}', 'edit', ${rowData.id}, '${rowData.name}', '${rowData.namecz}')" class="text-info is-link py-1">
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
            },
        ],
        height: '600px',
        layout: 'fitColumns',
        pagination: 'local',
        paginationSize: 25,
    })

    table.setData(
        ajaxUrl + '?action=keywords_table_data&type=' + letterTypes['keyword']
    )

    document.querySelectorAll('.tabulator-header-filter').forEach((item) => {
        item.querySelector('input').classList.add(
            'form-control',
            'form-control-sm'
        )
    })
}
