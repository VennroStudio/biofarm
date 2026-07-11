import type { PropsWithChildren, ReactNode } from 'react';

export function Field({ children, label }: PropsWithChildren<{ label: ReactNode }>) {
  return (
    <label className="grid gap-2 text-sm font-semibold text-[#26382d]">
      <span>{label}</span>
      {children}
    </label>
  );
}

export const inputClass =
  'h-11 w-full rounded-md border border-[#d9dece] bg-[#fbfaf4] px-3 text-sm outline-none transition placeholder:text-[#9aac9f] focus:border-[#2f7d4b] focus:bg-white';

export const textareaClass =
  'min-h-28 w-full rounded-md border border-[#d9dece] bg-[#fbfaf4] px-3 py-2 text-sm outline-none transition placeholder:text-[#9aac9f] focus:border-[#2f7d4b] focus:bg-white';
