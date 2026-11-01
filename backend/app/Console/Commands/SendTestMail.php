<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Checks the SMTP settings in .env: sends one plain email right away (not queued) and shows the mail server's error.
 */
#[Signature('mail:test {to : Address to send the test email to}')]
#[Description('Send a test email to check the MAIL_* settings')]
class SendTestMail extends Command
{
    public function handle(): int
    {
        $to = (string) $this->argument('to');

        try {
            Mail::raw('This is a test email from Disrupt Cyprus. If you can read this, email sending works.', function ($message) use ($to): void {
                $message->to($to)->subject('Disrupt Cyprus test email');
            });
        } catch (Throwable $exception) {
            $this->error('Sending failed: '.$exception->getMessage());
            $this->line('Check MAIL_HOST, MAIL_PORT, MAIL_SCHEME, MAIL_USERNAME and MAIL_PASSWORD in backend/.env, then run: php artisan optimize');

            return self::FAILURE;
        }

        $this->info("Sent to {$to} from ".config('mail.from.address').'. Check the inbox (and the spam folder).');

        return self::SUCCESS;
    }
}
