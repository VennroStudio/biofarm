import type { Dispatch, FormEvent, SetStateAction } from 'react';
import { Button, ErrorAlert, Field, inputClass, Modal, textareaClass } from '../../../shared/ui';
import { faqScopes, type FaqForm } from '../model/faqForm';

type Props = {
  form: FaqForm;
  open: boolean;
  error?: string | null;
  saving: boolean;
  setForm: Dispatch<SetStateAction<FaqForm>>;
  onClose: () => void;
  onSubmit: (event: FormEvent<HTMLFormElement>) => void;
};

export function FaqFormModal({ form, open, error, saving, setForm, onClose, onSubmit }: Props) {
  return (
    <Modal
      open={open}
      title={form.id ? 'Редактировать вопрос' : 'Новый вопрос FAQ'}
      description="ID/slug нужен только для точечной привязки. Можно указать несколько значений через запятую, точку с запятой или с новой строки."
      maxWidth="max-w-3xl"
      onClose={onClose}
      footer={(
        <>
          <Button type="button" variant="outline" onClick={onClose}>Отмена</Button>
          <Button type="submit" form="admin-faq-form" disabled={saving || !form.question || !form.answer}>
            {saving ? 'Сохранение...' : (form.id ? 'Сохранить' : 'Добавить')}
          </Button>
        </>
      )}
    >
      <form id="admin-faq-form" className="grid gap-4" onSubmit={onSubmit}>
        <ErrorAlert>{error}</ErrorAlert>
        <Field label="Вопрос *">
          <input className={inputClass} value={form.question} onChange={(event) => setForm({ ...form, question: event.target.value })} />
        </Field>
        <Field label="Ответ *">
          <textarea className={textareaClass} value={form.answer} onChange={(event) => setForm({ ...form, answer: event.target.value })} />
        </Field>
        <div className="grid gap-4 md:grid-cols-2">
          <Field label="Где выводить">
            <select className={inputClass} value={form.page_scope} onChange={(event) => setForm({ ...form, page_scope: event.target.value })}>
              {faqScopes.map((scope) => (
                <option key={scope.value} value={scope.value}>{scope.label}</option>
              ))}
            </select>
          </Field>
          <Field label="ID/slug страниц">
            <input className={inputClass} value={form.page_id} onChange={(event) => setForm({ ...form, page_id: event.target.value })} placeholder="ekstrakt-kory-osiny, kapsuly, faq" />
          </Field>
          <Field label="Порядок">
            <input className={inputClass} type="number" value={form.sort_order} onChange={(event) => setForm({ ...form, sort_order: Number(event.target.value) })} />
          </Field>
          <label className="flex items-center gap-3 self-end rounded-md border border-[#cfe2de] bg-[#f5faf8] px-3 py-3 text-sm font-semibold text-[#294555]">
            <input type="checkbox" checked={form.is_active} onChange={(event) => setForm({ ...form, is_active: event.target.checked })} />
            Активен
          </label>
        </div>
      </form>
    </Modal>
  );
}
