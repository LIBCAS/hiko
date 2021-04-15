/* global Tagify normalize */

window.letterForm = function () {
    return {
        authors: [],
        copies: [],
        day: '',
        dateIsRange: false,
        description: '',
        destinations: [],
        entitiesList: JSON.parse(document.getElementById('entities').innerHTML),
        keywordsList: JSON.parse(
            document.getElementById('keywords-list').innerHTML
        ),
        languagesList: JSON.parse(
            document.getElementById('languages-list').innerHTML
        ),
        month: '',
        origins: [],
        placesList: JSON.parse(
            document.getElementById('places-list').innerHTML
        ),
        recipients: [],
        relatedResources: [],
        year: '',
        formSubmit: false,

        fetch: function () {
            const data = JSON.parse(
                document.getElementById('letter-data').innerHTML
            )

            if (data.length === 0) {
                return this.initTagify()
            }

            this.dateIsRange = data.date_is_range

            if (data.related_resources.length > 0) {
                this.relatedResources = data.related_resources
            }

            this.day =
                data.date_day && data.date_day.length > 0 ? data.date_day : ''
            this.month =
                data.date_month && data.date_month.length > 0
                    ? data.date_month
                    : ''
            this.year =
                data.date_year && data.date_year.length > 0
                    ? data.date_year
                    : ''

            if (data.l_author.length > 0) {
                this.authors = data.l_author
            }

            if (data.recipient.length > 0) {
                this.recipients = data.recipient
            }

            if (data.origin.length > 0) {
                this.origins = data.origin
            }

            if (data.dest.length > 0) {
                this.destinations = data.dest
            }

            if (data.name.length > 0) {
                this.description = data.name
            }

            if (data.copies.length > 0) {
                this.copies = data.copies
            }

            this.updateTagify()
        },

        preventExit(e) {
            if (!this.formSubmit) {
                e.preventDefault()
            }
        },

        initTagify: function () {
            document
                .querySelectorAll('input.related-tagify')
                .forEach((select) => {
                    this.initRelatedTagify(select)
                })

            document
                .querySelectorAll('input.simple-tagify')
                .forEach((select) => {
                    this.initSimpleTagify(select)
                })
        },

        updateTagify: function () {
            const context = this
            setTimeout(() => {
                context.initTagify()
            }, 300) // wait for template render, refactor
        },

        initSimpleTagify(select) {
            if (select.dataset.tagify) {
                return
            }

            const context = this

            new Tagify(select, {
                delimiters: ';',
                enforceWhitelist: true,
                whitelist: JSON.parse(
                    JSON.stringify(context[select.dataset.type])
                ),
                dropdown: {
                    enabled: 0,
                    highlightFirst: true,
                    maxItems: Infinity,
                    placeAbove: false,
                },
            })

            select.dataset.tagify = true
        },

        initRelatedTagify(select) {
            if (select.dataset.tagify) {
                return
            }

            const context = this

            const optionsList = JSON.parse(
                JSON.stringify(context[select.dataset.type])
            )

            const tagify = new Tagify(select, {
                delimiters: ';',
                enforceWhitelist: true,
                whitelist:
                    select.value.length === 0 ? [] : JSON.parse(select.value),
                tagTextProp: 'value',
                mode: select.dataset.mode ? select.dataset.mode : null,
                dropdown: {
                    enabled: 0,
                    highlightFirst: true,
                    maxItems: Infinity,
                    placeAbove: false,
                    searchKeys: ['value', 'id'],
                },
            })

            select.dataset.tagify = true

            tagify.on('input', (e) => {
                const search = normalize(e.detail.value)
                const results = []

                tagify.settings.whitelist.length = 0 // reset the whitelist
                tagify.loading(true).dropdown.hide.call(tagify)

                optionsList.map((option) => {
                    if (normalize(option.name).includes(search)) {
                        results.push({
                            id: option.id,
                            value: option.name,
                        })
                    }
                })

                tagify.settings.whitelist = results
                tagify.loading(false).dropdown.show.call(tagify, search) // render the suggestions dropdown
            })

            const target = select.dataset.target
            const targetIndex = select.dataset.index

            if (target && targetIndex) {
                tagify.on('change', (e) => {
                    let newName = ''
                    let newId = ''

                    if (e.detail.value) {
                        const currentValue = JSON.parse(e.detail.value)[0]
                        newName = currentValue.value
                        newId = currentValue.id
                    }

                    context[target][targetIndex]['id'] = newId
                    context[target][targetIndex]['name'] = newName

                    context.updateTagify()
                })
            }
        },

        addCopy: function () {
            this.copies.push({
                archive: '',
                collection: '',
                copy: '',
                l_number: '',
                location_note: '',
                manifestation_notes: '',
                ms_manifestation: '',
                preservation: '',
                repository: '',
                signature: '',
                type: '',
            })
        },

        removeCopy: function (copyIndex) {
            const copies = JSON.parse(JSON.stringify(this.copies))

            this.copies = copies.filter((item, index) => {
                return index !== copyIndex
            })
        },

        addNewAuthor: function () {
            this.authors.push({
                id: '',
                marked: '',
                name: '',
                key: this.randomKey(),
            })
            this.updateTagify()
        },

        removeAuthor: function (authorIndex) {
            const authors = JSON.parse(JSON.stringify(this.authors))

            this.authors = authors.filter((item, index) => {
                return index !== authorIndex
            })

            this.updateTagify()
        },

        addNewRecipient: function () {
            this.recipients.push({
                id: '',
                marked: '',
                name: '',
                salutation: '',
                key: this.randomKey(),
            })
            this.updateTagify()
        },

        removeRecipient: function (recipientIndex) {
            const recipients = JSON.parse(JSON.stringify(this.recipients))

            this.recipients = recipients.filter((item, index) => {
                return index !== recipientIndex
            })

            this.updateTagify()
        },

        addNewOrigin: function () {
            this.origins.push({
                id: '',
                marked: '',
                name: '',
                type: 'origin',
                key: this.randomKey(),
            })
            this.updateTagify()
        },

        removeOrigin: function (originIndex) {
            const origins = JSON.parse(JSON.stringify(this.origins))

            this.origins = origins.filter((item, index) => {
                return index !== originIndex
            })

            this.updateTagify()
        },

        addNewDestination: function () {
            this.destinations.push({
                id: '',
                marked: '',
                name: '',
                type: 'destination',
                key: this.randomKey(),
            })

            this.updateTagify()
        },

        removeDestination: function (destinationIndex) {
            const destinations = JSON.parse(JSON.stringify(this.destinations))

            this.destinations = destinations.filter((item, index) => {
                return index !== destinationIndex
            })

            this.updateTagify()
        },

        addNewRelatedResource: function () {
            this.relatedResources.push({ link: '', title: '' })
        },

        removeRelatedResource: function (resourceIndex) {
            const resources = JSON.parse(JSON.stringify(this.relatedResources))

            this.relatedResources = resources.filter((item, index) => {
                return index !== resourceIndex
            })
        },

        // random key for forcing Alpine to rerender template if used as :key
        randomKey: function () {
            return Math.random().toString(36).substring(7)
        },

        handleSubmit: function (event) {
            event.preventDefault()
            this.formSubmit = true
            document.getElementById('letter-form').submit()
        },

        regenerateSelectData: function () {},

        generateDescription: function () {
            let title = ''

            title += this.day != '' ? this.day + '. ' : '? '
            title += this.month != '' ? this.month + '. ' : '? '
            title += this.year != '' ? this.year + ' ' : '? '
            title += this.formatLetterInfo(this.authors)
            title += ' (' + this.formatLetterInfo(this.origins) + ') to '
            title += this.formatLetterInfo(this.recipients)
            title += ' (' + this.formatLetterInfo(this.destinations) + ')'

            this.description = title
        },

        formatLetterInfo: function (info) {
            let names = []

            info.forEach((item) => {
                names.push(item.name.split(' (')[0])
            })

            return names.join('; ')
        },
    }
}
