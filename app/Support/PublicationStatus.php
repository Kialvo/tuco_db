<?php

namespace App\Support;

/**
 * Unified publication status list (Phase 3) — single source of truth is
 * config/linkbuilding.php `publication_statuses`. The DB (storage.status)
 * stores SLUGS; the UI shows LABELS. Group 1 = Site Evaluation,
 * Group 2 = Production.
 */
class PublicationStatus
{
    /** slug => ['label' =>…, 'group' => 1|2, 'tone' =>…] */
    public static function all(): array
    {
        return config('linkbuilding.publication_statuses', []);
    }

    /** @return string[] all valid slugs */
    public static function slugs(): array
    {
        return array_keys(static::all());
    }

    /** slug => label */
    public static function labels(): array
    {
        return array_map(fn ($s) => $s['label'], static::all());
    }

    public static function label(?string $slug): ?string
    {
        if ($slug === null || $slug === '' || $slug === '0') {
            return null;
        }

        return static::all()[$slug]['label'] ?? $slug;
    }

    /** 1 (Site Evaluation) | 2 (Production); unknown/legacy values fall into group 1 */
    public static function group(?string $slug): int
    {
        return static::all()[$slug]['group'] ?? 1;
    }

    public static function tone(?string $slug): string
    {
        return static::all()[$slug]['tone'] ?? 'gray';
    }

    /**
     * Which side of the client's site-approval decision this status represents:
     * 'approved' | 'rejected' | 'pending'.
     *
     * Unknown/legacy values ('0', '', anything not in config) fall back to
     * 'pending' so they can never inflate an approval or rejection rate.
     */
    public static function decision(?string $slug): string
    {
        if ($slug === null || $slug === '' || $slug === '0') {
            return 'pending';
        }

        return static::all()[$slug]['decision'] ?? 'pending';
    }

    /**
     * @return string[] every slug on the given side of the decision, in config order
     */
    public static function slugsByDecision(string $decision): array
    {
        return array_keys(array_filter(
            static::all(),
            fn (array $def) => ($def['decision'] ?? 'pending') === $decision
        ));
    }

    /** group label => [slug => label] — for <optgroup> selects */
    public static function grouped(): array
    {
        $groups = config('linkbuilding.publication_status_groups', []);
        $out = [];

        foreach ($groups as $num => $groupLabel) {
            $out[$groupLabel] = [];
        }

        foreach (static::all() as $slug => $def) {
            $groupLabel = $groups[$def['group']] ?? 'Other';
            $out[$groupLabel][$slug] = $def['label'];
        }

        return $out;
    }
}
