import type { PropsWithChildren, ReactNode } from 'react';

export function Field({ children, label }: PropsWithChildren<{ label: ReactNode }>) {
  return (
    <label className="grid gap-2 text-sm font-semibold text-[#294555]">
      <span>{label}</span>
      {children}
    </label>
  );
}

export const inputClass =
  'h-10 w-full rounded-xl border border-[#cfe2de] bg-white px-3 text-sm text-[#294555] outline-none transition placeholder:text-[#8ca2aa] focus:border-[#2e8175] focus:ring-2 focus:ring-[#2e8175]/15';

export const textareaClass =
  'min-h-28 w-full rounded-xl border border-[#cfe2de] bg-white px-3 py-2 text-sm text-[#294555] outline-none transition placeholder:text-[#8ca2aa] focus:border-[#2e8175] focus:ring-2 focus:ring-[#2e8175]/15';
