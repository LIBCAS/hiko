/* global Uppy ajaxUrl */

if (document.getElementById('media-handler')) {
    let urlParams = new URLSearchParams(window.location.search)
    let letterId = urlParams.get('letter')
    let letterType = urlParams.get('l_type')

    Uppy.Core({
        restrictions: {
            maxFileSize: 1000000,
            minNumberOfFiles: 1,
            allowedFileTypes: ['image/jpeg'],
        },
    })
        .use(Uppy.Dashboard, {
            target: '#drag-drop-area',
            inline: true,
            showProgressDetails: true,
            note:
                'Soubory nahrávejte ve formátu .jpg o maximální velikosti 1MB.',
            metaFields: [{ id: 'caption', name: 'Popisek', placeholder: '' }],
            proudlyDisplayPoweredByUppy: false,
            locale: {},
        })

        .use(Uppy.XHRUpload, {
            endpoint:
                ajaxUrl +
                '?action=handle_img_uploads&l_type=' +
                letterType +
                '&letter=' +
                letterId,
        })
}
