import type { Dispatch, FormEvent, SetStateAction } from 'react';
import { Button, ErrorAlert, Field, inputClass, Modal } from '../../../shared/ui';
import type { PromoCodeForm } from '../model/promoCodeForm';

type Props = {
  form: PromoCodeForm;
  open: boolean;
  error?: string | null;
  saving: boolean;
  setForm: Dispatch<SetStateAction<PromoCodeForm>>;
  onClose: () => void;
  onSubmit: (event: FormEvent<HTMLFormElement>) => void;
};

export function PromoCodeFormModal({ form, open, error, saving, setForm, onClose, onSubmit }: Props) {
  return (
    <Modal
      open={open}
      title={form.id ? 'Редактировать промокод' : 'Новый промокод'}
      description="Промокод применяется на checkout, если включены заказы и промокоды"
      onClose={onClose}
      footer={(
        <>
          <Button type="button" variant="outline" onClick={onClose}>Отмена</Button>
          <Button type="submit" form="admin-promo-code-form" disabled={saving || !form.code || form.value <= 0}>
            {saving ? 'Сохранение...' : (form.id ? 'Сохранить' : 'Добавить')}
          </Button>
        </>
      )}
    >
      <form id="admin-promo-code-form" className="grid gap-4" onSubmit={onSubmit}>
        <ErrorAlert>{error}</ErrorAlert>
        <div className="grid gap-4 md:grid-cols-2">
          <Field label="Код *">
            <input
              className={inputClass}
              value={form.code}
              onChange={(event) => setForm({ ...form, code: event.target.value.toUpperCase() })}
              placeholder="BIOFARM10"
            />
          </Field>
          <Field label="Тип скидки">
            <select className={inputClass} value={form.type} onChange={(event) => setForm({ ...form, type: event.target.value as PromoCodeForm['type'] })}>
              <option value="percent">Процент</option>
              <option value="fixed">Фиксированная сумма</option>
            </select>
          </Field>
          <Field label={form.type === 'percent' ? 'Скидка, % *' : 'Скидка, ₽ *'}>
            <input
              className={inputClass}
              type="number"
              min={1}
              max={form.type === 'percent' ? 100 : undefined}
              value={form.value}
              onChange={(event) => setForm({ ...form, value: Number(event.target.value) })}
            />
          </Field>
          <Field label="Минимальная сумма заказа, ₽">
            <input
              className={inputClass}
              type="number"
              min={0}
              value={form.min_order_total}
              onChange={(event) => setForm({ ...form, min_order_total: Number(event.target.value) })}
            />
          </Field>
          <Field label="Начало действия">
            <input className={inputClass} type="datetime-local" value={form.starts_at} onChange={(event) => setForm({ ...form, starts_at: event.target.value })} />
          </Field>
          <Field label="Окончание действия">
            <input className={inputClass} type="datetime-local" value={form.ends_at} onChange={(event) => setForm({ ...form, ends_at: event.target.value })} />
          </Field>
          <Field label="Лимит использований">
            <input
              className={inputClass}
              type="number"
              min={1}
              value={form.usage_limit}
              onChange={(event) => setForm({ ...form, usage_limit: event.target.value })}
              placeholder="Без лимита"
            />
          </Field>
          <label className="flex items-center gap-3 self-end rounded-md border border-[#cfe2de] bg-[#f5faf8] px-3 py-3 text-sm font-semibold text-[#294555]">
            <input
              type="checkbox"
              checked={form.is_active}
              onChange={(event) => setForm({ ...form, is_active: event.target.checked })}
            />
            Активен
          </label>
        </div>
      </form>
    </Modal>
  );
}
