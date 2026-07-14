export type ApiItems<T> = {
  count: number;
  items: T[];
};

export type AdminUser = {
  id: number;
  first_name: string;
  email?: string;
  role: {
    id: number;
    label: string;
  };
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
  attribute_value_ids: number[] | null;
  component_ids: number[] | null;
  purpose_ids: number[] | null;
  product_group_id: number | null;
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
  user_id: number;
  status: string;
  payment_status: string;
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
  id: number;
  name: string;
  email: string;
  phone: string | null;
  card_number: string | null;
  bonus_balance: number;
  is_partner: boolean;
  referral_code: string | null;
  referred_by_user_id: number | null;
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
  order_bonus_enabled: boolean;
  order_bonus_percent: number;
  site_name: string;
  site_phone: string;
  site_email: string;
  site_logo_url: string;
  site_default_og_image: string;
  site_address_country: string;
  site_address_region: string;
  site_address_locality: string;
  site_address_street: string;
  robots_extra_disallow: string;
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
