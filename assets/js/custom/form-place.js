/* global Vue axios ajaxUrl getLetterType getGeoCoord isString */

if (document.getElementById('places-form')) {
    new Vue({
        el: '#places-form',
        data: {
            country: {},
            lat: '',
            long: '',
            note: '',
            place: '',
        },
        computed: {
            countries() {
                let results = []
                let countries = document.querySelector('#countries').innerHTML
                countries = JSON.parse(countries)
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
        mounted: function() {
            let letterTypes = getLetterType()
            if (isString(letterTypes)) {
                self.error = letterTypes
                return
            }

            this.placeType = letterTypes['placeType']

            let url = new URL(window.location.href)
            if (url.searchParams.get('edit')) {
                this.getInitialData(url.searchParams.get('edit'))
            }
        },
        methods: {
            getCoord: function() {
                let self = this
                getGeoCoord(function(latlng) {
                    let coord = latlng.value.split(',')
                    self.lat = coord[0]
                    self.long = coord[1]
                })
            },
            getInitialData: function(id) {
                let self = this
                axios
                    .get(
                        ajaxUrl +
                        '?action=list_place_single&pods_id=' +
                        id +
                        '&type=' +
                        self.placeType
                    )
                    .then(function(response) {
                        self.place = response.data.name
                        self.country = {
                            value: response.data.country,
                            label: response.data.country,
                        }
                        self.note = response.data.note
                        self.lat = response.data.latitude
                        self.long = response.data.longitude
                    })
                    .catch(function() {
                        self.error = true
                    })
            },
        },
    })
}
