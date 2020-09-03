/* global Vue axios ajaxUrl getLetterType decodeHTML isString getObjectValues */

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
            loading: true,
            nationality: '',
            note: '',
            personType: '',
            profession: '',
            professionDetailed: [{ label: null, value: null }],
            professionShort: [{ label: null, value: null }],
            professions: [],
            professionsPalladio: [],
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
            let self = this
            let url = new URL(window.location.href)

            if (isString(letterTypes)) {
                self.error = letterTypes
                return
            }

            this.personType = letterTypes['personType']
            this.professionsType = letterTypes['profession']

            let initialProfessions = JSON.parse(
                document.querySelector('#professions').innerHTML
            )

            self.mapProfessions(initialProfessions)

            if (url.searchParams.get('edit')) {
                this.getInitialData(url.searchParams.get('edit'), () => {
                    self.loading = false
                })
            } else {
                self.loading = false
            }
        },

        methods: {
            cleanCopy(obj) {
                return JSON.parse(JSON.stringify(obj))
            },

            capitalize: function (str) {
                return str.charAt(0).toUpperCase() + str.slice(1)
            },

            decodeHTML: function (str) {
                return decodeHTML(str)
            },

            getInitialData: function (id, callback = null) {
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

                        if (rd.profession_detailed) {
                            self.professionDetailed = []
                            rd.profession_detailed.split(';').map((item) => {
                                if (item != '') {
                                    self.professionDetailed.push(
                                        self.getProfessionById(
                                            item,
                                            self.professions
                                        )
                                    )
                                }
                            })
                        }

                        if (rd.profession_short) {
                            self.professionShort = []
                            rd.profession_short.split(';').map((item) => {
                                if (item != '') {
                                    self.professionShort.push(
                                        self.getProfessionById(
                                            item,
                                            self.professionsPalladio
                                        )
                                    )
                                }
                            })
                        }
                    })
                    .catch(function (error) {
                        self.error = true
                        console.log(error)
                    })
                    .then(callback)
            },

            regenerateProfessions: function (event) {
                this.professions = []
                event.target.classList.add('rotate')
                this.getProfessions(() => {
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
                        self.mapProfessions(response.data)
                    })
                    .catch(function (error) {
                        console.log(error)
                    })
                    .then(callback)
            },

            mapProfessions: function (rawProfessions) {
                let self = this
                rawProfessions.map((profession) => {
                    if (profession.palladio) {
                        self.professionsPalladio.push({
                            label: self.decodeHTML(profession.name),
                            value: profession.id,
                        })
                    } else {
                        self.professions.push({
                            label: self.decodeHTML(profession.name),
                            value: profession.id,
                        })
                    }
                })
            },

            addNewprofession: function () {
                this.professionDetailed.push({ label: null, value: null })
            },

            addNewPalladioProfession: function () {
                this.professionShort.push({ label: null, value: null })
            },

            removePalladioProfession: function (professionIndex) {
                this.professionShort = this.professionShort.filter(function (
                    item,
                    index
                ) {
                    return index !== professionIndex
                })
            },

            getProfessionById: function (id, professions) {
                professions = this.cleanCopy(professions)

                if (professions.length == 0) {
                    return []
                }

                let filtered = professions.filter((profession) => {
                    return profession.value == id
                })

                if (filtered.length == 0) {
                    return []
                }

                return {
                    label: filtered[0].label,
                    value: filtered[0].value,
                }
            },

            getObjectValues: function (o) {
                let values = getObjectValues(o)

                // remove duplicates
                return values.filter(function (item, pos, self) {
                    return self.indexOf(item) == pos
                })
            },
        },
    })
}
