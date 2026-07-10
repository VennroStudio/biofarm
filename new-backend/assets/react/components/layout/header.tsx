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

      if (menuToggle) {
        menuToggle.classList.toggle('text-white', !isActive);
        menuToggle.classList.toggle('text-foreground', isActive);
      }

      if (logo) {
        logo.style.filter = isActive ? greenLogoFilter : '';
      }
    };

    update();
    const closeMenu = () => {
      if (details) {
        details.open = false;
        update();
      }
    };

    window.addEventListener('scroll', update, { passive: true });
    details?.addEventListener('toggle', update);
    mobileLinks.forEach((link) => link.addEventListener('click', closeMenu));

    return () => {
      window.removeEventListener('scroll', update);
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
