export type ApiItems<T> = {
  count: number;
  items: T[];
};

export type AdminUser = {
  id: number;
  first_name: string;
  email?: string;
  role: number;
};

export type Product = {
  id: number;
  slug: string;
  name: string;
  h1: string | null;
  seo_title: string | null;
  seo_description: string | null;
  category_id: string;
  price: number;
  old_price: number | null;
  image: string;
  image_alt: string | null;
  images: string[] | null;
  product_images: ProductImage[] | null;
  badge: string | null;
  weight: string;
  sku: string | null;
  gtin: string | null;
  availability: string;
  description: string;
  short_description: string | null;
  ingredients: string | null;
  usage_text: string | null;
  contraindications: string | null;
  country: string | null;
  shelf_life: string | null;
  storage_conditions: string | null;
  bad_disclaimer: string | null;
  active_components_text: string | null;
  attribute_value_ids: number[] | null;
  product_group_id: number | null;
  related_blog_post_ids: number[] | null;
  certificate_ids: number[] | null;
  faq_ids?: number[] | null;
  features: string[] | null;
  wb_link: string | null;
  ozon_link: string | null;
  is_active: boolean;
  published_at: string | null;
};

export type ProductImage = {
  id: number;
  path: string;
  alt: string | null;
  title: string | null;
  sort_order: number;
  is_main: boolean;
  width: number | null;
  height: number | null;
};

export type AttributeValue = {
  id: number;
  attribute_id: number;
  slug: string;
  name: string;
  h1: string | null;
  seo_title: string | null;
  seo_description: string | null;
  intro_text: string | null;
  bottom_text: string | null;
  short_description: string | null;
  synonyms: string[];
  is_indexable: boolean;
  sort_order: number;
  products_count: number;
};

export type ProductAttribute = {
  id: number;
  slug: string;
  name: string;
  filter_prefix: string | null;
  is_filterable: boolean;
  is_indexable: boolean;
  show_on_product: boolean;
  sort_order: number;
  values_count: number;
  products_count: number;
  values: AttributeValue[];
};

export type ProductGroup = {
  id: number;
  name: string;
  products_count: number;
};

export type PromoCode = {
  id: number;
  code: string;
  type: 'percent' | 'fixed';
  value: number;
  min_order_total: number;
  starts_at: string | null;
  ends_at: string | null;
  usage_limit: number | null;
  used_count: number;
  is_active: boolean;
  created_at: string;
  updated_at: string | null;
};

export type Certificate = {
  id: number;
  title: string;
  file_path: string;
  document_type: 'pdf' | 'image';
  product_id: number | null;
  product_name: string | null;
  description: string | null;
  is_active: boolean;
  sort_order: number;
  created_at: string;
  updated_at: string | null;
};

export type FaqItem = {
  id: number;
  question: string;
  answer: string;
  page_scope: string;
  page_id: string | null;
  is_active: boolean;
  sort_order: number;
  created_at: string;
  updated_at: string | null;
};

export type CmsPage = {
  id: number;
  page_type: 'system' | 'custom';
  system_key: string | null;
  slug_path: string | null;
  template: string | null;
  title: string;
  h1: string | null;
  content: string | null;
  excerpt: string | null;
  seo_title: string | null;
  seo_description: string | null;
  og_title: string | null;
  og_description: string | null;
  og_image: string | null;
  og_image_alt: string | null;
  is_published: boolean;
  is_indexable: boolean;
  show_in_sitemap: boolean;
  show_in_header: boolean;
  show_in_footer: boolean;
  sort_order: number;
  published_at: string | null;
  created_at: string;
  updated_at: string | null;
};

export type CmsPageTemplate = {
  key: string;
  label: string;
  description: string;
};

export type Category = {
  id: number;
  slug: string;
  name: string;
  parent_id: number | null;
  h1: string | null;
  seo_title: string | null;
  seo_description: string | null;
  intro_text: string | null;
  bottom_text: string | null;
  image: string | null;
  is_indexable: boolean;
  sort_order: number;
};

export type BlogPost = {
  id: number;
  slug: string;
  title: string;
  h1: string | null;
  seo_title: string | null;
  seo_description: string | null;
  excerpt: string;
  content: string;
  image: string;
  image_alt: string | null;
  category_id: string;
  author_name: string;
  read_time: number;
  is_published: boolean;
  published_at: string | null;
  created_at: string;
};

