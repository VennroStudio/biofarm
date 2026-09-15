import { useState } from 'react';
import { formatMoney } from '../../../shared/lib';
import { Button, ErrorAlert, Field, inputClass, Modal } from '../../../shared/ui';
import type { Order } from '../../../types';
import { normalizePaymentStatus, orderStatusOptions, paymentStatusOptions } from '../model/orderOptions';

type Props = {
  order: Order | null;
  error?: string | null;
  saving: boolean;
  onClose: () => void;
  onSave: (order: Order, payload: Record<string, unknown>) => Promise<void>;
};

type OrderForm = {
  status: string;
  paymentStatus: string;
  paymentMethod: string;
  deliveryMethod: string;
  deliveryCost: string;
  discountAmount: string;
  promoCode: string;
  bonusUsed: string;
  bonusEarned: string;
  trackingNumber: string;
  customerName: string;
  phone: string;
  email: string;
  city: string;
  address: string;
  postalCode: string;
};

const deliveryOptions = [
  { value: '', label: 'Не указано' },
  { value: 'cdek', label: 'СДЭК' },
  { value: 'post', label: 'Почта России' },
  { value: 'pickup', label: 'Самовывоз' },
];

function addressValue(order: Order | null, key: string): string {
  return String(order?.shipping_address[key] ?? '');
}

function toForm(order: Order | null): OrderForm {
  return {
    status: order?.status ?? 'pending',
    paymentStatus: normalizePaymentStatus(order?.payment_status),
    paymentMethod: order?.payment_method ?? '',
    deliveryMethod: order?.delivery_method ?? '',
    deliveryCost: String(order?.delivery_cost ?? 0),
    discountAmount: String(order?.discount_amount ?? 0),
    promoCode: order?.promo_code ?? '',
    bonusUsed: String(order?.bonus_used ?? 0),
    bonusEarned: String(order?.bonus_earned ?? 0),
    trackingNumber: order?.tracking_number ?? '',
    customerName: addressValue(order, 'name'),
    phone: addressValue(order, 'phone'),
    email: addressValue(order, 'email'),
    city: addressValue(order, 'city'),
    address: addressValue(order, 'address'),
    postalCode: addressValue(order, 'postal_code') || addressValue(order, 'postalCode'),
  };
}

function intValue(value: string): number {
  const parsed = Number.parseInt(value, 10);
  return Number.isFinite(parsed) && parsed > 0 ? parsed : 0;
}

function orderSubtotal(order: Order | null): number {
  if (!order) {
    return 0;
  }

  if (order.subtotal > 0) {
    return order.subtotal;
  }

  return order.items.reduce((sum, item) => sum + item.price * item.quantity, 0);
}

