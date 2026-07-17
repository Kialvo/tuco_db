<?php

namespace App\Services;

use App\Models\CrmNotification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Tuco's write path into the org-wide notification hub (the shared
 * `notifications` MySQL table owned by the Menford CRM).
 *
 * Semantics replicate menford-crm's createNotification() exactly:
 *  - recipient_email lowercased/trimmed, always populated (the cross-app key)
 *  - user_id resolved from `crm_users` by email when a match exists —
 *    that's what makes the row appear in the CRM's central bell
 *  - source_app='tuco', from_user_id stays NULL (it references crm_users),
 *    the actor's display name travels in from_user_name
 *  - links are ABSOLUTE URLs so the CRM bell can deep-link back to tuco
 *
 * Failures are silently non-fatal (logged): notifications must never break
 * the feature that fires them — same philosophy as SOPs' notifyCrm().
 */
class NotificationHub
{
    /**
     * @param array{
     *   type: string,
     *   recipients: iterable<User|string|null>,
     *   body: string,
     *   entity_type?: string|null,
     *   entity_id?: string|int|null,
     *   entity_label?: string|null,
     *   link?: string|null,
     *   from_user_name?: string|null,
     *   exclude?: User|string|null,
     * } $payload
     */
    public static function notify(array $payload): void
    {
        try {
            $exclude = static::email($payload['exclude'] ?? null);

            $emails = collect($payload['recipients'] ?? [])
                ->map(fn ($r) => static::email($r))
                ->filter()
                ->unique()
                ->reject(fn ($e) => $e === $exclude)
                ->values();

            if ($emails->isEmpty()) {
                return;
            }

            // Resolve crm_users ids in one query (shared table, read-only).
            $crmIds = DB::table('crm_users')
                ->whereIn('email', $emails)
                ->pluck('id', 'email')
                ->mapWithKeys(fn ($id, $email) => [mb_strtolower($email) => (int) $id]);

            foreach ($emails as $email) {
                CrmNotification::create([
                    'user_id'         => $crmIds[$email] ?? null,
                    'recipient_email' => $email,
                    'type'            => mb_substr($payload['type'], 0, 50),
                    'source_app'      => CrmNotification::SOURCE,
                    'entity_type'     => isset($payload['entity_type']) ? mb_substr((string) $payload['entity_type'], 0, 30) : null,
                    'entity_id'       => isset($payload['entity_id']) ? mb_substr((string) $payload['entity_id'], 0, 255) : null,
                    'entity_label'    => isset($payload['entity_label']) ? mb_substr((string) $payload['entity_label'], 0, 255) : null,
                    'body'            => mb_substr($payload['body'], 0, 1000),
                    'link'            => isset($payload['link']) ? mb_substr((string) $payload['link'], 0, 512) : null,
                    'from_user_name'  => isset($payload['from_user_name']) ? mb_substr((string) $payload['from_user_name'], 0, 100) : null,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('[NotificationHub] failed to create notification: ' . $e->getMessage());
        }
    }

    /**
     * Standard event: a new (marketplace) user registered — tell the tuco
     * admins with real work emails (test/@example.com accounts excluded).
     */
    public static function userRegistered(User $newUser): void
    {
        static::notify([
            'type'           => 'user_registered',
            'recipients'     => User::where('role', 'admin')
                ->where('email', 'not like', '%@example.com')
                ->get(),
            'entity_type'    => 'user',
            'entity_id'      => (string) $newUser->id,
            'entity_label'   => $newUser->name,
            'body'           => 'New user registered: ' . $newUser->name . ' (' . $newUser->email . ')',
            'link'           => route('admin.users.index'),
            'from_user_name' => 'Linkinablink',
        ]);

        static::newUserAlerts($newUser);
    }

    /**
     * External alerts for a completed registration: email to the team inbox
     * + Discord webhook (config: services.admin_alerts). Each channel is
     * independently non-fatal — an outage must never break the verification
     * page that fires this.
     */
    public static function newUserAlerts(User $newUser): void
    {
        try {
            \Illuminate\Support\Facades\Mail::to(config('services.admin_alerts.new_user_email'))
                ->send(new \App\Mail\NewUserRegisteredAdminMail($newUser));
        } catch (\Throwable $e) {
            Log::warning('[NotificationHub] new-user email alert failed: ' . $e->getMessage());
        }

        try {
            if ($url = config('services.admin_alerts.discord_webhook_url')) {
                \Illuminate\Support\Facades\Http::timeout(5)->post($url, [
                    'content' => "There's a new user on Linkinablink. Here are their details:\n"
                        . '**Name:** ' . $newUser->name . "\n"
                        . '**Email:** ' . $newUser->email,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('[NotificationHub] new-user discord alert failed: ' . $e->getMessage());
        }
    }

    /** Normalize a recipient (User model or raw address) to a lowercase email. */
    private static function email(User|string|null $recipient): ?string
    {
        $email = $recipient instanceof User ? $recipient->email : $recipient;
        $email = mb_strtolower(trim((string) $email));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }
}
