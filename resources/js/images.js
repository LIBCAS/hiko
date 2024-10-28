import * as FilePond from 'filepond'
import Sortable from 'sortablejs/modular/sortable.core.esm.js'
import FilePondPluginFileValidateSize from 'filepond-plugin-file-validate-size'
import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type'
import FilePondPluginImagePreview from 'filepond-plugin-image-preview'

FilePond.registerPlugin(FilePondPluginImagePreview)
FilePond.registerPlugin(FilePondPluginFileValidateType)
FilePond.registerPlugin(FilePondPluginFileValidateSize)

window.FilePond = FilePond
window.Sortable = Sortable
