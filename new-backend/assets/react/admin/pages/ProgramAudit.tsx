import { money, type Row } from '../../program/shared';

const settings: Record<string, string> = {
  partnerDirectBps: 'Партнёру с покупок своего реферала',
  partnerMemberBps: 'Партнёру с личных покупок участника команды',
  partnerTeamBps: 'Партнёру с покупок реферала участника команды',
  memberDirectBps: 'Участнику команды с покупок своего реферала',
  referralBonusBps: 'Бонусы за приглашённого покупателя',
  buyerBps: 'Бонусы за собственную покупку',
  holdDays: 'Удержание после доставки',
  minimumWithdrawalMinor: 'Минимальная выплата',
  directBps: 'Общая прямая комиссия (прежняя настройка)',
  teamBps: 'Комиссия партнёру за команду (прежняя настройка)',
  partnerBps: 'Комиссия партнёру (прежняя настройка)',
  levelsBps: 'Комиссии по уровням (прежняя настройка)',
  capBps: 'Лимит вознаграждений (прежняя настройка)',
  products: 'Коэффициенты товаров (прежняя настройка)',
  maxPromoPercent: 'Максимальная скидка промокода (прежняя настройка)',
};
const statuses: Record<string, string> = { paid: 'Переведено', rejected: 'Отказ', approved: 'Одобрено по прежней схеме', pending: 'Ожидает' };
const percent = (value: unknown) => `${new Intl.NumberFormat('ru-RU').format(Number(value) / 100)} %`;
const payloadOf = (row: Row) => (row.payload ?? {}) as Row;
const nameOf = (row: Row, id: unknown) => {
  const names = (row.related_names ?? {}) as Record<string, string>;
  return names[String(id)] || (id == null ? 'Имя не указано' : `Пользователь №${String(id)} (имя недоступно)`);
};

export function auditAction(row: Row): string {
  const p = payloadOf(row);
  switch (row.kind) {
    case 'settings': return 'Сохранены настройки программы';
    case 'team_joined': return `Вступление в команду партнёра: ${nameOf(row, p.partnerId)}`;
    case 'adjustment': return `Изменён баланс: ${nameOf(row, p.userId)}`;
    case 'withdrawal': return `${statuses[String(p.status)] ?? 'Изменён статус выплаты'}: ${nameOf(row, row.withdrawal_user_id)}`;
    case 'partner_status': return `${p.isPartner ? 'Назначен партнёром' : 'Снят статус партнёра'}: ${nameOf(row, p.userId)}`;
    case 'referral_attached': return `Закреплён пригласивший за покупателем: ${nameOf(row, p.userId)}`;
    case 'demo_seed': return 'Подготовлены тестовые данные';
    default: return 'Служебное событие';
  }
}

export function AuditDetails({ row }: { row: Row }) {
  const p = payloadOf(row);
  const fields: [string, string][] = [];
  const add = (title: string, value: unknown) => { if (value !== undefined && value !== null && value !== '') fields.push([title, String(value)]); };
  if (row.kind === 'settings') {
    for (const [key, value] of Object.entries(p)) {
      let formatted: string;
      if (key === 'levelsBps' && Array.isArray(value)) formatted = value.map((rate, i) => `${i + 1}-й уровень: ${percent(rate)}`).join('; ');
      else if (key.endsWith('Bps')) formatted = percent(value);
      else if (key === 'minimumWithdrawalMinor') formatted = money(value);
      else if (key === 'holdDays') formatted = `${String(value)} дн.`;
      else if (key === 'products' && typeof value === 'object' && value !== null) formatted = Object.entries(value).map(([id, coefficient]) => `Товар №${id}: ${percent(coefficient)}`).join('; ') || 'Не заданы';
      else formatted = typeof value === 'object' ? JSON.stringify(value) : String(value);
      add(settings[key] ?? key, formatted);
    }
  } else {
    if (row.kind === 'withdrawal') {
      add('Получатель', nameOf(row, row.withdrawal_user_id));
      if (row.withdrawal_amount_minor != null) add('Сумма выплаты', money(row.withdrawal_amount_minor));
      add('Статус', statuses[String(p.status)] ?? p.status);
      add('Подтверждение перевода', p.reference);
      add('Номер заявки', p.id);
    }
    if (row.kind === 'adjustment') {
      add('Получатель', nameOf(row, p.userId));
      add('Счёт', p.wallet === 'commission' ? 'Денежные комиссии' : p.wallet === 'shopping' ? 'Покупательские бонусы' : p.wallet);
      add('Изменение баланса', `${Number(p.amountMinor) > 0 ? '+' : ''}${money(p.amountMinor)}`);
    }
    if (row.kind === 'team_joined') add('Партнёр', nameOf(row, p.partnerId));
    if (row.kind === 'partner_status') add('Пользователь', nameOf(row, p.userId));
    if (row.kind === 'referral_attached') {
      add('Покупатель', nameOf(row, p.userId)); add('Пригласивший', nameOf(row, p.parentId)); add('Заказ', p.orderId);
    }
    if (row.kind === 'demo_seed') {
      if (Array.isArray(p.orders)) add('Тестовые заказы', p.orders.join(', '));
      if (Array.isArray(p.chain)) add('Тестовая цепочка', p.chain.map(id => nameOf(row, id)).join(' → '));
      if (Array.isArray(p.archivedDemoUsers)) add('Архивированные тестовые пользователи', p.archivedDemoUsers.map(id => nameOf(row, id)).join(', '));
    }
    add('Причина', p.reason);
  }
  return <details className="min-w-48 max-w-xl">
    <summary className="cursor-pointer font-medium text-primary">Подробности</summary>
    <div className="mt-3 space-y-3 rounded-xl bg-[#f4faf8] p-4">
      {row.kind === 'settings' && <p className="text-xs text-muted-foreground">Значения, сохранённые в этот момент. Прежние настройки показаны только для истории.</p>}
      {fields.length ? <dl className="space-y-3">{fields.map(([title, value]) => <div key={title}><dt className="text-xs text-muted-foreground">{title}</dt><dd className="mt-1 whitespace-pre-wrap break-words">{value}</dd></div>)}</dl> : <p>Дополнительное описание отсутствует. Код события: {String(row.kind)}.</p>}
    </div>
  </details>;
}
