import { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { ArrowLeft } from 'lucide-react';
import { getToken, refreshUser, type SiteUser } from '../../site/api';
import { ReferralPanel } from './components/ReferralPanel';

function PartnerPage({ referralEnabled, withdrawalsEnabled }: { referralEnabled: boolean; withdrawalsEnabled: boolean }) {
  const [user, setUser] = useState<SiteUser | null>(null);
  const [error, setError] = useState('');
  useEffect(() => {
    let live = true;
    if (!getToken()) {
      window.location.replace('/login?redirect=/partner');
      return;
    }
    void refreshUser().then((freshUser) => {
      if (!live) return;
      if (!freshUser) window.location.replace('/login?redirect=/partner');
      else if (!freshUser.isPartner && !freshUser.isTeamMember && !freshUser.hasCommissionHistory) window.location.replace('/profile');
      else setUser(freshUser);
    }).catch(() => {
      if (!live) return;
      if (!getToken()) window.location.replace('/login?redirect=/partner');
      else setError('Не удалось загрузить кабинет. Обновите страницу.');
    });
    return () => { live = false; };
  }, []);
  return (
    <section className="min-h-[70vh] bg-secondary/30 pb-12 pt-[120px] md:pt-[128px]">
      <div className="container mx-auto px-4 sm:px-6">
        <a href="/profile" className="mb-5 inline-flex items-center gap-2 text-sm text-primary hover:underline"><ArrowLeft className="h-4 w-4" />Личный кабинет</a>
        <header className="mb-8">
          <h1 className="text-3xl font-normal tracking-tight text-primary md:text-4xl">{user?.isPartner ? 'Кабинет партнёра' : user?.isTeamMember ? 'Кабинет участника команды' : 'История начислений и выплаты'}</h1>
          {user && <p className="mt-2 text-muted-foreground">{user.name}</p>}
        </header>
        {!referralEnabled ? <p>Программа сейчас недоступна.</p> : !user ? <p role={error ? 'alert' : 'status'}>{error || 'Загрузка кабинета…'}</p> : <ReferralPanel withdrawalsEnabled={withdrawalsEnabled} />}
      </div>
    </section>
  );
}

export function mountPartnerPage() {
  document.querySelectorAll<HTMLElement>('[data-react-island="partner-page"]').forEach((root) => {
    if (root.dataset.mounted === 'true') return;
    root.dataset.mounted = 'true';
    createRoot(root).render(<PartnerPage referralEnabled={root.dataset.referralEnabled !== 'false'} withdrawalsEnabled={root.dataset.withdrawalsEnabled !== 'false'} />);
  });
}
