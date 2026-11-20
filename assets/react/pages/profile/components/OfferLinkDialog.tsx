import type { ReactNode } from 'react';
import { LinkQR } from '../../../program/shared';
import { PartnerDialog } from './PartnerDialog';

export function OfferLinkDialog({ url, onClose, title = 'Ссылка на корзину', description = 'Сканируйте QR-код или отправьте ссылку покупателю.', children }: { url: string; onClose: () => void; title?: string; description?: string; children?: ReactNode }) {
    return <PartnerDialog title={title} onClose={onClose}>
        <p className="mb-4 text-sm text-muted-foreground">{description}</p>
        <LinkQR url={url} centered />
        {children}
    </PartnerDialog>;
}