export function OrderDetailsModal({ order, error, saving, onClose, onSave }: Props) {
  const [form, setForm] = useState<OrderForm>(() => toForm(order));
  const subtotal = orderSubtotal(order);
  const total = Math.max(0, subtotal + intValue(form.deliveryCost) - intValue(form.discountAmount) - intValue(form.bonusUsed));

  async function submit() {
    if (!order) {
      return;
    }

    await onSave(order, {
      status: form.status,
      paymentStatus: form.paymentStatus,
      paymentMethod: form.paymentMethod,
      deliveryMethod: form.deliveryMethod || null,
      deliveryCost: intValue(form.deliveryCost),
      discountAmount: intValue(form.discountAmount),
      promoCode: form.promoCode || null,
      bonusUsed: intValue(form.bonusUsed),
      bonusEarned: intValue(form.bonusEarned),
      trackingNumber: form.trackingNumber || null,
      shippingAddress: {
        name: form.customerName || null,
        phone: form.phone || null,
        email: form.email || null,
        city: form.city || null,
        address: form.address || null,
        postal_code: form.postalCode || null,
      },
    });
  }

  return (
    <Modal
      open={!!order}
      title={`Заказ ${order?.id ?? ''}`}
      description="Доставка, промокод, бонусы и трек-номер"
      maxWidth="max-w-5xl"
      onClose={onClose}
      footer={(
        <>
          <Button type="button" variant="outline" onClick={onClose}>Отмена</Button>
          <Button type="button" disabled={saving || !order} onClick={() => void submit()}>
            {saving ? 'Сохранение...' : 'Сохранить'}
          </Button>
        </>
      )}
    >
      {order && (
        <div className="space-y-6">
          <ErrorAlert>{error}</ErrorAlert>
          <section className="grid gap-4 md:grid-cols-3">
            <Field label="Статус">
              <select className={inputClass} value={form.status} onChange={(event) => setForm({ ...form, status: event.target.value })}>
                {orderStatusOptions.map((status) => <option key={status.value} value={status.value}>{status.label}</option>)}
              </select>
            </Field>
            <Field label="Оплата">
              <select className={inputClass} value={form.paymentStatus} onChange={(event) => setForm({ ...form, paymentStatus: event.target.value })}>
                {paymentStatusOptions.map((status) => <option key={status.value} value={status.value}>{status.label}</option>)}
              </select>
            </Field>
            <Field label="Способ оплаты">
              <input className={inputClass} value={form.paymentMethod} onChange={(event) => setForm({ ...form, paymentMethod: event.target.value })} />
            </Field>
          </section>

          <section>
            <h4 className="mb-3 font-semibold">Клиент и доставка</h4>
            <div className="grid gap-4 md:grid-cols-2">
              <Field label="Имя">
                <input className={inputClass} value={form.customerName} onChange={(event) => setForm({ ...form, customerName: event.target.value })} />
              </Field>
              <Field label="Телефон">
                <input className={inputClass} value={form.phone} onChange={(event) => setForm({ ...form, phone: event.target.value })} />
              </Field>
              <Field label="Email">
                <input className={inputClass} value={form.email} onChange={(event) => setForm({ ...form, email: event.target.value })} />
              </Field>
              <Field label="Город">
                <input className={inputClass} value={form.city} onChange={(event) => setForm({ ...form, city: event.target.value })} />
              </Field>
              <Field label="Адрес">
                <input className={inputClass} value={form.address} onChange={(event) => setForm({ ...form, address: event.target.value })} />
              </Field>
              <Field label="Индекс">
                <input className={inputClass} value={form.postalCode} onChange={(event) => setForm({ ...form, postalCode: event.target.value })} />
              </Field>
              <Field label="Доставка">
                <select className={inputClass} value={form.deliveryMethod} onChange={(event) => setForm({ ...form, deliveryMethod: event.target.value })}>
                  {deliveryOptions.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}
                </select>
              </Field>
              <Field label="Трек-номер">
                <input className={inputClass} value={form.trackingNumber} onChange={(event) => setForm({ ...form, trackingNumber: event.target.value })} />
              </Field>
            </div>
          </section>

          <section>
            <h4 className="mb-3 font-semibold">Скидки и бонусы</h4>
            <div className="grid gap-4 md:grid-cols-4">
              <Field label="Доставка, ₽">
                <input className={inputClass} inputMode="numeric" value={form.deliveryCost} onChange={(event) => setForm({ ...form, deliveryCost: event.target.value })} />
              </Field>
              <Field label="Скидка, ₽">
                <input className={inputClass} inputMode="numeric" value={form.discountAmount} onChange={(event) => setForm({ ...form, discountAmount: event.target.value })} />
              </Field>
              <Field label="Списано бонусов">
                <input className={inputClass} inputMode="numeric" value={form.bonusUsed} onChange={(event) => setForm({ ...form, bonusUsed: event.target.value })} />
              </Field>
              <Field label="Начислить бонусов">
                <input className={inputClass} inputMode="numeric" value={form.bonusEarned} onChange={(event) => setForm({ ...form, bonusEarned: event.target.value })} />
              </Field>
              <Field label="Промокод">
                <input className={inputClass} value={form.promoCode} onChange={(event) => setForm({ ...form, promoCode: event.target.value })} />
              </Field>
            </div>
          </section>

          <section>
            <h4 className="mb-3 font-semibold">Товары</h4>
            <div className="space-y-2">
              {order.items.map((item) => (
                <div key={`${item.product_id}-${item.product_name}`} className="flex justify-between rounded bg-[#f7fbfa] p-3">
                  <span>{item.product_name} × {item.quantity}</span>
                  <span className="font-semibold">{formatMoney(item.price * item.quantity)}</span>
                </div>
              ))}
            </div>
          </section>

          <div className="grid gap-3 border-t border-[#dfece9] pt-4 md:grid-cols-4">
            <div>
              <p className="text-sm text-[#5f7580]">Товары</p>
              <p className="font-semibold">{formatMoney(subtotal)}</p>
            </div>
            <div>
              <p className="text-sm text-[#5f7580]">Доставка</p>
              <p className="font-semibold">{formatMoney(intValue(form.deliveryCost))}</p>
            </div>
            <div>
              <p className="text-sm text-[#5f7580]">Скидки и бонусы</p>
              <p className="font-semibold">{formatMoney(intValue(form.discountAmount) + intValue(form.bonusUsed))}</p>
            </div>
            <div>
              <p className="text-sm text-[#5f7580]">Итого</p>
              <p className="text-xl font-bold">{formatMoney(total)}</p>
            </div>
          </div>
        </div>
      )}
    </Modal>
  );
}
