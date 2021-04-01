/* global SlimSelect Swal ajaxUrl axios */

window.placeForm = function () {
    return {
        country: '',
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
                this.initSelect()
                return
            }

            this.country = data.country
            this.name = data.name
            this.latitude = data.latitude
            this.longitude = data.longitude
            this.note = data.note

            this.initSelect()
        },

        initSelect: function () {
            new SlimSelect({
                select: 'select',
            })
        },

        handleSubmit: function (event) {
            event.preventDefault()

            this.errors = []

            if (this.name.length === 0) {
                this.errors.push('Empty name')
            }

            if (this.country.length === 0) {
                this.errors.push('Empty country')
            }

            if (this.errors.length > 0) {
                return
            }

            document.getElementById('places-form').submit()
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
