export function formatMoney(value: number) {
  return `${Math.round(value).toLocaleString('ru-RU')} ₽`;
}

export function formatDate(value?: string | null) {
  if (!value) {
    return 'Не указано';
  }

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return value;
  }

  return date.toLocaleDateString('ru-RU');
}

export function pluralProduct(count: number) {
  const normalized = Math.abs(count) % 100;
  const last = normalized % 10;

  if (normalized > 10 && normalized < 20) {
    return 'товаров';
  }

  if (last === 1) {
    return 'товар';
  }

  if (last > 1 && last < 5) {
    return 'товара';
  }

  return 'товаров';
}
