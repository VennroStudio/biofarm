import { CheckCircle, Home, Package, User } from 'lucide-react';
import { createRoot } from 'react-dom/client';
import { Card, CardContent, LinkButton } from '../../site/ui';

function OrderSuccessPage() {
  const params = new URLSearchParams(window.location.search);
  const orderId = params.get('order') || 'Не указан';

  return (
    <section className="flex min-h-screen items-center justify-center bg-secondary/30 py-12 pt-24">
      <div className="container mx-auto max-w-lg px-4">
        <Card className="border-0 text-center shadow-premium-lg">
          <CardContent className="px-6 pb-8 pt-8">
            <div className="mb-6">
              <div className="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-green-100">
                <CheckCircle className="h-10 w-10 text-green-600" />
              </div>
            </div>

            <h1 className="mb-2 text-2xl font-bold">Заказ оформлен!</h1>
            <p className="mb-6 text-muted-foreground">
              Спасибо за ваш заказ. Мы уже начали его обработку.
            </p>

            <div className="mb-6 rounded-lg bg-muted/50 p-4">
              <p className="text-sm text-muted-foreground">Номер заказа</p>
              <p className="text-xl font-bold text-primary">{orderId}</p>
            </div>

            <div className="mb-6 space-y-3 text-left">
              <div className="flex items-start gap-3 rounded-lg bg-background p-3">
                <Package className="mt-0.5 h-5 w-5 text-primary" />
                <div>
                  <p className="font-medium">Что дальше?</p>
                  <p className="text-sm text-muted-foreground">
                    Менеджер свяжется с вами для подтверждения заказа и уточнения деталей доставки.
                  </p>
                </div>
              </div>
            </div>

            <div className="flex flex-col gap-3 sm:flex-row">
              <LinkButton className="flex-1" href="/profile">
                <User className="h-4 w-4" />
                Мои заказы
              </LinkButton>
              <LinkButton className="flex-1" href="/" variant="outline">
                <Home className="h-4 w-4" />
                На главную
              </LinkButton>
            </div>
          </CardContent>
        </Card>
      </div>
    </section>
  );
}

export function mountOrderSuccessPage() {
  document.querySelectorAll<HTMLElement>('[data-react-island="order-success-page"]').forEach((root) => {
    if (root.dataset.mounted === 'true') {
      return;
    }
    root.dataset.mounted = 'true';
    createRoot(root).render(<OrderSuccessPage />);
  });
}
