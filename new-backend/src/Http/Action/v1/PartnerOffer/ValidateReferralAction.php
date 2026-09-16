<?php

declare(strict_types=1);

namespace App\Http\Action\v1\PartnerOffer;

use App\Components\Http\Response\JsonDataResponse;
use App\Components\Router\Route;
use App\Components\Setting\SiteSettings;
use Doctrine\DBAL\Connection;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class ValidateReferralAction implements RequestHandlerInterface
{
    public function __construct(private Connection $db, private SiteSettings $settings) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $code = (string)Route::getArgument($request, 'code');
        $valid = $this->settings->bool('referral_enabled')
            && preg_match('/^[a-zA-Z0-9_-]{1,100}$/D', $code)
            && (bool)$this->db->fetchOne(
                'SELECT p.user_id FROM user_profiles p JOIN users u ON u.id=p.user_id WHERE (p.referral_code=? OR p.user_id=?) AND u.deleted_at IS NULL AND u.status=1 LIMIT 1',
                [$code, ctype_digit($code) ? (int)$code : 0],
            );
        return new JsonDataResponse(['valid' => (bool)$valid]);
    }
}
