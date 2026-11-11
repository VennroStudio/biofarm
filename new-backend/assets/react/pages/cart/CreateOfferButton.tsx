import { useState, type FormEvent } from 'react';
import { QrCode } from 'lucide-react';
import { Field, inputClass } from '../../program/shared';
import { request } from '../../site/api';
import type { CartItem } from '../../site/cart';
import { Button } from '../../site/ui';
import { OfferLinkDialog } from '../profile/components/OfferLinkDialog';
import { PartnerDialog } from '../profile/components/PartnerDialog';

export function CreateOfferButton({ cart }: { cart: CartItem[] }) {
  const [open, setOpen] = useState(false);
  const [url, setUrl] = useState('');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');

  async function createOffer(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (busy) return;

    const form = new FormData(event.currentTarget);
    const title = String(form.get('title') ?? '').trim();
    const expiresAt = String(form.get('expiresAt') ?? '');
    if (!title) {
      setError('Укажите название корзины.');
      return;
    }

    setBusy(true);
    setError('');
    try {
      const offer = await request<{ url: string }>('/v1/program/offers', {
        method: 'POST',
        body: {
          title,
          expiresAt: expiresAt ? new Date(expiresAt).toISOString() : undefined,
          items: cart.map(({ product, quantity }) => ({ productId: product.id, quantity })),
        },
      });
      setOpen(false);
      setUrl(offer.url);
    } catch (cause) {
      setError(cause instanceof Error ? cause.message : 'Не удалось создать ссылку. Попробуйте ещё раз.');
    } finally {
      setBusy(false);
    }
  }

  return (
    <>
      <Button className="w-full" variant="secondary" onClick={() => { setError(''); setOpen(true); }}>
        <QrCode className="h-4 w-4" aria-hidden="true" />
        Создать ссылку и QR-код
      </Button>
      {open && (
        <PartnerDialog title="Корзина для покупателя" onClose={() => { if (!busy) setOpen(false); }}>
          <form className="mt-4 space-y-4" onSubmit={(event) => void createOffer(event)}>
            <Field name="Название корзины">
              <input className={inputClass} name="title" maxLength={150} required autoFocus />
            </Field>
            <Field name="Действует до (необязательно)">
              <input className={inputClass} name="expiresAt" type="datetime-local" />
            </Field>
            {error && <p className="text-sm text-destructive" role="alert">{error}</p>}
            <Button className="w-full" type="submit" disabled={busy}>
              {busy ? 'Создаём ссылку…' : 'Создать ссылку и QR-код'}
            </Button>
          </form>
        </PartnerDialog>
      )}
      {url && <OfferLinkDialog url={url} onClose={() => setUrl('')} />}
    </>
  );
}
