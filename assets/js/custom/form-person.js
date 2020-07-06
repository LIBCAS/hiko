/* global Vue axios ajaxUrl getLetterType decodeHTML isString */

if (document.getElementById('person-name')) {
    new Vue({
        el: '#person-name',
        data: {
            alternativeNames: [],
            dob: '',
            dod: '',
            emlo: '',
            error: false,
            firstName: '',
            gender: '',
            lastName: '',
            nationality: '',
            note: '',
            personType: '',
            profession: '',
            professions: [],
            professionsType: '',
            type: 'person',
        },

        computed: {
            fullName: function () {
                if (this.type == 'institution') {
                    return this.lastName.trim()
                }

                let fullName = this.capitalize(this.lastName).trim()

                if (this.firstName.length > 0) {
                    fullName += ', ' + this.capitalize(this.firstName).trim()
                }

                return fullName.trim()
            },
            personsFormValidated: function () {
                if (this.lastName == '' || this.fullName.length < 3) {
                    return false
                }
                return true
            },
        },

        mounted: function () {
            let letterTypes = getLetterType()

            if (isString(letterTypes)) {
                self.error = letterTypes
                return
            }

            this.personType = letterTypes['personType']

            this.professionsType = letterTypes['profession']

            let url = new URL(window.location.href)

            if (url.searchParams.get('edit')) {
                this.getInitialData(url.searchParams.get('edit'))
            }

            this.getProfessions()
        },

        methods: {
            capitalize: function (str) {
                return str.charAt(0).toUpperCase() + str.slice(1)
            },

            decodeHTML: function (str) {
                return decodeHTML(str)
            },

            getInitialData: function (id) {
                let self = this
                axios
                    .get(
                        ajaxUrl +
                            '?action=list_people_single&pods_id=' +
                            id +
                            '&type=' +
                            self.personType
                    )
                    .then(function (response) {
                        let rd = response.data

                        if (rd == '404') {
                            self.error = true
                            return
                        }

                        if (Array.isArray(rd.names)) {
                            self.alternativeNames = rd.names
                        } else {
                            self.alternativeNames = []
                        }

                        self.dob = rd.birth_year
                        self.dod = rd.death_year
                        self.emlo = rd.emlo
                        self.firstName = rd.forename
                        self.gender = rd.gender
                        self.lastName = rd.surname
                        self.nationality = rd.nationality
                        self.note = rd.note
                        self.profession = rd.profession
                        self.type = rd.type ? rd.type : 'person'
                    })
                    .catch(function (error) {
                        self.error = true
                        console.log(error)
                    })
            },

            regenerateProfessions: function (event) {
                let self = this
                event.target.classList.add('rotate')
                self.getProfessions(() => {
                    event.target.classList.remove('rotate')
                })
            },

            getProfessions: function (callback = null) {
                let self = this

                axios
                    .get(
                        ajaxUrl +
                            '?action=professions_table_data&type=' +
                            self.professionsType
                    )
                    .then(function (response) {
                        let professions = response.data

                        professions.map((kw) => {
                            self.professions.push({
                                label: self.decodeHTML(kw.name),
                                value: kw.id,
                            })
                        })
                    })
                    .catch(function (error) {
                        console.log(error)
                    })
                    .then(callback)
            },
        },
    })
}
