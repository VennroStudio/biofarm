import { useState, useEffect } from 'react';
import { Save, Percent, Gift, ShoppingBag, Power, Lock } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { adminApi, ReferralSettings } from '@/data/admin';
import { useToast } from '@/hooks/use-toast';

const AdminSettings = () => {
  const { toast } = useToast();
  const [settings, setSettings] = useState<ReferralSettings>({
    referralPercent: 5,
    orderBonusEnabled: true,
    orderBonusPercent: 5,
  });
  const [isLoading, setIsLoading] = useState(false);
  const [passwordData, setPasswordData] = useState({
    currentPassword: '',
    newPassword: '',
    confirmPassword: '',
  });
  const [isChangingPassword, setIsChangingPassword] = useState(false);

  useEffect(() => {
    adminApi.getReferralSettings().then(setSettings);
  }, []);

  const handleSave = async () => {
    setIsLoading(true);
    try {
      await adminApi.updateReferralSettings(settings);
      toast({ title: 'Настройки сохранены' });
    } finally {
      setIsLoading(false);
    }
  };

  const handleChangePassword = async () => {
    if (passwordData.newPassword !== passwordData.confirmPassword) {
      toast({ title: 'Пароли не совпадают', variant: 'destructive' });
      return;
    }

    if (passwordData.newPassword.length < 4) {
      toast({ title: 'Новый пароль должен быть не менее 4 символов', variant: 'destructive' });
      return;
    }

    setIsChangingPassword(true);
    try {
      await adminApi.changePassword(passwordData.currentPassword, passwordData.newPassword);
      toast({ title: 'Пароль успешно изменен' });
      setPasswordData({
        currentPassword: '',
        newPassword: '',
        confirmPassword: '',
      });
    } catch (error: any) {
      toast({ title: error.message || 'Ошибка при смене пароля', variant: 'destructive' });
    } finally {
      setIsChangingPassword(false);
    }
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold">Настройки</h1>
        <p className="text-muted-foreground">Конфигурация магазина и бонусной программы</p>
      </div>

      {/* Referral Settings */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Gift className="h-5 w-5" />
            Реферальная программа
          </CardTitle>
          <CardDescription>
            Настройки реферальной системы для партнёров
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-6">
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div className="space-y-2">
              <Label className="flex items-center gap-2">
                <Percent className="h-4 w-4" />
                Процент рефералов
              </Label>
              <div className="relative">
                <Input
                  type="number"
                  min="0"
                  max="100"
                  value={settings.referralPercent}
                  onChange={(e) => setSettings({ ...settings, referralPercent: Number(e.target.value) })}
                />
                <span className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground">%</span>
              </div>
              <p className="text-xs text-muted-foreground">
                Процент от покупок приглашённых пользователей
              </p>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Order Bonus Settings */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <ShoppingBag className="h-5 w-5" />
            Бонусы за заказ
          </CardTitle>
          <CardDescription>
            Настройки начисления бонусов за покупки
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-6">
          <div className="flex items-center justify-between p-4 border rounded-lg">
            <div className="space-y-1">
              <div className="flex items-center gap-2">
                <Power className="h-4 w-4" />
                <Label>Начисление бонусов за заказ</Label>
              </div>
              <p className="text-sm text-muted-foreground">
                Пользователи получают бонусы за каждый заказ
              </p>
            </div>
            <Switch
              checked={settings.orderBonusEnabled}
              onCheckedChange={(checked) => setSettings({ ...settings, orderBonusEnabled: checked })}
            />
          </div>

          {settings.orderBonusEnabled && (
            <div className="space-y-2">
              <Label className="flex items-center gap-2">
                <Percent className="h-4 w-4" />
                Процент бонусов за заказ
              </Label>
              <div className="relative max-w-xs">
                <Input
                  type="number"
                  min="0"
                  max="100"
                  value={settings.orderBonusPercent}
                  onChange={(e) => setSettings({ ...settings, orderBonusPercent: Number(e.target.value) })}
                />
                <span className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground">%</span>
              </div>
              <p className="text-xs text-muted-foreground">
                Процент от суммы заказа, начисляемый в виде бонусов
              </p>
            </div>
          )}
        </CardContent>
      </Card>

      {/* Change Password */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Lock className="h-5 w-5" />
            Смена пароля
          </CardTitle>
          <CardDescription>
            Измените пароль для входа в админ-панель
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="currentPassword">Текущий пароль</Label>
            <Input
              id="currentPassword"
              type="password"
              value={passwordData.currentPassword}
              onChange={(e) => setPasswordData({ ...passwordData, currentPassword: e.target.value })}
              placeholder="Введите текущий пароль"
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="newPassword">Новый пароль</Label>
            <Input
              id="newPassword"
              type="password"
              value={passwordData.newPassword}
              onChange={(e) => setPasswordData({ ...passwordData, newPassword: e.target.value })}
              placeholder="Введите новый пароль (минимум 4 символа)"
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="confirmPassword">Подтвердите новый пароль</Label>
            <Input
              id="confirmPassword"
              type="password"
              value={passwordData.confirmPassword}
              onChange={(e) => setPasswordData({ ...passwordData, confirmPassword: e.target.value })}
              placeholder="Повторите новый пароль"
            />
          </div>
          <Button 
            onClick={handleChangePassword} 
            disabled={isChangingPassword || !passwordData.currentPassword || !passwordData.newPassword || !passwordData.confirmPassword}
            variant="outline"
          >
            <Lock className="h-4 w-4 mr-2" />
            {isChangingPassword ? 'Изменение...' : 'Изменить пароль'}
          </Button>
        </CardContent>
      </Card>

      {/* Save Button */}
      <div className="flex justify-end">
        <Button onClick={handleSave} disabled={isLoading} size="lg">
          <Save className="h-4 w-4 mr-2" />
          {isLoading ? 'Сохранение...' : 'Сохранить все настройки'}
        </Button>
      </div>

    </div>
  );
};

export default AdminSettings;
