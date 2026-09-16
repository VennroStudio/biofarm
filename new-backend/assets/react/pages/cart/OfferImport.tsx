import { useEffect, useState } from "react";
import { request } from "../../site/api";
import { readCart, writeCart, type CartItem } from "../../site/cart";
import { rememberReferral } from "../../site/referral";
import { Section, buttonClass } from "../../program/shared";
type Offer = {
    id: string;
    title: string;
    items: CartItem[];
    referralCode: string;
    expiresAt?: string;
};
export function OfferImport() {
    const [offer, setOffer] = useState<Offer | null>(null);
    const [error, setError] = useState("");
    const [done, setDone] = useState(false);
    const token = new URLSearchParams(window.location.search).get("offer");
    useEffect(() => {
        if (token)
            void request<Offer>(`/v1/offers/${encodeURIComponent(token)}`)
                .then(setOffer)
                .catch((e) => setError(`Предложение недоступно или товары закончились: ${String(e)}`));
    }, [token]);
    if (!token || done) return null;
    function apply(merge: boolean) {
        if (!offer) return;
        const cart = merge ? readCart() : [];
        for (const item of offer.items) {
            const old = cart.find((i) => i.product.id === item.product.id);
            if (old) old.quantity += item.quantity;
            else cart.push(item);
        }
        writeCart(cart);
        sessionStorage.setItem("biofarm_offer_id", String(offer.id));
        rememberReferral(offer.referralCode);
        setDone(true);
    }
    return (
        <div className="mx-auto max-w-6xl px-4 pt-28">
            <Section title={offer?.title || "Предложение партнёра"}>
                {error ? (
                    <p role="alert">{error}</p>
                ) : offer ? (
                    <>
                        <ul>
                            {offer.items.map((i) => (
                                <li key={i.product.id}>
                                    {i.product.name} × {i.quantity} — {i.product.price.toLocaleString("ru-RU")} ₽
                                </li>
                            ))}
                        </ul>
                        <p>Выберите, как добавить товары. Текущая корзина: {readCart().length} позиций.</p>
                        <div className="flex flex-wrap gap-3">
                            <button className={buttonClass} onClick={() => apply(true)}>
                                Добавить к корзине
                            </button>
                            <button className={buttonClass} onClick={() => apply(false)}>
                                Заменить корзину
                            </button>
                            <button onClick={() => setDone(true)}>Отказаться</button>
                        </div>
                    </>
                ) : (
                    <p>Проверяем цены и наличие…</p>
                )}
            </Section>
        </div>
    );
}
