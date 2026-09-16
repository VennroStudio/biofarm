import { createRoot } from "react-dom/client";
import { useEffect, useState } from "react";
import { payment, startPayment, type PaymentState } from "../../site/payment";
import { clearCart, readCart } from "../../site/cart";
import { Section, buttonClass, money } from "../../program/shared";
function OrderSuccessPage() {
    const id = new URLSearchParams(location.search).get("order") || "";
    const [state, setState] = useState<PaymentState | null>(null);
    const [error, setError] = useState("");
    const [busy, setBusy] = useState(false);
    useEffect(() => {
        let stopped = false;
        let timer: number;
        let attempts = 0;
        async function check() {
            try {
                const data = await payment(id);
                if (stopped) return;
                setState(data);
                if (data.orderPaymentStatus === "completed") {
                    if (sessionStorage.getItem(`biofarm_order_cart_${id}`) === JSON.stringify(readCart())) {
                        clearCart();
                        sessionStorage.removeItem("biofarm_offer_id");
                        sessionStorage.removeItem("biofarm_offer_promo");
                    }
                    sessionStorage.removeItem("biofarm_checkout_order");
                } else if (++attempts < 8 && data.configured) timer = window.setTimeout(() => void check(), 4000);
            } catch (e) {
                if (!stopped) setError(String(e));
            }
        }
        if (id) void check();
        return () => {
            stopped = true;
            window.clearTimeout(timer);
        };
    }, [id]);
    async function retry() {
        setBusy(true);
        setError("");
        try {
            const current = await payment(id);
            if (current.orderPaymentStatus === "completed") {
                setState(current);
                if (sessionStorage.getItem(`biofarm_order_cart_${id}`) === JSON.stringify(readCart())) clearCart();
                sessionStorage.removeItem("biofarm_checkout_order");
            } else setState(await startPayment(id));
        } catch (e) {
            setError(String(e));
        } finally {
            setBusy(false);
        }
    }
    return (
        <main className="mx-auto max-w-3xl px-4 pb-16 pt-32">
            <Section title={state?.orderPaymentStatus === "completed" ? "Оплата подтверждена" : "Заказ сохранён"}>
                <p className="break-all">Номер заказа: {id || "не указан"}</p>
                {state && (
                    <>
                        <p>{money(state.amountMinor)}</p>
                        <p>
                            {state.orderPaymentStatus === "completed"
                                ? "Платёж проверен сервером."
                                : !state.configured
                                  ? "Онлайн-оплата пока не настроена. Заказ сохранён."
                                  : state.orderPaymentStatus === "refunded"
                                    ? "Оплата возвращена."
                                    : "Подтверждение оплаты пока не получено. Корзина сохранена."}
                        </p>
                    </>
                )}
                {error && <p role="alert">{error}</p>}
                {id && state?.orderPaymentStatus !== "completed" && state?.orderPaymentStatus !== "refunded" && (
                    <button className={buttonClass} disabled={busy} onClick={() => void retry()}>
                        Повторить оплату / проверить подключение
                    </button>
                )}
                <div className="flex gap-6">
                    <a href="/profile" className="underline">
                        Мои заказы
                    </a>
                    <a href="/catalog" className="underline">
                        В каталог
                    </a>
                </div>
            </Section>
        </main>
    );
}
export function mountOrderSuccessPage() {
    document.querySelectorAll<HTMLElement>('[data-react-island="order-success-page"]').forEach((root) => {
        if (root.dataset.mounted === "true") {
            return;
        }
        root.dataset.mounted = "true";
        createRoot(root).render(<OrderSuccessPage />);
    });
}
