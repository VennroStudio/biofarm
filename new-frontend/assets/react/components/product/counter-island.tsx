import { useEffect } from 'react';
import { createRoot } from 'react-dom/client';

type Props = {
  counterElement: HTMLElement;
};

const money = new Intl.NumberFormat('en-US', {
  currency: 'USD',
  style: 'currency',
});

const readPositiveNumber = (value: string | undefined, fallback: number) => {
  const parsed = Number(value);

  return Number.isFinite(parsed) && parsed > 0 ? parsed : fallback;
};

function ProductCounter({ counterElement }: Props) {
  useEffect(() => {
    const decrement = counterElement.querySelector<HTMLButtonElement>('[data-counter-decrement]');
    const increment = counterElement.querySelector<HTMLButtonElement>('[data-counter-increment]');
    const quantityElement = counterElement.querySelector<HTMLElement>('[data-counter-quantity]');
    const totalElement = counterElement.querySelector<HTMLElement>('[data-counter-total]');

    if (!decrement || !increment || !quantityElement || !totalElement) {
      return undefined;
    }

    const productPrice = readPositiveNumber(counterElement.dataset.productPrice, 0);
    let quantity = readPositiveNumber(counterElement.dataset.productQuantity, 1);

    const render = () => {
      quantityElement.textContent = String(quantity);
      totalElement.textContent = money.format(productPrice * quantity);
      decrement.disabled = quantity <= 1;
    };

    const decrease = () => {
      quantity = Math.max(1, quantity - 1);
      render();
    };

    const increase = () => {
      quantity += 1;
      render();
    };

    decrement.addEventListener('click', decrease);
    increment.addEventListener('click', increase);
    render();

    return () => {
      decrement.removeEventListener('click', decrease);
      increment.removeEventListener('click', increase);
    };
  }, [counterElement]);

  return null;
}

export function mountProductCounter() {
  document.querySelectorAll('[data-react-island="product-counter"]').forEach((element) => {
    const htmlElement = element as HTMLElement;
    const rootElement = htmlElement.closest<HTMLElement>('[data-product-counter-root]');
    const counterElement = rootElement?.querySelector<HTMLElement>(
      htmlElement.dataset.counterSelector || '[data-product-counter]',
    );

    if (counterElement) {
      createRoot(htmlElement).render(<ProductCounter counterElement={counterElement} />);
    }
  });
}
