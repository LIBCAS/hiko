/* global SlimSelect Vue axios ajaxUrl homeUrl Swal */

if (document.getElementById('letter-form')) {
    new Vue({
        el: '#letter-add-form',
        data: {
            error: false,
            letterType: '',
            personType: '',
            placeType: '',
            path: '',
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
            persons: [],
            places: [],
            locations: [],
            collection: '',
            archive: '',
            signature: '',
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
        },
        mounted: function() {
            let url = new URL(window.location.href)
            let letterTypes = getLetterType()
            if (
                typeof letterTypes === 'string' ||
                letterTypes instanceof String
            ) {
                self.error = letterTypes
                return
            } else {
                this.letterType = letterTypes['letterType']
                this.personType = letterTypes['personType']
                this.placeType = letterTypes['placeType']
                this.path = letterTypes['path']
            }

            let edit = url.searchParams.get('edit')
            if (edit) {
                this.letterID = edit
                this.edit = true
                this.getInitialData()
            } else {
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

                for (let i = 0; i < this.author.length; i++) {
                    authors.push(getNameById(this.persons, this.author[i]))
                }
                for (let i = 0; i < this.recipient.length; i++) {
                    recipients.push(
                        getNameById(this.persons, this.recipient[i])
                    )
                }

                for (let i = 0; i < this.origin.length; i++) {
                    origin.push(getNameById(this.places, this.origin[i]))
                }

                for (let i = 0; i < this.destination.length; i++) {
                    destination.push(
                        getNameById(this.places, this.destination[i])
                    )
                }

                origin = origin.join('; ')
                destination = destination.join('; ')

                let date = this.day + '. ' + this.month + '. ' + this.year
                let from = authors.join('; ') + ' (' + origin + ')'
                let to = recipients + ' (' + destination + ')'

                this.title = date + ' ' + from + ' to ' + to
                return
            },

            getLocationData: function() {
                let self = this
                axios
                    .get(ajaxUrl + '?action=list_locations')
                    .then(function(response) {
                        self.locations = response.data.data
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
                            self.l_number = rd.l_number
                            self.year = rd.date_year == '0' ? '' : rd.date_year
                            self.month =
                                rd.date_month == '0' ? '' : rd.date_month
                            self.day = rd.date_day == '0' ? '' : rd.date_day
                            self.date_marked = rd.date_marked
                            self.date_uncertain = rd.date_uncertain
                            self.date_approximate = rd.date_approximate
                            self.date_is_range = rd.date_approximate
                            self.date_note = rd.date_note
                            self.range_year = rd.range_year
                            self.range_month = rd.range_month
                            self.range_day = rd.range_day
                            self.author = Object.keys(rd.l_author)
                            self.author_as_marked = rd.l_author_marked
                            self.author_inferred = rd.author_inferred
                            self.author_uncertain = rd.author_uncertain
                            self.author_note = rd.author_note
                            self.recipient = Object.keys(rd.recipient)
                            self.recipient_marked = rd.recipient_marked
                            self.recipient_inferred = rd.recipient_inferred
                            self.recipient_uncertain = rd.recipient_uncertain
                            self.recipient_notes = rd.recipient_notes
                            self.origin = Object.keys(rd.origin)
                            self.origin_marked = rd.origin_marked
                            self.origin_inferred = rd.origin_inferred
                            self.origin_uncertain = rd.origin_uncertain
                            self.origin_note = rd.origin_note
                            self.destination = Object.keys(rd.dest)
                            self.dest_marked = rd.dest_marked
                            self.dest_inferred = rd.dest_inferred
                            self.dest_uncertain = rd.dest_uncertain
                            self.dest_note = rd.dest_note
                            self.languages =
                                rd.languages.length === 0
                                    ? []
                                    : rd.languages.split(';')
                            self.keywords =
                                rd.keywords.length === 0
                                    ? [{ value: '' }]
                                    : self.parseKeywords(rd.keywords)
                            self.abstract = rd.abstract
                            self.incipit = rd.incipit
                            self.explicit = rd.explicit
                            self.mentioned = Object.keys(rd.people_mentioned)
                            self.people_mentioned_notes =
                                rd.people_mentioned_notes
                            self.notes_public = rd.notes_public
                            self.notes_private = rd.notes_private
                            self.rel_rec_name = rd.rel_rec_name
                            self.rel_rec_url = rd.rel_rec_url
                            self.ms_manifestation = rd.ms_manifestation
                            self.repository = rd.repository
                            self.title = rd.name
                            self.status = rd.status
                            self.collection = rd.collection
                            self.archive = rd.archive
                            self.signature = rd.signature
                        }
                    })
                    .catch(function() {
                        self.error = true
                    })
                    .then(function() {
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

            regenerateSelectData: function(event) {
                let type = event.target.dataset.source
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
                }
            },

            addSlimSelect: function() {
                addSlimSelect()
            },

            removeKeyword: function(kw) {
                let kwIndex = this.keywords.indexOf(kw)
                this.keywords = this.keywords.filter(function(item, index) {
                    return index !== kwIndex
                })
            },

            addNewKeyword: function() {
                this.keywords.push({ value: '' })
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
