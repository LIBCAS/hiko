/* global Vue Swal ajaxUrl*/

if (document.getElementById('export')) {
    new Vue({
        el: '#export',
        methods: {
            exportLetters: function(letterType) {
                window.location.href =
                    ajaxUrl + '?action=export_letters&l_type=' + letterType
            },
        },
    })
}
