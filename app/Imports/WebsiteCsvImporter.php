<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\Country;
use App\Models\Language;
use App\Models\Website;
use App\Services\DomainPriceCalculator;
use App\Services\DomainProfitCalculator;
use Illuminate\Support\Facades\DB;

/**
 * Bulk-create Domains from a CSV.
 *
 * Replaces the metrics-only importer that used to live here. That one could
 * only refresh DR/TF/CF/Ahrefs on domains that already existed; this one
 * creates them.
 *
 * Two things worth knowing before changing anything here:
 *
 * 1. USD rows are NOT converted by this class. `websites_before_insert`
 *    already multiplies publisher_price and special_topic_price by the daily
 *    rate for USD rows, so converting here as well would shrink them twice.
 *    We write the raw foreign-currency figure and let the trigger do its job —
 *    exactly what the Add-domain form does. The `original_*` columns are
 *    mirrored from the entered price so they are never null, which is what
 *    `websites_before_update` re-derives the EUR columns from on every later
 *    save.
 *
 * 2. Price and Sensitive Topic Price come from DomainPriceCalculator, the same
 *    service the form uses, so an imported domain can never end up priced
 *    differently from a hand-entered one.
 */
class WebsiteCsvImporter
{
    /** Rows shown in the preview. */
    public const PREVIEW_LIMIT = 1000;

    /** Write batch size. */
    private const CHUNK_SIZE = 200;

    /** Imported domains go live immediately — a deliberate, signed-off decision. */
    private const IMPORT_STATUS = 'active';

    /** The only value column 8 accepts. Extend when other link builders send lists. */
    public const ALLOWED_LINK_BUILDERS = ['Martina Napolano'];

    /** The three values the Domains form offers. */
    public const ALLOWED_TYPES = ['VERTICAL', 'GENERALIST', 'LOCAL'];

    public const ALLOWED_CURRENCIES = ['EUR', 'USD'];

    /**
     * Header (normalised) => internal key.
     *
     * NOTE: normaliseHeader() preserves the euro sign precisely so that
     * "Link Builder EUR" (the money column) and "Link Builder" (the person)
     * cannot collide. Do not strip it.
     */
    private const HEADER_MAP = [
        'domain' => 'domain_name',
        'website' => 'domain_name',
        'dr' => 'DR',
        'link builder €' => 'link_builder_amount',
        'link builder eur' => 'link_builder_amount',
        'link builder amount' => 'link_builder_amount',
        'publisher price' => 'publisher_price',
        'special topic price' => 'special_topic_price',
        'language' => 'language_name',
        'country' => 'country_name',
        'link builder' => 'linkbuilder',
        'linkbuilder' => 'linkbuilder',
        'notes' => 'notes',
        'internal notes' => 'extra_notes',
        'type' => 'type_of_website',
        'category' => 'category_names',
        'categories' => 'category_names',
        'betting' => 'betting',
        'trading' => 'trading',
        'currency' => 'currency_code',
    ];

    /** Header order of the downloadable sample. */
    public const TEMPLATE_HEADERS = [
        'Domain', 'DR', 'Link Builder €', 'Publisher Price', 'Special Topic Price',
        'Language', 'Country', 'Link Builder', 'Notes', 'Internal Notes',
        'Type', 'Category', 'Betting', 'Trading', 'Currency',
    ];

    private array $countryDict = [];

    private array $languageDict = [];

    private array $categoryDict = [];

