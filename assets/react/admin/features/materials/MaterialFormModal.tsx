import { lazy, Suspense, useEffect, useId, useRef, useState, type FormEvent } from 'react';
import { Button, ErrorAlert, Field, inputClass, Modal, textareaClass } from '../../shared/ui';
import { messageFromError } from '../../shared/lib';
import { CertificateFileUploader } from '../certificates/ui/CertificateFileUploader';
import { materialsApi } from './api';
import { PlacementPicker } from './PlacementPicker';
import type { Material, MaterialCategory, MaterialKind, MaterialPayload, Placement } from './types';

const RichTextEditor = lazy(() => import('../../shared/ui/RichTextEditor'));

type Props = { kind: MaterialKind; open: boolean; id?: number; onClose: () => void; onSaved: (item: Material) => void; showUsage?: boolean; initialTab?: 'main' | 'usage' };
export function MaterialFormModal(props: Props) {
  return props.open ? <MaterialFormSession key={`${props.kind}:${props.id ?? 'new'}`} {...props} /> : null;
}
function MaterialFormSession({ kind, id, onClose, onSaved, showUsage = true, initialTab = 'main' }: Props) {
  const formId = useId();
  const savedId = useRef(id);
  const [form, setForm] = useState<MaterialPayload>({ title: '', question: '', answer: '', file_path: '', document_type: 'pdf', description: '', category_id: null, is_active: true });
  const [placements, setPlacements] = useState<Placement[]>([]);
  const [categories, setCategories] = useState<MaterialCategory[]>([]);
  const [tab, setTab] = useState(initialTab);
  const [usageCount, setUsageCount] = useState(0);
  const [loaded, setLoaded] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  useEffect(() => {
    let active = true;
    Promise.all([kind === 'certificate' ? materialsApi.categories(kind) : Promise.resolve({ items: [] }), id ? materialsApi.get(kind, id) : Promise.resolve(null)]).then(([result, item]) => {
      if (!active) return;
      setCategories(result.items);
      if (item) {
        setForm({ title: item.title, question: item.question ?? item.title, answer: item.answer ?? '', file_path: item.file_path ?? '', document_type: item.document_type ?? 'pdf', description: item.description ?? '', category_id: item.category_id, is_active: item.is_active });
        setPlacements(item.placements ?? []);
        setUsageCount(item.usage_count);
      }
      setLoaded(true);
    }).catch((e) => { if (active) setError(messageFromError(e, 'Не удалось загрузить запись')); });
    return () => { active = false; };
  }, [kind, id]);
  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault(); event.stopPropagation();
    if (!loaded || saving) return;
    setSaving(true); setError(null);
    try {
      const payload = { ...form, ...(showUsage ? { placements: placements.map(({ target_type, target_id }) => ({ target_type, target_id })) } : {}) };
      const result = savedId.current ? await materialsApi.update(kind, savedId.current, payload) : await materialsApi.create(kind, payload);
      savedId.current = result.id;
      const item = await materialsApi.get(kind, result.id);
      onSaved(item);
    } catch (e) { setError(messageFromError(e, 'Не удалось сохранить запись')); } finally { setSaving(false); }
  }
  const valid = kind === 'certificate' ? Boolean(form.title?.trim() && form.file_path) : Boolean(form.question?.trim() && form.answer?.trim());
  return <Modal open title={kind === 'certificate' ? (id ? 'Редактировать сертификат' : 'Новый сертификат') : (id ? 'Редактировать вопрос' : 'Новый вопрос FAQ')} maxWidth="max-w-3xl" onClose={() => { if (!saving) onClose(); }}
    headerContent={showUsage && <div className="flex gap-2 p-3">{([['main', 'Основное'], ['usage', `Где используется (${placements.length})`]] as const).map(([key, title]) => <Button key={key} type="button" variant={tab === key ? 'primary' : 'ghost'} onClick={() => setTab(key)}>{title}</Button>)}</div>}
    footer={<><Button type="button" variant="outline" disabled={saving} onClick={onClose}>Отмена</Button><Button type="submit" form={formId} disabled={!loaded || saving || !valid}>{saving ? 'Сохранение...' : 'Сохранить'}</Button></>}>
    <form id={formId} onSubmit={(event) => void submit(event)} className="grid gap-4">
      <ErrorAlert>{error}</ErrorAlert>
      {loaded && usageCount > 0 && <p className="rounded-xl bg-amber-50 p-3 text-sm text-amber-900">Изменения отобразятся во всех местах использования ({usageCount}).</p>}
      {!loaded ? <p className="text-sm text-[#5f7580]">{error ? 'Закройте окно и повторите попытку.' : 'Загрузка...'}</p> : <fieldset disabled={saving} className="min-w-0 grid gap-4">
        {tab === 'main' ? <>
          <Field label={kind === 'certificate' ? 'Название *' : 'Вопрос *'}><input required className={inputClass} value={kind === 'certificate' ? form.title : form.question} onChange={(e) => setForm({ ...form, [kind === 'certificate' ? 'title' : 'question']: e.target.value })} /></Field>
          {kind === 'certificate' ? <><Field label="Файл *"><div className="grid gap-2"><input className={inputClass} readOnly value={form.file_path} placeholder="Загрузите документ" /><CertificateFileUploader onUploaded={(url, documentType) => setForm((current) => ({ ...current, file_path: url, document_type: documentType }))} /></div></Field><Field label="Описание"><textarea className={textareaClass} value={form.description ?? ''} onChange={(e) => setForm({ ...form, description: e.target.value })} /></Field></> : <Field label="Ответ *"><Suspense fallback={<p className="text-sm">Загрузка редактора...</p>}><RichTextEditor label="Ответ" value={form.answer ?? ''} disabled={saving} onChange={(answer) => setForm((current) => ({ ...current, answer }))} /></Suspense></Field>}
          {kind === 'certificate' && <Field label="Категория"><select className={inputClass} value={form.category_id ?? ''} onChange={(e) => setForm({ ...form, category_id: e.target.value ? Number(e.target.value) : null })}><option value="">Без категории</option>{categories.map((category) => <option key={category.id} value={category.id}>{category.name}</option>)}</select></Field>}
          <label className="flex items-center gap-3 text-sm font-semibold"><input type="checkbox" checked={form.is_active} onChange={(e) => setForm({ ...form, is_active: e.target.checked })} />Активен</label><p className="text-xs text-[#5f7580]">Неактивная запись скрыта на сайте. Места использования сохраняются.</p>
        </> : <PlacementPicker value={placements} onChange={setPlacements} />}
      </fieldset>}
    </form>
  </Modal>;
}
