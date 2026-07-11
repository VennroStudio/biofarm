import { Badge } from '../../../shared/ui';

type WithdrawalTab = 'pending' | 'processed';

type Props = {
  processedCount: number;
  tab: WithdrawalTab;
  onChange: (tab: WithdrawalTab) => void;
};

export function WithdrawalTabs({ processedCount, tab, onChange }: Props) {
  return (
    <div className="mt-6 inline-flex rounded-md bg-[#eef1e8] p-1">
      <button
        type="button"
        className={`rounded-md px-4 py-2 text-sm font-semibold transition ${tab === 'pending' ? 'bg-white text-[#26382d] shadow-sm' : 'text-[#789083]'}`}
        onClick={() => onChange('pending')}
      >
        Ожидают
      </button>
      <button
        type="button"
        className={`rounded-md px-4 py-2 text-sm font-semibold transition ${tab === 'processed' ? 'bg-white text-[#26382d] shadow-sm' : 'text-[#789083]'}`}
        onClick={() => onChange('processed')}
      >
        Обработанные <Badge tone="gray" className="ml-2">{processedCount}</Badge>
      </button>
    </div>
  );
}
