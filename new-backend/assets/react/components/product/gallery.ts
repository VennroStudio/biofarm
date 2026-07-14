const VISIBLE_THUMBNAILS = 4;

const activeThumbnailClasses = ['border-primary', 'ring-2', 'ring-primary/20'];
const inactiveThumbnailClasses = ['border-transparent', 'hover:border-muted-foreground/30'];
const activeLightboxThumbnailClasses = ['border-white', 'opacity-100'];
const inactiveLightboxThumbnailClasses = ['border-transparent', 'opacity-60', 'hover:opacity-100'];

const parseImages = (value: string | undefined): string[] => {
  if (!value) {
    return [];
  }

  try {
    const decoded = JSON.parse(value);

    return Array.isArray(decoded)
      ? decoded.filter((image): image is string => typeof image === 'string' && image.trim() !== '')
      : [];
  } catch {
    return [];
  }
};

const toggleClasses = (element: HTMLElement, classes: string[], force: boolean) => {
  classes.forEach((className) => element.classList.toggle(className, force));
};

const fadeImageTo = (image: HTMLImageElement | null | undefined, src: string, alt?: string) => {
  if (!image) {
    return;
  }

  image.style.transition = 'opacity 300ms ease, transform 300ms ease';
  image.style.opacity = '0';

  requestAnimationFrame(() => {
    image.src = src;
    if (alt !== undefined) {
      image.alt = alt;
    }
    image.style.opacity = '1';
  });
};

