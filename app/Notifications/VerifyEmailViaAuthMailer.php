<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;

/**
 * Stock email-verification notification, delivered through the dedicated
 * 'auth' mailer (AUTH_MAIL_* env — the same transport the admin
 * temporary-password mail uses) instead of the default gmail-address
 * sender, whose mail to Google Workspace domains was silently filtered.
 *
 * NOTE: $mailer stays UNTYPED on purpose — adding a type to a property
 * the framework declares untyped is a PHP fatal (bit us in PR #31).
 */
class VerifyEmailViaAuthMailer extends VerifyEmail
{
    public $mailer = 'auth';
}
