import { loadFavoriteIds, toggleFavorite } from '../../site/favorites';

const activeClasses = ['border-primary', 'bg-primary/10', 'text-primary'];
const inactiveClasses = ['border-input', 'bg-background'];

function productId(button: HTMLElement): number {
  const id = Number(button.dataset.productId || 0);

  return Number.isFinite(id) ? id : 0;
}

function setButtonState(button: HTMLElement, active: boolean) {
  button.dataset.favoriteActive = active ? 'true' : 'false';
  button.setAttribute('aria-pressed', active ? 'true' : 'false');
  button.setAttribute('aria-label', active ? 'Убрать из избранного' : 'Добавить в избранное');
  button.setAttribute('title', active ? 'Убрать из избранного' : 'Добавить в избранное');
  const label = button.querySelector<HTMLElement>('[data-favorite-label]');
  if (label) label.textContent = active ? 'В избранном' : 'В избранное';
  button.classList.remove(...(active ? inactiveClasses : activeClasses));
  button.classList.add(...(active ? activeClasses : inactiveClasses));
}

async function refreshButtons(buttons: HTMLElement[]) {
  const favoriteIds = await loadFavoriteIds().catch(() => []);
  const ids = new Set(favoriteIds);

  buttons.forEach((button) => {
    const id = productId(button);
    if (id > 0) {
      setButtonState(button, ids.has(id));
    }
  });
}

const buttonSelector = '[data-favorite-button]';
let noticeTimer: number | undefined;
let listenersBound = false;

function dismissNotice() {
  const notice = document.querySelector<HTMLElement>('[data-favorite-notice-panel]');
  if (notice) notice.hidden = true;
  window.clearTimeout(noticeTimer);
}

function showNotice(message: string) {
  const notice = document.querySelector<HTMLElement>('[data-favorite-notice-panel]');
  const text = notice?.querySelector<HTMLElement>('[data-favorite-notice-text]');
  if (!notice || !text) return;
  window.clearTimeout(noticeTimer);
  text.textContent = message;
  notice.hidden = false;
  noticeTimer = window.setTimeout(() => {
    if (!notice.contains(document.activeElement)) dismissNotice();
  }, 6000);
}

export function mountProductFavorites(root: ParentNode = document) {
  const buttons = Array.from(root.querySelectorAll<HTMLElement>(buttonSelector))
    .filter((button) => button.dataset.mounted !== 'true');
  if (buttons.length === 0) return;

  if (!listenersBound) {
    listenersBound = true;
    document.querySelector('[data-favorite-notice-close]')?.addEventListener('click', dismissNotice);
    window.addEventListener('biofarm-favorites-updated', () => {
      void refreshButtons(Array.from(document.querySelectorAll<HTMLElement>(buttonSelector)));
    });
  }

  buttons.forEach((button) => {
    button.dataset.mounted = 'true';
    button.setAttribute('disabled', 'disabled');
    button.addEventListener('click', (event) => {
      event.preventDefault();
      event.stopPropagation();

      const id = productId(button);
      if (id <= 0) {
        return;
      }

      button.setAttribute('disabled', 'disabled');
      void toggleFavorite(id, button.dataset.favoriteActive === 'true')
        .then((active) => {
          document.querySelectorAll<HTMLElement>(buttonSelector).forEach((item) => {
            if (productId(item) === id) setButtonState(item, active);
          });
          showNotice(active ? 'Добавлено в избранное' : 'Удалено из избранного');
        })
        .catch((error: unknown) => {
          console.error('Failed to toggle favorite product', error);
          showNotice('Не удалось обновить избранное. Попробуйте ещё раз.');
        })
        .finally(() => button.removeAttribute('disabled'));
    });
  });

  void refreshButtons(buttons).finally(() => {
    buttons.forEach((button) => button.removeAttribute('disabled'));
  });
}
