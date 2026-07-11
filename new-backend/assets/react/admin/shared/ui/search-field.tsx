import { Search } from 'lucide-react';
import { cn } from '../lib';
import { inputClass } from './field';

type SearchFieldProps = {
  className?: string;
  onChange: (value: string) => void;
  placeholder: string;
  value: string;
};

export function SearchField({ className, onChange, placeholder, value }: SearchFieldProps) {
  return (
    <div className={cn('relative w-full max-w-sm', className)}>
      <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#789083]" />
      <input className={`${inputClass} pl-10`} placeholder={placeholder} value={value} onChange={(event) => onChange(event.target.value)} />
    </div>
  );
}
