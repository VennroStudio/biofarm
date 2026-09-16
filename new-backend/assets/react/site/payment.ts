import { request } from "./api";
export type PaymentState = {
    configured: boolean;
    status: string;
    orderPaymentStatus: string;
    amountMinor: number;
    confirmationUrl?: string;
};
export function payment(orderId: string, start = false) {
    const token = window.sessionStorage.getItem(`biofarm_payment_${orderId}`);
    return request<PaymentState>(
        `/v1/payments/${encodeURIComponent(orderId)}`,
        start ? { method: "POST", body: { token } } : { headers: token ? { "X-Order-Token": token } : {} },
    );
}
export async function startPayment(orderId: string) {
    const result = await payment(orderId, true);
    if (result.confirmationUrl) window.location.assign(result.confirmationUrl);
    return result;
}
