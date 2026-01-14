import { useState, useEffect, useCallback } from 'react';
import { CartItem, cartApi } from '@/data/orders';
import { Product } from '@/data/products';

export function useCart() {
  const [cart, setCart] = useState<CartItem[]>([]);
  const [isLoading, setIsLoading] = useState(true);

  const refreshCart = useCallback(() => {
    setCart(cartApi.getCart());
    setIsLoading(false);
  }, []);

  useEffect(() => {
    refreshCart();
    
    const handleCartUpdate = () => refreshCart();
    window.addEventListener('cartUpdated', handleCartUpdate);
    
    return () => {
      window.removeEventListener('cartUpdated', handleCartUpdate);
    };
  }, [refreshCart]);

  const addToCart = useCallback((product: Product, quantity: number = 1) => {
    const updated = cartApi.addToCart(product, quantity);
    setCart(updated);
  }, []);

  const updateQuantity = useCallback((productId: number, quantity: number) => {
    const updated = cartApi.updateQuantity(productId, quantity);
    setCart(updated);
  }, []);

  const removeFromCart = useCallback((productId: number) => {
    const updated = cartApi.removeFromCart(productId);
    setCart(updated);
  }, []);

  const clearCart = useCallback(() => {
    cartApi.clearCart();
    setCart([]);
  }, []);

  const total = cart.reduce((sum, item) => sum + item.product.price * item.quantity, 0);
  const itemCount = cart.reduce((sum, item) => sum + item.quantity, 0);

  return {
    cart,
    isLoading,
    total,
    itemCount,
    addToCart,
    updateQuantity,
    removeFromCart,
    clearCart,
  };
}
