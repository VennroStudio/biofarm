export function mountCatalogFilter() {
  document.querySelectorAll<HTMLElement>('[data-react-island="catalog-filter"]').forEach((island) => {
    if (island.dataset.mounted === 'true') return;

    const root = island.closest<HTMLElement>(island.dataset.rootSelector || '[data-catalog-filter-root]');
    const dialog = root?.querySelector<HTMLDialogElement>('[data-ingredient-dialog]');
    const trigger = root?.querySelector<HTMLButtonElement>('[data-filter-open]');
    const form = dialog?.querySelector('form');
    if (!dialog || !trigger || !form) return;

    island.dataset.mounted = 'true';
    let previousOverflow = '';
    let backdropPointerDown = false;
    const outsideDialog = (event: PointerEvent) => {
      const bounds = dialog.getBoundingClientRect();
      return event.clientX < bounds.left || event.clientX > bounds.right
        || event.clientY < bounds.top || event.clientY > bounds.bottom;
    };

    trigger.addEventListener('click', () => {
      if (dialog.open) return;
      form.reset();
      previousOverflow = document.body.style.overflow;
      dialog.showModal();
      document.body.style.overflow = 'hidden';
      trigger.setAttribute('aria-expanded', 'true');
    });
    dialog.querySelector('[data-filter-close]')?.addEventListener('click', () => dialog.close());
    dialog.querySelector('[data-filter-clear]')?.addEventListener('click', () => {
      const all = form.querySelector<HTMLInputElement>('input[name="sostav"][value=""]');
      if (all) all.checked = true;
    });
    dialog.addEventListener('pointerdown', (event) => {
      backdropPointerDown = outsideDialog(event);
    });
    dialog.addEventListener('pointerup', (event) => {
      if (backdropPointerDown && outsideDialog(event)) dialog.close();
      backdropPointerDown = false;
    });
    dialog.addEventListener('close', () => {
      document.body.style.overflow = previousOverflow;
      trigger.setAttribute('aria-expanded', 'false');
      trigger.focus({ preventScroll: true });
    });
  });
}
