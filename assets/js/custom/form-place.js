/* global SlimSelect */

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
    }
}