    /**
     * Parse and validate a CSV into preview rows.
     *
     * @return array{rows: array<int, array>, stats: array<string, int>, truncated: bool}
     */
    public function parse(string $csv, bool $hasHeader = true): array
    {
        $this->loadDictionaries();

        $rate = DomainPriceCalculator::usdEurRate();
        $handle = fopen('php://memory', 'r+');
        fwrite($handle, $this->stripBom($csv));
        rewind($handle);

        $header = null;
        $lineNo = 0;
        $rows = [];
        $truncated = false;
        $seenDomains = [];

        while (($cols = fgetcsv($handle)) !== false) {
            $lineNo++;

            if ($lineNo === 1 && $hasHeader) {
                $header = $this->mapHeader($cols);

                continue;
            }

            if ($this->isBlankLine($cols)) {
                continue;
            }

            $raw = $this->associate($cols, $header);
            [$data, $categoryIds, $errors] = $this->normaliseRow($raw, $rate);

            // A domain repeated inside one file is an error, never a silent
            // overwrite — we cannot know which of the two rows was intended.
            $key = $data['domain_name'] ?? null;
            if ($key !== null && isset($seenDomains[$key])) {
                $errors[] = 'Duplicated in this file (first seen on line '.$seenDomains[$key].').';
            } elseif ($key !== null) {
                $seenDomains[$key] = $lineNo;
            }

            $rows[] = [
                'line' => $lineNo,
                'data' => $data,
                'category_ids' => $categoryIds,
                'errors' => $errors,
                'valid' => $errors === [],
            ];

            if (count($rows) >= self::PREVIEW_LIMIT) {
                $truncated = true;
                break;
            }
        }

        fclose($handle);

        $rows = $this->flagExistingDomains($rows);

        return [
            'rows' => $rows,
            'stats' => $this->summarise($rows),
            'truncated' => $truncated,
        ];
    }

    /**
     * Write the rows.
     *
     * @param  array<string, string>  $existingActions  domain => "update"|"skip"
     * @return array{created: int, updated: int, skipped: int, failed: int}
     */
    public function commit(array $rows, array $existingActions = [], string $defaultExistingAction = 'skip'): array
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $failed = 0;
        $rate = DomainPriceCalculator::usdEurRate();

