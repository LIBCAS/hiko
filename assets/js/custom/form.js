/* global SlimSelect Vue axios ajaxUrl homeUrl Swal */

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
                author_as_marked: '',
                author_inferred: false,
                author_uncertain: false,
                author_note: '',
                recipient: [],
                recipient_marked: '',
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
                day: '',
                month: '',
                year: '',
                date_marked: '',
                date_uncertain: false,
                date_approximate: false,
                date_is_range: false,
                date_note: '',
                range_year: '',
                range_month: '',
                range_day: '',
                title: '',
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
            },
            persons: [],
            places: [],
            locations: [],
            edit: false,
            letterID: null,
        },
        computed: {
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
                addSlimSelect()
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

                let date = letter.day + '. ' + letter.month + '. ' + letter.year
                let from = authors.join('; ') + ' (' + origin + ')'
                let to = recipients + ' (' + destination + ')'

                letter.title = date + ' ' + from + ' to ' + to
                return
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
                            let rd = response.data
                            self.letter = rd
                            self.letter.year =
                                rd.date_year == '0' ? '' : rd.date_year
                            self.letter.month =
                                rd.date_month == '0' ? '' : rd.date_month
                            self.letter.day =
                                rd.date_day == '0' ? '' : rd.date_day
                            self.letter.author = Object.keys(rd.l_author)
                            self.letter.author_as_marked = rd.l_author_marked
                            self.letter.recipient = Object.keys(rd.recipient)
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
                            self.letter.title = rd.name
                        }
                    })
                    .catch(function() {
                        self.error = true
                    })
                    .then(function() {
                        self.loading = false
                        addSlimSelect()
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

            addSlimSelect: function() {
                addSlimSelect()
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
        },
    })
}

if (document.getElementById('places-form')) {
    new Vue({
        el: '#places-form',
        data: {
            place: '',
            country: '',
            note: '',
            lat: '',
            long: '',
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
                addSlimSelect()
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
                        self.country = response.data.country
                        self.note = response.data.note
                        self.lat = response.data.latitude
                        self.long = response.data.longitude
                    })
                    .catch(function() {
                        self.error = true
                    })
                    .then(function() {
                        addSlimSelect()
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

function addSlimSelect() {
    Array.prototype.forEach.call(
        document.querySelectorAll('.slim-select'),
        function(selected) {
            if (selected.id) {
                new SlimSelect({
                    select: '#' + selected.id,
                })
            }
        }
    )
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
            if (value.length < 3) {
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
