/* global Uppy ajaxUrl Vue axios Swal errorInfoSwal decodeHTML */

const imageHandlerSwal = {
    confirmSave: {
        title: 'Chcete uložit zadané údaje?',
        type: 'info',
        buttonsStyling: false,
        showCancelButton: true,
        confirmButtonText: 'Ano!',
        cancelButtonText: 'Zrušit',
        confirmButtonClass: 'btn btn-primary btn-lg mr-1',
        cancelButtonClass: 'btn btn-secondary btn-lg ml-1',
    },
    removeInfo: {
        title: 'Odstraněno.',
        type: 'success',
        buttonsStyling: false,
        confirmButtonText: 'OK',
        confirmButtonClass: 'btn btn-primary btn-lg',
    },
    confirmRemove: {
        title: 'Opravdu chcete odstranit tento obrázek?',
        type: 'warning',
        buttonsStyling: false,
        showCancelButton: true,
        confirmButtonText: 'Ano!',
        cancelButtonText: 'Zrušit',
        confirmButtonClass: 'btn btn-primary btn-lg mr-1',
        cancelButtonClass: 'btn btn-secondary btn-lg ml-1',
    },
    saveInfo: {
        title: 'Data byla úspěšně uložena.',
        type: 'success',
        buttonsStyling: false,
        confirmButtonText: 'OK',
        confirmButtonClass: 'btn btn-primary btn-lg',
    },
}

if (document.getElementById('media-handler')) {
    new Vue({
        el: '#media-handler',
        data: {
            images: [],
            error: false,
            title: '',
            url: '#',
            letterType: '',
            letterId: '',
            modal: {
                visibility: false,
                src: false,
            },
            orderMode: false,
            orderedImages: [],
        },
        created: function () {
            let self = this
            let urlParams = new URLSearchParams(window.location.search)
            self.letterType = urlParams.get('l_type')
            self.letterId = urlParams.get('letter')
            if (!self.letterType || !self.letterId) {
                self.error = true
                return
            }
        },

        mounted: function () {
            this.getImages()
            this.registerUppy()
        },

        methods: {
            decodeHTML: function (str) {
                return decodeHTML(str)
            },

            editImageMetadataAjax: function (image, callback) {
                Swal.fire(imageHandlerSwal.confirmSave).then((result) => {
                    if (result.value) {
                        let data = {
                            ['img_id']: image.id,
                            ['img_status']: image.status,
                            ['img_description']: image.description,
                        }
                        axios
                            .post(ajaxUrl + '?action=change_metadata', data, {
                                headers: {
                                    'Content-Type':
                                        'application/json;charset=utf-8',
                                },
                            })
                            .then(function () {
                                Swal.fire(imageHandlerSwal.saveInfo)
                                callback()
                            })
                            .catch(function (error) {
                                Swal.fire(errorInfoSwal(error))
                                callback()
                            })
                    }
                })
            },

            removeImage: function (letterID, letterType, imgID, callback) {
                Swal.fire(imageHandlerSwal.removeInfo).then((result) => {
                    if (result.value) {
                        axios
                            .post(
                                ajaxUrl + '?action=delete_hiko_image',
                                {
                                    ['letter']: letterID,
                                    ['l_type']: letterType,
                                    ['img']: imgID,
                                },
                                {
                                    headers: {
                                        'Content-Type':
                                            'application/json;charset=utf-8',
                                    },
                                }
                            )

                            .then(function () {
                                Swal.fire(imageHandlerSwal.removeInfo)
                                callback()
                            })
                            .catch(function (error) {
                                Swal.fire(errorInfoSwal(error))
                            })
                    }
                })
            },

            saveImageOrder: function (id, order) {
                axios({
                    method: 'post',
                    url: ajaxUrl + '?action=change_image_order',
                    data: {
                        img_id: id,
                        img_order: order,
                    },
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'Access-Control-Allow-Origin': '*',
                    },
                })
                    .then(function () {
                        return true
                    })
                    .catch(function (error) {
                        Swal.fire(errorInfoSwal(error))
                    })
            },

            openModal: function (image) {
                this.modal.visibility = true
                this.modal.src = this.decodeHTML(image.img.large)
            },

            closeModal: function () {
                this.modal.visibility = false
                this.modal.src = false
            },

            deleteImage: function (id) {
                let self = this
                self.removeImage(
                    self.letterId,
                    self.letterType,
                    id,
                    function () {
                        self.deleteRow(id)
                    }
                )
            },

            deleteRow: function (id) {
                let self = this
                self.images = self.images.filter(function (item) {
                    return item.id !== id
                })
            },

            getImages: function () {
                let self = this
                axios
                    .get(ajaxUrl, {
                        params: {
                            action: 'list_images',
                            letter: this.letterId,
                            l_type: this.letterType,
                        },
                    })
                    .then(function (response) {
                        self.title = response.data.data.name
                        self.images = response.data.data.images
                        self.url = response.data.data.url
                    })
                    .catch(function () {
                        self.error = true
                    })
            },

            editImageMetadata: function (image) {
                let self = this
                self.editImageMetadataAjax(image, function () {
                    self.getImages()
                })
            },

            saveImagesOrder: function () {
                let self = this
                Swal.fire(imageHandlerSwal.confirmSave).then((result) => {
                    if (result.value) {
                        let ordered = this.$refs.dnd.realList
                        for (let i = 0; i < ordered.length; i++) {
                            self.saveImageOrder(ordered[i].id, i)
                        }
                        self.getImages()
                        self.orderMode = false
                    }
                })
            },

            registerUppy: function () {
                let self = this
                var uppy = Uppy.Core({
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
                uppy.on('complete', (result) => {
                    if (result.hasOwnProperty('failed')) {
                        let failed = result.failed
                        let err = ''
                        for (let i = 0; i < failed.length; i++) {
                            if ('body' in failed[i].response) {
                                err = JSON.stringify(failed[i].response.body)
                            } else {
                                err = JSON.stringify(failed[i].response)
                            }
                            Swal.fire(errorInfoSwal(err))
                        }
                    }
                    self.getImages()
                })
            },
        },
    })
}
