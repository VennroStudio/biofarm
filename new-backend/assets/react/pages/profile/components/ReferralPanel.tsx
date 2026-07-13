import { Check, ChevronRight, Copy, Package } from 'lucide-react';
import type { FormEvent } from 'react';
import type { ReferralInfo, SiteOrder, WithdrawalRequest } from '../../../site/api';
import { formatDate, formatMoney } from '../../../site/format';
import { Badge, Button, Card, CardContent, CardDescription, CardHeader, CardTitle, Input, Label } from '../../../site/ui';
import { isPaid, OrderBadge } from './orderDisplay';

type Props = {
  copied: boolean;
  onCopyReferralLink: () => void;
  onSelectOrder: (order: SiteOrder) => void;
  onWithdrawal: (event: FormEvent<HTMLFormElement>) => void;
  referralCode: string;
  referralInfo: ReferralInfo | null;
  referralOrders: SiteOrder[];
  setWithdrawalAmount: (value: string) => void;
  withdrawalAmount: string;
  withdrawals: WithdrawalRequest[];
};

export function ReferralPanel({
  copied,
  onCopyReferralLink,
  onSelectOrder,
  onWithdrawal,
  referralCode,
  referralInfo,
  referralOrders,
  setWithdrawalAmount,
  withdrawalAmount,
  withdrawals,
}: Props) {
  return (
    <div className="grid gap-6">
      <Card className="border-0 shadow-premium">
        <CardHeader>
          <CardTitle>Реферальная программа</CardTitle>
          <CardDescription>
            Приглашайте друзей и получайте {referralInfo?.referralPercent || 5}% от их покупок
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-6">
          <div className="rounded-lg border border-primary/20 bg-primary/5 p-4">
            <Label className="text-sm font-medium">Ваша реферальная ссылка</Label>
            <div className="mt-2 flex gap-2">
              <Input className="bg-background" readOnly value={`${window.location.origin}?ref=${referralCode}`} />
              <Button variant="outline" onClick={onCopyReferralLink}>
                {copied ? <Check className="h-4 w-4" /> : <Copy className="h-4 w-4" />}
              </Button>
            </div>
          </div>

          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div className="rounded-lg bg-muted/50 p-4 text-center">
              <p className="text-2xl font-bold text-green-600">{formatMoney(referralInfo?.totalEarnings || 0)}</p>
              <p className="text-sm text-muted-foreground">Заработано всего</p>
            </div>
            <div className="rounded-lg bg-muted/50 p-4 text-center">
              <p className="text-2xl font-bold text-accent">{formatMoney(referralInfo?.pendingEarnings || 0)}</p>
              <p className="text-sm text-muted-foreground">Ожидает начисления</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card className="border-0 shadow-premium">
        <CardHeader>
          <CardTitle>Вывод бонусов</CardTitle>
          <CardDescription>Создайте заявку на выплату партнерских начислений</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <form className="grid grid-cols-1 gap-3 sm:grid-cols-[1fr_auto]" onSubmit={onWithdrawal}>
            <Input
              min="1"
              placeholder="Сумма"
              type="number"
              value={withdrawalAmount}
              onChange={(event) => setWithdrawalAmount(event.target.value)}
            />
            <Button type="submit">Создать заявку</Button>
          </form>
          {withdrawals.length > 0 && (
            <div className="space-y-2">
              {withdrawals.map((withdrawal) => (
                <div className="flex items-center justify-between rounded border p-3" key={withdrawal.id}>
                  <span>{formatMoney(withdrawal.amount)}</span>
                  <span className="text-sm text-muted-foreground">{formatDate(withdrawal.createdAt)}</span>
                  <Badge variant={withdrawal.status === 'approved' ? 'default' : 'secondary'}>{withdrawal.status}</Badge>
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>

      <Card className="border-0 shadow-premium">
        <CardHeader>
          <CardTitle>Заказы рефералов</CardTitle>
          <CardDescription>Все заказы ваших приглашенных пользователей</CardDescription>
        </CardHeader>
        <CardContent>
          {referralOrders.length === 0 ? (
            <div className="py-12 text-center">
              <Package className="mx-auto mb-4 h-12 w-12 text-muted-foreground" />
              <p className="text-muted-foreground">У ваших рефералов пока нет заказов</p>
            </div>
          ) : (
            <div className="space-y-4">
              {referralOrders.map((order) => {
                const earned = isPaid(order)
                  ? order.bonusEarned
                  : Math.floor(order.total * ((referralInfo?.referralPercent || 5) / 100));

                return (
                  <button
                    className="flex w-full cursor-pointer items-center justify-between rounded-lg border p-4 text-left transition-colors hover:bg-muted/50"
                    key={order.id}
                    type="button"
                    onClick={() => onSelectOrder(order)}
                  >
                    <div className="flex-1">
                      <div className="mb-1 flex flex-wrap items-center gap-3">
                        <span className="font-medium">Заказ #{order.id}</span>
                        <OrderBadge order={order} />
                        <Badge className={isPaid(order) ? 'bg-green-500 hover:bg-green-600' : ''} variant={isPaid(order) ? 'default' : 'outline'}>
                          {isPaid(order) ? 'Оплачен' : 'Не оплачен'}
                        </Badge>
                        {earned > 0 && (
                          <Badge
                            className={isPaid(order) ? 'border-green-300 bg-green-100 text-green-700' : 'border-orange-300 bg-orange-100 text-orange-700'}
                            variant="outline"
                          >
                            +{formatMoney(earned)}
                          </Badge>
                        )}
                      </div>
                      <p className="text-sm text-muted-foreground">
                        {formatDate(order.createdAt)} • {formatMoney(order.total)}
                      </p>
                    </div>
                    <ChevronRight className="h-5 w-5 text-muted-foreground" />
                  </button>
                );
              })}
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
