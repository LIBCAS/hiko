/* global VueSelect  Vue axios ajaxUrl homeUrl Swal */

Vue.component('v-select', VueSelect.VueSelect)
Vue.component('multiselect', window.VueMultiselect.default)

if (document.getElementById('letter-form')) {
    new Vue({
        el: '#letter-add-form',
        data: {
            error: false,
            loading: true,
            letterType: '',
            personType: '',
            placeType: '',
            path: '',
            letter: {
                author: [],
                author_inferred: false,
                author_uncertain: false,
                author_note: '',
                recipient: [],
                recipient_inferred: false,
                recipient_uncertain: false,
                recipient_notes: '',
                mentioned: [],
                origin: [],
                origin_note: '',
                origin_marked: '',
                origin_inferred: false,
                origin_uncertain: false,
                destination: [],
                dest_marked: '',
                dest_inferred: false,
                dest_uncertain: false,
                dest_note: '',
                date_day: '',
                date_month: '',
                date_year: '',
                date_marked: '',
                date_uncertain: false,
                date_approximate: false,
                date_is_range: false,
                date_note: '',
                range_year: '',
                range_month: '',
                range_day: '',
                l_number: '',
                languages: [],
                keywords: [{ value: '' }],
                abstract: '',
                incipit: '',
                explicit: '',
                people_mentioned_notes: '',
                notes_public: '',
                notes_private: '',
                rel_rec_name: '',
                rel_rec_url: '',
                ms_manifestation: '',
                repository: '',
                status: 'draft',
                collection: '',
                archive: '',
                signature: '',
                location_note: '',
            },
            persons: [],
            places: [],
            locations: [],
            edit: false,
            letterID: null,
            title: '',
            location_note: '',
        },
        computed: {
            personsData() {
                let self = this
                let personsData = []
                self.persons.map(el => {
                    let label = `${el.name} (${el.birth_year}–${el.death_year})`
                    personsData.push({
                        label: label,
                        value: el.id,
                    })
                })
                return personsData
            },
            placesData() {
                let self = this
                let placesData = []
                self.places.map(el => {
                    placesData.push({
                        label: el.name,
                        value: el.id,
                    })
                })
                return placesData
            },
            languages() {
                let langs = []
                let langsJSON = document.querySelector('#languages').innerHTML
                langsJSON = JSON.parse(langsJSON)
                for (let property in langsJSON) {
                    let n = langsJSON[property].name.toLowerCase()
                    langs.push({
                        label: n,
                        value: n,
                    })
                }
                return langs
            },
            participantsMeta() {
                let authorsMeta = JSON.parse(JSON.stringify(this.letter.author)) // copy without vue getters and setters
                let recipientsMeta = JSON.parse(
                    JSON.stringify(this.letter.recipient)
                )

                let merged = []

                authorsMeta.forEach(item => {
                    merged.push(item)
                })

                recipientsMeta.forEach(item => {
                    merged.push(item)
                })

                return JSON.stringify(merged)
            },
            imgUrl: function() {
                return (
                    homeUrl +
                    '/' +
                    this.path +
                    '/letters-media/?l_type=' +
                    this.letterType +
                    '&letter=' +
                    this.letterID
                )
            },
            previewUrl: function() {
                return (
                    homeUrl +
                    '/letter-preview/?l_type=' +
                    this.letterType +
                    '&letter=' +
                    this.letterID
                )
            },
            repositories: function() {
                let self = this
                return self.locations.filter(function(loc) {
                    if (loc.type == 'repository') {
                        return true
                    }
                })
            },
            collections: function() {
                let self = this
                return self.locations.filter(function(loc) {
                    if (loc.type == 'collection') {
                        return true
                    }
                })
            },
            archives: function() {
                let self = this
                return self.locations.filter(function(loc) {
                    if (loc.type == 'archive') {
                        return true
                    }
                })
            },
            formVisible: function() {
                let self = this
                if (
                    self.error ||
                    typeof self.error === 'string' ||
                    self.loading
                ) {
                    return false
                }
                return true
            },
        },
        mounted: function() {
            let self = this
            let url = new URL(window.location.href)
            let letterTypes = getLetterType()
            if (
                typeof letterTypes === 'string' ||
                letterTypes instanceof String
            ) {
                self.error = letterTypes
                self.loading = false
                return
            } else {
                self.letterType = letterTypes['letterType']
                self.personType = letterTypes['personType']
                self.placeType = letterTypes['placeType']
                self.path = letterTypes['path']
            }

            let edit = url.searchParams.get('edit')
            if (edit) {
                self.letterID = edit
                self.edit = true
                self.getInitialData()
            } else {
                self.loading = false
            }

            this.persons = JSON.parse(
                document.querySelector('#people').innerHTML
            )
            this.places = JSON.parse(
                document.querySelector('#places').innerHTML
            )

            this.getLocationData()
        },
        methods: {
            getTitle: function() {
                let authors = []
                let recipients = []
                let origin = []
                let destination = []
                let letter = this.letter
                let self = this
                for (let i = 0; i < letter.author.length; i++) {
                    authors.push(getNameById(self.persons, letter.author[i]))
                }
                for (let i = 0; i < letter.recipient.length; i++) {
                    recipients.push(
                        getNameById(self.persons, letter.recipient[i])
                    )
                }

                for (let i = 0; i < letter.origin.length; i++) {
                    origin.push(getNameById(self.places, letter.origin[i]))
                }

                for (let i = 0; i < letter.destination.length; i++) {
                    destination.push(
                        getNameById(self.places, letter.destination[i])
                    )
                }

                origin = origin.join('; ')
                destination = destination.join('; ')

                let date =
                    letter.date_day +
                    '. ' +
                    letter.date_month +
                    '. ' +
                    letter.date_year
                let from = authors.join('; ') + ' (' + origin + ')'
                let to = recipients + ' (' + destination + ')'

                let result = date + ' ' + from + ' to ' + to
                self.title = result
                return result
            },

            getLocationData: function(callback) {
                let self = this
                axios
                    .get(ajaxUrl + '?action=list_locations')
                    .then(function(response) {
                        self.locations = response.data.data
                        if (callback) {
                            callback()
                        }
                    })
                    .catch(function(error) {
                        self.error = error
                    })
            },

            getInitialData: function() {
                let self = this

                let id = self.letterID
                axios
                    .get(
                        ajaxUrl +
                            '?action=list_public_letters_single&pods_id=' +
                            id +
                            '&l_type=' +
                            self.letterType
                    )
                    .then(function(response) {
                        if (response.data == '404') {
                            self.error = true
                        } else {
                            /*
                             * TODO
                             */
                            let rd = response.data
                            let authors = Object.keys(rd.l_author)
                            let recipients = Object.keys(rd.recipient)
                            self.letter = rd

                            self.$set(self.letter, 'recipient', []) // must set reactive data again
                            self.$set(self.letter, 'author', [])

                            self.letter.date_year =
                                rd.date_year == '0' ? '' : rd.date_year
                            self.letter.date_month =
                                rd.date_month == '0' ? '' : rd.date_month
                            self.letter.date_day =
                                rd.date_day == '0' ? '' : rd.date_day
                            self.letter.range_year =
                                rd.range_year == '0' ? '' : rd.range_year
                            self.letter.range_month =
                                rd.range_month == '0' ? '' : rd.range_month
                            self.letter.range_day =
                                rd.range_day == '0' ? '' : rd.range_day
                            /*
                            self.letter.author = self.getPersonMeta(
                                authors,
                                rd.authors_meta
                            )

                            self.letter.recipient = self.getPersonMeta(
                                recipients,
                                rd.authors_meta
                            )
                                */
                            self.letter.origin = Object.keys(rd.origin)
                            self.letter.destination = Object.keys(rd.dest)

                            self.letter.languages =
                                rd.languages.length === 0
                                    ? []
                                    : rd.languages.split(';')
                            self.letter.keywords =
                                rd.keywords.length === 0
                                    ? [{ value: '' }]
                                    : self.parseKeywords(rd.keywords)
                            self.letter.mentioned = Object.keys(
                                rd.people_mentioned
                            )
                            self.title = rd.name
                            self.location_note = rd.location_note
                        }
                    })
                    .catch(function() {
                        self.error = true
                    })
                    .then(function() {
                        self.loading = false
                    })
            },

            ajaxToData: function(action, targetData, postType, targetElement) {
                let self = this
                targetElement.classList.add('rotate')
                axios
                    .get(ajaxUrl + '?action=' + action + '&type=' + postType)
                    .then(function(response) {
                        self[targetData] = response.data
                    })
                    .catch(function(error) {
                        console.log(error)
                    })
                    .then(function() {
                        targetElement.classList.remove('rotate')
                    })
            },

            regenerateSelectData: function(type, event) {
                let self = this
                if (type == 'persons') {
                    self.ajaxToData(
                        'list_people_simple',
                        'persons',
                        self.personType,
                        event.target
                    )
                } else if (type == 'places') {
                    self.ajaxToData(
                        'list_places_simple',
                        'places',
                        self.placeType,
                        event.target
                    )
                } else if (type == 'locations') {
                    event.target.classList.add('rotate')
                    self.getLocationData(function() {
                        event.target.classList.remove('rotate')
                    })
                }
            },

            removeKeyword: function(kw) {
                let kwIndex = this.letter.keywords.indexOf(kw)
                this.letter.keywords = this.letter.keywords.filter(function(
                    item,
                    index
                ) {
                    return index !== kwIndex
                })
            },

            addNewKeyword: function() {
                this.letter.keywords.push({ value: '' })

                let index = this.letter.keywords.length
                setTimeout(function() {
                    let inputs = document.querySelectorAll('.keywords input')
                    let last = inputs[index - 1]
                    if (last) {
                        last.select()
                    }
                }, 50)
            },

            parseKeywords: function(keywords) {
                if (keywords.length === 0) {
                    return
                }
                let kwArr = keywords.split(';')

                let kwObj = []

                for (let i = 0; i < kwArr.length; i++) {
                    kwObj.push({ value: kwArr[i] })
                }

                return kwObj
            },

            removePersonMeta: function(personIndex, type) {
                this.letter[type] = this.letter[type].filter(function(
                    item,
                    index
                ) {
                    return index !== personIndex
                })
            },

            addPersonMeta: function(type) {
                let self = this
                self.letter[type].push({
                    id: null,
                    marked: '',
                    salutation: '',
                    key:
                        type +
                        Math.random()
                            .toString(36)
                            .substring(7),
                    // random key for forcing Vue to update list while removing PersonMeta
                })
            },

            getPersonMeta: function(ids, allMeta) {
                let metaJSON = JSON.parse(allMeta)
                let results = []

                for (let index = 0; index < ids.length; index++) {
                    let personID = ids[index][0]
                    let find = metaJSON.filter(obj => {
                        return obj.id === personID
                    })
                    results.push(find[0])
                }

                return results
            },
        },
    })
}

