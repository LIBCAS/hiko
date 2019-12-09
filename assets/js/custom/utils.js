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
    var filtered = data.filter(function(line) {
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
    let url = new URL(window.location.href)
    if (stringContains(url.pathname, 'blekastad')) {
        return {
            letterType: 'bl_letter',
            personType: 'bl_person',
            placeType: 'bl_place',
            path: 'blekastad',
            keyword: 'bl_keyword',
        }
    }

    if (stringContains(url.pathname, 'demo')) {
        return {
            letterType: 'demo_letter',
            personType: 'demo_person',
            placeType: 'demo_place',
            path: 'demo',
            keyword: 'demo_keyword',
        }
    }

    if (stringContains(url.pathname, 'tgm')) {
        return {
            letterType: 'tgm_letter',
            personType: 'tgm_person',
            placeType: 'tgm_place',
            path: 'tgm',
            keyword: 'tgm_keyword',
        }
    }

    return 'Neplatný typ dopisu'
}

function getGeoCoord(callback) {
    Swal.fire({
        buttonsStyling: false,
        cancelButtonClass: 'btn btn-secondary btn-lg ml-1',
        cancelButtonText: 'Zrušit',
        confirmButtonClass: 'btn btn-primary btn-lg mr-1',
        confirmButtonText: 'Vyhledat',
        input: 'text',
        showCancelButton: true,
        showLoaderOnConfirm: true,
        title: 'Zadejte název místa',
        type: 'question',
        allowOutsideClick: () => !Swal.isLoading(),
        inputValidator: value => {
            if (value.length < 2) {
                return 'Zadejte název místa'
            }
        },
        preConfirm: function(value) {
            return axios
                .get(ajaxUrl + '?action=get_geocities_latlng&query=' + value)
                .then(function(response) {
                    return response.data.data
                })
                .catch(function(error) {
                    Swal.showValidationMessage(
                        `Při vyhledávání došlo k chybě: ${error}`
                    )
                })
        },
    }).then(result => {
        if (result.value) {
            Swal.fire({
                buttonsStyling: false,
                cancelButtonClass: 'btn btn-secondary btn-lg ml-1',
                cancelButtonText: 'Zrušit',
                confirmButtonClass: 'btn btn-primary btn-lg mr-1',
                confirmButtonText: 'Potvrdit',
                input: 'select',
                inputOptions: geoDataToSelect(result.value),
                showCancelButton: true,
                title: 'Vyberte místo',
                type: 'question',
            }).then(result => {
                callback(result)
            })
        }
    })
}

function geoDataToSelect(geoData) {
    let output = {}
    for (let i = 0; i < geoData.length; i++) {
        let latlng = geoData[i].lat + ',' + geoData[i].lng
        output[latlng] =
            geoData[i].name +
            ' (' +
            geoData[i].adminName +
            ' – ' +
            geoData[i].country +
            ')'
    }
    return output
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
    }).then(result => {
        if (result.value) {
            axios
                .post(
                    ajaxUrl + '?action=delete_hiko_pod', {
                        ['pod_type']: podType,
                        ['pod_name']: podName,
                        ['id']: id,
                    }, {
                        headers: {
                            'Content-Type': 'application/json;charset=utf-8',
                        },
                    }
                )
                .then(function() {
                    Swal.fire({
                        title: 'Odstraněno.',
                        type: 'success',
                        buttonsStyling: false,
                        confirmButtonText: 'OK',
                        confirmButtonClass: 'btn btn-primary btn-lg',
                    })
                    callback()
                })
                .catch(function(error) {
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

function removeElFromArr(el, array) {
    return array.filter(function(value) {
        return value != el
    })
}

function getCustomSorting(columns) {
    let sorting = {}
    for (let i = 0; i < columns.length; i++) {
        sorting[columns[i]] = function(ascending) {
            return function(a, b) {
                if (a[columns[i]] == null) {
                    a = ''
                } else if (Array.isArray(a[columns[i]])) {
                    a = a[columns[i]].toString()
                } else {
                    a = a[columns[i]].toLowerCase()
                }

                if (b[columns[i]] == null) {
                    b = ''
                } else if (Array.isArray(b[columns[i]])) {
                    b = b[columns[i]].toString()
                } else {
                    b = b[columns[i]].toLowerCase()
                }

                if (ascending) {
                    return b.localeCompare(a)
                }
                return a.localeCompare(b)
            }
        }
    }

    return sorting
}

function getTimestampFromCustomFormat(customDate) {
    let d = new Date()
    if (customDate == '0/0/0') {
        d.setFullYear(1000, 12, 31)
        return d.getTime()
    }

    customDate = customDate.split('/')
    let year = customDate[0] == 0 ? 1000 : customDate[0]
    let month = customDate[1] == 0 ? 0 : customDate[1] - 1
    let day = customDate[2]
    d.setFullYear(year, month, day)
    return d.getTime()
}

function getTimestampFromDate(year, month, day) {
    year = year == 0 ? 1000 : year
    month = month == 0 ? 0 : month - 1

    let d = new Date()
    d.setFullYear(year, month, day)

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
