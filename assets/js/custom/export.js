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
                let customActions = []

                if (this.path == 'tgm') {
                    customActions.push({
                        url:
                            ajaxUrl +
                            '?action=export_palladio&type=' +
                            this.path +
                            '&format=csv',
                        title: 'Palladio – vše',
                    })

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

                if (this.path == 'blekastad') {
                    customActions.push({
                        url:
                            ajaxUrl +
                            '?action=export_palladio&type=' +
                            this.path +
                            '&format=csv',
                        title: 'Palladio – vše',
                    })

                    customActions.push({
                        url:
                            ajaxUrl +
                            '?action=export_palladio_masaryk&format=csv&from=1',
                        title: ' Palladio – dopisy od MB',
                    })

                    customActions.push({
                        url:
                            ajaxUrl +
                            '?action=export_palladio_masaryk&format=csv&from=0',
                        title: ' Palladio – dopisy pro MB',
                    })
                }
                return customActions
            },
        },
        mounted: function () {
            let self = this

            let letterTypes = getLetterType()

            if (isString(letterTypes)) {
                self.error = letterTypes
            } else {
                self.path = letterTypes['path']
            }

            return
        },
    })
}
