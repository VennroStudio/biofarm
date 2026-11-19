<?php

declare(strict_types=1);

namespace App\Http\Action\v1\Auth;

use App\Components\Exception\DomainExceptionModule;
use App\Components\Http\Cookie\CookieContext;
use App\Components\Http\Cookie\CookieManager;
use App\Components\Http\Middleware\Identity\RequestIdentity;
use App\Components\Http\Response\JsonDataResponse;
use App\Components\Privacy\ConsentRecorder;
use App\Components\Setting\SiteSettings;
use App\Components\Validator\Validator;
use App\Modules\Ozon\OzonPickupCheckout;
use App\Modules\PartnerOffer\Service\OfferService;
use App\Modules\Program\Service\ProgramService;
use App\Modules\User\Command\Auth\Login\LoginHandler;
use App\Modules\User\Command\User\Create\CreateUserCommand;
use App\Modules\User\Command\User\Create\CreateUserHandler;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;
use App\Modules\User\Service\Phone\PhoneAccounts;
use App\Modules\User\Service\Phone\PhoneChallenges;
use App\Modules\User\Service\Phone\PhoneLoginReceipt;
use App\Modules\User\Service\Phone\PhoneNumber;
use App\Modules\User\Service\Phone\SmsAero;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

use function App\Components\env;

