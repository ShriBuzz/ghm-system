<?php

namespace App\Support;

use Illuminate\Queue\Failed\DatabaseUuidFailedJobProvider;
use RuntimeException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Throwable;

/**
 * Laravel's failed_jobs table, keeping a failed job's error the way the log does: a mail
 * server that refuses an email quotes the recipient's address, which is never stored in clear (S3, S5).
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
        return $e instanceof TransportExceptionInterface
            ? new RuntimeException('An email could not be sent (mail server code '.$e->getCode().').')
            : $e;
    }
}
