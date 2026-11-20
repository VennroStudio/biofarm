<?php

declare(strict_types=1);

return [
    'error.invalid_mime_type'     => 'Invalid file format.',
    'error.file_required'         => 'File was not provided.',
    'error.file_too_large'        => 'The file size is too large.',
    'error.file_upload_failed'    => 'Could not upload file.',
    'error.unauthorized'          => 'User is not authorized.',
    'error.invalid_token'         => 'Token is invalid or expired.',
    'error.missing_cookie'        => 'Missing token cookie.',
    'error.wrong_token_type'      => 'Wrong token type.',
    'error.invalid_claim'         => 'Invalid token claim.',
    'error.invalid_identity'      => 'Invalid identity.',
    'error.category_parent_cycle' => 'A category cannot be nested under its own child category.',
];