export function mountProductGallery() {
  document.querySelectorAll<HTMLElement>('[data-react-island="product-gallery"]').forEach((island) => {
    if (island.dataset.mounted === 'true') {
      return;
    }

    island.dataset.mounted = 'true';

    const root = island.closest<HTMLElement>(island.dataset.rootSelector || '[data-product-gallery]');
    const images = parseImages(island.dataset.images);
    const productName = island.dataset.productName || '';

    if (!root || images.length === 0) {
      return;
    }

    const mainImage = root.querySelector<HTMLImageElement>('[data-product-gallery-main]');
    const thumbnailButtons = Array.from(root.querySelectorAll<HTMLButtonElement>('[data-product-gallery-thumb]'));
    const thumbnailImages = thumbnailButtons.map((button) => button.querySelector<HTMLImageElement>('img'));
    const thumbnailUpButton = root.querySelector<HTMLButtonElement>('[data-product-gallery-up]');
    const thumbnailDownButton = root.querySelector<HTMLButtonElement>('[data-product-gallery-down]');
    const openButton = root.querySelector<HTMLButtonElement>('[data-product-gallery-open]');
    const lightbox = root.querySelector<HTMLElement>('[data-product-gallery-lightbox]');
    const lightboxImage = root.querySelector<HTMLImageElement>('[data-product-gallery-lightbox-image]');
    const lightboxCloseButton = root.querySelector<HTMLButtonElement>('[data-product-gallery-close]');
    const lightboxPreviousButton = root.querySelector<HTMLButtonElement>('[data-product-gallery-prev]');
    const lightboxNextButton = root.querySelector<HTMLButtonElement>('[data-product-gallery-next]');
    const lightboxThumbnails = Array.from(root.querySelectorAll<HTMLButtonElement>('[data-product-gallery-lightbox-thumb]'));
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let selectedIndex = 0;

    const normalizeIndex = (index: number) => (index + images.length) % images.length;

    const visibleThumbnailStart = () => {
      const half = Math.floor(VISIBLE_THUMBNAILS / 2);
      let start = selectedIndex - half;

      if (start < 0) {
        start = 0;
      } else if (start + VISIBLE_THUMBNAILS > images.length) {
        start = Math.max(0, images.length - VISIBLE_THUMBNAILS);
      }

      return start;
    };

    const renderThumbnails = () => {
      const start = visibleThumbnailStart();

      thumbnailButtons.forEach((button, slot) => {
        const actualIndex = start + slot;
        const image = thumbnailImages[slot];
        const isAvailable = actualIndex < images.length;
        const isActive = actualIndex === selectedIndex;

        button.hidden = !isAvailable;
        button.dataset.galleryIndex = String(actualIndex);
        button.setAttribute('aria-label', `Показать фото ${actualIndex + 1}`);

        toggleClasses(button, activeThumbnailClasses, isActive);
        toggleClasses(button, inactiveThumbnailClasses, !isActive);

        if (image && isAvailable) {
          image.src = images[actualIndex];
          image.alt = `${productName} - фото ${actualIndex + 1}`;
        }
      });
    };

    const renderLightboxThumbnails = () => {
      lightboxThumbnails.forEach((button, index) => {
        const isActive = index === selectedIndex;
        toggleClasses(button, activeLightboxThumbnailClasses, isActive);
        toggleClasses(button, inactiveLightboxThumbnailClasses, !isActive);
      });
    };

    const renderImages = () => {
      const src = images[selectedIndex];
      const alt = productName || `Фото товара ${selectedIndex + 1}`;

      if (reduceMotion) {
        if (mainImage) {
          mainImage.src = src;
          mainImage.alt = alt;
        }
        if (lightboxImage) {
          lightboxImage.src = src;
          lightboxImage.alt = alt;
        }
        return;
      }

      fadeImageTo(mainImage, src, alt);
      if (lightbox && !lightbox.classList.contains('hidden')) {
        fadeImageTo(lightboxImage, src, alt);
      }
    };

    const select = (index: number) => {
      selectedIndex = normalizeIndex(index);
      renderThumbnails();
      renderLightboxThumbnails();
      renderImages();
    };

    const openLightbox = () => {
      if (!lightbox || !lightboxImage) {
        return;
      }

      if (lightbox.parentElement !== document.body) {
        document.body.appendChild(lightbox);
      }

      lightboxImage.src = images[selectedIndex];
      lightboxImage.alt = productName || `Фото товара ${selectedIndex + 1}`;
      lightbox.classList.remove('hidden');
      lightbox.classList.add('flex');
      lightbox.setAttribute('aria-hidden', 'false');
      document.documentElement.classList.add('overflow-hidden');
      document.body.classList.add('overflow-hidden');
      lightbox.focus();

      if (reduceMotion) {
        return;
      }

      lightbox.style.opacity = '0';
      lightbox.style.transition = 'opacity 200ms ease';
      lightboxImage.style.transform = 'scale(0.9)';
      lightboxImage.style.transition = 'transform 200ms ease';

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
      lightbox.style.opacity = '';
      lightbox.style.transition = '';
      lightboxImage.style.transform = '';
      lightboxImage.style.transition = '';
      document.documentElement.classList.remove('overflow-hidden');
      document.body.classList.remove('overflow-hidden');
      openButton?.focus();
    };

    thumbnailButtons.forEach((button) => {
      button.addEventListener('click', () => {
        select(Number(button.dataset.galleryIndex || 0));
      });
    });

    lightboxThumbnails.forEach((button) => {
      button.addEventListener('click', (event) => {
        event.stopPropagation();
        select(Number(button.dataset.galleryIndex || 0));
      });
    });

    thumbnailUpButton?.addEventListener('click', () => select(selectedIndex - 1));
    thumbnailDownButton?.addEventListener('click', () => select(selectedIndex + 1));
    openButton?.addEventListener('click', openLightbox);
    lightboxCloseButton?.addEventListener('click', closeLightbox);
    lightboxPreviousButton?.addEventListener('click', (event) => {
      event.stopPropagation();
      select(selectedIndex - 1);
    });
    lightboxNextButton?.addEventListener('click', (event) => {
      event.stopPropagation();
      select(selectedIndex + 1);
    });
    lightboxImage?.addEventListener('click', (event) => event.stopPropagation());
    lightbox?.addEventListener('click', (event) => {
      if (event.target === lightbox) {
        closeLightbox();
      }
    });
    window.addEventListener('keydown', (event) => {
      if (!lightbox || lightbox.classList.contains('hidden')) {
        return;
      }

      if (event.key === 'Escape') {
        closeLightbox();
      } else if (event.key === 'ArrowLeft') {
        select(selectedIndex - 1);
      } else if (event.key === 'ArrowRight') {
        select(selectedIndex + 1);
      }
    });

    select(0);
  });
}
