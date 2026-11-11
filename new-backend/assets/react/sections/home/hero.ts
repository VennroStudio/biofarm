/** Progressive enhancement: original artwork and native video work without JS. */
export function mountHomeHero() {
  document.querySelectorAll<HTMLElement>('[data-home-hero]').forEach((root) => {
    if (root.dataset.mounted) return;
    root.dataset.mounted = 'true';
    const slides = Array.from(root.querySelectorAll<HTMLElement>('[data-hero-slide]'));
    const dots = Array.from(root.querySelectorAll<HTMLButtonElement>('[data-hero-go]'));
    const stage = root.querySelector<HTMLElement>('[data-hero-stage]');
    const video = root.querySelector<HTMLVideoElement>('[data-hero-video]');
    const status = root.querySelector<HTMLElement>('[data-hero-status]');
    if (!stage || !slides.length) return;
    // These five small originals are reused on mobile and desktop; warm the next slide.
    const preloadNext = (current: number) => {
      const image = slides[(current + 1) % slides.length].querySelector<HTMLImageElement>('img');
      if (image) image.loading = 'eager';
    };
    preloadNext(0);
    const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)');
    let index = 0;
    let timer: ReturnType<typeof setTimeout> | undefined;
    let pointer: { x: number; y: number } | undefined;
    let suppressClick = false;

    const schedule = () => {
      clearTimeout(timer);
      if (document.hidden) return;
      // The first slide stays for the full video, other slides rotate every eight seconds.
      if (index === 0 && video && !video.paused && !video.ended) return;
      timer = setTimeout(() => show(index + 1, false), 8000);
    };
    const playVideo = () => {
      if (video && index === 0 && !document.hidden && !reducedMotion.matches) {
        void video.play().catch(schedule);
      }
    };
    const show = (next: number, manual = true) => {
      const previous = index;
      index = (next + slides.length) % slides.length;
      preloadNext(index);
      if (index !== 0) video?.pause();
      slides.forEach((slide, i) => {
        slide.hidden = i !== index;
        slide.inert = i !== index;
      });
      dots.forEach((dot, i) => {
        if (i === index) dot.setAttribute('aria-current', 'true');
        else dot.removeAttribute('aria-current');
      });
      if (manual && status) status.textContent = slides[index].getAttribute('aria-label');
      if (index === 0 && previous !== 0 && video) {
        video.currentTime = 0;
        playVideo();
      }
      schedule();
    };
    root.querySelector<HTMLElement>('[data-hero-navigation]')?.removeAttribute('hidden');
    root.querySelector('[data-hero-prev]')?.addEventListener('click', () => show(index - 1));
    root.querySelector('[data-hero-next]')?.addEventListener('click', () => show(index + 1));
    dots.forEach((dot, i) => dot.addEventListener('click', () => show(i)));
    root.addEventListener('keydown', (event) => {
      if (event.target instanceof HTMLVideoElement) return;
      if (event.key === 'ArrowRight' || event.key === 'ArrowLeft') {
        event.preventDefault();
        show(index + (event.key === 'ArrowRight' ? 1 : -1));
      }
    });
    stage.addEventListener('pointerdown', (event) => {
      suppressClick = false;
      if (event.target instanceof Element && event.target.closest('video, button')) return;
      pointer = { x: event.clientX, y: event.clientY };
    });
    stage.addEventListener('pointerup', (event) => {
      if (!pointer) return;
      const dx = event.clientX - pointer.x;
      const dy = event.clientY - pointer.y;
      pointer = undefined;
      if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy) * 1.5) {
        suppressClick = true;
        show(index + (dx < 0 ? 1 : -1));
      }
    });
    stage.addEventListener('pointercancel', () => { pointer = undefined; });
    stage.addEventListener('click', (event) => {
      if (suppressClick) { event.preventDefault(); event.stopPropagation(); suppressClick = false; }
    }, true);
    document.addEventListener('visibilitychange', () => {
      if (document.hidden) video?.pause();
      else playVideo();
      schedule();
    });
    reducedMotion.addEventListener('change', () => {
      if (reducedMotion.matches) video?.pause();
      schedule();
    });
    video?.addEventListener('play', schedule);
    video?.addEventListener('pause', schedule);
    video?.addEventListener('ended', () => {
      if (index === 0 && !document.hidden) show(index + 1, false);
      else schedule();
    });
    schedule();
    if (video) video.muted = true;
    playVideo();
  });
}
