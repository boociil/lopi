import './styles.scss'

import Emoji, { gitHubEmojis } from '@tiptap/extension-emoji'
import { EditorContent, useEditor } from '@tiptap/react'
import StarterKit from '@tiptap/starter-kit'
import React from 'react'

import suggestion from './suggestion.js'

export default ({html, setHtml}) => {
  const editor = useEditor({
    extensions: [
      StarterKit,
      Emoji.configure({
        emojis: gitHubEmojis,
        enableEmoticons: true,
        suggestion,
        HTMLAttributes: {
            class: 'my-custom-class',
        },
      }),
    ],
    content: html,
    onUpdate: () => {
        setHtml(editor.getHTML())
    }
  })

  return (
    <>
     <div className="border border-gray-400 rounded appearance-none">
        <div v-if="editor" className="border-b border-gray-600 p-1 text-xs gap-1">
            <button onClick={()=> {editor.chain().focus().toggleBold().run()}}
                className={editor.isActive('bold') ? 'is-active bg-yellow-300 p-1 mr-1' : 'bg-yellow-100 p-1 mr-1'}
                type="button">
                bold
            </button>
            <button onClick={()=> {editor.chain().focus().toggleItalic().run()}}
                className={editor.isActive('italic') ? 'is-active bg-yellow-300 p-1 mr-1' : 'bg-yellow-100 p-1 mr-1'}
                type="button">
                italic
            </button>
        </div>
         <EditorContent editor={editor} className="bg-gray-100 p-2"/>
    </div>

    </>
  )
}
