/* global SlimSelect Vue axios ajaxUrl */

if (document.getElementById('letter-form')) {
    new Vue({
        el: '#letter-add-form',
        data: {
            error: false,
            author: [],
            author_as_marked: '',
            author_inferred: '',
            author_uncertain: '',
            recipient: [],
            recipient_marked: '',
            recipient_inferred: '',
            recipient_uncertain: '',
            recipient_notes: '',
            mentioned: [],
            origin: '',
            origin_marked: '',
            origin_inferred: '',
            origin_uncertain: '',
            destination: '',
            dest_marked: '',
            dest_inferred: '',
            dest_uncertain: '',
            day: '',
            month: '',
            year: '',
            date_marked: '',
            date_uncertain: '',
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
            status: '',
            persons: JSON.parse(document.querySelector('#people').innerHTML),
            places: JSON.parse(document.querySelector('#places').innerHTML),
        },

        mounted: function() {
            let url = new URL(window.location.href)
            if (url.searchParams.get('edit')) {
                this.getInitialData(url.searchParams.get('edit'))
            } else {
                this.addSlimSelect()
            }
        },

        methods: {
            getTitle: function() {
                let authors = []
                let recipients = []
                for (let i = 0; i < this.author.length; i++) {
                    authors.push(getNameById(this.persons, this.author[i]))
                }
                for (let i = 0; i < this.recipient.length; i++) {
                    recipients.push(
                        getNameById(this.persons, this.recipient[i])
                    )
                }

                let origin = getNameById(this.places, this.origin)
                let destination = getNameById(this.places, this.destination)

                let date = this.day + '. ' + this.month + '. ' + this.year
                let from = authors.join('; ') + ' (' + origin + ')'
                let to = recipients + ' (' + destination + ')'

                this.title = date + ' ' + from + ' to ' + to
                return
            },

            getInitialData: function(id) {
                let self = this
                axios
                    .get(
                        ajaxUrl +
                            '?action=list_public_bl_letters_single&pods_id=' +
                            id
                    )
                    .then(function(response) {
                        if (response.data == '404') {
                            self.error = true
                        } else {
                            let rd = response.data
                            console.log(rd)
                            self.l_number = rd.l_number
                            self.year = rd.date_year == '0' ? '' : rd.date_year
                            self.month =
                                rd.date_month == '0' ? '' : rd.date_month
                            self.day = rd.date_day == '0' ? '' : rd.date_day
                            self.date_marked = rd.date_marked
                            self.date_uncertain = rd.date_uncertain
                            self.author = Object.keys(rd.l_author)
                            self.author_as_marked = rd.l_author_marked
                            self.author_inferred = rd.author_inferred
                            self.author_uncertain = rd.author_uncertain
                            self.recipient = Object.keys(rd.recipient)
                            self.recipient_marked = rd.recipient_marked
                            self.recipient_inferred = rd.recipient_inferred
                            self.recipient_uncertain = rd.recipient_uncertain
                            self.recipient_notes = rd.recipient_notes
                            self.origin = Object.keys(rd.origin)[0]
                            self.origin_marked = rd.origin_marked
                            self.origin_inferred = rd.origin_inferred
                            self.origin_uncertain = rd.origin_uncertain
                            self.destination = Object.keys(rd.dest)[0]
                            self.dest_marked = rd.dest_marked
                            self.dest_inferred = rd.dest_inferred
                            self.dest_uncertain = rd.dest_uncertain
                            self.languages =
                                rd.languages.length === 0
                                    ? []
                                    : rd.languages.split(';')
                            self.keywords = self.parseKeywords(rd.keywords)
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
                        }
                    })
                    .catch(function() {
                        self.error = true
                    })
                    .then(function() {
                        self.addSlimSelect()
                    })
            },

            ajaxToData: function(action, targetData, targetElement) {
                let self = this
                targetElement.classList.add('rotate')
                axios
                    .get(ajaxUrl + '?action=' + action)
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
                        'list_bl_people_simple',
                        'persons',
                        event.target
                    )
                } else if (type == 'places') {
                    self.ajaxToData(
                        'list_bl_places_simple',
                        'places',
                        event.target
                    )
                }
            },

            addSlimSelect: function() {
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
            },

            removeKeyword: function(kw) {
                this.keywords = this.keywords.filter(function(item) {
                    return item.value !== kw.value
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
        },
        mounted: function() {
            let url = new URL(window.location.href)
            if (url.searchParams.get('edit')) {
                this.getInitialData(url.searchParams.get('edit'))
            }
        },
        methods: {
            getInitialData: function(id) {
                let self = this
                axios
                    .get(ajaxUrl + '?action=list_bl_place_single&pods_id=' + id)
                    .then(function(response) {
                        if (response.data == '404') {
                            self.error = true
                        } else {
                            self.place = response.data.name
                            self.country = response.data.country
                        }
                    })
                    .catch(function() {
                        self.error = true
                    })
            },
        },
    })

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

if (document.getElementById('person-name')) {
    new Vue({
        el: '#person-name',
        data: {
            firstName: '',
            lastName: '',
            emlo: '',
            dob: '',
            dod: '',
            error: false,
        },

        computed: {
            fullName: function() {
                let fullName
                fullName =
                    this.capitalize(this.lastName).trim() +
                    ', ' +
                    this.capitalize(this.firstName).trim()
                return fullName.trim()
            },
            personsFormValidated: function() {
                if (
                    this.firstName == '' ||
                    this.lastName == '' ||
                    this.fullName.length < 8
                ) {
                    return false
                }
                return true
            },
        },

        mounted: function() {
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
                        ajaxUrl + '?action=list_bl_people_single&pods_id=' + id
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
