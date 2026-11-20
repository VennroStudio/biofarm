import { Package } from 'lucide-react';

export function ProductGroupThumbnail({ image }: { image: string | null }) {
  return <span className="grid h-12 w-12 shrink-0 place-items-center overflow-hidden rounded-lg bg-[#f3f8f7]">
    {image ? <img src={image} alt="" width="48" height="48" className="h-full w-full object-contain" loading="lazy" />
      : <Package aria-hidden="true" className="h-5 w-5 text-[#5f7580]" />}
  </span>;
}
