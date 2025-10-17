<?php

namespace App\Http\Controllers;

use App\Mail\OutreachMail;
use App\Models\Website;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

class OutreachController extends Controller
{
    /**
     * Preview eligible recipients and counts (includes how many will skip the sensitive clause).
     */
    public function preview(Request $request)
    {
        $data = $request->validate([
            'ids'          => 'required|array',
            'ids.*'        => 'integer',
            'only_past'    => 'boolean',
            'template_key' => 'nullable|string|in:first,followup',
            'language'     => 'nullable|string|in:' . implode(',', array_keys(Config::get('outreach.languages'))),
        ]);

        $onlyPast = (bool) ($data['only_past'] ?? false);
        $tplKey   = $data['template_key'] ?? 'first';

        $rows = Website::with(['contact'])->whereIn('id', $data['ids'])->get();

        $eligible = [];
        $skipped  = [];
        $noSpecialCount = 0;

        foreach ($rows as $w) {
            if ($onlyPast && $w->status !== 'past') {
                $skipped[] = ['id' => $w->id, 'domain' => $w->domain_name, 'reason' => 'Status is not "past"'];
                continue;
            }

            $email = optional($w->contact)->email;
            if (!$email) {
                $skipped[] = ['id' => $w->id, 'domain' => $w->domain_name, 'reason' => 'No contact email'];
                continue;
            }

            if ($tplKey === 'first' && (empty($w->special_topic_price) && $w->special_topic_price !== 0 && $w->special_topic_price !== '0')) {
                $noSpecialCount++;
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
                'eligible'         => $eligible,
                'skipped'          => $skipped,
                'no_special_count' => $noSpecialCount,
            ],
        ]);
    }

    /**
     * Send personalized emails in bulk (multi-language, per-recipient sensitive line).
     */
    public function send(Request $request)
    {
        $data = $request->validate([
            'ids'          => 'required|array',
            'ids.*'        => 'integer',
            'template_key' => 'required|string|in:first,followup',
            'language'     => 'nullable|string',
            'target_url'   => 'nullable|string',
            'brand'        => 'nullable|string',
            'subject'      => 'required|string',
            'body'         => 'required|string',
            'only_past'    => 'boolean',
            'cc_me'        => 'nullable|boolean',
        ]);

        $onlyPast   = (bool) ($data['only_past'] ?? false);
        $tplKey     = $data['template_key'];
        $lang       = $data['language'] ?: 'en';
        $subjectTpl = $data['subject'];
        $bodyTpl    = $data['body'];
        $ccMe       = (bool)($data['cc_me'] ?? false);

        $rows = Website::with(['contact'])->whereIn('id', $data['ids'])->get();

        $sent = 0; $failed = 0; $failedDetails = [];

        foreach ($rows as $w) {
            if ($onlyPast && $w->status !== 'past') continue;
            $email = optional($w->contact)->email;
            if (!$email) continue;

            $subject = $this->personalize($subjectTpl, $w, $data['brand'] ?? null, $data['target_url'] ?? null, $tplKey, $lang, true);
            $body    = $this->personalize($bodyTpl,    $w, $data['brand'] ?? null, $data['target_url'] ?? null, $tplKey, $lang, false);

            try {
                $alwaysBcc = config('mail.always_bcc');

                Mail::raw($body, function ($m) use ($email, $subject, $ccMe, $alwaysBcc) {
                    $m->to($email)->subject($subject);
                    if ($ccMe && config('mail.from.address')) {
                        $m->bcc(config('mail.from.address'));
                    }
                    if ($alwaysBcc) {
                        $m->bcc($alwaysBcc);
                    }
                });

                \Log::info('Outreach sent', ['to' => $email, 'subject' => $subject]);
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
            'data'   => compact('sent', 'failed') + ['failed_details' => $failedDetails],
        ]);
    }

    /** Replace placeholders and apply per-language sensitive line logic. */
    private function personalize(
        string $text,
        Website $w,
        ?string $brand,
        ?string $targetUrl,
        string $tplKey,
        string $lang,
        bool $isSubject
    ): string {
        // 1) Basic placeholders
        $map = [
            '[domain]'              => (string) $w->domain_name,
            '[brand]'               => (string) ($brand ?: ''),
            '[target url]'          => (string) ($targetUrl ?: ''),
            '[publisher price]'     => $this->formatMoney($w->publisher_price,     $w->currency_code),
            '[special topic price]' => $this->formatMoney($w->special_topic_price, $w->currency_code),
        ];
        $out = strtr($text, $map);

        // 2) Handle @{{ sensitive_line }} (and common variants) ONLY for "first" template
        //    Accept any of these: @{{ sensitive_line }}, {{ sensitive_line }}, {{sensitive_line}}, [[sensitive_line]], [sensitive_line]
        $tokenRegex = '/(@\{\{\s*sensitive_line\s*\}\}|\{\{\s*sensitive_line\s*\}\}|\[\[\s*sensitive_line\s*\]\]|\[sensitive_line\])/i';

        if ($tplKey === 'first') {
            $lineTpls = (array) config('outreach.sensitive_line', []);
            $lineTpl  = $lineTpls[$lang] ?? $lineTpls['en'] ?? '';

            if (!empty($w->special_topic_price) && $lineTpl) {
                // Build localized line, e.g. "and that the rate ... is [special topic price]?"
                $line = strtr($lineTpl, [
                    '[special topic price]' => $this->formatMoney($w->special_topic_price, $w->currency_code),
                ]);

                // Replace any token form with the line
                $out = preg_replace($tokenRegex, $line, $out);
            } else {
                // No special price: drop the token entirely
                $out = preg_replace($tokenRegex, '', $out);
            }
        } else {
            // Follow-up: just remove token if it exists
            $out = preg_replace($tokenRegex, '', $out);
        }

        // 3) General punctuation tidy-ups (handles cases like "… price{{token}}?" or double question marks)
        // remove extra spaces before punctuation
        // Punctuation tidying (preserve line breaks)
        $out = preg_replace('/[ \t]+([,.;!?])/', '$1', $out); // spaces before punctuation
        $out = preg_replace('/,\s*\?/', '?', $out);           // ", ?" -> "?"
        $out = preg_replace('/\?{2,}/', '?', $out);           // "??" -> "?"
        $out = preg_replace('/[ \t]{2,}/', ' ', $out);        // collapse spaces/tabs only (keep \r\n)
        $out = preg_replace("/(\r?\n){3,}/", "\n\n", $out);   // 3+ blank lines -> one empty line

        $out = trim($out);

        if ($isSubject) {
            // Subjects should be single-line, spaces only
            $out = preg_replace('/\s+/', ' ', $out);
        }


        return $out;
    }


    private function formatMoney($value, ?string $currency): string
    {
        if ($value === null || $value === '') return '';

        // Always display Euro symbol
        $symbol = '€';

        // Ensure it’s a clean numeric
        $value = (float) $value;

        // Format: no decimals if integer, 2 decimals otherwise
        $formatted = (floor($value) == $value)
            ? number_format($value, 0, ',', '.')
            : number_format($value, 2, ',', '.');

        // Example output: "€ 85" or "€ 123,50"
        return $symbol . ' ' . $formatted;
    }

}
