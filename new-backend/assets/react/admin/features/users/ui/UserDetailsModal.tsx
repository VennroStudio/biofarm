import { useEffect, useRef, useState, type FormEvent, type KeyboardEvent } from 'react';
import { UserRound, ShoppingBag, Users, Network, Gift, Coins, Wallet, UserCheck } from 'lucide-react';
import { formatDate } from '../../../shared/lib';
import { Badge, Button, ErrorAlert, Field, inputClass, Modal } from '../../../shared/ui';
import type { AdminCustomer } from '../../../types';
import { UserActivity, type UserSection } from './UserActivity';

const tabs = [
  { id: 'profile', title: 'Профиль', icon: UserRound },
  { id: 'orders', title: 'Заказы', icon: ShoppingBag },
  { id: 'referrals', title: 'Рефералы', icon: Users },
  { id: 'team', title: 'Команда', icon: Network },
  { id: 'shopping', title: 'Бонусы', icon: Gift },
  { id: 'commission', title: 'Комиссии', icon: Coins },
  { id: 'withdrawals', title: 'Выплаты', icon: Wallet },
] as const;
function toForm(user: AdminCustomer) {
  return { firstName: user.first_name, lastName: user.last_name, phone: user.phone ?? '', referralCode: user.referral_code ?? '' };
}
type Props = {
  user: AdminCustomer;
  error?: string | null;
  saving: boolean;
  changingPartner: boolean;
  onClose: () => void;
  onTogglePartner: (user: AdminCustomer) => void;
  onSave: (user: AdminCustomer, payload: Record<string, unknown>) => Promise<void>;
};
export function UserDetailsModal({ user, error, saving, changingPartner, onClose, onSave, onTogglePartner }: Props) {
  const [form, setForm] = useState(() => toForm(user));
  const [initial] = useState(() => JSON.stringify(toForm(user)));
  const [activeTab, setActiveTab] = useState<UserSection>('profile');
  const [confirmClose, setConfirmClose] = useState(false);
  const bodyRef = useRef<HTMLFormElement>(null);
  const dirty = JSON.stringify(form) !== initial;
  const busy = saving || changingPartner;
  const visibleTabs = tabs.filter(tab => tab.id !== 'team' || user.is_partner);
  const currentTab = activeTab === 'team' && !user.is_partner ? 'profile' : activeTab;
  const role = user.is_partner ? 'Партнёр' : user.is_team_member ? 'Участник команды' : user.is_referral ? 'Реферал' : 'Пользователь';
  useEffect(() => {
    if (!dirty) return;
    const warn = (event: BeforeUnloadEvent) => { event.preventDefault(); event.returnValue = ''; };
    window.addEventListener('beforeunload', warn);
    return () => window.removeEventListener('beforeunload', warn);
  }, [dirty]);
  function close() { if (!busy) { if (dirty) setConfirmClose(true); else onClose(); } }
  function changeTab(tab: UserSection) { setActiveTab(tab); bodyRef.current?.parentElement?.scrollTo({ top: 0 }); }
  function tabKey(event: KeyboardEvent<HTMLButtonElement>, index: number) {
    const next = event.key === 'ArrowRight' ? (index + 1) % visibleTabs.length : event.key === 'ArrowLeft' ? (index + visibleTabs.length - 1) % visibleTabs.length : event.key === 'Home' ? 0 : event.key === 'End' ? visibleTabs.length - 1 : null;
    if (next === null) return;
    event.preventDefault(); changeTab(visibleTabs[next].id); document.getElementById(`user-tab-${visibleTabs[next].id}`)?.focus();
  }
  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (busy || !form.firstName.trim() || !form.lastName.trim()) { changeTab('profile'); return; }
    await onSave(user, { ...form, phone: form.phone || null, referralCode: form.referralCode || null });
  }
  return <Modal open title={user.name || user.email} description={`${role} · ${user.email}`} maxWidth="max-w-6xl" onClose={close}
    headerContent={<div role="tablist" aria-label="Данные пользователя" className="flex gap-1 overflow-x-auto bg-[#f5faf8] px-3 py-2 sm:px-5">{visibleTabs.map((tab,index) => <button key={tab.id} type="button" role="tab" id={`user-tab-${tab.id}`} aria-controls={`user-panel-${tab.id}`} aria-selected={currentTab === tab.id} tabIndex={currentTab === tab.id ? 0 : -1} onClick={() => changeTab(tab.id)} onKeyDown={event => tabKey(event,index)} className={`inline-flex shrink-0 items-center gap-2 rounded-xl px-3 py-3 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2e8175] ${currentTab === tab.id ? 'bg-[#2e8175] text-white shadow-sm' : 'text-[#526d78] hover:bg-[#e5f1ee]'}`}><tab.icon className="h-4 w-4" />{tab.title}</button>)}</div>}
    footer={confirmClose ? <><p className="mr-auto text-sm">Закрыть без сохранения изменений?</p><Button type="button" variant="outline" onClick={() => setConfirmClose(false)}>Продолжить</Button><Button type="button" variant="danger" onClick={onClose}>Закрыть без сохранения</Button></> : <><span className="mr-auto self-center text-xs text-[#5f7580]">{dirty ? 'Есть несохранённые изменения профиля' : 'История и балансы доступны для просмотра'}</span><Button type="button" variant="outline" disabled={busy} onClick={close}>Закрыть</Button><Button type="submit" form="admin-user-form" disabled={busy || !dirty || !form.firstName.trim() || !form.lastName.trim()}>{saving ? 'Сохранение…' : 'Сохранить'}</Button></>}>
    <form ref={bodyRef} id="admin-user-form" onSubmit={event => void submit(event)} className="min-h-[min(25rem,45dvh)] space-y-5">
      <ErrorAlert>{error}</ErrorAlert>
      <div role="tabpanel" id={`user-panel-${currentTab}`} aria-labelledby={`user-tab-${currentTab}`} className="space-y-6">
        {currentTab === 'profile' && <>
          <fieldset disabled={busy} className="grid gap-4 rounded-2xl border border-[#dfece9] p-5 sm:grid-cols-2">
            <legend className="px-2 font-semibold">Личные данные</legend>
            <Field label="Имя *"><input className={inputClass} value={form.firstName} onChange={event => setForm({ ...form, firstName: event.target.value })} /></Field>
            <Field label="Фамилия *"><input className={inputClass} value={form.lastName} onChange={event => setForm({ ...form, lastName: event.target.value })} /></Field>
            <Field label="Телефон"><input className={inputClass} type="tel" value={form.phone} onChange={event => setForm({ ...form, phone: event.target.value })} /></Field>
            <div className="text-sm"><p className="text-[#5f7580]">Дата регистрации</p><p className="mt-2 font-medium">{formatDate(user.created_at)}</p></div>
          </fieldset>
          <section className="space-y-4 rounded-2xl border border-[#dfece9] p-5">
            <div className="flex flex-wrap items-center justify-between gap-3"><h3 className="font-semibold">Участие в программе</h3><Badge tone={user.is_partner ? 'green' : 'gray'}>{role}</Badge></div>
            {user.is_team_member && <p className="text-sm">Партнёр команды: <b>{user.team_partner_name || '—'}</b></p>}
            {!user.is_team_member && user.parent_name && <p className="text-sm">Пригласивший: <b>{user.parent_name}</b></p>}
            <Field label="Реферальный код"><input className={inputClass} disabled={busy} value={form.referralCode} onChange={event => setForm({ ...form, referralCode: event.target.value })} /></Field>
            <div className="flex flex-wrap items-start gap-4"><Button type="button" variant={user.is_partner ? 'outline' : 'primary'} disabled={busy || dirty} onClick={() => onTogglePartner(user)}><UserCheck className="h-4 w-4" />{changingPartner ? 'Сохранение…' : user.is_partner ? 'Снять статус партнёра' : 'Сделать партнёром'}</Button><p className="max-w-lg text-xs leading-relaxed text-[#5f7580]">{dirty ? 'Сначала сохраните изменения профиля.' : user.is_partner ? 'Рефералы и история начислений сохранятся. Прежний пригласивший не восстанавливается.' : 'При назначении пользователь выйдет из прежней команды вместе со своими рефералами. История начислений сохранится.'}</p></div>
          </section>
        </>}

        <UserActivity key={`${user.id}:${currentTab}:${user.is_partner}`} userId={user.id} section={currentTab} revision={user.is_partner} />
      </div>
    </form>
  </Modal>;
}
