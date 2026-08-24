import {
  Award,
  Building2,
  Gift,
  LayoutDashboard,
  Mail,
  Newspaper,
  ShoppingBag,
  Sparkles,
  Star,
  Store,
  Video,
} from 'lucide-react';
import type { ComponentType } from 'react';
import { Card } from '../../../shared/ui';
import type { Settings } from '../../../types';

type Props = {
  settings: Settings;
  onChange: (settings: Settings) => void;
};

type HomeBlockKey =
  | 'home_about_enabled'
  | 'home_blog_enabled'
  | 'home_catalog_enabled'
  | 'home_certificates_enabled'
  | 'home_contacts_enabled'
  | 'home_features_enabled'
  | 'home_loyalty_enabled'
  | 'home_marketplaces_enabled'
  | 'home_reviews_enabled'
  | 'home_video_enabled';

type ToggleItem = {
  key: HomeBlockKey;
  label: string;
  description: string;
  icon: ComponentType<{ className?: string }>;
};

const items: ToggleItem[] = [
  {
    key: 'home_features_enabled',
    label: 'Преимущества',
    description: 'Блок с карточками преимуществ под первым экраном.',
    icon: Sparkles,
  },
  {
    key: 'home_catalog_enabled',
    label: 'Каталог на главной',
    description: 'Подборка товаров и переход в полный каталог.',
    icon: ShoppingBag,
  },
  {
    key: 'home_video_enabled',
    label: 'Видео',
    description: 'Производственный видеоблок.',
    icon: Video,
  },
  {
    key: 'home_blog_enabled',
    label: 'Блог',
    description: 'Последние статьи на главной.',
    icon: Newspaper,
  },
  {
    key: 'home_about_enabled',
    label: 'О компании',
    description: 'Текстовый блок о БИОФАРМ.',
    icon: Building2,
  },
  {
    key: 'home_marketplaces_enabled',
    label: 'Маркетплейсы',
    description: 'Карточки Wildberries и Ozon.',
    icon: Store,
  },
  {
    key: 'home_certificates_enabled',
    label: 'Сертификаты',
    description: 'Документы качества с переходом на страницу сертификатов.',
    icon: Award,
  },
  {
    key: 'home_loyalty_enabled',
    label: 'Лояльность',
    description: 'Блок бонусов, промокодов и реферальной программы.',
    icon: Gift,
  },
  {
    key: 'home_reviews_enabled',
    label: 'Отзывы',
    description: 'Карусель отзывов клиентов.',
    icon: Star,
  },
  {
    key: 'home_contacts_enabled',
    label: 'Контакты',
    description: 'Блок связи перед футером.',
    icon: Mail,
  },
];

export function HomeBlocksSettingsCard({ settings, onChange }: Props) {
  function toggle(key: HomeBlockKey) {
    onChange({ ...settings, [key]: !settings[key] });
  }

  return (
    <Card className="p-6">
      <div className="mb-6">
        <h2 className="flex items-center gap-2 text-2xl font-bold">
          <LayoutDashboard className="h-5 w-5" />
          Блоки главной
        </h2>
        <p className="text-sm text-[#789083]">Управляет видимостью секций на главной странице.</p>
      </div>

      <div className="grid gap-4 lg:grid-cols-2">
        {items.map((item) => (
          <div className="flex items-center justify-between gap-4 rounded-lg border border-[#e4e5da] px-4 py-4" key={item.key}>
            <div>
              <p className="flex items-center gap-2 font-semibold">
                <item.icon className="h-4 w-4" />
                {item.label}
              </p>
              <p className="text-sm text-[#789083]">{item.description}</p>
            </div>
            <button
              aria-label={item.label}
              aria-pressed={settings[item.key]}
              className={`relative h-7 w-12 shrink-0 rounded-full transition ${settings[item.key] ? 'bg-[#2f7d4b]' : 'bg-[#d9dece]'}`}
              type="button"
              onClick={() => toggle(item.key)}
            >
              <span className={`absolute top-1 h-5 w-5 rounded-full bg-white shadow transition ${settings[item.key] ? 'left-6' : 'left-1'}`} />
            </button>
          </div>
        ))}
      </div>
    </Card>
  );
}
