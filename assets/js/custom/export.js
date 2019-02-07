/* global Vue Swal axios ajaxUrl*/

if (document.getElementById('export')) {
    new Vue({
        el: '#export',
        methods: {
            exportLetters: function(letterType) {
                axios
                    .get(ajaxUrl + '?action=export_letters&l_type' + letterType)
                    .then(function(response) {
                        console.log(response.data);
                    })
                    .catch(function(error) {
                        console.log(error)
                    })
            }
        }
    })
}
