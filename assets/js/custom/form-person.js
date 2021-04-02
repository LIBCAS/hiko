/* global decodeHTML Tagify DragSort */

window.entityForm = function () {
    return {
        type: 'person',
        surname: '',
        forename: '',
        viaf: '',

        errors: [],

        fetch: function () {
            const data = JSON.parse(
                document.getElementById('entity-data').innerHTML
            )

            if (data.length === 0) {
                return this.initTagify()
            }

            this.type = data.type
            this.surname = decodeHTML(data.surname)
            this.forename = decodeHTML(data.forename)
            this.viaf = data.viaf

            this.initTagify()
        },

        initTagify: function () {
            this.tagifyTemplate('profession_short', 'profession-short-data')
            this.tagifyTemplate(
                'profession_detailed',
                'profession-detailed-data'
            )
        },

        tagifyTemplate: function (renderElId, dataElId) {
            const tags = new Tagify(document.getElementById(renderElId), {
                delimiters: ';',
                enforceWhitelist: true,
                whitelist: JSON.parse(
                    document.getElementById(dataElId).innerHTML
                ),
                dropdown: {
                    enabled: 0,
                    maxItems: Infinity,
                },
            })

            new DragSort(tags.DOM.scope, {
                selector: '.' + tags.settings.classNames.tag,
                callbacks: {
                    dragEnd: () => {
                        tags.updateValueByDOMTags()
                    },
                },
            })
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
