import { Check, Edit, Mail, Phone, User } from 'lucide-react';
import type { SiteUser } from '../../../site/api';
import { formatDate } from '../../../site/format';
import { Button, Card, CardContent, CardDescription, CardHeader, CardTitle, Input, Label } from '../../../site/ui';

type Props = {
  editCardNumber: string;
  editName: string;
  editPhone: string;
  isEditing: boolean;
  onSave: () => void;
  onStartEdit: () => void;
  setEditCardNumber: (value: string) => void;
  setEditName: (value: string) => void;
  setEditPhone: (value: string) => void;
  user: SiteUser;
};

export function ProfileDetailsCard({
  editCardNumber,
  editName,
  editPhone,
  isEditing,
  onSave,
  onStartEdit,
  setEditCardNumber,
  setEditName,
  setEditPhone,
  user,
}: Props) {
  return (
    <Card className="border-0 shadow-premium">
      <CardHeader className="flex flex-row items-center justify-between">
        <div>
          <CardTitle>Личные данные</CardTitle>
          <CardDescription>Управление профилем</CardDescription>
        </div>
        <Button size="sm" variant="outline" onClick={() => (isEditing ? onSave() : onStartEdit())}>
          {isEditing ? <Check className="h-4 w-4" /> : <Edit className="h-4 w-4" />}
          {isEditing ? 'Сохранить' : 'Редактировать'}
        </Button>
      </CardHeader>
      <CardContent className="space-y-4">
        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div className="space-y-2">
            <Label>Имя</Label>
            {isEditing ? (
              <Input value={editName} onChange={(event) => setEditName(event.target.value)} />
            ) : (
              <div className="flex items-center gap-2 rounded-md bg-muted/50 p-2">
                <User className="h-4 w-4 text-muted-foreground" />
                {user.name}
              </div>
            )}
          </div>

          <div className="space-y-2">
            <Label>Email</Label>
            <div className="flex items-center gap-2 rounded-md bg-muted/50 p-2">
              <Mail className="h-4 w-4 text-muted-foreground" />
              {user.email}
            </div>
          </div>

          <div className="space-y-2">
            <Label>Телефон</Label>
            {isEditing ? (
              <Input placeholder="+7 (999) 123-45-67" value={editPhone} onChange={(event) => setEditPhone(event.target.value)} />
            ) : (
              <div className="flex items-center gap-2 rounded-md bg-muted/50 p-2">
                <Phone className="h-4 w-4 text-muted-foreground" />
                {user.phone || 'Не указан'}
              </div>
            )}
          </div>

          <div className="space-y-2">
            <Label>Дата регистрации</Label>
            <div className="rounded-md bg-muted/50 p-2">{formatDate(user.createdAt)}</div>
          </div>

          {user.isPartner && (
            <div className="space-y-2 md:col-span-2">
              <Label>Карта для выплат</Label>
              {isEditing ? (
                <Input value={editCardNumber} onChange={(event) => setEditCardNumber(event.target.value)} />
              ) : (
                <div className="rounded-md bg-muted/50 p-2">{user.cardNumber || 'Не указана'}</div>
              )}
            </div>
          )}
        </div>
      </CardContent>
    </Card>
  );
}