        DB::transaction(function () use ($rows, $existingActions, $defaultExistingAction, $rate, &$created, &$updated, &$skipped, &$failed) {
            foreach (array_chunk($rows, self::CHUNK_SIZE) as $chunk) {
                foreach ($chunk as $row) {
                    if (! ($row['valid'] ?? false)) {
                        $failed++;

                        continue;
                    }

                    $domain = $row['data']['domain_name'];

                    if (! empty($row['existing_id'])) {
                        $action = $existingActions[$domain] ?? $defaultExistingAction;
                        if ($action !== 'update') {
                            $skipped++;

                            continue;
                        }

                        $existing = Website::withTrashed()->find($row['existing_id']);
                        if (! $existing) {
                            $failed++;

                            continue;
                        }

                        $this->applyUpdate($existing, $row, $rate);
                        $updated++;

                        continue;
                    }

                    $website = Website::create($row['data']);
                    if ($row['category_ids']) {
                        $website->categories()->sync($row['category_ids']);
                    }
                    $created++;
                }
            }
        });

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'failed' => $failed,
        ];
    }

    /* ===================== internals ===================== */

    /**
     * Update an existing domain from a CSV row.
     *
     * Only columns actually present in the file are touched, and `status` is
     * never among them: a domain someone marked inactive or refused must not
     * be silently republished by a re-import.
     */
    private function applyUpdate(Website $website, array $row, float $rate): void
    {
        $payload = $row['data'];
        unset($payload['status'], $payload['price'], $payload['sensitive_topic_price'], $payload['profit']);

        foreach ($payload as $field => $value) {
            if ($value !== null && $value !== '') {
                $website->{$field} = $value;
            }
        }

        // Recompute from the merged state, not from the CSV row alone — the
        // domain may already carry values the file does not mention.
        $merged = $website->getAttributes();
        $merged['price'] = DomainPriceCalculator::price($merged, $rate);

        $website->price = $merged['price'];
        $website->sensitive_topic_price = DomainPriceCalculator::sensitiveTopicPrice($merged, $rate);
        $website->profit = DomainProfitCalculator::calculate(
            $merged['kialvo_evaluation'] ?? null,
            DomainPriceCalculator::publisherPriceInEur($merged, $rate),
            $merged['link_builder_amount'] ?? null
        );

        $website->save();

        if ($row['category_ids']) {
            $website->categories()->sync($row['category_ids']);
        }
    }

    /** @return array{0: array, 1: array<int>, 2: array<string>} */
    private function normaliseRow(array $raw, float $rate): array
    {
        $errors = [];
        $data = [];

        /* ---- 1. Domain ---- */
        $domain = $this->cleanDomain($raw['domain_name'] ?? null);
        if ($domain === null) {
            $errors[] = 'Domain is required.';
        }
        $data['domain_name'] = $domain;

        /* ---- 15. Currency (blank = EUR) ---- */
        $currency = strtoupper(trim((string) ($raw['currency_code'] ?? '')));
        if ($currency === '') {
            $currency = 'EUR';
        }
        if (! in_array($currency, self::ALLOWED_CURRENCIES, true)) {
            $errors[] = 'Currency must be EUR or USD, got "'.$currency.'".';
            $currency = 'EUR';
        }
        $data['currency_code'] = $currency;

        /* ---- 4/5. Prices, in the site's own currency ---- */
        $publisher = $this->num($raw['publisher_price'] ?? null);
        if ($publisher === null) {
            $errors[] = 'Publisher Price is required.';
        } elseif ($publisher < 0) {
            $errors[] = 'Publisher Price cannot be negative.';
            $publisher = null;
        }
        $data['publisher_price'] = $publisher;

        $specialTopic = $this->num($raw['special_topic_price'] ?? null);
        if ($specialTopic !== null && $specialTopic < 0) {
            $errors[] = 'Special Topic Price cannot be negative.';
            $specialTopic = null;
        }
        $data['special_topic_price'] = $specialTopic;

        // Mirror the originals so they are never null. On a USD row these are
        // what the triggers re-derive the EUR columns from on every later
        // save; leaving them empty is what silently blanks a price.
        $data['original_publisher_price'] = $publisher;
        $data['original_special_topic_price'] = $specialTopic;

        /* ---- 3. Link Builder EUR, always in euros ---- */
        $linkBuilderAmount = $this->num($raw['link_builder_amount'] ?? null);
        if ($linkBuilderAmount !== null && $linkBuilderAmount < 0) {
            $errors[] = 'Link Builder € cannot be negative.';
            $linkBuilderAmount = null;
        }
        $data['link_builder_amount'] = $linkBuilderAmount;

        /* ---- 6/7. Language + Country ---- */
        $languageName = trim((string) ($raw['language_name'] ?? ''));
        if ($languageName === '') {
            $errors[] = 'Language is required.';
        } else {
            $id = $this->languageDict[mb_strtolower($languageName)] ?? null;
            if ($id === null) {
                $errors[] = 'Language not found: "'.$languageName.'".';
            }
            $data['language_id'] = $id;
        }

        $countryName = trim((string) ($raw['country_name'] ?? ''));
        if ($countryName === '') {
            $errors[] = 'Country is required.';
        } else {
            $id = $this->countryDict[mb_strtolower($countryName)] ?? null;
            if ($id === null) {
                $errors[] = 'Country not found: "'.$countryName.'".';
            }
            $data['country_id'] = $id;
        }

        /* ---- 8. Link Builder (the person) ---- */
        $linkBuilder = trim((string) ($raw['linkbuilder'] ?? ''));
        if ($linkBuilder === '') {
            $errors[] = 'Link Builder is required.';
        } else {
            $match = null;
            foreach (self::ALLOWED_LINK_BUILDERS as $allowed) {
                if (mb_strtolower($allowed) === mb_strtolower($linkBuilder)) {
                    $match = $allowed;
                    break;
                }
            }
            if ($match === null) {
                $errors[] = 'Link Builder must be '.implode(' or ', self::ALLOWED_LINK_BUILDERS).'.';
            }
            $data['linkbuilder'] = $match;
        }

        /* ---- 11. Type ---- */
        $type = strtoupper(trim((string) ($raw['type_of_website'] ?? '')));
        if ($type === '') {
            $errors[] = 'Type is required.';
        } elseif (! in_array($type, self::ALLOWED_TYPES, true)) {
            $errors[] = 'Type must be '.implode(', ', self::ALLOWED_TYPES).'.';
        } else {
            $data['type_of_website'] = $type;
        }

        /* ---- 12. Category (comma separated, must already exist) ---- */
        [$categoryIds, $categoryNames, $categoryErrors] = $this->resolveCategories($raw['category_names'] ?? null);
        $errors = array_merge($errors, $categoryErrors);

        /* ---- 13/14. Betting + Trading ---- */
        $betting = $this->bool($raw['betting'] ?? null);
        if ($betting === null) {
            $errors[] = 'Betting is required (Yes or No).';
        }
        // Listing Betting among the categories wins over the column: it is the
        // more specific statement about the site.
        if ($this->namesContainBetting($categoryNames)) {
            $betting = true;
        }
        $data['betting'] = $betting;

        $trading = $this->bool($raw['trading'] ?? null);
        if ($trading === null) {
            $errors[] = 'Trading is required (Yes or No).';
        }
        $data['trading'] = $trading;

        /* ---- 2/9/10. Optional free fields ---- */
        $dr = $this->num($raw['DR'] ?? null);
        $data['DR'] = $dr === null ? null : (int) $dr;
        $data['notes'] = $this->text($raw['notes'] ?? null);
        $data['extra_notes'] = $this->text($raw['extra_notes'] ?? null);

        /* ---- derived ---- */
        $data['status'] = self::IMPORT_STATUS;
        // Same shape as the form's formula; DR is the only input the template
        // carries, so this stays deliberately small.
        $data['automatic_evaluation'] = round(($data['DR'] ?? 0) * 0.5, 2);
        $data['price'] = DomainPriceCalculator::price($data, $rate);
        $data['sensitive_topic_price'] = DomainPriceCalculator::sensitiveTopicPrice($data, $rate);
        // No Kialvo Evaluation in the template, so profit is null by design —
        // blank rather than a negative number. See DomainProfitCalculator.
        $data['profit'] = DomainProfitCalculator::calculate(
            null,
            DomainPriceCalculator::publisherPriceInEur($data, $rate),
            $data['link_builder_amount']
        );

        return [$data, $categoryIds, $errors];
    }

    /** @return array{0: array<int>, 1: array<string>, 2: array<string>} */
    private function resolveCategories($value): array
    {
        $errors = [];
        $ids = [];
        $names = [];

        $raw = trim((string) ($value ?? ''));
        if ($raw === '') {
            return [[], [], ['Category is required.']];
        }

        foreach (explode(',', $raw) as $name) {
            $name = trim($name);
            if ($name === '') {
                continue;
            }

            $names[] = $name;

            // Betting is a Yes/No column on the domain, not a category anyone
            // can pick — the Domains form hides it from the category list — so
            // it sets the flag and is not attached.
            if ($this->namesContainBetting([$name])) {
                continue;
            }

            $id = $this->categoryDict[mb_strtolower($name)] ?? null;
            if ($id === null) {
                $errors[] = 'Category not found: "'.$name.'".';

                continue;
            }
            $ids[] = $id;
        }

        return [array_values(array_unique($ids)), $names, $errors];
    }

    private function namesContainBetting(array $names): bool
    {
        foreach ($names as $name) {
            if (mb_strtolower(trim($name)) === 'betting') {
                return true;
            }
        }

        return false;
    }

    /**
     * Mark rows whose domain is already in the database.
     *
     * Soft-deleted domains count: re-creating one would leave two rows with
     * the same name, one of them invisible.
     */
    private function flagExistingDomains(array $rows): array
    {
        $domains = array_values(array_filter(array_map(
            fn ($r) => $r['data']['domain_name'] ?? null,
            $rows
        )));

        if ($domains === []) {
            return $rows;
        }

        $existing = Website::withTrashed()
            ->whereIn('domain_name', array_unique($domains))
            ->get(['id', 'domain_name', 'deleted_at'])
            ->keyBy(fn ($w) => mb_strtolower($w->domain_name));

        foreach ($rows as $i => $row) {
            $key = mb_strtolower((string) ($row['data']['domain_name'] ?? ''));
            $hit = $existing->get($key);

            $rows[$i]['exists'] = (bool) $hit;
            $rows[$i]['existing_id'] = $hit->id ?? null;
            $rows[$i]['existing_trashed'] = $hit ? $hit->deleted_at !== null : false;
        }

        return $rows;
    }

    private function summarise(array $rows): array
    {
        $valid = array_filter($rows, fn ($r) => $r['valid']);

        return [
            'total' => count($rows),
            'invalid' => count($rows) - count($valid),
            'existing' => count(array_filter($valid, fn ($r) => $r['exists'] ?? false)),
            'new' => count(array_filter($valid, fn ($r) => ! ($r['exists'] ?? false))),
        ];
    }

    private function loadDictionaries(): void
    {
        $this->countryDict = Country::pluck('id', 'country_name')
            ->mapWithKeys(fn ($id, $name) => [mb_strtolower(trim((string) $name)) => $id])->all();

        $this->languageDict = Language::pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [mb_strtolower(trim((string) $name)) => $id])->all();

        $this->categoryDict = Category::pluck('id', 'name')
            ->mapWithKeys(fn ($id, $name) => [mb_strtolower(trim((string) $name)) => $id])->all();
    }

    /* ---------------- parsing helpers ---------------- */

    private function mapHeader(array $cols): array
    {
        $out = [];
        foreach ($cols as $i => $col) {
            $out[$i] = static::headerKey((string) $col);
        }

        return $out;
    }

    /**
     * Which internal field a CSV header refers to, or null if we ignore it.
     *
     * Public so the "Link Builder €" / "Link Builder" distinction can be
     * unit tested — getting those two the wrong way round would put a money
     * value in a person's name column, and vice versa.
     */
    public static function headerKey(string $header): ?string
    {
        return self::HEADER_MAP[(new static)->normaliseHeader($header)] ?? null;
    }

    /**
     * Lower-case, collapse whitespace, drop punctuation — but KEEP the euro
     * sign, which is the only thing separating "Link Builder €" (money) from
     * "Link Builder" (person).
     */
    private function normaliseHeader(string $header): string
    {
        $h = mb_strtolower(trim($this->stripBom($header)));
        $h = str_replace('_', ' ', $h);
        $h = preg_replace('/[^\p{L}\p{N}€ ]+/u', '', $h) ?? $h;

        return trim(preg_replace('/\s+/', ' ', $h) ?? $h);
    }

    private function associate(array $cols, ?array $header): array
    {
        $row = [];
        foreach ($cols as $i => $value) {
            $key = $header[$i] ?? null;
            if ($key !== null) {
                $row[$key] = is_string($value) ? trim($value) : $value;
            }
        }

        return $row;
    }

    private function isBlankLine(array $cols): bool
    {
        foreach ($cols as $c) {
            if (trim((string) $c) !== '') {
                return false;
            }
        }

        return true;
    }

    private function stripBom(string $s): string
    {
        return preg_replace('/^\x{FEFF}/u', '', $s) ?? $s;
    }

    private function cleanDomain(?string $v): ?string
    {
        if ($v === null) {
            return null;
        }

        $v = trim($v);
        $v = preg_replace('#^https?://#i', '', $v) ?? $v;
        $v = preg_replace('#^www\.#i', '', $v) ?? $v;
        $v = rtrim($v, '/');

        return $v === '' ? null : mb_strtolower($v);
    }

    private function num($v): ?float
    {
        if ($v === null || $v === '') {
            return null;
        }

        $v = str_replace([' ', "\u{00A0}", '€', '$', ','], '', (string) $v);
        $v = preg_replace('/[^\d.\-]/', '', $v) ?? '';

        return is_numeric($v) ? (float) $v : null;
    }

    private function text($v): ?string
    {
        $v = trim((string) ($v ?? ''));

        return $v === '' ? null : $v;
    }

    private function bool($v): ?bool
    {
        if ($v === null || trim((string) $v) === '') {
            return null;
        }

        $s = mb_strtolower(trim((string) $v));

        return match ($s) {
            'yes', 'y', 'true', '1', 'si', 'sì' => true,
            'no', 'n', 'false', '0' => false,
            default => null,
        };
    }
}
