import { Edit, MapPin, Plus, Save, Trash2, X } from 'lucide-react';
import { type FormEvent, useMemo, useState } from 'react';
import type { ShippingAddress, SiteUser, UserAddress } from '../../../site/api';
import { Badge, Button, Card, CardContent, CardHeader, CardTitle, Input, Label, Textarea } from '../../../site/ui';

type AddressForm = ShippingAddress & {
  isDefault: boolean;
  label: string;
};

type Props = {
  addresses: UserAddress[];
  onDelete: (id: number) => Promise<void>;
  onSave: (address: AddressForm, id?: number) => Promise<void>;
  user: SiteUser;
};

function blankAddress(user: SiteUser): AddressForm {
  return {
    address: '',
    city: '',
    comment: '',
    email: user.email,
    isDefault: false,
    label: 'Основной адрес',
    name: user.name,
    phone: user.phone || '',
    postalCode: '',
  };
}

function formFromAddress(address: UserAddress): AddressForm {
  return {
    address: address.address,
    city: address.city,
    comment: address.comment || '',
    email: address.email,
    isDefault: address.isDefault,
    label: address.label,
    name: address.name,
    phone: address.phone,
    postalCode: address.postalCode,
  };
}

export function AddressesPanel({ addresses, onDelete, onSave, user }: Props) {
  const initialForm = useMemo(() => blankAddress(user), [user]);
  const [editingId, setEditingId] = useState<number | null>(null);
  const [form, setForm] = useState<AddressForm>(initialForm);
  const [saving, setSaving] = useState(false);

  function resetForm() {
    setEditingId(null);
    setForm(blankAddress(user));
  }

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSaving(true);
    try {
      await onSave(form, editingId ?? undefined);
      resetForm();
    } finally {
      setSaving(false);
    }
  }

  return (
    <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(320px,420px)]">
      <Card className="border-0 shadow-premium">
        <CardHeader>
          <CardTitle>Адреса доставки</CardTitle>
        </CardHeader>
        <CardContent className="space-y-3">
          {addresses.length === 0 && (
            <div className="rounded-md border border-dashed p-6 text-sm text-muted-foreground">
              Адреса пока не добавлены.
            </div>
          )}

          {addresses.map((address) => (
            <article key={address.id} className="rounded-lg border bg-background p-4">
              <div className="mb-3 flex items-start justify-between gap-3">
                <div>
                  <div className="flex items-center gap-2">
                    <h3 className="font-semibold">{address.label}</h3>
                    {address.isDefault && <Badge variant="secondary">По умолчанию</Badge>}
                  </div>
                  <p className="mt-1 text-sm text-muted-foreground">
                    {address.city}, {address.address}
                  </p>
                </div>
                <MapPin className="h-5 w-5 flex-shrink-0 text-primary" />
              </div>
              <p className="text-sm text-muted-foreground">
                {address.name}, {address.phone}, {address.email}
              </p>
              {address.comment && <p className="mt-2 text-sm text-muted-foreground">{address.comment}</p>}
              <div className="mt-4 flex flex-wrap gap-2">
                <Button
                  size="sm"
                  variant="outline"
                  onClick={() => {
                    setEditingId(address.id);
                    setForm(formFromAddress(address));
                  }}
                >
                  <Edit className="h-4 w-4" />
                  Изменить
                </Button>
                <Button size="sm" variant="destructive" onClick={() => void onDelete(address.id)}>
                  <Trash2 className="h-4 w-4" />
                  Удалить
                </Button>
              </div>
            </article>
          ))}
        </CardContent>
      </Card>

      <Card className="border-0 shadow-premium">
        <CardHeader>
          <CardTitle>{editingId ? 'Редактировать адрес' : 'Новый адрес'}</CardTitle>
        </CardHeader>
        <CardContent>
          <form className="space-y-4" onSubmit={(event) => void submit(event)}>
            <div className="space-y-2">
              <Label>Название</Label>
              <Input value={form.label} onChange={(event) => setForm({ ...form, label: event.target.value })} />
            </div>
            <div className="space-y-2">
              <Label>Получатель</Label>
              <Input value={form.name} onChange={(event) => setForm({ ...form, name: event.target.value })} />
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-2">
                <Label>Телефон</Label>
                <Input value={form.phone} onChange={(event) => setForm({ ...form, phone: event.target.value })} />
              </div>
              <div className="space-y-2">
                <Label>Email</Label>
                <Input type="email" value={form.email} onChange={(event) => setForm({ ...form, email: event.target.value })} />
              </div>
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-2">
                <Label>Город</Label>
                <Input required value={form.city} onChange={(event) => setForm({ ...form, city: event.target.value })} />
              </div>
              <div className="space-y-2">
                <Label>Индекс</Label>
                <Input value={form.postalCode} onChange={(event) => setForm({ ...form, postalCode: event.target.value })} />
              </div>
            </div>
            <div className="space-y-2">
              <Label>Адрес</Label>
              <Textarea required value={form.address} onChange={(event) => setForm({ ...form, address: event.target.value })} />
            </div>
            <div className="space-y-2">
              <Label>Комментарий</Label>
              <Textarea value={form.comment} onChange={(event) => setForm({ ...form, comment: event.target.value })} />
            </div>
            <label className="flex items-center gap-2 text-sm font-medium">
              <input
                checked={form.isDefault}
                className="h-4 w-4 rounded border-input text-primary"
                type="checkbox"
                onChange={(event) => setForm({ ...form, isDefault: event.target.checked })}
              />
              Адрес по умолчанию
            </label>
            <div className="flex gap-2">
              <Button className="flex-1" disabled={saving} type="submit">
                {editingId ? <Save className="h-4 w-4" /> : <Plus className="h-4 w-4" />}
                {editingId ? 'Сохранить' : 'Добавить'}
              </Button>
              {editingId && (
                <Button variant="outline" onClick={resetForm}>
                  <X className="h-4 w-4" />
                </Button>
              )}
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  );
}
