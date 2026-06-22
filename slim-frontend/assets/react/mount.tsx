import { createRoot } from 'react-dom/client';
import { ProductCounter } from './islands/ProductCounter';

document.querySelectorAll('[data-react-island="product-counter"]').forEach((element) => {
  const htmlElement = element as HTMLElement;

  createRoot(htmlElement).render(
    <ProductCounter
      productId={Number(htmlElement.dataset.productId)}
      productTitle={htmlElement.dataset.productTitle || ''}
      productPrice={Number(htmlElement.dataset.productPrice)}
    />,
  );
});
