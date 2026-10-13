import { createRoot } from 'react-dom/client';
import { useEffect, useState } from 'react';
import { ArrowRight, CheckCircle2, Clock3, LoaderCircle, RotateCcw, ShieldCheck, ShoppingBag, XCircle } from 'lucide-react';
import { payment, startPayment, type PaymentState } from '../../site/payment';
import { clearCart, readCart } from '../../site/cart';
import { money } from '../../program/shared';
import { finishSavedCheckout, paymentReturnState, type PaymentReturnPhase } from './paymentReturn';

const copy: Record<PaymentReturnPhase, { title: string; text: string }> = {
    missing: { title: 'Заказ не указан', text: 'Откройте заказ из личного кабинета или вернитесь в каталог.' },
    checking: { title: 'Проверяем оплату', text: 'Получаем актуальный статус вашего заказа. Это займёт несколько секунд.' },
    completed: { title: 'Заказ оплачен', text: 'Спасибо за покупку! Оплата подтверждена. Мы приступим к обработке заказа.' },
    refunded: { title: 'Оплата возвращена', text: 'Возврат подтверждён. Срок зачисления денег зависит от вашего банка.' },
    order_canceled: { title: 'Заказ отменён', text: 'Этот заказ больше нельзя оплатить. Вы можете оформить новый заказ в каталоге.' },
    unavailable: { title: 'Оплата временно недоступна', text: 'Заказ сохранён. Попробуйте проверить оплату позже.' },
    canceled: { title: 'Оплата не завершена', text: 'Платёж отменён. Заказ и корзина сохранены — вы можете повторить оплату.' },
    capture: { title: 'Ожидаем подтверждение оплаты', text: 'Платёж обрабатывается. Не оплачивайте заказ повторно — статус обновится автоматически.' },
    pending: { title: 'Ожидаем оплату', text: 'Подтверждение оплаты пока не получено. Если вы уже оплатили заказ, подождите немного и проверьте статус.' },
};

function finishCheckout(id: string) {
    finishSavedCheckout(id, JSON.stringify(readCart()), sessionStorage, clearCart);
}

