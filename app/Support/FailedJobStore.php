<?php

namespace App\Support;

use Illuminate\Queue\Failed\DatabaseUuidFailedJobProvider;
use RuntimeException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Exception\RfcComplianceException;
use Throwable;

/**
 * Laravel's failed_jobs table, keeping a failed job's error the way the log does: a mail
 * server that refuses an email quotes the recipient's address, which is never stored in clear.
 */
class FailedJobStore extends DatabaseUuidFailedJobProvider
{
    public function log($connection, $queue, $payload, $exception)
    {
        return parent::log($connection, $queue, $payload, self::withoutAddress($exception));
    }

    /**
     * A refused email's error with only the mail server's code, never the address it quoted.
     * Used for the log too (`bootstrap/app.php`).
     */
    public static function withoutAddress(Throwable $e): Throwable
    {
        return match (true) {
            $e instanceof TransportExceptionInterface => new RuntimeException('An email could not be sent (mail server code '.$e->getCode().').'),
            // The mailer refuses an address it cannot use before sending, quoting it the same way.
            $e instanceof RfcComplianceException => new RuntimeException('An email could not be sent (the address is not valid).'),
            default => $e,
        };
    }
}
