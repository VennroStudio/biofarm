import { Edit, ExternalLink, Trash2 } from 'lucide-react';
import { Badge, Button, EmptyState } from '../../../shared/ui';
import type { Certificate } from '../../../types';

type Props = {
  certificates: Certificate[];
  onEdit: (certificate: Certificate) => void;
  onRemove: (certificate: Certificate) => void;
};

export function CertificatesTable({ certificates, onEdit, onRemove }: Props) {
  if (certificates.length === 0) {
    return <EmptyState>Сертификаты пока не добавлены</EmptyState>;
  }

  return (
    <div className="grid gap-3">
      {certificates.map((certificate) => (
        <article key={certificate.id} className="rounded-lg border border-[#dfece9] bg-white p-5 shadow-sm">
          <div className="flex flex-wrap items-start justify-between gap-4">
            <div className="min-w-0">
              <div className="flex flex-wrap items-center gap-2">
                <h2 className="font-bold text-[#294555]">{certificate.title}</h2>
                <Badge tone={certificate.is_active ? 'green' : 'gray'}>{certificate.is_active ? 'Активен' : 'Выключен'}</Badge>
                <Badge tone="gray">{certificate.document_type.toUpperCase()}</Badge>
              </div>
              <p className="mt-1 text-sm text-[#5f7580]">{certificate.product_name || 'Общий сертификат'}</p>
              {certificate.description && <p className="mt-2 max-w-3xl text-sm text-[#526d78]">{certificate.description}</p>}
            </div>
            <div className="flex gap-2">
              <a
                href={certificate.file_path}
                target="_blank"
                rel="noreferrer"
                className="grid h-9 w-9 place-items-center rounded-md text-[#526d78] transition hover:bg-[#eaf5f1]"
                title="Открыть файл"
              >
                <ExternalLink className="h-4 w-4" />
              </a>
              <Button variant="ghost" size="icon" onClick={() => onEdit(certificate)} title="Изменить">
                <Edit className="h-4 w-4" />
              </Button>
              <Button variant="ghost" size="icon" className="text-[#ef4444]" onClick={() => onRemove(certificate)} title="Удалить">
                <Trash2 className="h-4 w-4" />
              </Button>
            </div>
          </div>
        </article>
      ))}
    </div>
  );
}
