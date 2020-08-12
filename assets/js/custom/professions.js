/* global Tabulator updateTableHeaders getLetterType removeItemAjax axios Swal ajaxUrl */

const professionsSwal = {
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
        title: 'Profese byla úspěšně upravena',
        type: 'success',
        buttonsStyling: false,
        confirmButtonText: 'OK',
        confirmButtonClass: 'btn btn-primary btn-lg',
    },
}

const letterTypes = getLetterType()

var table

function deleteProfession(id, index) {
    removeItemAjax(id, 'profession', letterTypes['path'], () => {
        table.deleteRow(index)
    })
}

function getProfessionForm(nameEn, nameCs, palladio) {
    palladio = palladio ? 'checked="checked"' : ''

    return `
    <div class="form-group">
    <label for="nameen">EN</label>
    <input value="${nameEn}" id="nameen" class="form-control" pattern=".{2,255}" required title="2 to 255 characters">
    </div>
    <div class="form-group">
    <label for="namecz">CZ</label>
    <input value="${nameCs}" id="namecz" class="form-control" pattern=".{2,255}" required title="2 to 255 characters">
    </div>
    <div class="form-check">
    <input type="checkbox" class="form-check-input" id="palladio" ${palladio} autocomplete="off">
    <label class="form-check-label" id="palladio" for="palladio">Palladio</label>
    </div>
    `
}

function addProfession(
    type,
    action,
    id,
    oldProfession = '',
    oldProfessionCZ = '',
    oldPalladio = false
) {
    let swalConfig = professionsSwal.confirmSave
    swalConfig.title = id ? 'Upravit profesi' : 'Nová profese '
    swalConfig.allowOutsideClick = () => !Swal.isLoading()
    swalConfig.html = getProfessionForm(
        oldProfession,
        oldProfessionCZ,
        oldPalladio
    )
    swalConfig.focusConfirm = false
    swalConfig.preConfirm = () => {
        let nameen = document.getElementById('nameen').value
        let namecz = document.getElementById('namecz').value
        let palladio = document.getElementById('palladio').checked

        if (nameen.length < 2) {
            return Swal.showValidationMessage(
                'Zadané hodnoty nejsou zadané v požadovaném formátu (2-255 znaků)'
            )
        }

        return axios
            .post(
                ajaxUrl + '?action=insert_profession',
                {
                    ['type']: type,
                    ['nameen']: nameen,
                    ['namecz']: namecz,
                    ['palladio']: palladio,
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
                        '?action=professions_table_data&type=' +
                        letterTypes['profession']
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
            Swal.fire(professionsSwal.saveSuccess)
        }
    })
}

if (document.getElementById('datatable-profession')) {
    table = new Tabulator('#datatable-profession', {
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
                            <span onclick="addProfession('${letterTypes['profession']}', 'edit', ${rowData.id}, '${rowData.name}', '${rowData.namecz}', ${rowData.palladio})" class="text-info is-link py-1">
                                Upravit
                            </span>
                        </li>
                        <li>
                            <span onclick="deleteProfession(${rowData.id}, ${rowIndex})" class="text-danger is-link py-1">
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
        groupBy: 'palladio',
        groupHeader: function (value, count) {
            value = value ? 'Palladio' : 'Profession'

            return `
            ${value} <span class="">${count} items</span>
            `
        },
        height: '600px',
        layout: 'fitColumns',
        pagination: 'local',
        paginationSize: 25,
        selectable: false,
    })

    table.setData(
        ajaxUrl +
            '?action=professions_table_data&type=' +
            letterTypes['profession']
    )

    updateTableHeaders()
}
