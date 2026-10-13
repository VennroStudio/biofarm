import type { PaymentState } from '../../site/payment';
export type PaymentReturnPhase = 'missing' | 'checking' | 'completed' | 'refunded' | 'order_canceled' | 'unavailable' | 'canceled' | 'capture' | 'pending';
export function paymentReturnState(state: PaymentState | null, hasOrder = true): { phase: PaymentReturnPhase; canPay: boolean; poll: boolean } {
  if (!hasOrder) return { phase: 'missing', canPay: false, poll: false };
  if (!state) return { phase: 'checking', canPay: false, poll: false };
  if (state.orderPaymentStatus === 'refunded') return { phase: 'refunded', canPay: false, poll: false };
  if (state.orderPaymentStatus === 'completed') return { phase: 'completed', canPay: false, poll: false };
  if (['cancelled', 'canceled'].includes(state.orderStatus || '')) return { phase: 'order_canceled', canPay: false, poll: false };
  if (!state.configured) return { phase: 'unavailable', canPay: false, poll: false };
  if (state.status === 'canceled') return { phase: 'canceled', canPay: true, poll: false };
  if (state.status === 'waiting_for_capture') return { phase: 'capture', canPay: false, poll: true };
  return { phase: 'pending', canPay: true, poll: state.status === 'pending' };
}
export function finishSavedCheckout(id: string, cartSnapshot: string, storage: Pick<Storage, 'getItem' | 'removeItem'>, clear: () => void): void {
  if (storage.getItem(`biofarm_order_cart_${id}`) === cartSnapshot) {
    clear();
    storage.removeItem('biofarm_offer_id');
    storage.removeItem('biofarm_offer_promo');
  }
  // Returning to an old paid order must not discard a later checkout.
  if (storage.getItem('biofarm_checkout_order') === id) storage.removeItem('biofarm_checkout_order');
}
