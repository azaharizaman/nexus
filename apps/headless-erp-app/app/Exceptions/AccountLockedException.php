<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Account Locked Exception
 *
 * Thrown when a user attempts to authenticate with a locked account.
 */
class AccountLockedException extends Exception
{
    /**
     * HTTP status code for this exception
     */
    protected $code = 423; // 423 Locked

    /**
     * Create a new exception instance
     *
     * @param  string  $message  Exception message
     */
    public function __construct(string $message = 'Account is locked')
    {
        parent::__construct($message, 423);
    }
}
