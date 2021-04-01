/* global decodeHTML */

window.entityForm = function () {
    return {
        type: 'person',
        surname: '',
        forename: '',
        note: '',
        birth_year: null,
        death_year: null,
        gender: '',
        nationality: '',
        viaf: '',
        profession_short: [],
        profession_detailed: [],
        names: [],

        errors: [],

        fetch: function () {
            const data = JSON.parse(
                document.getElementById('entity-data').innerHTML
            )

            if (data.length === 0) {
                return
            }

            this.type = data.type
            this.surname = decodeHTML(data.surname)
            this.forename = decodeHTML(data.forename)
            this.note = data.note
            this.birth_year = data.birth_year
            this.death_year = data.death_year
            this.gender = data.gender
            this.nationality = data.nationality
            this.viaf = data.viaf
            this.profession_short = data.profession_short
            this.profession_detailed = data.profession_detailed
            this.names = data.names
        },

        fullName: function () {
            let name = this.surname

            if (this.forename.length > 0) {
                name += ', ' + this.forename
            }

            return decodeHTML(name)
        },

        handleSubmit: function (event) {
            event.preventDefault()

            this.errors = []

            if (this.surname.length === 0) {
                this.errors.push('Empty name')
            }

            if (this.type.length === 0) {
                this.errors.push('Empty type')
            }

            if (this.errors.length > 0) {
                return
            }

            document.getElementById('entity-form').submit()
        },
    }
}
