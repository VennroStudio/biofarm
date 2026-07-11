export function mountReferralHandler() {
  const params = new URLSearchParams(window.location.search);
  const ref = params.get('ref');

  if (!ref) {
    return;
  }

  window.localStorage.setItem('referralCode', ref);
  params.delete('ref');

  const query = params.toString();
  window.history.replaceState({}, '', `${window.location.pathname}${query ? `?${query}` : ''}${window.location.hash}`);
}
