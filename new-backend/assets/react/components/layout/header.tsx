import { useEffect } from 'react';
import { createRoot } from 'react-dom/client';

type Props = {
  rootSelector: string;
};

const greenLogoFilter =
  'brightness(0) saturate(100%) invert(30%) sepia(20%) saturate(1118%) hue-rotate(94deg) brightness(94%) contrast(90%)';

function SiteHeader({ rootSelector }: Props) {
  useEffect(() => {
    const root = document.querySelector<HTMLElement>(rootSelector);
    if (!root) {
      return undefined;
    }

    const logo = root.querySelector<HTMLImageElement>('img');
    const links = Array.from(root.querySelectorAll<HTMLAnchorElement>('nav[aria-label="Основная навигация"] a'));
    const mobileLinks = Array.from(root.querySelectorAll<HTMLAnchorElement>('#site-header-mobile-menu a'));
    const cartLinks = Array.from(root.querySelectorAll<HTMLAnchorElement>('[data-cart-link]'));
    const cartCounters = Array.from(root.querySelectorAll<HTMLElement>('[data-cart-count]'));
    const menuToggle = root.querySelector<HTMLElement>('summary');
    const details = root.querySelector<HTMLDetailsElement>('details');

    const update = () => {
      const isActive = window.scrollY > 50 || details?.open === true;

      root.classList.toggle('bg-primary/90', !isActive);
      root.classList.toggle('backdrop-blur-sm', !isActive);
      root.classList.toggle('bg-background/95', isActive);
      root.classList.toggle('backdrop-blur-md', isActive);
      root.classList.toggle('shadow-premium', isActive);

      links.forEach((link) => {
        link.classList.toggle('text-white/90', !isActive);
        link.classList.toggle('text-foreground', isActive);
      });

      cartLinks.forEach((link) => {
        link.classList.toggle('text-white', !isActive);
        link.classList.toggle('text-foreground', isActive);
      });

      if (menuToggle) {
        menuToggle.classList.toggle('text-white', !isActive);
        menuToggle.classList.toggle('text-foreground', isActive);
      }

      if (logo) {
        logo.style.filter = isActive ? greenLogoFilter : '';
      }
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
