import { addToCart, type CartProduct } from '../../site/cart';
import { formatMoney } from '../../site/format';

function parseProduct(value: string | undefined): CartProduct | null {
  if (!value) {
    return null;
  }

  try {
    const data = JSON.parse(value) as Partial<CartProduct>;
    const id = Number(data.id);
    const price = Number(data.price);

    if (!Number.isFinite(id) || !Number.isFinite(price)) {
      return null;
    }

    return {
      id,
      slug: String(data.slug || ''),
      name: String(data.name || data.title || 'Товар'),
      title: data.title,
      image: String(data.image || ''),
      price,
      weight: data.weight,
    };
  } catch {
    return null;
  }
}

function flashButton(button: HTMLElement, label: string) {
  const target = button.querySelector<HTMLElement>('[data-cart-add-label]') || button;
  const previous = target.textContent || '';
  target.textContent = label;
  window.setTimeout(() => {
    target.textContent = previous;
  }, 1400);
}

function mountProductDetail(root: HTMLElement) {
  if (root.dataset.cartMounted === 'true') {
    return;
  }
  root.dataset.cartMounted = 'true';

  const product = parseProduct(root.dataset.product);
  if (!product) {
    return;
  }
  const cartProduct = product;

  const quantityNode = root.querySelector<HTMLElement>('[data-cart-quantity]');
  const increment = root.querySelector<HTMLButtonElement>('[data-cart-increment]');
  const decrement = root.querySelector<HTMLButtonElement>('[data-cart-decrement]');
  const add = root.querySelector<HTMLButtonElement>('[data-cart-add]');
  const label = root.querySelector<HTMLElement>('[data-cart-add-label]');
  let quantity = 1;

  function render() {
    if (quantityNode) {
      quantityNode.textContent = String(quantity);
    }
    if (label) {
      label.textContent = `Добавить в корзину — ${formatMoney(cartProduct.price * quantity)}`;
    }
  }

  increment?.addEventListener('click', () => {
    quantity += 1;
    render();
  });

  decrement?.addEventListener('click', () => {
    quantity = Math.max(1, quantity - 1);
    render();
  });

  add?.addEventListener('click', () => {
    addToCart(cartProduct, quantity);
    flashButton(add, 'Добавлено');
  });

  render();
}

function mountCatalogButton(button: HTMLButtonElement) {
  if (button.dataset.cartMounted === 'true') {
    return;
  }
  button.dataset.cartMounted = 'true';

  const product = parseProduct(button.dataset.product);
  if (!product) {
    return;
  }

  button.addEventListener('click', (event) => {
    event.preventDefault();
    event.stopPropagation();
    addToCart(product, 1);
    flashButton(button, 'Добавлено');
  });
}

export function mountProductCart() {
  document.querySelectorAll<HTMLElement>('[data-product-cart]').forEach(mountProductDetail);
  document.querySelectorAll<HTMLButtonElement>('[data-catalog-cart-add]').forEach(mountCatalogButton);
}
