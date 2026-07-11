import { Upload } from 'lucide-react';
import { useState } from 'react';
import { uploadImage } from '../api/client';
import { Button } from './ui';

type Props = {
  scope: string;
  onUploaded: (url: string) => void;
};

export function ImageUploader({ scope, onUploaded }: Props) {
  const [loading, setLoading] = useState(false);

  async function upload(file: File) {
    setLoading(true);
    try {
      const asset = await uploadImage(file, scope);
      onUploaded(asset.url);
    } finally {
      setLoading(false);
    }
  }

  return (
    <label className="inline-flex cursor-pointer items-center">
      <input
        type="file"
        accept="image/*"
        className="sr-only"
        onChange={(event) => {
          const file = event.currentTarget.files?.[0];
          if (file) {
            void upload(file);
          }
          event.currentTarget.value = '';
        }}
      />
      <Button type="button" variant="ghost" disabled={loading}>
        <Upload className="mr-2 h-4 w-4" />
        {loading ? 'Загрузка...' : 'Загрузить'}
      </Button>
    </label>
  );
}
