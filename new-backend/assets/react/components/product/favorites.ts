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

export function mountProductFavorites() {
  const buttons = Array.from(document.querySelectorAll<HTMLElement>('[data-favorite-button]'))
    .filter((button) => button.dataset.mounted !== 'true');

  if (buttons.length === 0) {
    return;
  }

  buttons.forEach((button) => {
    button.dataset.mounted = 'true';
    button.addEventListener('click', (event) => {
      event.preventDefault();
      event.stopPropagation();

      const id = productId(button);
      if (id <= 0) {
        return;
      }

      button.setAttribute('disabled', 'disabled');
      void toggleFavorite(id, button.dataset.favoriteActive === 'true')
        .then((active) => setButtonState(button, active))
        .catch((error: unknown) => {
          console.error('Failed to toggle favorite product', error);
        })
        .finally(() => button.removeAttribute('disabled'));
    });
  });

  window.addEventListener('biofarm-favorites-updated', () => {
    void refreshButtons(buttons);
  });

  void refreshButtons(buttons);
}
