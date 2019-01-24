/* global Uppy */
// global Vue axios ajaxUrl Uppy

if (document.getElementById('media-handler')) {
    Uppy.Core({
        restrictions: {
            maxFileSize: 1000000,
            minNumberOfFiles: 1,
            allowedFileTypes: ['image/jpeg']
        }
    })
        .use(Uppy.Dashboard, {
            target: '#drag-drop-area',
            inline: true,
            showProgressDetails: true,
            note: 'Soubory nahrávejte ve formátu .jpg o maximální velikosti 1MB.',
            metaFields: [
                { id: 'caption', name: 'Popisek', placeholder: '' }
            ],
            proudlyDisplayPoweredByUppy: false,
            locale: {},
        });
}
