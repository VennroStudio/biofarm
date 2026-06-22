import { createRoot } from 'react-dom/client';
import { ProductCommandPanel } from './islands/ProductCommandPanel';
import { ProductCounter } from './islands/ProductCounter';

document.querySelectorAll('[data-react-island="product-counter"]').forEach((element) => {
  const htmlElement = element as HTMLElement;
  const counterElement = htmlElement.previousElementSibling;

  if (counterElement instanceof HTMLElement && counterElement.matches('[data-product-counter]')) {
    createRoot(htmlElement).render(<ProductCounter counterElement={counterElement} />);
  }
});

document.querySelectorAll('[data-react-island="product-command-panel"]').forEach((element) => {
  const htmlElement = element as HTMLElement;

  createRoot(htmlElement).render(
    <ProductCommandPanel
      feedbackSelector={htmlElement.dataset.feedbackSelector || '[data-command-feedback]'}
      panelSelector={htmlElement.dataset.panelSelector || '#product-commands'}
      modalSelector={htmlElement.dataset.modalSelector || '[data-modal="product-command-result"]'}
    />,
  );
});
