import type { ReactNode } from 'react';

type Props = {
  children: ReactNode;
  checked: boolean;
  name: string;
  onChange: (value: string) => void;
  value: string;
};

export function RadioOption({ children, checked, name, onChange, value }: Props) {
  return (
    <label className="flex cursor-pointer items-center space-x-3 rounded-lg border p-3 transition-colors hover:bg-muted/50">
      <input
        checked={checked}
        className="h-4 w-4 accent-primary"
        name={name}
        type="radio"
        value={value}
        onChange={() => onChange(value)}
      />
      {children}
    </label>
  );
}
