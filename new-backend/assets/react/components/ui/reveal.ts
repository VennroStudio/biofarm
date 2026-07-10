const DEFAULT_DURATION = 600;
const DEFAULT_Y = 30;
const DEFAULT_ROOT_MARGIN = '0px 0px -100px 0px';

type RevealElement = HTMLElement & {
  dataset: DOMStringMap & {
    reveal?: string;
    revealDelay?: string;
    revealDuration?: string;
    revealX?: string;
    revealY?: string;
    revealOnload?: string;
  };
};

const toNumber = (value: string | undefined, fallback: number) => {
  if (value === undefined || value === '') {
    return fallback;
  }

  const parsed = Number(value);

  return Number.isFinite(parsed) ? parsed : fallback;
};

const show = (element: RevealElement) => {
  element.dataset.revealed = 'true';
  element.style.opacity = '1';
  element.style.transform = 'translate3d(0, 0, 0)';
};

const prepare = (element: RevealElement) => {
  const delay = toNumber(element.dataset.revealDelay, 0);
  const duration = toNumber(element.dataset.revealDuration, DEFAULT_DURATION);
  const x = toNumber(element.dataset.revealX, 0);
  const y = toNumber(element.dataset.revealY, DEFAULT_Y);

  element.style.opacity = '0';
  element.style.transform = `translate3d(${x}px, ${y}px, 0)`;
  element.style.transition = `opacity ${duration}ms ease, transform ${duration}ms ease`;
  element.style.transitionDelay = `${delay}ms`;
  element.style.willChange = 'opacity, transform';
};

export function mountRevealEffects() {
  const elements = Array.from(document.querySelectorAll<RevealElement>('[data-reveal]'));

  if (elements.length === 0) {
    return;
  }

  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  if (reduceMotion || !('IntersectionObserver' in window)) {
    elements.forEach(show);
    return;
  }

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) {
          return;
        }

        const element = entry.target as RevealElement;
        show(element);
        observer.unobserve(element);
      });
    },
    {
      rootMargin: DEFAULT_ROOT_MARGIN,
      threshold: 0,
    },
  );

  elements.forEach((element) => {
    if (element.dataset.revealed === 'true') {
      return;
    }

    prepare(element);

    if (element.dataset.revealOnload === 'true') {
      requestAnimationFrame(() => show(element));
      return;
    }

    observer.observe(element);
  });
}
