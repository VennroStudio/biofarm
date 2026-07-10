export function mountHomeReviews() {
  document.querySelectorAll<HTMLElement>('[data-react-island="home-reviews"]').forEach((island) => {
    if (island.dataset.mounted === 'true') {
      return;
    }

    island.dataset.mounted = 'true';

    const root = island.closest<HTMLElement>(island.dataset.rootSelector || '[data-home-reviews-root]');
    if (!root) {
      return;
    }

    const cards = Array.from(root.querySelectorAll<HTMLElement>('[data-review-card]'));
    const dots = Array.from(root.querySelectorAll<HTMLButtonElement>('[data-review-dot]'));
    const previousButton = root.querySelector<HTMLButtonElement>('[data-reviews-prev]');
    const nextButton = root.querySelector<HTMLButtonElement>('[data-reviews-next]');
    const lightbox = root.querySelector<HTMLElement>('[data-reviews-lightbox]');
    const lightboxImage = lightbox?.querySelector<HTMLImageElement>('[data-reviews-lightbox-image]');
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let currentIndex = 0;

    if (cards.length === 0) {
      return;
    }

    const setDotState = (dot: HTMLButtonElement, isActive: boolean) => {
      dot.classList.toggle('w-6', isActive);
      dot.classList.toggle('bg-primary', isActive);
      dot.classList.toggle('w-2', !isActive);
      dot.classList.toggle('bg-primary/30', !isActive);
      dot.classList.toggle('hover:bg-primary/50', !isActive);
    };

    const update = () => {
      const visibleIndexes = new Map<number, number>();

      for (let slot = 0; slot < Math.min(3, cards.length); slot++) {
        visibleIndexes.set((currentIndex + slot) % cards.length, slot);
      }

      cards.forEach((card, index) => {
        const order = visibleIndexes.get(index);
        card.hidden = order === undefined;
        card.style.order = order === undefined ? '' : String(order);

        if (order === undefined) {
          card.style.opacity = '';
          card.style.transform = '';
          card.style.transition = '';
          card.style.transitionDelay = '';
        }
      });

      dots.forEach((dot, index) => {
        setDotState(dot, index === currentIndex);
      });

      if (reduceMotion) {
        return;
      }

      const visibleCards = cards.filter((card) => !card.hidden);

      visibleCards.forEach((card) => {
        const order = Number(card.style.order || 0);

        card.style.opacity = '0';
        card.style.transform = 'translate3d(0, 30px, 0)';
        card.style.transition = 'opacity 600ms ease, transform 600ms ease';
        card.style.transitionDelay = `${200 + order * 150}ms`;
      });

      requestAnimationFrame(() => {
        visibleCards.forEach((card) => {
          card.style.opacity = '1';
          card.style.transform = 'translate3d(0, 0, 0)';
        });
      });
    };

    const show = (index: number) => {
      currentIndex = (index + cards.length) % cards.length;
      update();
    };

    const openLightbox = (image: string) => {
      if (!lightbox || !lightboxImage) {
        return;
      }

      lightboxImage.src = image;
      lightbox.style.opacity = '0';
      lightbox.style.transition = 'opacity 200ms ease';
      lightboxImage.style.transform = 'scale(0.9)';
      lightboxImage.style.transition = 'transform 200ms ease';
      lightbox.classList.remove('hidden');
      lightbox.classList.add('flex');
      lightbox.setAttribute('aria-hidden', 'false');
      lightbox.focus();

      requestAnimationFrame(() => {
        lightbox.style.opacity = '1';
        lightboxImage.style.transform = 'scale(1)';
      });
    };

    const closeLightbox = () => {
      if (!lightbox || !lightboxImage || lightbox.classList.contains('hidden')) {
        return;
      }

      lightbox.classList.add('hidden');
      lightbox.classList.remove('flex');
      lightbox.setAttribute('aria-hidden', 'true');
      lightboxImage.removeAttribute('src');
      lightbox.style.opacity = '';
      lightbox.style.transition = '';
      lightboxImage.style.transform = '';
      lightboxImage.style.transition = '';
    };

    previousButton?.addEventListener('click', () => show(currentIndex - 1));
    nextButton?.addEventListener('click', () => show(currentIndex + 1));

    dots.forEach((dot) => {
      dot.addEventListener('click', () => {
        show(Number(dot.dataset.reviewIndex || 0));
      });
    });

    root.querySelectorAll<HTMLButtonElement>('[data-review-image]').forEach((button) => {
      button.addEventListener('click', () => {
        const image = button.dataset.lightboxImage;
        if (image) {
          openLightbox(image);
        }
      });
    });

    lightbox?.addEventListener('click', closeLightbox);
    window.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        closeLightbox();
      }
    });

    update();
  });
}
