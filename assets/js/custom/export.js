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
            actions: function() {
                return [
                    {
                        url:
                            ajaxUrl +
                            '?action=export_palladio&type=' +
                            this.path +
                            '&format=csv',
                        title: 'Palladio',
                    },
                ]
            },
        },
        mounted: function() {
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