function OrderSuccessPage() {
    const id = new URLSearchParams(location.search).get('order') || '';
    const [state, setState] = useState<PaymentState | null>(null);
    const [error, setError] = useState('');
    const [checking, setChecking] = useState(Boolean(id));
    const [paying, setPaying] = useState(false);
    const [revision, setRevision] = useState(0);
    const view = paymentReturnState(state, Boolean(id));

    useEffect(() => {
        let stopped = false;
        let timer: number | undefined;
        let attempts = 0;
        async function check() {
            setChecking(true);
            setError('');
            try {
                const data = await payment(id);
                if (stopped) return;
                setState(data);
                if (data.orderPaymentStatus === 'completed') finishCheckout(id);
                if (paymentReturnState(data).poll && ++attempts < 15) timer = window.setTimeout(() => void check(), 4000);
            } catch (e) {
                if (!stopped) setError(e instanceof Error ? e.message : 'Не удалось проверить оплату. Попробуйте ещё раз.');
            } finally {
                if (!stopped) setChecking(false);
            }
        }
        if (id) void check();
        return () => { stopped = true; window.clearTimeout(timer); };
    }, [id, revision]);

    async function pay() {
        setPaying(true);
        setError('');
        try {
            // Verify again before retrying: payment may have completed in another tab.
            const current = await payment(id);
            setState(current);
            if (current.orderPaymentStatus === 'completed') finishCheckout(id);
            else if (paymentReturnState(current).canPay) {
                const result = await startPayment(id);
                setState(result);
                if (result.orderPaymentStatus === 'completed') finishCheckout(id);
                else if (!result.confirmationUrl) setRevision(value => value + 1);
            }
        } catch (e) {
            setError(e instanceof Error ? e.message : 'Не удалось перейти к оплате. Попробуйте ещё раз.');
        } finally { setPaying(false); }
    }

    const failedCheck = Boolean(error && !state);
    const success = view.phase === 'completed';
    const canceled = ['canceled', 'order_canceled'].includes(view.phase);
    const Icon = success ? CheckCircle2 : view.phase === 'refunded' ? RotateCcw : canceled || failedCheck || view.phase === 'missing' ? XCircle : view.phase === 'checking' ? LoaderCircle : Clock3;
    const iconClass = success ? 'bg-primary/10 text-primary' : canceled || failedCheck ? 'bg-red-50 text-red-600' : 'bg-secondary text-primary';
    const busy = checking || paying;

    return <main className="min-h-[70vh] bg-secondary/30 px-4 pb-16 pt-[128px] sm:px-6">
        <section className="mx-auto max-w-2xl rounded-2xl border border-border bg-background p-6 shadow-sm sm:p-10" aria-labelledby="payment-title" aria-busy={busy}>
            <div className={`mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full ${iconClass}`}>
                <Icon className={`h-10 w-10 ${view.phase === 'checking' && !failedCheck ? 'animate-spin' : ''}`} aria-hidden="true" />
            </div>
            <div className="text-center" aria-live="polite">
                <h1 id="payment-title" className="text-3xl font-normal tracking-tight text-primary sm:text-4xl">{failedCheck ? 'Не удалось проверить оплату' : copy[view.phase].title}</h1>
                <p className="mx-auto mt-4 max-w-lg text-muted-foreground">{failedCheck ? 'Статус оплаты пока неизвестен. Повторите проверку. Если вы гость, откройте страницу в том же браузере, где оформляли заказ.' : copy[view.phase].text}</p>
            </div>
            {id && <dl className="my-7 space-y-4 rounded-xl border border-border bg-secondary/30 p-5">
                <div className="flex flex-wrap justify-between gap-2"><dt className="text-muted-foreground">Номер заказа</dt><dd className="break-all font-semibold">{id}</dd></div>
                {state && <div className="flex items-center justify-between gap-4 border-t border-border pt-4"><dt className="text-muted-foreground">{success ? 'Оплачено' : ['refunded', 'order_canceled'].includes(view.phase) ? 'Сумма заказа' : 'Сумма к оплате'}</dt><dd className="text-xl font-semibold text-primary">{money(state.amountMinor)}</dd></div>}
            </dl>}
            {error && <p className="mb-5 rounded-xl bg-destructive/10 p-4 text-sm text-destructive" role="alert">{error}</p>}
            <div className="flex flex-col gap-3">
                {view.canPay && <button type="button" className="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-5 py-3 font-semibold text-primary-foreground disabled:opacity-50" disabled={busy} onClick={() => void pay()}>
                    {paying ? <LoaderCircle className="h-5 w-5 animate-spin" /> : <ArrowRight className="h-5 w-5" />}{paying ? 'Переходим к оплате…' : view.phase === 'canceled' ? 'Повторить оплату' : 'Оплатить заказ'}
                </button>}
                {id && !['completed', 'refunded', 'order_canceled'].includes(view.phase) && <button type="button" className="inline-flex items-center justify-center gap-2 rounded-xl border border-border px-5 py-3 font-semibold disabled:opacity-50" disabled={busy} onClick={() => setRevision(value => value + 1)}>
                    <RotateCcw className={`h-4 w-4 ${checking ? 'animate-spin' : ''}`} />{checking ? 'Проверяем…' : 'Проверить оплату'}
                </button>}
                <a className="inline-flex items-center justify-center gap-2 rounded-xl border border-border px-5 py-3 font-semibold" href="/profile"><ShoppingBag className="h-4 w-4" />Мои заказы</a>
                <a className="mt-2 text-center text-primary underline underline-offset-4" href="/catalog">Продолжить покупки</a>
            </div>
            {success && <p className="mt-6 flex items-center justify-center gap-2 text-sm text-muted-foreground"><ShieldCheck className="h-4 w-4 shrink-0" />Оплата подтверждена сервером</p>}
        </section>
    </main>;
}
export function mountOrderSuccessPage() {
    document.querySelectorAll<HTMLElement>('[data-react-island="order-success-page"]').forEach(root => {
        if (root.dataset.mounted === 'true') return;
        root.dataset.mounted = 'true';
        createRoot(root).render(<OrderSuccessPage />);
    });
}
