import { FileCheck2, Plus } from 'lucide-react';
import type { FormEvent } from 'react';
import { useMemo, useState } from 'react';
import { certificatesApi, productsApi } from '../api/resources';
import {
  certificateFormFromCertificate,
  certificatePayloadFromForm,
  emptyCertificateForm,
  type CertificateForm,
} from '../features/certificates/model/certificateForm';
import { CertificateFormModal } from '../features/certificates/ui/CertificateFormModal';
import { CertificatesTable } from '../features/certificates/ui/CertificatesTable';
import { useLoadOnMount } from '../hooks/useLoadOnMount';
import { messageFromError } from '../shared/lib';
import { Badge, Button, Card, ErrorAlert, PageHeader, SearchField } from '../shared/ui';
import type { Certificate, Product } from '../types';

export function AdminCertificates() {
  const [certificates, setCertificates] = useState<Certificate[]>([]);
  const [products, setProducts] = useState<Product[]>([]);
  const [search, setSearch] = useState('');
  const [form, setForm] = useState<CertificateForm>(emptyCertificateForm);
  const [dialogOpen, setDialogOpen] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);

  const filteredCertificates = useMemo(
    () => certificates.filter((certificate) => `${certificate.title} ${certificate.product_name || ''}`.toLowerCase().includes(search.toLowerCase())),
    [certificates, search],
  );

  async function load() {
    const [certificateResult, productResult] = await Promise.all([
      certificatesApi.list(),
      productsApi.list(),
    ]);
    setCertificates(certificateResult.items);
    setProducts(productResult.items);
  }

  useLoadOnMount(load);

  function openCreate() {
    setError(null);
    setForm(emptyCertificateForm);
    setDialogOpen(true);
  }

  function openEdit(certificate: Certificate) {
    setError(null);
    setForm(certificateFormFromCertificate(certificate));
    setDialogOpen(true);
  }

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError(null);
    setSaving(true);
    try {
      if (form.id) {
        await certificatesApi.update(form.id, certificatePayloadFromForm(form));
      } else {
        await certificatesApi.create(certificatePayloadFromForm(form));
      }
      setDialogOpen(false);
      await load();
    } catch (submitError) {
      setError(messageFromError(submitError, 'Не удалось сохранить сертификат'));
    } finally {
      setSaving(false);
    }
  }

  async function remove(certificate: Certificate) {
    if (!confirm(`Удалить сертификат "${certificate.title}"?`)) {
      return;
    }
    setError(null);
    try {
      await certificatesApi.delete(certificate.id);
      await load();
    } catch (removeError) {
      setError(messageFromError(removeError, 'Не удалось удалить сертификат'));
    }
  }

  return (
    <>
      <PageHeader
        title="Сертификаты"
        subtitle="Документы качества для общей страницы и карточек товаров"
        actions={<Button onClick={openCreate}><Plus className="h-4 w-4" />Добавить сертификат</Button>}
      />

      <ErrorAlert className="mb-5">{dialogOpen ? null : error}</ErrorAlert>

      <Card className="p-6">
        <div className="mb-8 flex flex-wrap items-center gap-4">
          <SearchField placeholder="Поиск сертификатов..." value={search} onChange={setSearch} />
          <Badge tone="gray"><FileCheck2 className="mr-1 h-3.5 w-3.5" />{filteredCertificates.length} документов</Badge>
        </div>
        <CertificatesTable certificates={filteredCertificates} onEdit={openEdit} onRemove={(certificate) => void remove(certificate)} />
      </Card>

      <CertificateFormModal
        form={form}
        open={dialogOpen}
        products={products}
        error={dialogOpen ? error : null}
        saving={saving}
        setForm={setForm}
        onClose={() => {
          setDialogOpen(false);
          setError(null);
        }}
        onSubmit={(event) => void submit(event)}
      />
    </>
  );
}
