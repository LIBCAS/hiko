/* global Vue axios ajaxUrl baguetteBox */

if (document.getElementById('letter-preview')) {
    new Vue({
        el: '#letter-preview',
        data: {
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
            keywords: [],
            abstract: '',
            incipit: '',
            explicit: '',
            people_mentioned_notes: '',
            notes_public: '',
            rel_rec_name: '',
            rel_rec_url: '#',
            ms_manifestation: '',
            repository: '',
            edit: false,
            letterID: null,
            images: [],
        },

        mounted: function() {
            let url = new URL(window.location.href)
            let self = this
            if (url.searchParams.get('letter')) {
                self.getLetter(url.searchParams.get('letter'))
            }
        },
        updated: function() {
            if (this.images.length > 0) {
                baguetteBox.run('#gallery')
            }
        },
        methods: {
            getLetter: function(id) {
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
                            self.title = rd.name
                            self.year = rd.date_year == '0' ? '' : rd.date_year
                            self.month =
                                rd.date_month == '0' ? '' : rd.date_month
                            self.day = rd.date_day == '0' ? '' : rd.date_day
                            self.date_marked = rd.date_marked
                            self.date_uncertain = rd.date_uncertain
                            self.author = Object.values(rd.l_author)
                            self.author_as_marked = rd.l_author_marked
                            self.author_inferred = rd.author_inferred
                            self.author_uncertain = rd.author_uncertain
                            self.recipient = Object.values(rd.recipient)
                            self.recipient_marked = rd.recipient_marked
                            self.recipient_inferred = rd.recipient_inferred
                            self.recipient_uncertain = rd.recipient_uncertain
                            self.recipient_notes = rd.recipient_notes
                            self.origin = Object.values(rd.origin)[0]
                            self.origin_marked = rd.origin_marked
                            self.origin_inferred = rd.origin_inferred
                            self.origin_uncertain = rd.origin_uncertain
                            self.destination = Object.values(rd.dest)[0]
                            self.dest_marked = rd.dest_marked
                            self.dest_inferred = rd.dest_inferred
                            self.dest_uncertain = rd.dest_uncertain
                            self.languages =
                                rd.languages.length === 0
                                    ? []
                                    : rd.languages.split(';')
                            self.keywords =
                                rd.keywords.length === 0
                                    ? []
                                    : rd.keywords.split(';')
                            self.abstract = rd.abstract
                            self.incipit = rd.incipit
                            self.explicit = rd.explicit
                            self.mentioned = Object.values(rd.people_mentioned)
                            self.people_mentioned_notes =
                                rd.people_mentioned_notes
                            self.notes_public = rd.notes_public
                            self.rel_rec_name = rd.rel_rec_name
                            self.rel_rec_url =
                                rd.rel_rec_url.length === 0
                                    ? '#'
                                    : rd.rel_rec_url
                            self.ms_manifestation = rd.ms_manifestation
                            self.repository = rd.repository

                            self.images = rd.images
                            self.l_number = rd.l_number
                        }
                    })
                    .catch(function() {
                        self.error = true
                    })
            },
        },
    })
}
