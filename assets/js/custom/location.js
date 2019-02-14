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

function addNewLocationItem(type, title) {
    Swal.fire({
        title: title,
        type: 'question',
        input: 'text',
        inputValidator: value => {
            if (value.length < 3) {
                return 'Zadejte hodnotu'
            }
        },
        buttonsStyling: false,
        showCancelButton: true,
        confirmButtonText: 'Uložit',
        cancelButtonText: 'Zrušit',
        confirmButtonClass: 'btn btn-primary btn-lg mr-1',
        cancelButtonClass: 'btn btn-secondary btn-lg ml-1',
        showLoaderOnConfirm: true,
        preConfirm: function(value) {
            return axios
                .post(
                    ajaxUrl + '?action=add_new_location_data',
                    {
                        ['type']: type,
                        ['item']: value,
                    },
                    {
                        headers: {
                            'Content-Type': 'application/json;charset=utf-8',
                        },
                    }
                )
                .then(function(response) {
                    console.log(response.data)
                    return response.data
                })
                .catch(function(error) {
                    Swal.showValidationMessage(
                        `Při ukládání došlo k chybě: ${error}`
                    )
                })
        },
        allowOutsideClick: () => !Swal.isLoading(),
    }).then(result => {
        if (result.value) {
            Swal.fire({
                title: 'Položka byla úspěšně přidána',
                type: 'success',
                buttonsStyling: false,
                confirmButtonText: 'OK',
                confirmButtonClass: 'btn btn-primary btn-lg',
            })
        }
    })
}
