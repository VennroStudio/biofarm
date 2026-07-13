import { Upload } from 'lucide-react';
import { type ChangeEvent, useRef, useState } from 'react';
import { uploadImage } from '../../../api/client';
import { Button } from '../../../shared/ui';

type Props = {
  scope: string;
  onUploaded: (url: string) => void;
};

export function ImageUploader({ scope, onUploaded }: Props) {
  const inputRef = useRef<HTMLInputElement | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function upload(file: File) {
    setLoading(true);
    setError(null);
    try {
      const asset = await uploadImage(file, scope);
      onUploaded(asset.url);
    } catch (uploadError) {
      setError(uploadError instanceof Error ? uploadError.message : 'Не удалось загрузить изображение');
    } finally {
      setLoading(false);
    }
  }

  function handleChange(event: ChangeEvent<HTMLInputElement>) {
    const file = event.currentTarget.files?.[0];
    if (file) {
      void upload(file);
    }
    event.currentTarget.value = '';
  }

  return (
    <span className="inline-flex flex-col items-start gap-2">
      <input
        ref={inputRef}
        type="file"
        accept="image/*"
        className="sr-only"
        disabled={loading}
        onChange={handleChange}
      />
      <Button type="button" variant="ghost" disabled={loading} onClick={() => inputRef.current?.click()}>
        <Upload className="mr-2 h-4 w-4" />
        {loading ? 'Загрузка...' : 'Загрузить'}
      </Button>
      {error ? (
        <span className="text-sm font-medium text-red-600" role="alert">
          {error}
        </span>
      ) : null}
    </span>
  );
}
