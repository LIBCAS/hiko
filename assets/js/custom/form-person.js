/* global decodeHTML Tagify DragSort ajaxUrl getLetterType axios */

window.entityForm = function () {
    return {
        type: 'person',
        surname: '',
        forename: '',
        viaf: '',
        professionsShort: [],
        professionsDetailed: [],
        professionsShortTagify: null,
        professionsDetailedTagify: null,

        errors: [],

        fetch: function () {
            const data = JSON.parse(
                document.getElementById('entity-data').innerHTML
            )

            if (data.length === 0) {
                return this.initTagify()
            }

            this.type = data.type ? data.type : 'person'
            this.surname = decodeHTML(data.surname)
            this.forename = decodeHTML(data.forename)
            this.viaf = data.viaf

            this.initTagify()
        },

        initTagify: function () {
            const context = this

            context.getProfessions(() => {
                context.tagifyTemplate(
                    'profession_short',
                    context.professionsShort,
                    'professionsShortTagify'
                )
                context.tagifyTemplate(
                    'profession_detailed',
                    context.professionsDetailed,
                    'professionsDetailedTagify'
                )
            })
        },

        tagifyTemplate: function (renderElId, whitelist, contextTagify) {
            const tags = new Tagify(document.getElementById(renderElId), {
                delimiters: ';',
                enforceWhitelist: true,
                whitelist: JSON.parse(JSON.stringify(whitelist)),
                dropdown: {
                    enabled: 0,
                    highlightFirst: true,
                    maxItems: Infinity,
                    placeAbove: false,
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

            this[contextTagify] = tags
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

        regenerateProfessions: function (e) {
            const context = this
            e.target.classList.add('rotate')
            this.getProfessions(() => {
                context.professionsShortTagify.settings.whitelist =
                    context.professionsShort
                context.professionsDetailedTagify.settings.whitelist =
                    context.professionsDetailed
                e.target.classList.remove('rotate')
            })
        },

        getProfessions: function (callback) {
            const context = this
            const types = getLetterType()

            axios
                .get(
                    ajaxUrl +
                        '?action=professions_select_data&type=' +
                        types['profession'] +
                        '&lang=' +
                        types['profession']
                )
                .then((response) => {
                    context.professionsDetailed =
                        response.data.professions_detailed
                    context.professionsShort = response.data.professions_short
                })
                .catch((error) => {
                    console.log(error)
                })
                .then(callback)
        },
    }
}
