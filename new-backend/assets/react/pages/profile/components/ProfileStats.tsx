import { Package, Users, Wallet } from 'lucide-react';
import type { ReferralInfo, SiteOrder, SiteUser } from '../../../site/api';
import { formatMoney } from '../../../site/format';
import { Card, CardContent } from '../../../site/ui';

type Props = {
  orders: SiteOrder[];
  referralInfo: ReferralInfo | null;
  user: SiteUser;
};

export function ProfileStats({ orders, referralInfo, user }: Props) {
  return (
    <div className="mb-8 grid grid-cols-1 gap-4 md:grid-cols-3">
      <Card className="border-0 shadow-premium">
        <CardContent className="flex items-center gap-4 p-6">
          <div className="rounded-full bg-primary/10 p-3">
            <Wallet className="h-6 w-6 text-primary" />
          </div>
          <div>
            <p className="text-2xl font-bold">{formatMoney(user.bonusBalance)}</p>
            <p className="text-sm text-muted-foreground">Бонусный баланс</p>
          </div>
        </CardContent>
      </Card>

      <Card className="border-0 shadow-premium">
        <CardContent className="flex items-center gap-4 p-6">
          <div className="rounded-full bg-accent/10 p-3">
            <Package className="h-6 w-6 text-accent" />
          </div>
          <div>
            <p className="text-2xl font-bold">{orders.length}</p>
            <p className="text-sm text-muted-foreground">Заказов</p>
          </div>
        </CardContent>
      </Card>

      {user.isPartner && (
        <Card className="border-0 shadow-premium">
          <CardContent className="flex items-center gap-4 p-6">
            <div className="rounded-full bg-green-100 p-3">
              <Users className="h-6 w-6 text-green-600" />
            </div>
            <div>
              <p className="text-2xl font-bold">{referralInfo?.referredUsers || 0}</p>
              <p className="text-sm text-muted-foreground">Приглашенных</p>
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
}
