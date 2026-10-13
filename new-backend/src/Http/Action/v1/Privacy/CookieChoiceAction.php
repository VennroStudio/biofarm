<?php

declare(strict_types=1);
namespace App\Http\Action\v1\Privacy;
use App\Components\Exception\DomainExceptionModule;
use App\Components\Http\Response\JsonDataResponse;
use App\Components\Privacy\ConsentRecorder;
use Doctrine\DBAL\Connection;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
final readonly class CookieChoiceAction implements RequestHandlerInterface
{
    public function __construct(private Connection $db, private ConsentRecorder $consents) {}
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body=(array)$request->getParsedBody();
        if (($body['version']??null)!==ConsentRecorder::VERSION || !\is_bool($body['analytics']??null) || !\is_bool($body['marketing']??null)) {
            throw new DomainExceptionModule('consent','Некорректные настройки cookies.',4,status:422);
        }
        $id=(string)($request->getCookieParams()['biofarm_cookie_id']??'');
        if (!preg_match('/^[a-f0-9]{32}$/D',$id)) $id=bin2hex(random_bytes(16));
        $this->db->transactional(function () use ($body,$request,$id): void {
            foreach (['analytics','marketing'] as $kind) $this->consents->record($request,'cookie',$id,'cookie_'.$kind,$body[$kind]);
        });
        $secure=$request->getUri()->getScheme()==='https' ? '; Secure' : '';
        return (new JsonDataResponse(['saved'=>true]))->withAddedHeader('Set-Cookie','biofarm_cookie_id='.$id.'; Path=/; Max-Age=15552000; SameSite=Lax; HttpOnly'.$secure);
    }
}
