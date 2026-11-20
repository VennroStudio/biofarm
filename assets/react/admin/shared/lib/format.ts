const moneyFormatter = new Intl.NumberFormat('ru-RU');

export function formatMoney(value: number) {
  return `${moneyFormatter.format(value)} ₽`;
}

export function formatDate(value: string | Date) {
  return new Date(value).toLocaleDateString('ru-RU');
}
