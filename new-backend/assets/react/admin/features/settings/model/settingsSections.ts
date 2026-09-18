export const defaultSettingsSection = 'features';

export const settingsSections = [
  {
    id: 'features',
    label: 'Функции',
    subtitle: 'Включение и отключение крупных возможностей сайта',
  },
  {
    id: 'seo',
    label: 'SEO и robots',
    subtitle: 'Метаданные, Open Graph, robots.txt и индексация',
  },
  {
    id: 'integrations',
    label: 'Интеграции',
    subtitle: 'Подключения внешних сервисов и журнал ошибок интеграций',
  },
  {
    id: 'orders',
    label: 'Заказы и доставка',
    subtitle: 'Доставка, промокоды и письма по заказам',
  },
  {
    id: 'security',
    label: 'Безопасность',
    subtitle: 'Смена пароля администратора',
  },
] as const;

export type SettingsSectionId = typeof settingsSections[number]['id'];

export function isSettingsSectionId(value: string | undefined): value is SettingsSectionId {
  return settingsSections.some((section) => section.id === value);
}

export function settingsSectionPath(section: SettingsSectionId): string {
  return `/admin/settings/${section}`;
}
