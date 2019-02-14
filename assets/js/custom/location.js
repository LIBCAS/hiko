/* global Vue Swal axios ajaxUrl */

if (document.getElementById('repository')) {
    new Vue({
        el: '#repository',
        data: {},
        methods: {
            addRepository: function() {
                addNewLocationItem('repository', 'Nový repozitář')
            },
            deleteRepository: function(id) {
                console.log(id)
            },
        },
    })
}
