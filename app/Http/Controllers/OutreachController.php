<?php

namespace App\Http\Controllers;

use App\Mail\OutreachMail;
use App\Models\Contact;
use App\Models\OutreachLog;
use App\Models\Website;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class OutreachController extends Controller
{
    /**
     * Check which of the selected websites are eligible to receive the outreach.
     * Rules:
     *  - must have a contact with a non-empty email
     *  - if only_past=true, website.status must be 'past'
     */
    /** Preview eligible recipients + counts (including how many will skip sensitive clause). */
    public function preview(Request $request)
    {
        $data = $request->validate([
            'ids'          => 'required|array',
            'ids.*'        => 'integer',
            'only_past'    => 'boolean',
            'template_key' => 'nullable|string|in:first,followup',
        ]);

        $onlyPast = (bool) ($data['only_past'] ?? false);
        $tplKey   = $data['template_key'] ?? 'first';

        $rows = Website::with(['contact'])->whereIn('id', $data['ids'])->get();

        $eligible = [];
        $skipped  = [];
        $noSpecialCount = 0;

        foreach ($rows as $w) {
            // basic checks
            if ($onlyPast && $w->status !== 'past') {
                $skipped[] = ['id' => $w->id, 'domain' => $w->domain_name, 'reason' => 'Status is not "past"'];
                continue;
            }
            $email = optional($w->contact)->email;
            if (!$email) {
                $skipped[] = ['id' => $w->id, 'domain' => $w->domain_name, 'reason' => 'No contact email'];
                continue;
            }

            // Count how many will skip the sensitive clause (only relevant for "first" template)
            if ($tplKey === 'first') {
                if (empty($w->special_topic_price)) {
                    $noSpecialCount++;
                }
            }

            $eligible[] = [
                'id'     => $w->id,
                'domain' => $w->domain_name,
                'email'  => $email,
            ];
        }

        return response()->json([
            'status' => 'ok',
            'data'   => [
                'eligible'        => $eligible,
                'skipped'         => $skipped,
                'no_special_count'=> $noSpecialCount,
            ],
        ]);
    }

    /** Send personalized emails in bulk. */
    public function send(Request $request)
    {
        $data = $request->validate([
            'ids'          => 'required|array',
            'ids.*'        => 'integer',
            'template_key' => 'required|string|in:first,followup',
            'target_url'   => 'nullable|string',
            'brand'        => 'nullable|string',
            'subject'      => 'required|string',
            'body'         => 'required|string',
            'only_past'    => 'boolean',
        ]);

        $onlyPast   = (bool) ($data['only_past'] ?? false);
        $tplKey     = $data['template_key'];
        $subjectTpl = $data['subject'];
        $bodyTpl    = $data['body'];

        $rows = Website::with(['contact'])->whereIn('id', $data['ids'])->get();

        $sent = 0; $failed = 0; $failedDetails = [];

        foreach ($rows as $w) {
            if ($onlyPast && $w->status !== 'past') {
                continue;
            }
            $email = optional($w->contact)->email;
            if (!$email) {
                continue;
            }

            // personalize
            $subject = $this->personalize($subjectTpl, $w, $data['brand'] ?? null, $data['target_url'] ?? null, $tplKey, true);
            $body    = $this->personalize($bodyTpl,    $w, $data['brand'] ?? null, $data['target_url'] ?? null, $tplKey, false);

            try {
                // Plain text send. Replace with your Mailer / Mailable if needed.
                Mail::raw($body, function ($m) use ($email, $subject) {
                    $m->to($email)->subject($subject);
                });
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
                $failedDetails[] = [
                    'id'     => $w->id,
                    'domain' => $w->domain_name,
                    'error'  => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'status' => 'ok',
            'data'   => [
                'sent'           => $sent,
                'failed'         => $failed,
                'failed_details' => $failedDetails,
            ],
        ]);
    }

    /** Replace placeholders and apply special-topic clause rules per website. */
    private function personalize(string $text, Website $w, ?string $brand, ?string $targetUrl, string $tplKey, bool $isSubject): string
    {
        // Base replacements
        $map = [
            '[domain]'               => (string) $w->domain_name,
            '[brand]'                => (string) ($brand ?: ''),
            '[target url]'           => (string) ($targetUrl ?: ''),
            '[publisher price]'      => $this->formatMoney($w->publisher_price, $w->currency_code),
            '[special topic price]'  => $this->formatMoney($w->special_topic_price, $w->currency_code),
        ];

        $out = strtr($text, $map);

        // If template is "first", remove the sensitive-topics clause when special_topic_price is empty
        if ($tplKey === 'first' && empty($w->special_topic_price)) {
            // Remove this exact clause including leading comma + space if present:
            // ", and that the rate for an article on sensitive topics is [special topic price]?"
            $out = preg_replace(
                '/,?\s*and that the rate for an article on sensitive topics is\s*\[special topic price\]\?/i',
                '?',
                $out
            );

            // If user already edited the text but left token elsewhere, clean any leftover token safely
            $out = str_ireplace('[special topic price]', '', $out);

            // Tidy up punctuation like ", ?" -> "?"
            $out = preg_replace('/,\s*\?/', '?', $out);
            // Remove double spaces
            $out = preg_replace('/\s{2,}/', ' ', $out);
        }

        // Final tidy
        $out = trim($out);

        // Subjects should be single-line
        if ($isSubject) {
            $out = preg_replace('/\s+/', ' ', $out);
        }

        return $out;
    }

    private function formatMoney($value, ?string $currency): string
    {
        if ($value === null || $value === '') return '';
        $value = (float) $value;
        $symbol = '€';
        if (is_string($currency)) {
            $c = strtoupper($currency);
            if ($c === 'USD') $symbol = '$';
            elseif ($c === 'EUR') $symbol = '€';
        }
        // Show without trailing .00 if integer-like
        $formatted = (floor($value) == $value) ? number_format($value, 0) : number_format($value, 2);
        return $symbol.' '.$formatted;
    }
}
