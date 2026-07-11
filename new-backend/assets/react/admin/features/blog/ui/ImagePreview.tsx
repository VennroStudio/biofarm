import { ImageOff } from 'lucide-react';
import { useState } from 'react';

type Props = {
  src: string;
  title: string;
};

export function ImagePreview({ src, title }: Props) {
  const [failed, setFailed] = useState(false);

  if (!src || failed) {
    return (
      <span className="grid h-12 w-12 place-items-center rounded bg-[#eef1e8] text-[#789083]">
        <ImageOff className="h-4 w-4" />
      </span>
    );
  }

  return <img src={src} alt={title} className="h-12 w-12 rounded object-cover" onError={() => setFailed(true)} />;
}
