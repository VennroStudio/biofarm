import { useState } from 'react';

type Props = {
  productId: number;
  productTitle: string;
  productPrice: number;
};

export function ProductCounter({ productId, productTitle, productPrice }: Props) {
  const [quantity, setQuantity] = useState(1);

  return (
    <div className="island-counter" data-product-id={productId}>
      <button
        type="button"
        data-variant="light"
        aria-label={`Decrease ${productTitle}`}
        onClick={() => setQuantity((value) => Math.max(1, value - 1))}
      >
        -
      </button>
      <span>{quantity}</span>
      <button
        type="button"
        data-variant="light"
        aria-label={`Increase ${productTitle}`}
        onClick={() => setQuantity((value) => value + 1)}
      >
        +
      </button>
      <button type="button">
        ${(productPrice * quantity).toFixed(2)}
      </button>
    </div>
  );
}
