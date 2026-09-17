import { useState, type FormEvent } from 'react';
import { type Row, money } from '../../program/shared';
import { messageFromError } from '../shared/lib';
import { Button, ErrorAlert, Field, inputClass, Modal } from '../shared/ui';

export function AdminWithdrawalModal({ withdrawal, onClose, onSave }: {
  withdrawal: Row;
  onClose: () => void;
  onSave: (body: Record<string, unknown>) => Promise<void>;
}) {
  const [status, setStatus] = useState('paid');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');
  const editable = ['pending', 'approved'].includes(String(withdrawal.status));
  const details = (withdrawal.details ?? {}) as Record<string, unknown>;
  const stateLabels: Record<string, string> = { pending: 'Ожидает', approved: 'Ожидает перевода', paid: 'Переведено', rejected: 'Отказ' };

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (busy || !editable) return;
    const form = new FormData(event.currentTarget);
    const reference = status === 'paid' ? String(form.get('reference') ?? '').trim() : null;
    const reason = status === 'rejected' ? String(form.get('reason') ?? '').trim() : null;
    if (status === 'paid' ? !reference : !reason) {
      setError(status === 'paid' ? 'Укажите подтверждение перевода.' : 'Укажите причину отказа.');
      return;
    }
    setBusy(true); setError('');
    try { await onSave({ status, reference, reason }); }
    catch (e) { setError(messageFromError(e, 'Не удалось сохранить выплату')); }
    finally { setBusy(false); }
  }

  return <Modal open title={`Выплата: ${withdrawal.user_name || 'Имя не указано'}`}
    description={`${money(withdrawal.amount_minor)} · ${stateLabels[String(withdrawal.status)] ?? withdrawal.status}`}
    onClose={() => { if (!busy) onClose(); }}
    footer={<><Button variant="outline" disabled={busy} onClick={onClose}>Закрыть</Button>
      {editable && <Button type="submit" form="withdrawal-decision" disabled={busy}>{busy ? 'Сохранение…' : 'Сохранить'}</Button>}</>}>
    <div className="space-y-5">
      <ErrorAlert>{error}</ErrorAlert>
      <section className="space-y-3 rounded-xl bg-[#f4faf8] p-4">
        <h3 className="font-semibold">Реквизиты получателя</h3>
        <dl className="grid gap-3 text-sm">
          {([['recipient', 'Получатель'], ['bank', 'Банк'], ['account', 'Счёт получателя']] as const).map(([key, label]) => <div key={key}>
            <dt className="text-[#5f7580]">{label}</dt><dd className="mt-1 break-words font-medium">{String(details[key] || '—')}</dd>
          </div>)}
        </dl>
      </section>
      {editable ? <form id="withdrawal-decision" className="space-y-4" onSubmit={(event) => void submit(event)}>
        <Field label="Действие"><select className={inputClass} value={status} disabled={busy} onChange={(event) => { setStatus(event.target.value); setError(''); }}>
          <option value="paid">Переведено</option><option value="rejected">Отказ</option>
        </select></Field>
        {status === 'paid' ? <Field label="Подтверждение перевода *">
          <input name="reference" className={inputClass} required disabled={busy} placeholder="Номер банковской операции или ссылка на чек" />
          <p className="mt-2 text-sm text-[#5f7580]">Укажите после фактического перевода. Сохранение только фиксирует выплату и не отправляет деньги.</p>
        </Field> : <Field label="Причина *"><textarea name="reason" className={inputClass} required disabled={busy} rows={3} /></Field>}
      </form> : <dl className="space-y-3 text-sm">
        {Boolean(withdrawal.reference) && <div><dt className="text-[#5f7580]">Подтверждение перевода</dt><dd className="mt-1 break-words">{String(withdrawal.reference)}</dd></div>}
        {Boolean(withdrawal.reason) && <div><dt className="text-[#5f7580]">Причина отказа</dt><dd className="mt-1 break-words">{String(withdrawal.reason)}</dd></div>}
      </dl>}
      <p className="break-all text-xs text-[#5f7580]">Заявка {String(withdrawal.id)} · {String(withdrawal.created_at)}</p>
    </div>
  </Modal>;
}
