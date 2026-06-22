import { useEffect } from 'react';
import { createRoot } from 'react-dom/client';

type Props = {
  rootElement: HTMLElement;
};

function SiteHeader({ rootElement }: Props) {
  useEffect(() => {
    const toggle = rootElement.querySelector<HTMLButtonElement>('[data-site-header-menu-toggle]');
    const mobileMenu = rootElement.querySelector<HTMLElement>('[data-site-header-mobile-menu]');
    if (!toggle || !mobileMenu) {
      return undefined;
    }

    const setMenuOpen = (open: boolean) => {
      rootElement.classList.toggle('is-menu-open', open);
      mobileMenu.hidden = !open;
      toggle.setAttribute('aria-expanded', String(open));
      toggle.setAttribute('aria-label', open ? 'Закрыть меню' : 'Открыть меню');
    };

    const syncScrolledState = () => {
      rootElement.classList.toggle('is-scrolled', window.scrollY > 50);
    };

    const handleToggle = () => {
      setMenuOpen(!rootElement.classList.contains('is-menu-open'));
    };

    const handleEscape = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        setMenuOpen(false);
      }
    };

    const closeMenu = () => setMenuOpen(false);

    toggle.addEventListener('click', handleToggle);
    mobileMenu.querySelectorAll('a').forEach((link) => link.addEventListener('click', closeMenu));
    window.addEventListener('scroll', syncScrolledState, { passive: true });
    window.addEventListener('keydown', handleEscape);
    syncScrolledState();

    return () => {
      toggle.removeEventListener('click', handleToggle);
      mobileMenu.querySelectorAll('a').forEach((link) => link.removeEventListener('click', closeMenu));
      window.removeEventListener('scroll', syncScrolledState);
      window.removeEventListener('keydown', handleEscape);
    };
  }, [rootElement]);

  return null;
}

export function mountSiteHeader() {
  document.querySelectorAll('[data-react-island="site-header"]').forEach((element) => {
    const htmlElement = element as HTMLElement;
    const rootElement = htmlElement.closest<HTMLElement>(
      htmlElement.dataset.rootSelector || '[data-site-header-root]',
    );

    if (rootElement) {
      createRoot(htmlElement).render(<SiteHeader rootElement={rootElement} />);
    }
  });
}
