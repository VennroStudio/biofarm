import { useEffect } from 'react';
import { createRoot } from 'react-dom/client';

type Props = {
  rootSelector: string;
};

function SiteHeader({ rootSelector }: Props) {
  useEffect(() => {
    const root = document.querySelector<HTMLElement>(rootSelector);
    if (!root) {
      return undefined;
    }

    const mobileLinks = Array.from(root.querySelectorAll<HTMLAnchorElement>('#site-header-mobile-menu a'));
    const cartCounters = Array.from(root.querySelectorAll<HTMLElement>('[data-cart-count]'));
    const details = root.querySelector<HTMLDetailsElement>('details');

    const update = () => {
      const isActive = window.scrollY > 50 || details?.open === true;

      root.classList.toggle('shadow-premium', isActive);
    };

    const updateCartCount = () => {
      const raw = window.localStorage.getItem('biofarm_cart') || window.localStorage.getItem('cart') || '[]';
      let count = 0;

      try {
        const items = JSON.parse(raw) as Array<{ quantity?: number }>;
        count = items.reduce((sum, item) => sum + Math.max(0, Number(item.quantity || 0)), 0);
      } catch {
        count = 0;
      }

      cartCounters.forEach((counter) => {
        counter.textContent = count > 0 ? String(count) : '';
        counter.classList.toggle('hidden', count === 0);
        counter.classList.toggle('inline-flex', count > 0);
      });
    };

    update();
    updateCartCount();
    const closeMenu = () => {
      if (details) {
        details.open = false;
        update();
      }
    };

    window.addEventListener('scroll', update, { passive: true });
    window.addEventListener('biofarm-cart-updated', updateCartCount);
    window.addEventListener('cartUpdated', updateCartCount);
    window.addEventListener('storage', updateCartCount);
    details?.addEventListener('toggle', update);
    mobileLinks.forEach((link) => link.addEventListener('click', closeMenu));

    return () => {
      window.removeEventListener('scroll', update);
      window.removeEventListener('biofarm-cart-updated', updateCartCount);
      window.removeEventListener('cartUpdated', updateCartCount);
      window.removeEventListener('storage', updateCartCount);
      details?.removeEventListener('toggle', update);
      mobileLinks.forEach((link) => link.removeEventListener('click', closeMenu));
    };
  }, [rootSelector]);

  return null;
}

export function mountSiteHeader() {
  document.querySelectorAll('[data-react-island="site-header"]').forEach((element) => {
    const htmlElement = element as HTMLElement;

    createRoot(htmlElement).render(
      <SiteHeader rootSelector={htmlElement.dataset.rootSelector || '[data-site-header-root]'} />,
    );
  });
}
