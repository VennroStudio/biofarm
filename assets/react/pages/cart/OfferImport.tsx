import { type ReactNode, useEffect, useState } from "react";
import { request } from "../../site/api";
import { writeCart, type CartItem } from "../../site/cart";
import { rememberReferral } from "../../site/referral";
import { Section } from "../../program/shared";

type Offer = {
    id: string;
    title: string;
    items: CartItem[];
    referralCode: string;
};

export function OfferImport({ children }: { children?: ReactNode }) {
    const [error, setError] = useState("");
    const [done, setDone] = useState(false);
    const token = new URLSearchParams(window.location.search).get("offer");
    const checkout = children !== undefined;
    const invalidToken = token !== null && !/^[a-f0-9]{48}$/.test(token);
    const message = invalidToken ? "Ссылка на корзину некорректна." : error;

    useEffect(() => {
        if (token === null || invalidToken) return;
        let cancelled = false;
        void request<Offer>(`/v1/offers/${encodeURIComponent(token)}`)
            .then((offer) => {
                if (cancelled) return;
                if (offer.items.length === 0) throw new Error("В корзине нет товаров.");
                // A different offer must not resume an unrelated unfinished order.
                if (sessionStorage.getItem("biofarm_offer_id") !== offer.id) {
                    sessionStorage.removeItem("biofarm_checkout_order");
                    sessionStorage.removeItem("biofarm_checkout_request");
                }
                writeCart(offer.items);
                sessionStorage.setItem("biofarm_offer_id", offer.id);
                rememberReferral(offer.referralCode);
                if (!checkout) {
                    window.location.replace("/checkout");
                    return;
                }
                const url = new URL(window.location.href);
                url.searchParams.delete("offer");
                window.history.replaceState(window.history.state, "", url);
                setDone(true);
            })
            .catch((e: unknown) => {
                if (!cancelled) setError(e instanceof Error ? e.message : "Попробуйте открыть ссылку ещё раз.");
            });
        return () => { cancelled = true; };
    }, [token, checkout, invalidToken]);

    if (token === null || done) return children ?? null;
    return (
        <section className="bg-secondary/30 pb-10 pt-[120px] md:pt-[128px]">
            <div className="container mx-auto px-4 sm:px-6">
                <Section title={message ? "Корзина партнёра недоступна" : "Оформление заказа"}>
                    {message ? (
                        <div role="alert" className="space-y-3">
                            <p>{message}</p>
                            <p>Обратитесь к партнёру за новой ссылкой или выберите товары в каталоге.</p>
                            <a href="/catalog" className="text-primary underline">Перейти в каталог</a>
                        </div>
                    ) : <p role="status">Проверяем цены и наличие товаров…</p>}
                </Section>
            </div>
        </section>
    );
}
