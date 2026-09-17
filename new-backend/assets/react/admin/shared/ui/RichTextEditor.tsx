import { useEffect, useState } from 'react';
import { EditorContent, useEditor, useEditorState } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import { TextStyleKit } from '@tiptap/extension-text-style';
import { TableKit } from '@tiptap/extension-table';
import Image from '@tiptap/extension-image';
import TextAlign from '@tiptap/extension-text-align';
import { Bold, Italic, Underline, List, ListOrdered, Link, Unlink, Undo2, Redo2 } from 'lucide-react';
import { Button } from './button';
import { inputClass } from './field';

export default function RichTextEditor({ value, onChange, disabled = false }: { value: string; onChange: (html: string) => void; disabled?: boolean }) {
  const [linkOpen, setLinkOpen] = useState(false);
  const [linkUrl, setLinkUrl] = useState('');
  const [linkError, setLinkError] = useState('');
  const editor = useEditor({
    extensions: [
      StarterKit.configure({ link: { openOnClick: false, defaultProtocol: 'https' } }),
      TextStyleKit,
      TableKit,
      Image,
      TextAlign.configure({ types: ['heading', 'paragraph'] }),
    ],
    content: value,
    editable: !disabled,
    editorProps: {
      attributes: {
        role: 'textbox', 'aria-label': 'Полное описание', 'aria-multiline': 'true',
        class: 'min-h-60 px-4 py-3 text-sm leading-relaxed text-[#294555] outline-none [&_p]:my-2 [&_h1]:text-2xl [&_h2]:text-xl [&_h3]:text-lg [&_h4]:text-base [&_h1]:font-bold [&_h2]:font-bold [&_h3]:font-bold [&_h4]:font-bold [&_ul]:list-disc [&_ul]:pl-6 [&_ol]:list-decimal [&_ol]:pl-6 [&_a]:text-[#2e8175] [&_a]:underline [&_blockquote]:border-l-4 [&_blockquote]:pl-4 [&_table]:w-full [&_td]:border [&_td]:p-2 [&_th]:border [&_th]:p-2 [&_img]:max-w-full [&_pre]:whitespace-pre-wrap',
      },
    },
    // Keep the original HTML untouched until the user edits the document.
    onUpdate: ({ editor: current }) => onChange(current.isEmpty ? '' : current.getHTML()),
  });
  useEffect(() => { editor?.setEditable(!disabled, false); }, [editor, disabled]);
  const state = useEditorState({ editor, selector: ({ editor: current }) => ({
    bold: current?.isActive('bold'), italic: current?.isActive('italic'), underline: current?.isActive('underline'),
    bullet: current?.isActive('bulletList'), ordered: current?.isActive('orderedList'), link: current?.isActive('link'),
    heading: [1, 2, 3, 4, 5, 6].find(level => current?.isActive('heading', { level })) ?? 0,
    undo: current?.can().undo(), redo: current?.can().redo(),
  }) });
  if (!editor) return <p className="p-4 text-sm text-[#5f7580]">Загрузка редактора…</p>;

  function applyLink() {
    const href = linkUrl.trim();
    try {
      const parsed = new URL(href);
      if (!['https:', 'http:', 'mailto:'].includes(parsed.protocol)) throw new Error();
    } catch {
      setLinkError('Укажите полную ссылку, например https://example.ru');
      return;
    }
    editor?.chain().focus().extendMarkRange('link').setLink({ href }).run();
    setLinkOpen(false); setLinkError('');
  }
  const buttons = [
    { label: 'Жирный', icon: Bold, active: state?.bold, run: () => editor.chain().focus().toggleBold().run() },
    { label: 'Курсив', icon: Italic, active: state?.italic, run: () => editor.chain().focus().toggleItalic().run() },
    { label: 'Подчёркнутый', icon: Underline, active: state?.underline, run: () => editor.chain().focus().toggleUnderline().run() },
    { label: 'Маркированный список', icon: List, active: state?.bullet, run: () => editor.chain().focus().toggleBulletList().run() },
    { label: 'Нумерованный список', icon: ListOrdered, active: state?.ordered, run: () => editor.chain().focus().toggleOrderedList().run() },
    { label: 'Добавить ссылку', icon: Link, active: state?.link, run: () => { setLinkUrl(String(editor.getAttributes('link').href ?? '')); setLinkError(''); setLinkOpen(v => !v); } },
    { label: 'Убрать ссылку', icon: Unlink, disabled: !state?.link, run: () => editor.chain().focus().extendMarkRange('link').unsetLink().run() },
    { label: 'Отменить изменение текста', icon: Undo2, disabled: !state?.undo, run: () => editor.chain().focus().undo().run() },
    { label: 'Повторить изменение текста', icon: Redo2, disabled: !state?.redo, run: () => editor.chain().focus().redo().run() },
  ];
  return <div className="overflow-hidden rounded-xl border border-[#cfe2de] bg-white focus-within:border-[#2e8175] focus-within:ring-2 focus-within:ring-[#2e8175]/15">
    <div role="group" aria-label="Форматирование описания" className="flex flex-wrap items-center gap-1 border-b border-[#dfece9] bg-[#f5faf8] p-2">
      <select aria-label="Стиль текста" className={`${inputClass} mr-1 !h-9 !w-36`} value={state?.heading ?? 0} disabled={disabled} onChange={event => {
        const level = Number(event.target.value) as 0 | 1 | 2 | 3 | 4 | 5 | 6;
        if (level) editor.chain().focus().setHeading({ level }).run();
        else editor.chain().focus().setParagraph().run();
      }}>
        <option value="0">Обычный текст</option>
        {[1, 2, 3, 4, 5, 6].map(level => <option key={level} value={level}>Заголовок {level}</option>)}
      </select>
      {buttons.map(item => <Button key={item.label} size="icon" variant={item.active ? 'secondary' : 'ghost'} aria-label={item.label} title={item.label} aria-pressed={item.active} disabled={disabled || item.disabled} onMouseDown={event => event.preventDefault()} onClick={item.run}><item.icon className="h-4 w-4" /></Button>)}
    </div>
    {linkOpen && <div className="space-y-2 border-b border-[#dfece9] bg-[#f5faf8] p-3">
      <div className="flex flex-wrap gap-2">
        <input autoFocus aria-label="Адрес ссылки" className={`${inputClass} min-w-40 flex-1`} placeholder="https://" value={linkUrl} onChange={event => setLinkUrl(event.target.value)} onKeyDown={event => { if (event.key === 'Enter') { event.preventDefault(); applyLink(); } }} />
        <Button size="sm" onClick={applyLink}>Применить ссылку</Button>
        <Button size="sm" variant="ghost" onClick={() => setLinkOpen(false)}>Отмена</Button>
      </div>
      {linkError && <p role="alert" className="text-sm text-red-700">{linkError}</p>}
    </div>}
    <EditorContent editor={editor} />
  </div>;
}
