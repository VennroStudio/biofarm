import { createRoot } from 'react-dom/client';
import { AdminApp } from './app';

const root = document.getElementById('admin-root');

if (root) {
  createRoot(root).render(<AdminApp />);
}