if (document.getElementById('places-form')) {
    new Vue({
        el: '#places-form',
        data: {
            place: '',
            country: {},
            note: '',
            lat: '',
            long: '',
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
            if (
                typeof letterTypes === 'string' ||
                letterTypes instanceof String
            ) {
                self.error = letterTypes
                return
            } else {
                this.placeType = letterTypes['placeType']
            }
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

if (document.getElementById('person-name')) {
    new Vue({
        el: '#person-name',
        data: {
            firstName: '',
            lastName: '',
            emlo: '',
            dob: '',
            dod: '',
            note: '',
            error: false,
            personType: '',
            nationality: '',
            profession: '',
            alternativeNames: [],
        },

        computed: {
            fullName: function() {
                let fullName
                if (this.firstName.length > 0) {
                    fullName =
                        this.capitalize(this.lastName).trim() +
                        ', ' +
                        this.capitalize(this.firstName).trim()
                } else {
                    fullName = this.capitalize(this.lastName).trim()
                }

                return fullName.trim()
            },
            personsFormValidated: function() {
                if (this.lastName == '' || this.fullName.length < 3) {
                    return false
                }
                return true
            },
        },

        mounted: function() {
            let letterTypes = getLetterType()
            if (
                typeof letterTypes === 'string' ||
                letterTypes instanceof String
            ) {
                self.error = letterTypes
                return
            } else {
                this.personType = letterTypes['personType']
            }
            let url = new URL(window.location.href)
            if (url.searchParams.get('edit')) {
                this.getInitialData(url.searchParams.get('edit'))
            }
        },

        methods: {
            capitalize: function(str) {
                return str.charAt(0).toUpperCase() + str.slice(1)
            },

            getInitialData: function(id) {
                let self = this
                axios
                    .get(
                        ajaxUrl +
                            '?action=list_people_single&pods_id=' +
                            id +
                            '&type=' +
                            self.personType
                    )
                    .then(function(response) {
                        if (response.data == '404') {
                            self.error = true
                        } else {
                            self.firstName = response.data.forename
                            self.lastName = response.data.surname
                            self.emlo = response.data.emlo
                            self.dob = response.data.birth_year
                            self.dod = response.data.death_year
                            self.note = response.data.note
                            self.nationality = response.data.nationality
                            self.profession = response.data.profession
                            self.alternativeNames = response.data.names
                        }
                    })
                    .catch(function(error) {
                        self.error = true
                        console.log(error)
                    })
            },
        },
    })
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
        }
    } else if (stringContains(url.pathname, 'demo')) {
        return {
            letterType: 'demo_letter',
            personType: 'demo_person',
            placeType: 'demo_place',
            path: 'demo',
        }
    } else {
        return 'Neplatný typ dopisu'
    }
}

function getGeoCoord(callback) {
    Swal.fire({
        title: 'Zadejte název místa',
        type: 'question',
        input: 'text',
        inputValidator: value => {
            if (value.length < 2) {
                return 'Zadejte název místa'
            }
        },
        buttonsStyling: false,
        showCancelButton: true,
        confirmButtonText: 'Vyhledat',
        cancelButtonText: 'Zrušit',
        confirmButtonClass: 'btn btn-primary btn-lg mr-1',
        cancelButtonClass: 'btn btn-secondary btn-lg ml-1',
        showLoaderOnConfirm: true,
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
        allowOutsideClick: () => !Swal.isLoading(),
    }).then(result => {
        if (result.value) {
            Swal.fire({
                title: 'Vyberte místo',
                type: 'question',
                buttonsStyling: false,
                showCancelButton: true,
                confirmButtonText: 'Potvrdit',
                cancelButtonText: 'Zrušit',
                confirmButtonClass: 'btn btn-primary btn-lg mr-1',
                cancelButtonClass: 'btn btn-secondary btn-lg ml-1',
                input: 'select',
                inputOptions: geoDataToSelect(result.value),
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
