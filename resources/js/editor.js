/* global Alpine */

import { Editor } from '@tiptap/core'
import StarterKit from '@tiptap/starter-kit'
import Underline from '@tiptap/extension-underline'

document.addEventListener('alpine:init', () => {
    Alpine.data('editor', (content) => {
        let editor

        return {
            isActive(type, opts = {}) {
                return editor.isActive(type, opts)
            },
            setParagraph() {
                editor.chain().focus().setParagraph().run()
            },
            toggleBold() {
                editor.chain().focus().toggleBold().run()
            },
            toggleItalic() {
                editor.chain().focus().toggleItalic().run()
            },
            toggleStrike() {
                editor.chain().focus().toggleStrike().run()
            },
            toggleHeading(level) {
                editor.chain().toggleHeading({ level: level }).focus().run()
            },
            toggleBulletList() {
                editor.chain().focus().toggleBulletList().run()
            },
            toggleOrderedList() {
                editor.chain().focus().toggleOrderedList().run()
            },
            setHorizontalRule() {
                editor.chain().focus().setHorizontalRule().run()
            },
            toggleBlockquote() {
                editor.chain().focus().toggleBlockquote().run()
            },
            toggleUnderline() {
                editor.chain().focus().toggleUnderline().run()
            },
            undo() {
                editor.chain().focus().undo().run()
            },
            redo() {
                editor.chain().focus().redo().run()
            },
            updatedAt: Date.now(),
            init() {
                const self = this

                editor = new Editor({
                    element: self.$refs.editorReference,
                    extensions: [StarterKit, Underline],
                    content: content,
                    onUpdate: ({ editor }) => {
                        self.content = editor.getHTML()
                    },
                    onSelectionUpdate: () => {
                        self.updatedAt = Date.now()
                    },
                })

                window.editor = editor
            },
        }
    })
})
