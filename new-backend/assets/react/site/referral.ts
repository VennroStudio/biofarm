import { getReferralCode, request } from "./api";
export { getReferralCode } from "./api";
const key = "biofarm_referral";
export function rememberReferral(code: string) {
    if (!getReferralCode() && /^[a-zA-Z0-9_-]{1,100}$/.test(code))
        window.localStorage.setItem(key, JSON.stringify({ code, expiresAt: Date.now() + 30 * 24 * 60 * 60 * 1000 }));
}
export function mountReferralHandler() {
    const params = new URLSearchParams(window.location.search);
    const ref = params.get("ref");
    void validateAndRemember(ref);
    if (!ref) return;
    params.delete("ref");
    const query = params.toString();
    window.history.replaceState(
        {},
        "",
        `${window.location.pathname}${query ? `?${query}` : ""}${window.location.hash}`,
    );
}

async function validateAndRemember(incoming: string | null) {
    try {
        const previous = getReferralCode();
        const valid = async (code: string) => /^[a-zA-Z0-9_-]{1,100}$/.test(code)
            && (await request<{ valid: boolean }>(`/v1/referrals/${encodeURIComponent(code)}`)).valid;
        if (previous && !(await valid(previous)) && getReferralCode() === previous) {
            window.localStorage.removeItem(key);
        }
        if (!getReferralCode() && incoming && await valid(incoming)) rememberReferral(incoming);
    } catch {
        // A temporary network failure must not overwrite a valid first-touch attribution.
    }
}
