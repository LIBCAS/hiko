/* global Vue ajaxUrl getLetterType isString */

if (document.getElementById('export')) {
    new Vue({
        el: '#export',
        data: {
            path: '',
            error: false,
            openDD: false,
        },
        computed: {
            actions: function () {
                let customActions = [
                    {
                        url:
                            ajaxUrl +
                            '?action=export_letters&type=' +
                            this.path +
                            '&format=csv',
                        title: 'Vše',
                    },
                    {
                        url:
                            ajaxUrl +
                            '?action=export_palladio&type=' +
                            this.path +
                            '&format=csv',
                        title: 'Palladio – vše',
                    },
                ]

                if (this.path == 'tgm') {
                    customActions.push({
                        url:
                            ajaxUrl +
                            '?action=export_palladio_masaryk&format=csv&from=1',
                        title: ' Palladio – dopisy od TGM',
                    })

                    customActions.push({
                        url:
                            ajaxUrl +
                            '?action=export_palladio_masaryk&format=csv&from=0',
                        title: ' Palladio – dopisy pro TGM',
                    })
                }

                return customActions
            },
        },
        mounted: function () {
            const letterTypes = getLetterType()

            if (isString(letterTypes)) {
                this.error = letterTypes
            } else {
                this.path = letterTypes['path']
            }

            return
        },
    })
}

if (document.getElementById('export-person')) {
    new Vue({
        el: '#export-person',
        data: {
            type: '',
            error: false,
            openDD: false,
        },
        computed: {
            actions: function () {
                return [
                    {
                        url:
                            ajaxUrl +
                            '?action=export_persons&type=' +
                            this.type +
                            '&format=csv',
                        title: 'Lidé a instituce',
                    },
                ]
            },
        },
        mounted: function () {
            const letterTypes = getLetterType()

            if (isString(letterTypes)) {
                this.error = letterTypes
            } else {
                this.type = letterTypes['personType']
            }

            return
        },
    })
}

if (document.getElementById('export-place')) {
    new Vue({
        el: '#export-place',
        data: {
            type: '',
            error: false,
            openDD: false,
        },
        computed: {
            actions: function () {
                return [
                    {
                        url:
                            ajaxUrl +
                            '?action=export_places&type=' +
                            this.type +
                            '&format=csv',
                        title: 'Místa',
                    },
                ]
            },
        },
        mounted: function () {
            const letterTypes = getLetterType()

            if (isString(letterTypes)) {
                this.error = letterTypes
            } else {
                this.type = letterTypes['placeType']
            }

            return
        },
    })
}
