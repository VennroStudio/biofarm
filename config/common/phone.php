<?php

declare(strict_types=1);

use App\Modules\User\Service\Phone\SmsAero;
use GuzzleHttp\Client;

use function App\Components\env;

return [
    SmsAero::class => static fn (): SmsAero => new SmsAero(
        new Client(), env('SMSAERO_EMAIL', ''), env('SMSAERO_API_KEY', ''),
        env('SMSAERO_SIGN', ''), env('SMSAERO_CALLBACK_URL', ''),
    ),
];
