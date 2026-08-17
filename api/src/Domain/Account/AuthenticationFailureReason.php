<?php

declare(strict_types=1);

namespace Lotdg\Domain\Account;

enum AuthenticationFailureReason: string
{
    case CREDENTIAL_MISMATCH = 'credential-mismatch';

    case ACCOUNT_LOCKED = 'account-locked';

    case EMAIL_NOT_VALIDATED = 'email-not-validated';

    case ACCESS_BANNED = 'access-banned';
}
