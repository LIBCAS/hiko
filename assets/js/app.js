/* global Vue */

if (document.getElementById('app')) {
    new Vue({
        el: '#app',
        data: {
            firstName: '',
            lastName: ''
        },

        computed: {
            fullName: function () {
                var fullName = this.lastName + ', ' + this.firstName;
                return fullName.trim();
            },
            personsFormValidated: function() {
                if (this.firstName == '' || this.lastName == '' || this.fullName.length < 8) {
                    return false;
                }
                return true;
            }
        }
    });
}
