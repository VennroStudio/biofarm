import type { Certificate } from '../../../types';

export type CertificateForm = {
  id?: number;
  title: string;
  file_path: string;
  document_type: 'pdf' | 'image';
  product_id: string;
  description: string;
  is_active: boolean;
  sort_order: number;
};

export const emptyCertificateForm: CertificateForm = {
  title: '',
  file_path: '',
  document_type: 'pdf',
  product_id: '',
  description: '',
  is_active: true,
  sort_order: 0,
};

export function certificateFormFromCertificate(certificate: Certificate): CertificateForm {
  return {
    id: certificate.id,
    title: certificate.title,
    file_path: certificate.file_path,
    document_type: certificate.document_type,
    product_id: certificate.product_id ? String(certificate.product_id) : '',
    description: certificate.description || '',
    is_active: certificate.is_active,
    sort_order: certificate.sort_order,
  };
}

export function certificatePayloadFromForm(form: CertificateForm) {
  return {
    title: form.title,
    file_path: form.file_path,
    document_type: form.document_type,
    product_id: form.product_id ? Number(form.product_id) : null,
    description: form.description || null,
    is_active: form.is_active,
    sort_order: form.sort_order,
  };
}
