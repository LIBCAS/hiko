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
                            self.alternativeNames = response.data.names
                            self.dob = response.data.birth_year
                            self.dod = response.data.death_year
                            self.emlo = response.data.emlo
                            self.firstName = response.data.forename
                            self.gender = response.data.gender
                            self.lastName = response.data.surname
                            self.nationality = response.data.nationality
                            self.note = response.data.note
                            self.profession = response.data.profession
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
