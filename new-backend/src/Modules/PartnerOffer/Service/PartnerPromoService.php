<?php

declare(strict_types=1);

namespace App\Modules\PartnerOffer\Service;

use App\Components\Exception\DomainExceptionModule;
use Doctrine\DBAL\Connection;

/** Read-only compatibility guard for codes issued by the retired partner feature. */
final readonly class PartnerPromoService
{
    public function __construct(private Connection $db) {}

    public function validateCheckout(?string $code): void
    {
        if ($code && $this->db->fetchOne('SELECT r.id FROM partner_promo_requests r JOIN promo_codes p ON p.id=r.promo_code_id WHERE p.code=?', [mb_strtoupper(trim($code))])) {
            throw new DomainExceptionModule('program', 'Промокоды партнёрской программы больше не действуют.', 29, status: 422);
        }
    }
}
