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
                return [{
                    url: ajaxUrl +
                        '?action=export_letters&type=' +
                        this.path,
                    title: 'Všechny dopisy bez obrázků',
                }, ]
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
