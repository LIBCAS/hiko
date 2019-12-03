/* global Vue axios ajaxUrl getLetterType */

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
            type: 'person',
        },

        computed: {
            fullName: function() {
                if (this.type == 'institution') {
                    return this.lastName.trim()
                }

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
            }

            this.personType = letterTypes['personType']

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
                        let rd = response.data
                        if (rd == '404') {
                            self.error = true
                            return
                        }

                        self.alternativeNames = rd.names
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
                    .catch(function(error) {
                        self.error = true
                        console.log(error)
                    })
            },
        },
    })
}
