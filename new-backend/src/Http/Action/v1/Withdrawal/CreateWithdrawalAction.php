<?php

declare(strict_types=1);

namespace App\Http\Action\v1\Withdrawal;

use App\Components\Exception\DomainExceptionModule;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class CreateWithdrawalAction implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        throw new DomainExceptionModule('program', 'Use program withdrawals; historical bonus requests are read-only', 1, status: 409);
    }
}
