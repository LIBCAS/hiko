/* global Vue axios ajaxUrl getLetterType getGeoCoord isString decodeHTML */

if (document.getElementById('places-form')) {
    new Vue({
        el: '#places-form',
        data: {
            country: {},
            lat: '',
            long: '',
            note: '',
            place: '',
            error: false,
            loading: true,
        },
        computed: {
            countries() {
                let results = []

                let countries = JSON.parse(
                    document.querySelector('#countries').innerHTML
                )

                let l = countries.length
                for (let index = 0; index < l; index++) {
                    results.push({
                        label: countries[index].name,
                        value: countries[index].name,
                    })
                }
                return results
            },
        },
        mounted: function () {
            let letterTypes = getLetterType()
            let url = new URL(window.location.href)
            let self = this

            if (isString(letterTypes)) {
                self.error = letterTypes
                return
            }

            this.placeType = letterTypes['placeType']

            if (url.searchParams.get('edit')) {
                this.getInitialData(url.searchParams.get('edit'), () => {
                    self.loading = false
                })
            } else {
                self.loading = false
            }
        },
        methods: {
            decodeHTML: function (str) {
                return decodeHTML(str)
            },

            getCoord: function () {
                let self = this

                getGeoCoord(function (latlng) {
                    let coord = latlng.value.split(',')
                    self.lat = coord[0]
                    self.long = coord[1]
                })
            },
            getInitialData: function (id, callback = null) {
                let self = this

                axios
                    .get(
                        ajaxUrl +
                            '?action=list_place_single&pods_id=' +
                            id +
                            '&type=' +
                            self.placeType
                    )
                    .then(function (response) {
                        self.place = response.data.name
                        self.country = {
                            value: response.data.country,
                            label: response.data.country,
                        }
                        self.note = response.data.note
                        self.lat = response.data.latitude
                        self.long = response.data.longitude
                    })
                    .catch(function () {
                        self.error = true
                    })
                    .then(callback)
            },
        },
    })
}
