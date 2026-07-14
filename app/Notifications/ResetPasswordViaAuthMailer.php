<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;

/**
 * Stock password-reset notification, delivered through the dedicated
 * 'auth' mailer (Resend API as noreply@linkinablink.com) instead of the
 * default gmail sender — same treatment as email verification.
 *
 * NOTE: $mailer stays UNTYPED on purpose — adding a type to a property
 * the framework declares untyped is a PHP fatal (bit us in PR #31).
 */
class ResetPasswordViaAuthMailer extends ResetPassword
{
    public $mailer = 'auth';
}
