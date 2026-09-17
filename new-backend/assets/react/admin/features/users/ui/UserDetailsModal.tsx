import type { FormEvent } from 'react';
import { useState } from 'react';
import { formatDate, formatMoney } from '../../../shared/lib';
import { Badge, Button, ErrorAlert, Field, inputClass, Modal } from '../../../shared/ui';
import type { AdminCustomer } from '../../../types';

type UserForm = {
  firstName: string;
  lastName: string;
  phone: string;
  cardNumber: string;
  referralCode: string;
};

type Props = {
  user: AdminCustomer | null;
  error?: string | null;
  saving: boolean;
  onClose: () => void;
  onSave: (user: AdminCustomer, payload: Record<string, unknown>) => Promise<void>;
};

function toForm(user: AdminCustomer | null): UserForm {
  return {
    firstName: user?.first_name ?? '',
    lastName: user?.last_name ?? '',
    phone: user?.phone ?? '',
    cardNumber: user?.card_number ?? '',
    referralCode: user?.referral_code ?? '',
  };
}

export function UserDetailsModal({ user, error, saving, onClose, onSave }: Props) {
  const [form, setForm] = useState<UserForm>(() => toForm(user));

  if (!user) {
    return null;
  }

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (!user) {
      return;
    }


    await onSave(user, {
      firstName: form.firstName,
      lastName: form.lastName,
      phone: form.phone || null,
      cardNumber: form.cardNumber || null,
      referralCode: form.referralCode || null,
    });
  }

  return (
    <Modal
      open
      title={user.name || user.email}
      description="Профиль, рефералка и бонусы пользователя"
      maxWidth="max-w-3xl"
      onClose={onClose}
      footer={(
        <>
          <Button type="button" variant="outline" onClick={onClose}>Отмена</Button>
          <Button type="submit" form="admin-user-form" disabled={saving || !form.firstName || !form.lastName}>
            {saving ? 'Сохранение...' : 'Сохранить'}
          </Button>
        </>
      )}
    >
      <form id="admin-user-form" className="grid gap-5" onSubmit={(event) => void submit(event)}>
        <ErrorAlert>{error}</ErrorAlert>
        <div className="grid gap-4 md:grid-cols-2">
          <Field label="Имя">
            <input className={inputClass} value={form.firstName} onChange={(event) => setForm({ ...form, firstName: event.target.value })} />
          </Field>
          <Field label="Фамилия">
            <input className={inputClass} value={form.lastName} onChange={(event) => setForm({ ...form, lastName: event.target.value })} />
          </Field>
          <Field label="Телефон">
            <input className={inputClass} value={form.phone} onChange={(event) => setForm({ ...form, phone: event.target.value })} />
          </Field>
          <Field label="Карта для выплат">
            <input className={inputClass} value={form.cardNumber} onChange={(event) => setForm({ ...form, cardNumber: event.target.value })} />
          </Field>
        </div>

        <div className="grid gap-4 rounded-lg border border-[#dfece9] bg-[#f5faf8] p-4 md:grid-cols-2">
          <Field label="Реферальный код">
            <input className={inputClass} value={form.referralCode} onChange={(event) => setForm({ ...form, referralCode: event.target.value })} />
          </Field>
          <div className="text-sm text-[#5f7580]">
            <p>Статус: <b className="text-[#294555]">{user.is_partner ? 'Партнёр' : user.is_team_member ? 'Участник команды' : user.is_referral ? 'Реферал' : 'Пользователь'}</b></p>
            <p>Пригласивший: {user.is_team_member ? user.team_partner_name : user.parent_name || user.referred_by_user_id || '—'}</p>
          </div>
          <div className="text-sm text-[#5f7580]">
            <p>Приглашено: <b className="text-[#294555]">{user.referrals_count}</b></p>
            <p>Оборот рефералов: <b className="text-[#294555]">{formatMoney(user.referral_orders_total)}</b></p>
          </div>
        </div>

        <a className="underline" href="/admin/program">Корректировки балансов с обязательной причиной — в журнале программы</a>

        <div>
          <div className="mb-2 flex items-center justify-between">
            <h4 className="font-semibold text-[#294555]">История бонусов</h4>
            <Badge tone="green">{formatMoney(user.bonus_balance)}</Badge>
          </div>
          <div className="max-h-72 overflow-y-auto rounded-lg border border-[#dfece9] bg-white">
            {user.bonus_transactions.length > 0 ? user.bonus_transactions.map((transaction) => (
              <div key={transaction.id} className="grid gap-2 border-b border-[#dfece9] p-3 text-sm last:border-b-0 md:grid-cols-[120px_1fr_auto]">
                <span className={transaction.amount >= 0 ? 'font-semibold text-[#2e8175]' : 'font-semibold text-[#c24141]'}>
                  {transaction.amount >= 0 ? '+' : ''}{formatMoney(transaction.amount)}
                </span>
                <span>
                  {transaction.comment || transaction.type}
                  {transaction.source_order_id && <span className="ml-2 text-[#5f7580]">Заказ {transaction.source_order_id}</span>}
                  {transaction.source_withdrawal_id && <span className="ml-2 text-[#5f7580]">Выплата {transaction.source_withdrawal_id}</span>}
                </span>
                <span className="text-[#5f7580]">{formatDate(transaction.created_at)}</span>
              </div>
            )) : (
              <p className="p-4 text-sm text-[#5f7580]">Операций по бонусам пока нет</p>
            )}
          </div>
        </div>
      </form>
    </Modal>
  );
}
