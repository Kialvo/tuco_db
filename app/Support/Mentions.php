<?php

namespace App\Support;

/**
 * @-mention tokens inside comment bodies — same wire format as the Menford
 * CRM (`@[Name:userId]`, parsed there by extractMentionedUserIds). Tokens
 * live in the stored text, so no schema changes; the UI renders them as
 * highlighted names.
 */
class Mentions
{
    public const PATTERN = '/@\[([^\]]+):(\d+)\]/';

    /** @return int[] unique user ids tagged in the body */
    public static function extractUserIds(string $body): array
    {
        $ids = [];
        if (preg_match_all(self::PATTERN, $body, $m)) {
            foreach ($m[2] as $id) {
                $ids[(int) $id] = true;
            }
        }

        return array_keys($ids);
    }
}