export type Review = {
  id: string;
  product_id: number;
  user_id: string | null;
  user_name: string;
  rating: number;
  text: string;
  images: string[] | null;
  source: string;
  is_approved: boolean;
  created_at: string;
};

export type Order = {
  id: string;
  user_id: number | null;
  status: string;
  payment_status: string;
  subtotal: number;
  delivery_method: string | null;
  delivery_cost: number;
  discount_amount: number;
  promo_code: string | null;
  total: number;
  bonus_used: number;
  bonus_earned: number;
  shipping_address: Record<string, string | null | undefined>;
  payment_method: string;
  tracking_number: string | null;
  created_at: string;
  paid_at: string | null;
  items: Array<{
    product_id: number;
    product_name: string;
    price: number;
    quantity: number;
  }>;
};

export type AdminCustomer = {
  parent_name?: string | null;
  id: number;
  first_name: string;
  last_name: string;
  name: string;
  email: string;
  phone: string | null;
  card_number: string | null;
  bonus_balance: number;
  is_partner: boolean;
  is_team_member: boolean;
  is_referral: boolean;
  team_partner_name: string | null;
  referral_code: string | null;
  referred_by_user_id: number | null;
  referrals_count: number;
  referral_orders_total: number;
  bonus_transactions: BonusTransaction[];
  created_at: string;
};

export type BonusTransaction = {
  id: number;
  amount: number;
  type: string;
  source_order_id: string | null;
  source_withdrawal_id: string | null;
  comment: string | null;
  created_at: string;
};

export type Withdrawal = {
  id: string;
  user_id: number;
  amount: number;
  status: 'pending' | 'approved' | 'rejected';
  processed_by: string | null;
  processed_at: string | null;
  created_at: string;
  user: {
    email: string | null;
    name: string;
    card_number: string | null;
    bonus_balance: number;
  };
};

export type Settings = {
  referral_percent: number;
  registration_enabled: boolean;
  cart_enabled: boolean;
  referral_enabled: boolean;
  withdrawals_enabled: boolean;
  favorites_enabled: boolean;
  order_bonus_enabled: boolean;
  order_bonus_percent: number;
  order_bonus_spend_limit_percent: number;
  welcome_bonus_enabled: boolean;
  welcome_bonus_amount: number;
  promo_codes_enabled: boolean;
  free_delivery_threshold: number;
  cdek_delivery_price: number;
  post_delivery_price: number;
  order_emails_enabled: boolean;
  yandex_metrika_enabled: boolean;
  yandex_metrika_id: string;
  bitrix_widget_enabled: boolean;
  bitrix_widget_code: string;
  bitrix_crm_enabled: boolean;
  seo_product_title_template: string;
  seo_product_description_template: string;
  seo_category_title_template: string;
  seo_category_description_template: string;
  seo_attribute_title_template: string;
  seo_attribute_description_template: string;
  site_name: string;
  site_phone: string;
  site_email: string;
  site_logo_url: string;
  site_default_og_image: string;
  site_address_country: string;
  site_address_region: string;
  site_address_locality: string;
  site_address_street: string;
  robots_txt: string;
  robots_extra_disallow: string;
};

export type Bitrix24IntegrationSettings = {
  enabled: boolean;
  has_webhook: boolean;
  webhook_mask: string | null;
};

export type IntegrationErrorLog = {
  id: number;
  service: string;
  scenario: string;
  operation: string;
  local_entity_type: string | null;
  local_entity_id: string | null;
  message: string;
  http_status: number | null;
  response_body: string | null;
  context: Record<string, unknown>;
  is_read: boolean;
  created_at: string;
  read_at: string | null;
};

export type DashboardStats = {
  total_orders: number;
  total_revenue: number;
  total_users: number;
  pending_withdrawals: number;
  total_withdrawal_amount: number;
};

export type MediaAsset = {
  id: number;
  path: string;
  url: string;
  mime_type: string;
  size: number;
  width: number | null;
  height: number | null;
  original_name: string | null;
};

export type BlogCategory = {
  id: number;
  name: string;
  slug: string;
  sort_order: number;
  posts_count: number;
};
