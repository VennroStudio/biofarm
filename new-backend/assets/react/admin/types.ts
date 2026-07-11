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
  category_id: string;
  price: number;
  old_price: number | null;
  image: string;
  images: string[] | null;
  badge: string | null;
  weight: string;
  description: string;
  short_description: string | null;
  ingredients: string | null;
  features: string[] | null;
  wb_link: string | null;
  ozon_link: string | null;
  is_active: boolean;
};

export type Category = {
  id: number;
  slug: string;
  name: string;
};

export type BlogPost = {
  id: number;
  slug: string;
  title: string;
  excerpt: string;
  content: string;
  image: string;
  category_id: string;
  author_name: string;
  read_time: number;
  is_published: boolean;
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
