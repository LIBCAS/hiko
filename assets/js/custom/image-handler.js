/* global Uppy ajaxUrl Vue axios Swal */

if (document.getElementById('media-handler')) {
    new Vue({
        el: '#media-handler',
        data: {
            images: [],
            error: false,
            title: '',
            letterType: '',
            letterId: '',
            modal: {
                visibility: false,
                src: false,
            },
        },
        created: function() {
            let self = this
            let urlParams = new URLSearchParams(window.location.search)
            self.letterType = urlParams.get('l_type')
            self.letterId = urlParams.get('letter')
            if (!self.letterType || !self.letterId) {
                self.error = true
                return
            }
        },

        mounted: function() {
            this.getImages()
            this.registerUppy()
        },

        methods: {
            openModal: function(image) {
                this.modal.visibility = true
                this.modal.src = image.img.large
            },

            closeModal: function() {
                this.modal.visibility = false
                this.modal.src = false
            },

            deleteImage: function(id) {
                let self = this
                removeImage(self.letterId, self.letterType, id, function() {
                    self.deleteRow(id)
                })
            },

            deleteRow: function(id) {
                let self = this
                self.images = self.images.filter(function(item) {
                    return item.id !== id
                })
            },

            getImages: function() {
                let self = this
                axios
                    .get(ajaxUrl, {
                        params: {
                            action: 'list_images',
                            letter: this.letterId,
                            l_type: this.letterType,
                        },
                    })
                    .then(function(response) {
                        self.title = response.data.data.name
                        self.images = response.data.data.images
                    })
                    .catch(function(error) {
                        self.error = true
                        console.log(error)
                    })
            },

            registerUppy: function() {
                Uppy.Core({
                    restrictions: {
                        maxFileSize: 500000,
                        minNumberOfFiles: 1,
                        allowedFileTypes: ['image/jpeg'],
                    },
                })
                    .use(Uppy.Dashboard, {
                        target: '#drag-drop-area',
                        inline: true,
                        showProgressDetails: true,
                        note:
                            'Soubory nahrávejte ve formátu .jpg o maximální velikosti 500KB.',
                        proudlyDisplayPoweredByUppy: false,
                    })

                    .use(Uppy.XHRUpload, {
                        endpoint:
                            ajaxUrl +
                            '?action=handle_img_uploads&l_type=' +
                            self.letterType +
                            '&letter=' +
                            self.letterId,
                    })
            },
        },
    })
}

function removeImage(letterID, letterType, imgID, callback) {
    Swal.fire({
        title: 'Opravdu chcete odstranit tento obrázek?',
        type: 'warning',
        buttonsStyling: false,
        showCancelButton: true,
        confirmButtonText: 'Ano!',
        cancelButtonText: 'Zrušit',
        confirmButtonClass: 'btn btn-primary btn-lg mr-1',
        cancelButtonClass: 'btn btn-secondary btn-lg ml-1',
    }).then(result => {
        if (result.value) {
            axios
                .get(ajaxUrl, {
                    params: {
                        action: 'delete_image',
                        letter: letterID,
                        l_type: letterType,
                        img: imgID,
                    },
                })
                .then(function() {
                    Swal.fire({
                        title: 'Odstraněno.',
                        type: 'success',
                        buttonsStyling: false,
                        confirmButtonText: 'OK',
                        confirmButtonClass: 'btn btn-primary btn-lg',
                    })
                    callback()
                })
                .catch(function(error) {
                    Swal.fire({
                        title: 'Při odstraňování došlo k chybě.',
                        text: error,
                        type: 'error',
                        buttonsStyling: false,
                        confirmButtonText: 'OK',
                        confirmButtonClass: 'btn btn-primary btn-lg',
                    })
                })
        }
    })
}
