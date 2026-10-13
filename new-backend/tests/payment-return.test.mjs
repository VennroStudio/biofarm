import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';
import vm from 'node:vm';
import ts from 'typescript';
const source = await readFile(new URL('../assets/react/pages/order-success/paymentReturn.ts', import.meta.url), 'utf8');
const { outputText } = ts.transpileModule(source, { compilerOptions: { module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2022 } });
const exports = {}; vm.runInNewContext(outputText, { exports });
const state = (changes = {}) => ({ configured: true, status: 'pending', orderPaymentStatus: 'pending', orderStatus: 'pending', amountMinor: 10000, ...changes });
test('a return without server confirmation never displays payment success', () => {
  assert.equal(exports.paymentReturnState(null, false).phase, 'missing');
  assert.equal(exports.paymentReturnState(null, true).phase, 'checking');
  assert.equal(exports.paymentReturnState(state()).phase, 'pending');
  assert.equal(exports.paymentReturnState(state({ status: 'succeeded' })).phase, 'pending');
});
test('confirmed payment takes precedence over gateway settings; refunds never offer another payment', () => {
  const paid = exports.paymentReturnState(state({ orderPaymentStatus: 'completed', configured: false }));
  assert.equal(paid.phase, 'completed'); assert.equal(paid.canPay, false); assert.equal(paid.poll, false);
  const refunded = exports.paymentReturnState(state({ status: 'succeeded', orderPaymentStatus: 'refunded' }));
  assert.equal(refunded.phase, 'refunded'); assert.equal(refunded.canPay, false); assert.equal(refunded.poll, false);
});
test('a canceled payment can be retried, a canceled order cannot', () => {
  const payment = exports.paymentReturnState(state({ status: 'canceled' }));
  assert.equal(payment.phase, 'canceled'); assert.equal(payment.canPay, true); assert.equal(payment.poll, false);
  const order = exports.paymentReturnState(state({ orderStatus: 'cancelled' }));
  assert.equal(order.phase, 'order_canceled'); assert.equal(order.canPay, false); assert.equal(order.poll, false);
});
test('capture waits for a confirmed result and never creates a second payment', () => {
  const capture = exports.paymentReturnState(state({ status: 'waiting_for_capture' }));
  assert.equal(capture.phase, 'capture'); assert.equal(capture.canPay, false); assert.equal(capture.poll, true);
  const offline = exports.paymentReturnState(state({ configured: false }));
  assert.equal(offline.phase, 'unavailable'); assert.equal(offline.canPay, false); assert.equal(offline.poll, false);
  assert.equal(exports.paymentReturnState(state({ status: 'not_started' })).poll, false);
});
function storageFixture(values) {
  const data = new Map(Object.entries(values));
  return { data, getItem: key => data.get(key) ?? null, removeItem: key => data.delete(key) };
}
test('a paid old order preserves a newer checkout and cart', () => {
  const storage = storageFixture({ biofarm_checkout_order: 'new-order', biofarm_order_cart_old: '["old"]', biofarm_offer_id: 'new-offer' });
  let cleared = false;
  exports.finishSavedCheckout('old', '["new"]', storage, () => { cleared = true; });
  assert.equal(cleared, false);
  assert.equal(storage.data.get('biofarm_checkout_order'), 'new-order');
  assert.equal(storage.data.get('biofarm_offer_id'), 'new-offer');
});
test('confirmed checkout clears its matching cart and offer while preserving payment access', () => {
  const storage = storageFixture({ biofarm_checkout_order: 'paid', biofarm_order_cart_paid: '["item"]', biofarm_offer_id: 'offer', biofarm_offer_promo: 'promo', biofarm_payment_paid: 'access' });
  let clears = 0;
  exports.finishSavedCheckout('paid', '["item"]', storage, () => { clears++; });
  assert.equal(clears, 1);
  assert.equal(storage.data.has('biofarm_checkout_order'), false);
  assert.equal(storage.data.has('biofarm_offer_id'), false);
  assert.equal(storage.data.has('biofarm_offer_promo'), false);
  assert.equal(storage.data.get('biofarm_payment_paid'), 'access');
});
