const Quill = require('Quill')

const QuillDeltaToHtmlConverter =
    require('quill-delta-to-html').QuillDeltaToHtmlConverter

window.editor = function () {
    return {
        quill: null,
        initEditor: () => {
            this.quill = new Quill('#editor', {
                theme: 'snow',
                modules: {
                    toolbar: [
                        [{ header: [1, 2, 3, 4, false] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        ['blockquote', { list: 'ordered' }, { list: 'bullet' }],
                        [{ indent: '-1' }, { indent: '+1' }],
                        [{ align: [] }],
                        ['clean'],
                    ],
                },
            })
        },
        getContent: () => {
            const converter = new QuillDeltaToHtmlConverter(
                this.quill.getContents().ops
            )

            return converter.convert()
        },
    }
}
