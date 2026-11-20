import { mountProductCart } from '../../components/product/cart';
import { mountProductFavorites } from '../../components/product/favorites';

const contentSelector = '[data-home-catalog-content]';
const desktopSize = 12;
const mobileSize = 6;

function pageSize(url: URL) {
  return url.searchParams.get('per_page') === String(mobileSize) ? mobileSize : desktopSize;
}

function pageNumber(url: URL) {
  const page = Number(url.searchParams.get('page'));
  return Number.isSafeInteger(page) && page > 0 ? page : 1;
}

function catalogKey(url: URL) {
  return JSON.stringify([url.searchParams.get('category') || '', pageNumber(url), pageSize(url)]);
}

function fitToGrid(url: URL, desiredSize: number) {
  const size = pageSize(url);
  if (size === desiredSize) return;

  const firstProduct = (pageNumber(url) - 1) * size;
  const page = Math.floor(firstProduct / desiredSize) + 1;
  if (page > 1) url.searchParams.set('page', String(page));
  else url.searchParams.delete('page');
  if (desiredSize === mobileSize) url.searchParams.set('per_page', String(mobileSize));
  else url.searchParams.delete('per_page');
}

export function mountHomeCatalog() {
  const root = document.querySelector<HTMLElement>('[data-home-catalog]');
  const results = root?.querySelector<HTMLElement>('[data-home-catalog-results]');
  const status = root?.querySelector<HTMLElement>('[data-home-catalog-status]');
  const heading = root?.querySelector<HTMLElement>('#home-catalog-heading');
  if (!root || !results || !status || !heading) return;

  const desktop = window.matchMedia('(min-width: 1024px)');
  let displayed = catalogKey(new URL(window.location.href));
  let pending: AbortController | undefined;

  const updateStatus = (message: string, error = false) => {
    status.classList.toggle('sr-only', !error);
    status.textContent = message;
  };

  const load = async (url: URL, navigate = false) => {
    fitToGrid(url, desktop.matches ? desktopSize : mobileSize);
    const requested = catalogKey(url);
    pending?.abort();
    if (requested === displayed) {
      pending = undefined;
      results.removeAttribute('aria-busy');
      updateStatus('');
      return;
    }

    const request = new AbortController();
    pending = request;
    results.setAttribute('aria-busy', 'true');
    updateStatus('Загружаем товары…');
    try {
      const response = await fetch(url, { headers: { 'X-Home-Catalog': '1' }, signal: request.signal });
      if (!response.ok) throw new Error(`Catalog request failed with ${response.status}`);
      const html = await response.text();
      if (request.signal.aborted) return;
      const content = new DOMParser().parseFromString(html, 'text/html').querySelector<HTMLElement>(contentSelector);
      if (!content) throw new Error('Catalog results are missing');

      results.replaceChildren(content);
      mountProductCart(results);
      mountProductFavorites(results);
      displayed = requested;
      if (navigate) {
        window.history.pushState(null, '', url);
        heading.focus({ preventScroll: true });
        root.scrollIntoView({ block: 'start', behavior: 'instant' });
      }
      updateStatus(`Страница ${content.dataset.page} загружена`);
    } catch (error) {
      if (!request.signal.aborted) {
        updateStatus('Не удалось загрузить товары. Попробуйте ещё раз.', true);
        console.error('Failed to load the home catalog', error);
      }
    } finally {
      if (pending === request) {
        pending = undefined;
        results.removeAttribute('aria-busy');
      }
    }
  };

  root.addEventListener('click', (event) => {
    if (!(event.target instanceof Element) || event.defaultPrevented || event.button !== 0
      || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    const link = event.target.closest<HTMLAnchorElement>('a[data-home-catalog-link]');
    if (!link) return;
    event.preventDefault();
    void load(new URL(link.href), true);
  });

  const syncFromLocation = () => { void load(new URL(window.location.href)); };
  desktop.addEventListener('change', syncFromLocation);
  window.addEventListener('popstate', syncFromLocation);
  syncFromLocation();
}
