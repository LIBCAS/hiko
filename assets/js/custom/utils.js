/* global Swal axios ajaxUrl */

function errorInfoSwal(error, title = 'Při ukládání došlo k chybě.') {
    return {
        title: title,
        text: error,
        type: 'error',
        buttonsStyling: false,
        confirmButtonText: 'OK',
        confirmButtonClass: 'btn btn-primary btn-lg',
    }
}

function getNameById(data, id) {
    const filtered = data.filter((line) => {
        return line.id == id
    })

    if (filtered.length == 0) {
        return false
    }

    return filtered[0].name
}

function stringContains(str, substr) {
    return str.indexOf(substr) !== -1
}

function getLetterType() {
    const datatypes = document.getElementById('datatype')

    if (datatypes) {
        return JSON.parse(document.getElementById('datatype').innerHTML)
    }
}

function getObjectValues(obj) {
    let result = []
    let i
    let l = obj.length
    for (i = 0; i < l; i++) {
        result.push(obj[i].value)
    }
    return result
}

function arrayToSingleObject(data) {
    let result = {}

    for (let i = 0; i < data.length; i++) {
        result[Object.keys(data[i])] = Object.values(data[i])[0]
    }

    return result
}

function removeItemAjax(id, podType, podName, callback) {
    Swal.fire({
        title: 'Opravdu chcete smazat tuto položku?',
        type: 'warning',
        buttonsStyling: false,
        showCancelButton: true,
        confirmButtonText: 'Ano!',
        cancelButtonText: 'Zrušit',
        confirmButtonClass: 'btn btn-primary btn-lg mr-1',
        cancelButtonClass: 'btn btn-secondary btn-lg ml-1',
    }).then((result) => {
        if (result.value) {
            axios
                .post(
                    ajaxUrl + '?action=delete_hiko_pod',
                    {
                        ['pod_type']: podType,
                        ['pod_name']: podName,
                        ['id']: id,
                    },
                    {
                        headers: {
                            'Content-Type': 'application/json;charset=utf-8',
                        },
                    }
                )
                .then(function () {
                    Swal.fire({
                        title: 'Odstraněno.',
                        type: 'success',
                        buttonsStyling: false,
                        confirmButtonText: 'OK',
                        confirmButtonClass: 'btn btn-primary btn-lg',
                    })
                    callback()
                })
                .catch(function (error) {
                    Swal.fire({
                        title: 'Při odstraňování došlo k chybě.',
                        text: error,
                        type: 'error',
                        buttonsStyling: false,
                        confirmButtonText: 'OK',
                        confirmButtonClass: 'btn btn-primary btn-lg',
                    })
                })
        }
    })
}

function getTimestampFromDate(year, month, day) {
    let d = new Date()
    d.setFullYear(year ? year : 0, month ? month - 1 : 0, day ? day : 1)
    return d.getTime()
}

function decodeHTML(str) {
    let txt = document.createElement('textarea')
    txt.innerHTML = str
    return txt.value
}

function isString(data) {
    if (typeof data === 'string' || data instanceof String) {
        return true
    }
    return false
}

function updateTableHeaders() {
    document.querySelectorAll('.tabulator-header-filter').forEach((item) => {
        item.querySelector('input').classList.add(
            'form-control',
            'form-control-sm'
        )
    })
}

function arrayToList(arr) {
    if (!Array.isArray(arr)) {
        return arr
    }

    let list = ''

    arr.forEach((item) => {
        list += `<li>${item}</li>`
    })

    return `<ul class="list-unstyled mb-0">${list}</ul>`
}
