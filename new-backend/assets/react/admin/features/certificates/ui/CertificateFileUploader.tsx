import { Upload } from 'lucide-react';
import { type ChangeEvent, useRef, useState } from 'react';
import { certificatesApi } from '../../../api/resources';
import { messageFromError } from '../../../shared/lib';
import { Button } from '../../../shared/ui';

const maxDocumentSize = 30 * 1024 * 1024;

type Props = {
  onUploaded: (url: string, documentType: 'pdf' | 'image') => void;
};

export function CertificateFileUploader({ onUploaded }: Props) {
  const inputRef = useRef<HTMLInputElement | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function upload(file: File) {
    setLoading(true);
    setError(null);
    try {
      const asset = await certificatesApi.upload(file);
      onUploaded(asset.url, asset.mime_type === 'application/pdf' ? 'pdf' : 'image');
    } catch (uploadError) {
      setError(messageFromError(uploadError, 'Не удалось загрузить файл'));
    } finally {
      setLoading(false);
    }
  }

  function handleChange(event: ChangeEvent<HTMLInputElement>) {
    const file = event.currentTarget.files?.[0];
    if (file) {
      if (file.size > maxDocumentSize) {
        setError('Файл слишком большой. Максимальный размер: 30 МБ.');
        event.currentTarget.value = '';
        return;
      }

      void upload(file);
    }
    event.currentTarget.value = '';
  }

  return (
    <span className="inline-flex flex-col items-start gap-2">
      <input
        ref={inputRef}
        type="file"
        accept="application/pdf,image/jpeg,image/png,image/webp"
        className="sr-only"
        disabled={loading}
        onChange={handleChange}
      />
      <Button type="button" variant="ghost" disabled={loading} onClick={() => inputRef.current?.click()}>
        <Upload className="mr-2 h-4 w-4" />
        {loading ? 'Загрузка...' : 'Загрузить файл'}
      </Button>
      {error ? (
        <span className="text-sm font-medium text-red-600" role="alert">
          {error}
        </span>
      ) : null}
    </span>
  );
}
