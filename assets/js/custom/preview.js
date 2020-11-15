/* global Vue axios ajaxUrl baguetteBox arrayToSingleObject decodeHTML */

if (document.getElementById('letter-preview')) {
    new Vue({
        el: '#letter-preview',
        data: {
            abstract: '',
            archive: '',
            author: [],
            author_inferred: '',
            author_note: '',
            author_uncertain: '',
            collection: '',
            copy: '',
            date_approximate: '',
            date_inferred: '',
            date_is_range: '',
            date_marked: '',
            date_note: '',
            date_uncertain: '',
            day: '',
            day2: '',
            dest_inferred: '',
            dest_marked: '',
            dest_note: '',
            dest_uncertain: '',
            destination: '',
            document_type: '',
            edit: false,
            explicit: '',
            images: [],
            incipit: '',
            keywords: [],
            l_number: '',
            languages: [],
            letterID: null,
            letterType: '',
            loading: true,
            location_note: '',
            manifestation_notes: '',
            mentioned: [],
            month: '',
            month2: '',
            notes_public: '',
            origin: '',
            origin_inferred: '',
            origin_marked: '',
            origin_note: '',
            origin_uncertain: '',
            people_mentioned_notes: '',
            preservation: '',
            recipient: [],
            recipient_inferred: '',
            recipient_notes: '',
            recipient_uncertain: '',
            related_resources: [],
            repository: '',
            title: '',
            year: '',
            year2: '',
        },

        mounted: function () {
            let url = new URL(window.location.href)
            this.letterID = url.searchParams.get('letter')
            this.letterType = url.searchParams.get('l_type')
            this.getLetter(this.letterID)
        },

        updated: function () {
            if (this.images.length > 0) {
                baguetteBox.run('#gallery')
            }
        },

        methods: {
            decodeHTML: function (str) {
                return decodeHTML(str)
            },

            getItemData: function (item, metaJSON, type = false) {
                let results = []

                let ids = Object.keys(item)

                let names = Object.values(item)

                metaJSON = JSON.parse(JSON.stringify(metaJSON))

                for (let index = 0; index < ids.length; index++) {
                    let find = metaJSON.filter((obj) => {
                        if (obj.hasOwnProperty('type')) {
                            return obj.id == ids[index] && obj.type == type
                        }

                        return obj.id == ids[index]
                    })
                    find[0].title = names[index]
                    results.push(find[0])
                }

                return JSON.parse(JSON.stringify(results))
            },

            parseDocumentTypesData(docData) {
                if (!docData) {
                    return {
                        type: '',
                        preservation: '',
                        copy: '',
                    }
                }

                docData = JSON.parse(docData)

                return arrayToSingleObject(docData)
            },

            getLetter: function (id) {
                let self = this
                axios
                    .get(
                        ajaxUrl +
                            '?action=list_public_letters_single&pods_id=' +
                            id +
                            '&l_type=' +
                            self.letterType
                    )
                    .then(function (response) {
                        let rd = response.data

                        if (rd == '404') {
                            self.error = true
                            return
                        }

                        let docTypes = self.parseDocumentTypesData(
                            rd.document_type
                        )

                        let authorsMeta = rd.authors_meta

                        self.title = rd.name

                        self.year = rd.date_year == '0' ? '' : rd.date_year
                        self.month = rd.date_month == '0' ? '' : rd.date_month
                        self.day = rd.date_day == '0' ? '' : rd.date_day
                        self.year2 = rd.range_year == '0' ? '' : rd.range_year
                        self.month2 =
                            rd.range_month == '0' ? '' : rd.range_month
                        self.day2 = rd.range_day == '0' ? '' : rd.range_day
                        self.date_approximate = rd.date_approximate
                        self.date_inferred = rd.date_inferred
                        self.date_is_range = rd.date_is_range
                        self.date_marked = rd.date_marked
                        self.date_note = rd.date_note
                        self.date_uncertain = rd.date_uncertain

                        self.author = self.getItemData(rd.l_author, authorsMeta)
                        self.author_inferred = rd.author_inferred
                        self.author_uncertain = rd.author_uncertain
                        self.author_note = rd.author_note

                        self.recipient = self.getItemData(
                            rd.recipient,
                            authorsMeta
                        )
                        self.recipient_inferred = rd.recipient_inferred
                        self.recipient_uncertain = rd.recipient_uncertain
                        self.recipient_notes = rd.recipient_notes

                        self.origin = self.getItemData(
                            rd.origin,
                            rd.places_meta,
                            'origin'
                        )
                        self.origin_inferred = rd.origin_inferred
                        self.origin_note = rd.origin_note
                        self.origin_uncertain = rd.origin_uncertain

                        self.destination = self.getItemData(
                            rd.dest,
                            rd.places_meta,
                            'destination'
                        )
                        self.dest_inferred = rd.dest_inferred
                        self.dest_note = rd.dest_note
                        self.dest_uncertain = rd.dest_uncertain

                        self.abstract = rd.abstract
                        self.explicit = rd.explicit
                        self.images = rd.images
                        self.incipit = rd.incipit
                        self.keywords = Object.values(rd.keywords)
                        self.languages =
                            rd.languages.length === 0
                                ? []
                                : rd.languages.split(';')
                        self.notes_public = rd.notes_public

                        self.mentioned = Object.values(rd.people_mentioned)
                        self.people_mentioned_notes = rd.people_mentioned_notes

                        if (rd.related_resources) {
                            self.related_resources = JSON.parse(
                                rd.related_resources
                            )
                        }

                        self.copy = docTypes.copy
                        self.document_type = docTypes.document_type
                        self.manifestation_notes = rd.manifestation_notes
                        self.preservation = docTypes.preservation

                        self.archive = rd.archive
                        self.collection = rd.collection
                        self.l_number = rd.l_number
                        self.location_note = rd.location_note
                        self.repository = rd.repository
                        self.signature = rd.signature

                        self.loading = false
                    })
                    .catch(function (error) {
                        console.log(error)
                        alert(error)
                        self.error = true
                    })
            },
        },
    })
}
