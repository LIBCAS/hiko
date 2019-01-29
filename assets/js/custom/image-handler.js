/* global Uppy ajaxUrl Vue axios */

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

        mounted: function() {
            this.getImages()
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
        },
    })
}
