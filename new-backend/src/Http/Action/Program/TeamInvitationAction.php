<?php

declare(strict_types=1);

namespace App\Http\Action\Program;

use App\Components\Exception\DomainExceptionModule;
use App\Components\Http\Response\JsonDataResponse;
use App\Components\Router\Route;
use App\Components\Setting\SiteSettings;
use App\Modules\Program\Service\ProgramService;
use DomainException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class TeamInvitationAction implements RequestHandlerInterface
{
    public function __construct(private ProgramService $program, private SiteSettings $settings) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            if (!$this->settings->bool('referral_enabled')) {
                throw new DomainException('Программа отключена');
            }
            return new JsonDataResponse($this->program->teamInvitation((string)Route::getArgument($request, 'code')));
        } catch (DomainException $e) {
            throw new DomainExceptionModule('program', $e->getMessage(), 1, status: 422);
        }
    }
}