final readonly class CheckoutPhoneAction implements RequestHandlerInterface
{
    public function __construct(
        private PhoneChallenges $challenges,
        private PhoneLoginReceipt $phoneLoginReceipt,
        private PhoneAccounts $accounts,
        private OzonPickupCheckout $ozon,
        private SiteSettings $settings,
        private CreateUserHandler $users,
        private LoginHandler $login,
        private CookieManager $cookies,
        private ConsentRecorder $consents,
        private Validator $validator,
        private OfferService $offers,
        private ProgramService $program,
        private LoggerInterface $logger,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->settings->bool('testing_enabled')) {
            throw new DomainExceptionModule('phone', SmsAero::UNAVAILABLE_MESSAGE, 73, status: 503);
        }
        $path = $request->getUri()->getPath();
        $loginOnly = str_starts_with($path, '/v1/phone-login/');
        $registration = str_starts_with($path, '/v1/phone-registration/');
        if ((!$loginOnly && !$registration && !$this->settings->bool('cart_enabled'))
            || (!$this->accounts->checkoutEnabled() && $this->ozon->method() === null)) {
            throw new DomainExceptionModule('phone', 'Подтверждение телефона сейчас недоступно.', 73, status: 404);
        }

        if ($registration && !$this->settings->bool('registration_enabled')) {
            throw new DomainExceptionModule('phone', 'Регистрация сейчас недоступна.', 76, status: 403);
        }
        $input = (array)$request->getParsedBody();
        $identity = RequestIdentity::find($request);
        $action = basename($path);
        if ($action === 'start') {
            if (($input['smsCodeRequested'] ?? null) !== true) {
                throw new DomainExceptionModule('phone', 'Подтвердите запрос кода на указанный номер.', 77, status: 422);
            }
            if (!$loginOnly) {
                $this->consents->requirePersonalData($input, $identity?->id);
            }
            $phone = PhoneNumber::normalize(is_string($input['phone'] ?? null) ? $input['phone'] : null);
            if ($phone === null) {
                throw new DomainExceptionModule('phone', 'Укажите мобильный номер в формате +7.', 70, status: 422);
            }
            $origin = (string)($request->getServerParams()['REMOTE_ADDR'] ?? '');
            $proxies = array_map('trim', explode(',', env('SMSAERO_TRUSTED_PROXIES', '')));
            $forwarded = $request->getHeaderLine('X-Real-IP');
            if (in_array($origin, $proxies, true) && filter_var($forwarded, FILTER_VALIDATE_IP) !== false) {
                $origin = $forwarded;
            }
            $consentUserId = $loginOnly ? $this->phoneLoginReceipt->rememberedUser($request, $phone) : $identity?->id;
            $recordRequest = function (string $challengeId) use ($request, $input, $identity, $consentUserId, $phone): void {
                $newConsent = $this->consents->requirePersonalData($input, $consentUserId, lock: true);
                if ($newConsent) {
                    $this->consents->record($request, $identity === null ? 'phone_verification' : 'user',
                        $identity === null ? $challengeId : (string)$identity->id, 'personal_data', true);
                }
                $this->consents->record($request, 'phone_verification', $challengeId, 'verification_sms', true, [
                    'action' => 'request_verification_code',
                    'channel' => 'sms',
                    'phoneDigest' => hash_hmac('sha256', $phone, env('APP_SECRET', env('JWT_SECRET', ''))),
                    'userId' => $consentUserId,
                ]);
            };
            return new JsonDataResponse($this->challenges->start($phone, $identity?->id, $origin, $recordRequest, $registration));
        }

        $challenge = $input['challenge'] ?? null;
        if (!is_string($challenge) || !preg_match('/^[a-f0-9]{64}$/D', $challenge)) {
            throw new DomainExceptionModule('phone', 'Запросите код подтверждения.', 74, status: 422);
        }

        if ($action === 'verify') {
            $code = $input['code'] ?? null;
            if ($code !== null && (!is_string($code) || !preg_match('/^[0-9]{4}$/D', $code))) {
                throw new DomainExceptionModule('phone', 'Введите четырёхзначный код.', 72, status: 422);
            }
            return new JsonDataResponse(['state' => $this->challenges->advance($challenge, $identity?->id, $code, $registration)]);
        }

        try {
            [$session, $newUser] = $this->challenges->consume($challenge, $identity?->id, function (string $phone, string $challengeId) use ($request, $identity, $input, $loginOnly, $registration): array {
                $account = $this->accounts->account($phone, $identity);
                if ($identity !== null) {
                    return [null, null];
                }
                if ($account) {
                    if ($loginOnly || $registration) {
                        $this->consents->attachPhoneLoginConsent($request, (int)$account['id'], $challengeId);
                    }
                    return [$this->login->issueSession((int)$account['id'], $account['first_name'], UserRole::USER), null];
                }
                if ($loginOnly) {
                    throw new DomainExceptionModule('phone', 'Аккаунт с подтверждённым номером не найден. Войдите по email или зарегистрируйтесь.', 75, status: 409);
                }

                $consentAcceptedAt = null;
                if ($registration) {
                    $consentAcceptedAt = $this->consents->requirePhoneRegistrationConsent($challengeId);
                } else {
                    $this->consents->assertForm($input);
                }
                $email = is_string($input['email'] ?? null) ? trim($input['email']) : '';
                $this->accounts->allowRegistration();
                $name = is_string($input['name'] ?? null) ? trim($input['name']) : '';
                $nameParts = preg_split('/\s+/u', $name, 2) ?: [];
                $firstName = $nameParts[0] ?? '';
                $lastName = $nameParts[1] ?? '';
                if ($lastName === '') {
                    throw new DomainExceptionModule('phone', 'Укажите имя и фамилию для регистрации.', 76, status: 422);
                }
                $referral = is_string($input['referredBy'] ?? null) ? $input['referredBy'] : '';
                if ($this->program->referralOwner($referral) === null && is_string($input['offerId'] ?? null) && $input['offerId'] !== '') {
                    $referral = $this->offers->read($input['offerId'], false)['referralCode'] ?? '';
                }
                $command = new CreateUserCommand(
                    lastName: $lastName,
                    firstName: $firstName,
                    email: $email,
                    password: 'Ab1!' . bin2hex(random_bytes(24)),
                    locale: 'ru',
                    referredBy: $referral,
                );
                $this->validator->validate($command);
                $user = $this->users->createWithVerifiedPhone($command);
                $userId = (int)$user->id;
                $this->accounts->bind($userId, $phone);
                if ($consentAcceptedAt !== null) {
                    $this->consents->record($request, 'user', (string)$userId, 'personal_data', true, [
                        'action' => 'phone_registration_confirmed',
                        'acceptedAt' => $consentAcceptedAt,
                        'challengeId' => $challengeId,
                    ]);
                } else {
                    $this->consents->recordForm($request, $input, 'user', (string)$userId);
                }

                return [$this->login->issueSession($userId, $user->firstName, $user->role), $user];
            }, $registration);
        } catch (UniqueConstraintViolationException) {
            throw new DomainExceptionModule('phone', 'Номер или email уже используется. Войдите в свой аккаунт.', 75, status: 409);
        }

        if ($newUser !== null) {
            try {
                $this->users->requestEmailConfirmation($newUser, 'ru');
            } catch (TransportExceptionInterface) {
                $this->logger->warning('Phone account confirmation email could not be sent.', ['user_id' => $newUser->id]);
            }
        }
        $response = (new JsonDataResponse([
            'access_token' => $session?->accessToken,
            'expires_in' => $session?->expiresIn,
        ]))->withHeader('Cache-Control', 'no-store');

        return $session === null ? $response : $this->cookies->apply(
            $response,
            new CookieContext(refreshToken: $session->refreshToken, loggedIn: '1'),
        );
    }
}
