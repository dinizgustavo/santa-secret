<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A request that is allowed in principle but breaks a group rule right now
 * (the group is locked, full, already joined...). Rendered as a translated
 * error toast on the previous page; see bootstrap/app.php.
 */
class GroupRuleViolation extends RuntimeException
{
    public static function because(string $translationKey): self
    {
        return new self(__($translationKey));
    }
}
