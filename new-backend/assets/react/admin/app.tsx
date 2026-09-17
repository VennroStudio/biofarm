import { AdminProgram } from './pages/AdminProgram';
import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';
import type { ReactNode } from 'react';
import { getToken } from './api/client';
import { AdminShopLayout } from './layout/AdminShopLayout';
import { AdminProductGroups } from './pages/AdminProductGroups';
import { AdminLayout } from './layout/AdminLayout';
import { AdminAttributes } from './pages/AdminAttributes';
import { AdminBlogLayout } from './layout/AdminBlogLayout';
import { AdminBlogCategories } from './pages/AdminBlogCategories';
import { AdminBlog } from './pages/AdminBlog';
import { AdminCategories } from './pages/AdminCategories';
import { AdminCertificates } from './pages/AdminCertificates';
import { AdminDashboard } from './pages/AdminDashboard';
import { AdminFaq } from './pages/AdminFaq';
import { AdminLogin } from './pages/AdminLogin';
import { AdminOrders } from './pages/AdminOrders';
import { AdminPages } from './pages/AdminPages';
import { AdminProducts } from './pages/AdminProducts';
import { AdminPromoCodes } from './pages/AdminPromoCodes';
import { AdminReviews } from './pages/AdminReviews';
import { AdminSettings } from './pages/AdminSettings';
import { AdminUsers } from './pages/AdminUsers';
import { defaultSettingsSection, settingsSectionPath } from './features/settings/model/settingsSections';

function Guard({ children }: { children: ReactNode }) {
  if (!getToken()) {
    return <Navigate to="/admin/login" replace />;
  }

  return children;
}

export function AdminApp() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/admin/login" element={<AdminLogin />} />
        <Route
          path="/admin"
          element={
            <Guard>
              <AdminLayout />
            </Guard>
          }
        >
          <Route index element={<AdminDashboard />} />
          <Route path="shop" element={<AdminShopLayout />}>
            <Route index element={<Navigate to="categories" replace />} />
            <Route path="categories" element={<AdminCategories />} />
            <Route path="products" element={<AdminProducts />} />
            <Route path="attributes" element={<AdminAttributes />} />
            <Route path="product-groups" element={<AdminProductGroups />} />
            <Route path="promo-codes" element={<AdminPromoCodes />} />
          </Route>
          <Route path="products" element={<Navigate to="/admin/shop/products" replace />} />
          <Route path="categories" element={<Navigate to="/admin/shop/categories" replace />} />
          <Route path="attributes" element={<Navigate to="/admin/shop/attributes" replace />} />
          <Route path="pages" element={<AdminPages />} />
          <Route path="product-groups" element={<Navigate to="/admin/shop/product-groups" replace />} />
          <Route path="components" element={<Navigate to="/admin/shop/attributes" replace />} />
          <Route path="purposes" element={<Navigate to="/admin/shop/attributes" replace />} />
          <Route path="orders" element={<AdminOrders />} />
          <Route path="promo-codes" element={<Navigate to="/admin/shop/promo-codes" replace />} />
          <Route path="certificates" element={<AdminCertificates />} />
          <Route path="faq" element={<AdminFaq />} />
          <Route path="blog" element={<AdminBlogLayout />}>
            <Route index element={<Navigate to="articles" replace />} />
            <Route path="articles" element={<AdminBlog />} />
            <Route path="categories" element={<AdminBlogCategories />} />
          </Route>
          <Route path="reviews" element={<AdminReviews />} />
          <Route path="program" element={<Navigate to="/admin/program/ledger" replace />} />
          <Route path="program/:section" element={<AdminProgram />} />
          <Route path="users" element={<AdminUsers />} />
          <Route path="withdrawals" element={<Navigate to="/admin/program/withdrawals" replace />} />
          <Route path="integration-errors" element={<Navigate to="/admin/settings/integrations#integration-errors" replace />} />
          <Route path="settings" element={<Navigate to={settingsSectionPath(defaultSettingsSection)} replace />} />
          <Route path="settings/loyalty" element={<Navigate to="/admin/program/settings" replace />} />
          <Route path="settings/:section" element={<AdminSettings />} />
        </Route>
        <Route path="*" element={<Navigate to="/admin" replace />} />
      </Routes>
    </BrowserRouter>
  );
}
