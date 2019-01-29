/* global Uppy ajaxUrl */

if (document.getElementById('media-handler')) {
    let urlParams = new URLSearchParams(window.location.search)
    let letterId = urlParams.get('letter')
    let letterType = urlParams.get('l_type')

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
                letterType +
                '&letter=' +
                letterId,
        })
}
