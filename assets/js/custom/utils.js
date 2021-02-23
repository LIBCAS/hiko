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
    var filtered = data.filter(function (line) {
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
            profession: 'bl_profession',
            defaultLanguage: 'en',
        }
    }

    if (stringContains(url.pathname, 'demo')) {
        return {
            letterType: 'demo_letter',
            personType: 'demo_person',
            placeType: 'demo_place',
            path: 'demo',
            keyword: 'demo_keyword',
            profession: 'demo_profession',
            defaultLanguage: 'en',
        }
    }

    if (stringContains(url.pathname, 'tgm')) {
        return {
            letterType: 'tgm_letter',
            personType: 'tgm_person',
            placeType: 'tgm_place',
            path: 'tgm',
            keyword: 'tgm_keyword',
            profession: 'tgm_profession',
            defaultLanguage: 'en',
        }
    }

    if (stringContains(url.pathname, 'pol')) {
        return {
            letterType: 'pol_letter',
            personType: 'pol_person',
            placeType: 'pol_place',
            path: 'pol',
            keyword: 'pol_keyword',
            profession: 'pol_profession',
            defaultLanguage: 'en',
        }
    }

    if (stringContains(url.pathname, 'musil')) {
        return {
            letterType: 'musil_letter',
            personType: 'musil_person',
            placeType: 'musil_place',
            path: 'musil',
            keyword: 'musil_keyword',
            profession: 'musil_profession',
            defaultLanguage: 'cs',
        }
    }

    if (stringContains(url.pathname, 'sachs')) {
        return {
            letterType: 'sachs_letter',
            personType: 'sachs_person',
            placeType: 'sachs_place',
            path: 'sachs',
            keyword: 'sachs_keyword',
            profession: 'sachs_profession',
            defaultLanguage: 'en',
        }
    }

    if (stringContains(url.pathname, 'marci')) {
        return {
            letterType: 'marci_letter',
            personType: 'marci_person',
            placeType: 'marci_place',
            path: 'marci',
            keyword: 'marci_keyword',
            profession: 'marci_profession',
            defaultLanguage: 'en',
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
        inputValidator: (value) => {
            if (value.length < 2) {
                return 'Zadejte název místa'
            }
        },
        preConfirm: function (value) {
            return axios
                .get(ajaxUrl + '?action=get_geocities_latlng&query=' + value)
                .then(function (response) {
                    return response.data.data
                })
                .catch(function (error) {
                    Swal.showValidationMessage(
                        `Při vyhledávání došlo k chybě: ${error}`
                    )
                })
        },
    }).then((result) => {
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
            }).then((result) => {
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
