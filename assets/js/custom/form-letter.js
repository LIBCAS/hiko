/* global Vue axios ajaxUrl homeUrl getLetterType getObjectValues getNameById arrayToSingleObject decodeHTML isString */

if (document.getElementById('letter-form')) {
    new Vue({
        el: '#letter-add-form',
        data: {
            letter: {
                abstract: '',
                archive: '',
                author: [],
                author_inferred: false,
                author_note: '',
                author_uncertain: false,
                collection: '',
                copy: {},
                date_approximate: false,
                date_day: '',
                date_inferred: false,
                date_is_range: false,
                date_marked: '',
                date_month: '',
                date_note: '',
                date_uncertain: false,
                date_year: '',
                dest_inferred: false,
                dest_note: '',
                dest_uncertain: false,
                destination: [],
                document_type: {},
                explicit: '',
                incipit: '',
                keywords: [],
                l_number: '',
                languages: [],
                location_note: '',
                mentioned: [],
                ms_manifestation: {},
                manifestation_notes: '',
                notes_private: '',
                notes_public: '',
                origin: [],
                origin_inferred: false,
                origin_note: '',
                origin_uncertain: false,
                people_mentioned_notes: '',
                preservation: {},
                range_day: '',
                range_month: '',
                range_year: '',
                recipient: [],
                recipient_inferred: false,
                recipient_notes: '',
                recipient_uncertain: false,
                related_resources: [{ link: '', title: '' }],
                repository: '',
                signature: '',
                status: 'draft',
            },
            edit: false,
            error: false,
            formChanged: false,
            keywordType: '',
            keywords: [],
            letterID: null,
            letterType: '',
            loading: true,
            locations: [],
            path: '',
            personType: '',
            persons: [],
            placeType: '',
            places: [],
            title: '',
            manifestations: [
                { label: 'Extract', value: 'E' },
                { label: 'MS Copy', value: 'S' },
                { label: 'MS Draft', value: 'D' },
                { label: 'MS Letter', value: 'ALS' },
                { label: 'Other', value: 'O' },
            ],
            documentTypes: [
                { label: 'Letter', value: 'letter' },
                { label: 'Picture postcard', value: 'picture postcard' },
                { label: 'Postcard', value: 'postcard' },
                { label: 'Telegram', value: 'telegram' },
            ],
            preservation: [
                { label: 'carbon copy', value: 'carbon copy' },
                { label: 'copy', value: 'copy' },
                { label: 'draft', value: 'draft' },
                { label: 'original', value: 'original' },
                { label: 'photocopy', value: 'photocopy' },
            ],
            copy: [
                { label: 'handwritten', value: 'handwritten' },
                { label: 'typewritten', value: 'typewritten' },
            ],
        },
        computed: {
            documentTypesData() {
                let docType = this.letter.document_type
                let preservation = this.letter.preservation
                let docCopy = this.letter.copy
                let data = []

                data.push({
                    type: docType !== null && docType.hasOwnProperty('value') ?
                        docType.value :
                        '',
                })

                data.push({
                    preservation: preservation !== null &&
                        preservation.hasOwnProperty('value') ?
                        preservation.value :
                        '',
                })

                data.push({
                    copy: docCopy !== null && docCopy.hasOwnProperty('value') ?
                        docCopy.value :
                        '',
                })

                return JSON.stringify(data)
            },

            personsData() {
                let self = this
                let personsData = []
                self.persons.map(el => {
                    let label = decodeHTML(el.name)
                    if (el.type != 'institution') {
                        label += ` (${el.birth_year}`

                        if (el.death_year != 0) {
                            label += `–${el.death_year})`
                        } else {
                            label += ')'
                        }
                    }

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

            resources() {
                let resources = JSON.parse(
                    JSON.stringify(this.letter.related_resources)
                )

                resources = resources.filter(el => {
                    return el.link && el.title
                })

                return JSON.stringify(resources)
            },

            participantsMeta() {
                let authorsMeta = JSON.parse(JSON.stringify(this.letter.author)) // copy without vue getters and setters
                let recipientsMeta = JSON.parse(
                    JSON.stringify(this.letter.recipient)
                )

                let merged = []

                authorsMeta.forEach(item => {
                    item = JSON.parse(JSON.stringify(item))
                    item.id = item.id.value
                    merged.push(item)
                })

                recipientsMeta.forEach(item => {
                    item = JSON.parse(JSON.stringify(item))
                    item.id = item.id.value
                    merged.push(item)
                })

                return JSON.stringify(merged)
            },

            placesMeta: function() {
                let origins = JSON.parse(JSON.stringify(this.letter.origin))
                let destinations = JSON.parse(
                    JSON.stringify(this.letter.destination)
                )

                let merged = []

                origins.forEach(item => {
                    item = JSON.parse(JSON.stringify(item))
                    item.id = item.id.value
                    merged.push(item)
                })

                destinations.forEach(item => {
                    item = JSON.parse(JSON.stringify(item))
                    item.id = item.id.value
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
            if (isString(letterTypes)) {
                self.error = letterTypes
                self.loading = false
                return
            }

            self.letterType = letterTypes['letterType']
            self.personType = letterTypes['personType']
            self.placeType = letterTypes['placeType']
            self.path = letterTypes['path']
            self.keywordType = letterTypes['keyword']

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

            this.getKeywords()

            this.getLocationData()
        },

        created() {
            window.addEventListener('beforeunload', e => {
                if (location.hostname !== 'localhost' && !this.formChanged) {
                    e.preventDefault()
                }
            })
        },

        methods: {
            validateForm(e) {
                this.formChanged = true
                this.$validator.validate().then(valid => {
                    if (!valid) {
                        e.preventDefault()
                    }
                })
            },

            getObjectValues: function(o) {
                return getObjectValues(o)
            },

            getTitle: function() {
                let self = this
                let letter = self.letter
                let personMeta = JSON.parse(JSON.stringify(self.persons))
                let placeMeta = JSON.parse(JSON.stringify(self.places))
                let authors = []
                let recipients = []
                let origin = []
                let destination = []
                let day = letter.date_day != '' ? letter.date_day : '?'
                let month = letter.date_month != '' ? letter.date_month : '?'
                let year = letter.date_year != '' ? letter.date_year : '?'

                for (let i = 0; i < letter.author.length; i++) {
                    let id = JSON.parse(JSON.stringify(letter.author[i])).id
                        .value
                    authors.push(getNameById(personMeta, id))
                }

                for (let i = 0; i < letter.recipient.length; i++) {
                    let id = JSON.parse(JSON.stringify(letter.recipient[i])).id
                        .value
                    recipients.push(getNameById(personMeta, id))
                }

                for (let i = 0; i < letter.origin.length; i++) {
                    let id = JSON.parse(JSON.stringify(letter.origin[i])).id
                        .value
                    origin.push(getNameById(placeMeta, id))
                }

                for (let i = 0; i < letter.destination.length; i++) {
                    let id = JSON.parse(JSON.stringify(letter.destination[i]))
                        .id.value
                    destination.push(getNameById(placeMeta, id))
                }

                authors = authors.join('; ')
                recipients = recipients.join('; ')
                origin = origin.join('; ')
                destination = destination.join('; ')

                let date = `${day}. ${month}. ${year}`

                let from = `${authors} (${origin})`
                let to = `${recipients} (${destination})`

                return `${date} ${from} to ${to}`
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
                            return
                        }
                        let rd = response.data

                        let authors = JSON.parse(JSON.stringify(rd.l_author))
                        authors = Object.keys(authors)

                        let recipients = JSON.parse(
                            JSON.stringify(rd.recipient)
                        )
                        recipients = Object.keys(recipients)

                        let origin = rd.origin
                        origin = Object.keys(origin)

                        let destination = rd.dest
                        destination = Object.keys(destination)

                        let mentioned = rd.people_mentioned
                        let manifestation = rd.ms_manifestation
                        let languages = rd.languages
                        let keywords = rd.keywords
                        let documentTypes = rd.document_type

                        self.letter = rd

                        self.$set(self.letter, 'languages', []) // must set reactive data again
                        self.$set(self.letter, 'keywords', [])
                        self.$set(self.letter, 'mentioned', [])

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

                        if (manifestation != '') {
                            manifestation = self.manifestations.find(
                                man => man.value === manifestation
                            )
                            self.$set(
                                self.letter,
                                'ms_manifestation',
                                manifestation
                            )
                        }

                        if (documentTypes === null || documentTypes == '') {
                            self.$set(self.letter, 'document_type', {})
                            self.$set(self.letter, 'preservation', {})
                            self.$set(self.letter, 'copy', {})
                        } else {
                            let documentTypesData = arrayToSingleObject(
                                JSON.parse(documentTypes)
                            )
                            self.$set(self.letter, 'document_type', {
                                label: documentTypesData.type,
                                value: documentTypesData.type,
                            })
                            self.$set(self.letter, 'preservation', {
                                label: documentTypesData.preservation,
                                value: documentTypesData.preservation,
                            })
                            self.$set(self.letter, 'copy', {
                                label: documentTypesData.copy,
                                value: documentTypesData.copy,
                            })
                        }

                        self.$set(
                            self.letter,
                            'author',
                            self.getPersonMeta(authors, rd.authors_meta)
                        )

                        self.$set(
                            self.letter,
                            'recipient',
                            self.getPersonMeta(recipients, rd.authors_meta)
                        )

                        self.$set(
                            self.letter,
                            'origin',
                            self.getPlaceMeta(origin, rd.places_meta)
                        )

                        self.$set(
                            self.letter,
                            'destination',
                            self.getPlaceMeta(destination, rd.places_meta)
                        )

                        if (languages != '') {
                            languages = languages.split(';')
                            for (
                                let index = 0; index < languages.length; index++
                            ) {
                                self.letter.languages.push({
                                    label: languages[index],
                                    value: languages[index],
                                })
                            }
                        }

                        self.letter.related_resources =
                            rd.related_resources === null ||
                            rd.related_resources.length === 0 ?
                            [{}] :
                            self.parseResources(rd.related_resources)

                        if (!Array.isArray(mentioned)) {
                            for (var key in mentioned) {
                                self.letter.mentioned.push({
                                    label: mentioned[key],
                                    value: key,
                                })
                            }
                        }

                        if (!Array.isArray(keywords)) {
                            for (var kw in keywords) {
                                self.letter.keywords.push({
                                    label: keywords[kw],
                                    value: kw,
                                })
                            }
                        }

                        self.title = rd.name
                    })
                    .catch(function(error) {
                        console.log(error)
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

            regenerateKeywords: function(event) {
                let self = this
                event.target.classList.add('rotate')
                self.getKeywords(() => {
                    event.target.classList.remove('rotate')
                })
            },

            getKeywords: function(callback = null) {
                let self = this
                axios
                    .get(
                        ajaxUrl +
                        '?action=keywords_table_data&type=' +
                        self.keywordType
                    )
                    .then(function(response) {
                        let keywords = response.data
                        let result = []

                        keywords.map(kw => {
                            result.push({
                                label: kw.name,
                                value: kw.id,
                            })
                        })
                        self.keywords = result
                    })
                    .catch(function(error) {
                        console.log(error)
                    })
                    .then(callback)
            },

            addNewResource: function() {
                this.letter.related_resources.push({ link: '', title: '' })
            },

            parseResources: function(resources) {
                if (resources.length === 0) {
                    return
                }

                resources = JSON.parse(resources)

                let result = []

                for (let i = 0; i < resources.length; i++) {
                    result.push({
                        link: resources[i].link,
                        title: resources[i].title,
                    })
                }

                return result
            },

            removeObjectMeta: function(personIndex, type) {
                this.letter[type] = this.letter[type].filter(function(
                    item,
                    index
                ) {
                    return index !== personIndex
                })
            },

            addPlaceMeta: function(type) {
                this.letter[type].push({
                    id: {},
                    marked: '',
                    key: type +
                        Math.random()
                        .toString(36)
                        .substring(7),
                    // random key for forcing Vue to update list while removing PlaceMeta
                })
            },

            addPersonMeta: function(type) {
                let self = this
                self.letter[type].push({
                    id: {},
                    marked: '',
                    salutation: '',
                    key: type +
                        Math.random()
                        .toString(36)
                        .substring(7),
                    // random key for forcing Vue to update list while removing PersonMeta
                })
            },

            getPlaceMeta: function(ids, allMeta) {
                if (ids.length == 0) {
                    return []
                }

                let self = this
                let result = []
                allMeta = JSON.parse(JSON.stringify(allMeta))

                let l = ids.length
                for (let index = 0; index < l; index++) {
                    let placesObj = self.placesData.find(
                        place => place.value === ids[index]
                    )
                    let placeData = allMeta.find(m => m.id === ids[index])

                    let place = {
                        id: JSON.parse(JSON.stringify(placesObj)),
                    }

                    if (placeData.hasOwnProperty('marked')) {
                        place.marked = placeData.marked
                    } else {
                        place.marked = ''
                    }

                    result.push(place)
                }

                return result
            },

            getPersonMeta: function(ids, allMeta) {
                if (ids.length == 0) {
                    return []
                }

                let self = this
                let result = []
                allMeta = JSON.parse(JSON.stringify(allMeta))

                let l = ids.length
                for (let index = 0; index < l; index++) {
                    let personObj = self.personsData.find(
                        person => person.value === ids[index]
                    )
                    let personData = allMeta.find(m => m.id === ids[index])

                    let author = {
                        id: JSON.parse(JSON.stringify(personObj)),
                    }

                    if (personData.hasOwnProperty('marked')) {
                        author.marked = personData.marked
                    } else {
                        author.marked = ''
                    }

                    if (personData.hasOwnProperty('salutation')) {
                        author.salutation = personData.salutation
                    } else {
                        author.salutation = ''
                    }

                    result.push(author)
                }

                return result
            },
        },
    })
}
