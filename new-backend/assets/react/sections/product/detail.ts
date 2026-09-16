export function mountProductDetail() {
  document.querySelectorAll<HTMLElement>('[data-react-island="product-detail"]').forEach((island) => {
    if (island.dataset.mounted === 'true') return;
    const root = island.closest<HTMLElement>(island.dataset.rootSelector || '[data-product-detail-root]');
    const tablist = root?.querySelector<HTMLElement>('[data-product-tablist]');
    const tabs = Array.from(tablist?.querySelectorAll<HTMLButtonElement>('[data-product-tab]') || []);
    const panels = Array.from(root?.querySelectorAll<HTMLElement>('[data-product-panel]') || []);
    if (!root || !tablist || tabs.length === 0 || panels.length !== tabs.length) return;

    island.dataset.mounted = 'true';
    const desktop = window.matchMedia('(min-width: 1024px)');
    const updateOrientation = () => tablist.setAttribute('aria-orientation', desktop.matches ? 'vertical' : 'horizontal');
    updateOrientation();
    desktop.addEventListener('change', updateOrientation);
    const activate = (selected: HTMLButtonElement) => {
      tabs.forEach((tab) => {
        const active = tab === selected;
        tab.setAttribute('aria-selected', String(active));
        tab.tabIndex = active ? 0 : -1;
      });
      panels.forEach((panel) => {
        panel.hidden = panel.id !== selected.getAttribute('aria-controls');
      });
    };

    tabs.forEach((tab, index) => {
      const panel = panels.find((item) => item.id === tab.getAttribute('aria-controls'));
      panel?.setAttribute('role', 'tabpanel');
      panel?.setAttribute('aria-labelledby', tab.id);
      panel?.setAttribute('tabindex', '0');
      tab.addEventListener('click', () => activate(tab));
      tab.addEventListener('keydown', (event) => {
        let next: number;
        if (event.key === (desktop.matches ? 'ArrowDown' : 'ArrowRight')) next = (index + 1) % tabs.length;
        else if (event.key === (desktop.matches ? 'ArrowUp' : 'ArrowLeft')) next = (index - 1 + tabs.length) % tabs.length;
        else if (event.key === 'Home') next = 0;
        else if (event.key === 'End') next = tabs.length - 1;
        else return;
        event.preventDefault();
        activate(tabs[next]);
        tabs[next].focus({ preventScroll: true });
      });
    });

    // Existing inbound links can still reveal a panel, while tabs never change the URL.
    const activateHash = () => {
      const linked = tabs.find((tab) => `#${tab.getAttribute('aria-controls')}` === window.location.hash);
      if (linked) activate(linked);
    };
    activate(tabs[0]);
    activateHash();
    tablist.hidden = false;
    window.addEventListener('hashchange', activateHash);
    root.querySelector('[data-product-description-link]')?.addEventListener('click', () => activate(tabs[0]));
  });
}
