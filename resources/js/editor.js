const Quill = require('quill').default;

const QuillDeltaToHtmlConverter =
    require('quill-delta-to-html').QuillDeltaToHtmlConverter

window.editor = function (selector = '#editor') {
    return {
        quill: null,
        initEditor() {
            if (this.quill) {
                return
            }

            this.quill = new Quill(selector, {
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
        getContent() {
            const converter = new QuillDeltaToHtmlConverter(
                this.quill.getContents().ops
            )

            return converter.convert()
        },

        getPlainText() {
            return this.quill.getText().replace(/(\r\n|\n|\r)/g, ' ')
        },

        setPlainText(text) {
            if (!this.quill || typeof text !== 'string') {
                return
            }

            this.quill.setText(text, 'api')
        },
    }
}
