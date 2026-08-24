import type { Dispatch, FormEvent, SetStateAction } from 'react';
import { Button, ErrorAlert, Field, inputClass, Modal, textareaClass } from '../../../shared/ui';
import type { Product } from '../../../types';
import type { CertificateForm } from '../model/certificateForm';
import { CertificateFileUploader } from './CertificateFileUploader';

type Props = {
  form: CertificateForm;
  open: boolean;
  products: Product[];
  error?: string | null;
  saving: boolean;
  setForm: Dispatch<SetStateAction<CertificateForm>>;
  onClose: () => void;
  onSubmit: (event: FormEvent<HTMLFormElement>) => void;
};

export function CertificateFormModal({ form, open, products, error, saving, setForm, onClose, onSubmit }: Props) {
  return (
    <Modal
      open={open}
      title={form.id ? 'Редактировать сертификат' : 'Новый сертификат'}
      description="Сертификат можно оставить общим или привязать к конкретному товару"
      maxWidth="max-w-3xl"
      onClose={onClose}
      footer={(
        <>
          <Button type="button" variant="outline" onClick={onClose}>Отмена</Button>
          <Button type="submit" form="admin-certificate-form" disabled={saving || !form.title || !form.file_path}>
            {saving ? 'Сохранение...' : (form.id ? 'Сохранить' : 'Добавить')}
          </Button>
        </>
      )}
    >
      <form id="admin-certificate-form" className="grid gap-4" onSubmit={onSubmit}>
        <ErrorAlert>{error}</ErrorAlert>
        <div className="grid gap-4 md:grid-cols-2">
          <Field label="Название *">
            <input className={inputClass} value={form.title} onChange={(event) => setForm({ ...form, title: event.target.value })} />
          </Field>
          <Field label="Товар">
            <select className={inputClass} value={form.product_id} onChange={(event) => setForm({ ...form, product_id: event.target.value })}>
              <option value="">Общий сертификат</option>
              {products.map((product) => (
                <option key={product.id} value={product.id}>{product.name}</option>
              ))}
            </select>
          </Field>
          <Field label="Файл *">
            <div className="grid gap-2">
              <input className={inputClass} value={form.file_path} readOnly placeholder="Загрузите файл" />
              <CertificateFileUploader onUploaded={(url, documentType) => setForm((current) => ({ ...current, file_path: url, document_type: documentType }))} />
            </div>
          </Field>
          <Field label="Тип документа">
            <select className={inputClass} value={form.document_type} onChange={(event) => setForm({ ...form, document_type: event.target.value as CertificateForm['document_type'] })}>
              <option value="pdf">PDF</option>
              <option value="image">Изображение</option>
            </select>
          </Field>
          <Field label="Порядок">
            <input className={inputClass} type="number" value={form.sort_order} onChange={(event) => setForm({ ...form, sort_order: Number(event.target.value) })} />
          </Field>
          <label className="flex items-center gap-3 self-end rounded-md border border-[#d9dece] bg-[#fbfaf4] px-3 py-3 text-sm font-semibold text-[#26382d]">
            <input type="checkbox" checked={form.is_active} onChange={(event) => setForm({ ...form, is_active: event.target.checked })} />
            Активен
          </label>
        </div>
        <Field label="Описание">
          <textarea className={textareaClass} value={form.description} onChange={(event) => setForm({ ...form, description: event.target.value })} />
        </Field>
      </form>
    </Modal>
  );
}
