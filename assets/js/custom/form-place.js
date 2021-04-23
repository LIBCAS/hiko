/* global Tagify Swal ajaxUrl axios normalize */

window.placeForm = function () {
    return {
        name: '',
        latitude: '',
        longitude: '',
        note: '',
        errors: [],

        fetch: function () {
            const data = JSON.parse(
                document.getElementById('place-data').innerHTML
            )

            if (data.length === 0) {
                this.initTagify()
                return
            }

            this.name = data.name
            this.latitude = data.latitude
            this.longitude = data.longitude
            this.note = data.note

            this.initTagify()
        },

        initTagify: function () {
            const countries = JSON.parse(
                document.getElementById('countries').innerHTML
            )

            const t = new Tagify(document.getElementById('country'), {
                enforceWhitelist: true,
                whitelist: countries,
                mode: 'select',
                dropdown: {
                    enabled: 0,
                    highlightFirst: true,
                    maxItems: Infinity,
                    placeAbove: false,
                    searchKeys: ['value'],
                },
            })

            // default search not working in single select mode
            t.on('input', (e) => {
                const search = normalize(e.detail.value)
                const results = []

                t.settings.whitelist.length = 0 // reset the whitelist
                t.loading(true).dropdown.hide.call(t)

                countries.map((option) => {
                    if (normalize(option.value).includes(search)) {
                        results.push({
                            value: option.value,
                        })
                    }
                })

                t.settings.whitelist = results
                t.loading(false).dropdown.show.call(t, search) // render the suggestions dropdown
            })
        },

        getCoordinates: function () {
            const context = this
            Swal.fire({
                buttonsStyling: false,
                cancelButtonClass: 'btn btn-link text-danger btn-sm ml-1',
                cancelButtonText: 'Cancel',
                confirmButtonClass: 'btn btn-primary btn-sm mr-1',
                confirmButtonText: 'Search',
                input: 'text',
                inputValue: context.name,
                showCancelButton: true,
                showLoaderOnConfirm: true,
                title: 'Place name',
                type: 'question',
                allowOutsideClick: () => !Swal.isLoading(),
                inputValidator: (value) => {
                    if (value.length < 2) {
                        return 'Place name'
                    }
                },
                preConfirm: (value) => {
                    return axios
                        .get(
                            ajaxUrl +
                                '?action=get_geocities_latlng&query=' +
                                value
                        )
                        .then((response) => {
                            return response.data.data
                        })
                        .catch((error) => {
                            Swal.showValidationMessage(
                                `Při vyhledávání došlo k chybě: ${error}`
                            )
                        })
                },
            }).then((result) => {
                if (!result.value) {
                    return
                }

                Swal.fire({
                    buttonsStyling: false,
                    cancelButtonClass: 'btn btn-link text-danger btn-sm ml-1',
                    cancelButtonText: 'Zrušit',
                    confirmButtonClass: 'btn btn-primary btn-sm mr-1',
                    confirmButtonText: 'Potvrdit',
                    input: 'select',
                    inputOptions: context.formatSearchResults(result.value),
                    showCancelButton: true,
                    title: 'Vyberte místo',
                    type: 'question',
                }).then((result) => {
                    if (!result.value) {
                        return
                    }

                    const latlng = result.value.split(',')

                    context.latitude = latlng[0]
                    context.longitude = latlng[1]
                })
            })
        },

        formatSearchResults(geoData) {
            let output = {}

            for (let i = 0; i < geoData.length; i++) {
                const latlng = geoData[i].lat + ',' + geoData[i].lng

                output[
                    latlng
                ] = `${geoData[i].name} (${geoData[i].adminName} – ${geoData[i].country})`
            }

            return output
        },
    }
}
