<?php
// app/Http/Controllers/NewEntryImportController.php

namespace App\Http\Controllers;

use App\Http\Requests\NewEntryImportRequest;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Country;
use App\Models\Language;
use App\Models\NewEntry;
use App\Models\NewEntry1;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NewEntryImportController extends Controller
{
    /** Accept up to this many rows in preview (UI). */
    private const PREVIEW_LIMIT = 300;

    /** Chunk size for DB upserts. */
    private const CHUNK_SIZE = 500;

    /**
     * Header aliases → canonical DB field.
     * We normalize headers (lowercase, trim, remove symbols) before matching.
     */
    private const HEADER_MAP = [
        // Basic identity / relations
        'website'               => 'domain_name',
        'domain'                => 'domain_name',
        'status'                => 'status',
        'lb'                    => 'linkbuilder',
        'currency'              => 'currency_code',
        'country'               => 'country_name',
        'language'              => 'language_name',
        'contact'               => 'contact_ref',  // email or name

        // Prices & dates
        'publisher'             => 'publisher_price',
        'publisher price'       => 'publisher_price',
        '€ publisher'           => 'publisher_price',
        'no follow price'       => 'no_follow_price',
        'special topic price'   => 'special_topic_price',
        'date publisher price'  => 'date_publisher_price',

        // Evaluations
        'kialvo'                => 'kialvo_evaluation',
        'ai'                    => 'automatic_evaluation',
        "kialvo's date"         => 'date_kialvo_evaluation',

        // SEO metrics
        'da'                    => 'DA',
        'pa'                    => 'PA',
        'tf'                    => 'TF',
        'cf'                    => 'CF',
        'za'                    => 'ZA',
        'dr'                    => 'DR',
        'ur'                    => 'UR',
        'semrush traffic'       => 'semrush_traffic',
        'seozoom'               => 'seozoom',
        'ahrefs keyword'        => 'ahrefs_keyword',
        'ahrefs traffic'        => 'ahrefs_traffic',
        'metrics date'          => 'seo_metrics_date',

        // Type + misc flags
        'type of website'       => 'type_of_website',
        'more than 1 link'      => 'more_than_one_link',
        'copywriting'           => 'copywriting',
        'no sponsored tag'      => 'no_sponsored_tag',
        'social media sharing'  => 'social_media_sharing',
        'post in homepage'      => 'post_in_homepage',
        'extra notes'           => 'extra_notes',
        'date_added'            => 'date_added',
        'profit'                => 'profit',
    ];

    /**
     * CSV columns that represent categories (Yes/No).
     * Add/adjust names to match your CSV exactly.
     */
    private const CATEGORY_COLUMNS = [
        'betting','trading','sport','economy','travel','tech','design',
        'food','wellness','hobby and d.i.y','moda/fashion',
    ];

    /** Fields we’ll assign on NewEntry (safe even if some are absent in DB). */
    private const ASSIGNABLE = [
        'domain_name','status','country_id','language_id','contact_id',
        'currency_code','type_of_website','linkbuilder',
        'publisher_price','no_follow_price','special_topic_price','date_publisher_price',
        'kialvo_evaluation','automatic_evaluation','profit','date_kialvo_evaluation',
        'DA','PA','TF','CF','DR','UR','ZA','semrush_traffic','seozoom',
        'ahrefs_keyword','ahrefs_traffic','seo_metrics_date',
        'more_than_one_link','copywriting','no_sponsored_tag',
        'social_media_sharing','post_in_homepage','extra_notes',
        // derived
        'keyword_vs_traffic',
        'TF_vs_CF',
        'date_added',
    ];

    public function index()
    {
        return view('new_entries.import'); // see Blade below
    }

    public function sample()
    {
        // Optional: serve a minimal sample CSV header your users can download.
        $headers = [
            'Website','Country','Contact','Status','LB','Currency',
            'Publisher','No Follow Price','Special Topic Price','Date Publisher Price',
            'Kialvo','AI','Profit',"Kialvo's date",
            'DA','PA','TF','CF','ZA','DR','Semrush Traffic','Seozoom',
            'Ahrefs Keyword','Ahrefs Traffic','Metrics Date','type of website',
            'Betting','Trading','Sport','Economy','Travel','Tech','Design','Food','Wellness','Hobby and D.I.Y','Moda/Fashion',
            'More than 1 link','Copywriting','No sponsored tag','Social Media Sharing','Post in homepage','Extra notes',
        ];
        $content = implode(',', $headers)."\n";
        return response($content, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="new_entries_sample.csv"',
        ]);
    }

    // app/Http/Controllers/NewEntryImportController.php

    public function preview(NewEntryImportRequest $request)
    {
        [$rows, $stats] = $this->readCsv($request);

        $normalized = [];
        $errorsCount = 0;

        foreach ($rows as $i => $raw) {
            [$data, $categories, $rowErrors] = $this->normalizeRow($raw);

            // ⬇️ compute exactly like in the UI
            $this->recalcArray($data);

            $errorsCount += count($rowErrors);
            $normalized[] = [
                'line'       => $i + 2,
                'data'       => $data,
                'categories' => $categories,
                'errors'     => $rowErrors,
            ];
            if (count($normalized) >= self::PREVIEW_LIMIT) break;
    }

        $token = 'ne_import_'.Str::random(16);
        Cache::put($token, [
            'rows'    => $rows,
            'options' => [
                // removed: create_missing_contacts
                'create_missing_categories' => (bool)$request->boolean('create_missing_categories'),
                'dedupe_by_domain'          => (bool)$request->boolean('dedupe_by_domain', true),
            ],
        ], now()->addMinutes(30));

        return response()->json([
            'token'        => $token,
            'stats'        => $stats,
            'preview'      => $normalized,
            'errors_count' => $errorsCount,
            'limit'        => self::PREVIEW_LIMIT,
        ]);
    }


    // Mutate an array with the automatic calculations used in the UI
    private function recalcArray(array &$d): void
    {
        $da = $d['DA'] ?? 0;
        $tf = $d['TF'] ?? 0;
        $dr = $d['DR'] ?? 0;
        $sr = $d['semrush_traffic'] ?? 0;

        $auto = ($da * 2.4) + ($tf * 1.45) + ($dr * 0.5);
        if ($sr >= 9700) {
            $auto += ($sr / 15000) * 1.35;
        }
        $d['automatic_evaluation'] = $auto;

        // profit
        $d['profit'] = ($d['kialvo_evaluation'] ?? 0) - ($d['publisher_price'] ?? 0);

        // TF vs CF
        $cf = $d['CF'] ?? 0;
        $d['TF_vs_CF'] = $cf ? (($d['TF'] ?? 0) / $cf) : 0;

        // keyword vs traffic
        $ahrefsTraffic = $d['ahrefs_traffic'] ?? 0;
        $d['keyword_vs_traffic'] = $ahrefsTraffic
            ? round(($d['ahrefs_keyword'] ?? 0) / $ahrefsTraffic, 2)
            : 0;
    }


    // add this helper near your other private helpers
    // inside NewEntryImportController (with your other private helpers)
    private function boolOrFalse($v): bool
    {
        $b = $this->bool($v);
        return $b === null ? false : $b;
    }


    public function commit(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'limit' => 'nullable|integer|min:1|max:10000',
        ]);

        $token = (string) $request->string('token');
        $payload = Cache::get($token);

        if (!is_array($payload)) {
            return response()->json(['ok' => false, 'message' => 'Session expired. Please re-upload.'], 422);
        }

        $rows    = $payload['rows'] ?? [];
        $options = $payload['options'] ?? [];

        // optional limiter while testing
        if ($request->filled('limit')) {
            $limit = max(1, (int) $request->input('limit'));
            $rows  = array_slice($rows, 0, $limit);
        }

        $created = 0; $updated = 0; $failed = 0;
        $failures = [];

        // === FIXED COLUMN SET (must match your DB!) ==========================
        // If a column below doesn't exist in your new_entries table, remove it.
        $insertCols = [
            'domain_name','status','country_id','language_id','contact_id',
            'currency_code','type_of_website','linkbuilder',
            'publisher_price','no_follow_price','special_topic_price','date_publisher_price',
            'kialvo_evaluation','automatic_evaluation','profit','date_kialvo_evaluation',
            'DA','PA','TF','CF','DR','UR','ZA','semrush_traffic','seozoom',
            'ahrefs_keyword','ahrefs_traffic','seo_metrics_date',
            'more_than_one_link','copywriting','no_sponsored_tag',
            'social_media_sharing','post_in_homepage','extra_notes',
            'keyword_vs_traffic','TF_vs_CF','date_added',
        ];
        $allCols = array_merge($insertCols, ['created_at','updated_at']);
        // =====================================================================

        // Prefetch dictionaries
        $countryDict  = Country::query()->pluck('id','country_name')
            ->mapWithKeys(fn($id,$n)=>[strtolower(trim($n))=>$id])->all();
        $languageDict = Language::query()->pluck('id','name')
            ->mapWithKeys(fn($id,$n)=>[strtolower(trim($n))=>$id])->all();
        $categoryDict = Category::query()->pluck('id','name')
            ->mapWithKeys(fn($id,$n)=>[strtolower(trim($n))=>$id])->all();

        // Process in chunks
        foreach (array_chunk($rows, self::CHUNK_SIZE, true) as $chunkOffset => $chunk) {
            DB::transaction(function () use (
                $chunk,$chunkOffset,&$created,&$updated,&$failed,&$failures,
                $options,$insertCols,$allCols,
                &$countryDict,&$languageDict,&$categoryDict
            ) {
                $now = now();

                // Build padded rows for bulk upsert + remember categories per domain
                $bulkByDomain = [];         // domain => row payload (padded)
                $catsByDomain = [];         // domain => [category_id,...]
                $domainsThisChunk = [];     // list of domains for post-upsert lookup

                foreach ($chunk as $absoluteIndex => $raw) {
                    $lineNo = $absoluteIndex + 2; // header is line 1

                    try {
                        [$data, $catNames, $rowErrors] = $this->normalizeRow(
                            $raw,
                            $countryDict,
                            $languageDict
                        );

                        if ($rowErrors) {
                            $failed++;
                            $failures[] = ['line' => $lineNo, 'errors' => $rowErrors];
                            continue;
                        }

                        // compute automatic fields
                        $this->recalcArray($data);

                        // Ensure date_added exists
                        if (empty($data['date_added'])) {
                            $data['date_added'] = $now->toDateString();
                        }

                        // Create missing categories if requested
                        $catIds = [];
                        foreach ($catNames as $cn) {
                            $key = strtolower(trim($cn));
                            if (!isset($categoryDict[$key])) {
                                if (!($options['create_missing_categories'] ?? false)) {
                                    continue;
                                }
                                $cat = Category::firstOrCreate(['name'=>$cn], ['name'=>$cn]);
                                $categoryDict[$key] = $cat->id;
                            }
                            $catIds[] = $categoryDict[$key];
                        }

                        $domain = $data['domain_name'] ?? null;
                        if (!$domain) {
                            $failed++;
                            $failures[] = ['line' => $lineNo, 'errors' => ['Website/domain is required.']];
                            continue;
                        }

                        // Pad row: every column present, missing → null
                        $padded = array_replace(
                            array_fill_keys($allCols, null),
                            Arr::only($data, $insertCols),
                            ['created_at' => $now, 'updated_at' => $now]
                        );

                        // In-chunk de-dupe by domain if requested: last one wins
                        if (($options['dedupe_by_domain'] ?? true)) {
                            $bulkByDomain[$domain] = $padded;
                            $catsByDomain[$domain] = $catIds;
                        } else {
                            // if not deduping, still keep categories by domain (multiple)
                            $bulkByDomain[$domain.'#'.spl_object_id((object)$raw)] = $padded;
                            $catsByDomain[$domain] = array_unique(array_merge($catsByDomain[$domain] ?? [], $catIds));
                        }
                    } catch (\Throwable $e) {
                        $failed++;
                        $failures[] = ['line' => $lineNo, 'errors' => [$e->getMessage()]];
                    }
                }

                if (empty($bulkByDomain)) {
                    return;
                }

                // Figure out created vs updated counts by checking which domains already exist
                $domainsThisChunk = [];
                foreach (array_keys($bulkByDomain) as $k) {
                    // strip the "#<id>" used above when dedupe=false
                    $d = explode('#',$k,2)[0];
                    $domainsThisChunk[$d] = true;
                }
                $domains = array_keys($domainsThisChunk);

                $existing = NewEntry::query()
                    ->whereIn('domain_name', $domains)
                    ->pluck('domain_name')
                    ->all();
                $existingSet = array_fill_keys($existing, true);

                $wouldUpdate = 0;
                foreach ($domains as $d) {
                    if (isset($existingSet[$d])) $wouldUpdate++;
                }
                $wouldCreate = count($domains) - $wouldUpdate;

                // Bulk UPSERT (requires a UNIQUE index on domain_name for true dedupe)
                // If you don't have it yet, add a migration:
                // Schema::table('new_entries', fn(Blueprint $t) => $t->unique('domain_name'));
                $updateCols = array_diff($allCols, ['domain_name','created_at']);
                DB::table('new_entries')->upsert(
                    array_values($bulkByDomain),
                    ['domain_name'],
                    $updateCols
                );

                $created += $wouldCreate;
                $updated += $wouldUpdate;

                // Re-fetch IDs for all domains in this chunk
                $idByDomain = NewEntry::query()
                    ->whereIn('domain_name', $domains)
                    ->pluck('id','domain_name')
                    ->all();

                // Prepare pivot rows with timestamps, avoid duplicates
                $pivot = [];
                foreach ($catsByDomain as $domain => $catIds) {
                    $entryId = $idByDomain[$domain] ?? null;
                    if (!$entryId || empty($catIds)) continue;
                    foreach ($catIds as $cid) {
                        $pivot[] = [
                            'category_id'   => $cid,
                            'new_entry_id'  => $entryId,
                            'created_at'    => $now,
                            'updated_at'    => $now,
                        ];
                    }
                }

                // Insert pivot (ignore duplicates)
                foreach (array_chunk($pivot, 1000) as $pv) {
                    DB::table('category_new_entry')->insertOrIgnore($pv);
                }
            });
        }

        return response()->json([
            'ok'       => true,
            'created'  => $created,
            'updated'  => $updated,
            'failed'   => $failed,
            'failures' => $failures,
        ]);
    }


    /**
     * Read CSV rows into arrays; returns [rows, stats].
     * Handles odd encodings and BOMs from exports.
     */
    private function readCsv(NewEntryImportRequest $request): array
    {
        $file = $request->file('file');

        $rows = [];
        $stats = ['total'=>0];

        $handle = fopen($file->getRealPath(), 'r');
        if (!$handle) {
            abort(422, 'Unable to read CSV file.');
        }

        // read header
        $header = fgetcsv($handle);
        if (!$header) abort(422, 'CSV has no header row.');

        $normHeader = array_map(fn($h) => $this->normalizeHeader($h), $header);

        // map index→canonical
        $indexToField = [];
        foreach ($normHeader as $i => $h) {
            $indexToField[$i] = $this->canonicalField($h);
        }

        while (($data = fgetcsv($handle)) !== false) {
            $row = [];
            foreach ($data as $i => $val) {
                $field = $indexToField[$i] ?? null;
                if (!$field) continue;
                $row[$field] = is_string($val) ? trim($val) : $val;
            }
            if ($row) {
                $rows[] = $row;
            }
        }
        fclose($handle);

        $stats['total'] = count($rows);
        return [$rows, $stats];
    }


    // UPPERCASE a field; returns null for empty/placeholder values
    private function toUpper(?string $v): ?string
    {
        if ($v === null) return null;
        $v = trim($v);
        if ($v === '' || in_array(strtolower($v), ['?', 'n/a', 'na', 'none', '-'], true)) {
            return null;
        }
        return mb_strtoupper($v, 'UTF-8');
    }

    /** Convert raw CSV row → [dataArray, categoryNames[], errors[]] */
    private function normalizeRow(
        array $row,
        ?array $countryDict = null,
        ?array $languageDict = null
    ): array {
        $errors = [];
        $data   = [];
        $cats   = [];

        // identity
        $domain = $row['domain_name'] ?? null;
        if (!$domain) $errors[] = 'Website/domain is required.';
        $data['domain_name'] = $this->cleanDomain($domain);

        // linkbuilder / status / currency / type
        $data['linkbuilder']     = $row['linkbuilder']       ?? null;

// status -> snake_case (e.g., "Never Opened" => "never_opened")
        $data['status']          = $this->enumSnake($row['status'] ?? null);

        $data['currency_code']   = $row['currency_code']     ?? null;

// type_of_website -> keep wording but force UPPERCASE (e.g., "Local Media" => "LOCAL MEDIA")
        $data['type_of_website'] = $this->toUpper($row['type_of_website'] ?? null);

        // Default status when missing → waiting_for_1st_answer
        if (empty($data['status'])) {
            $data['status'] = 'waiting_for_1st_answer';
        }



        // Country
        $countryName = $row['country_name'] ?? null;
        if ($countryName) {
            $cid = $countryDict
                ? ($countryDict[strtolower(trim($countryName))] ?? null)
                : Country::whereRaw('LOWER(country_name) = ?', [strtolower(trim($countryName))])->value('id');

            if ($cid) $data['country_id'] = $cid;
            else $errors[] = "Country not found: {$countryName}";
        }

        // Language (optional column in some sheets)
        $langName = $row['language_name'] ?? null;
        if ($langName) {
            $lid = $languageDict
                ? ($languageDict[strtolower(trim($langName))] ?? null)
                : Language::whereRaw('LOWER(name) = ?', [strtolower(trim($langName))])->value('id');

            if ($lid) $data['language_id'] = $lid;
            else $errors[] = "Language not found: {$langName}";
        }

        // Contact (email or name)
        // Contact (email or name) – OPTIONAL; if not found, we just skip
        $contactRef = $row['contact_ref'] ?? null;
        if ($contactRef) {
            $contact = $this->resolveContact($contactRef); // lookup only
            if ($contact) {
                $data['contact_id'] = $contact->id;
            }
            // else: leave contact_id unset (NULL) — no error
        }


        // Prices
        $data['publisher_price']     = $this->num($row['publisher_price'] ?? null);
        $data['no_follow_price']     = $this->num($row['no_follow_price'] ?? null);
        $data['special_topic_price'] = $this->num($row['special_topic_price'] ?? null);

        // Dates
        $data['date_publisher_price']   = $this->date($row['date_publisher_price'] ?? null);
        $data['kialvo_evaluation']      = $this->num($row['kialvo_evaluation'] ?? null);
        $data['automatic_evaluation']   = $this->num($row['automatic_evaluation'] ?? null);
        $data['profit']                 = $this->num($row['profit'] ?? null);
        $data['date_kialvo_evaluation'] = $this->date($row['date_kialvo_evaluation'] ?? null);

// 👇 add this line
        $data['date_added']             = $this->date($row['date_added'] ?? null);

        // SEO metrics
        foreach (['DA','PA','TF','CF','DR','UR','ZA','semrush_traffic','seozoom','ahrefs_keyword','ahrefs_traffic'] as $m) {
            if (array_key_exists($m, $row)) $data[$m] = $this->num($row[$m]);
        }
        $data['seo_metrics_date'] = $this->date($row['seo_metrics_date'] ?? null);

        // Derived metric
        if (!empty($data['ahrefs_traffic'])) {
            $data['keyword_vs_traffic'] = round(($data['ahrefs_keyword'] ?? 0) / max(1, $data['ahrefs_traffic']), 2);
        }

        // Flags / booleans
        // Flags / booleans  (default to false when missing/unknown)
        $data['more_than_one_link']   = $this->boolOrFalse($row['more_than_one_link']   ?? null);
        $data['copywriting']          = $this->boolOrFalse($row['copywriting']          ?? null);
        $data['no_sponsored_tag']     = $this->boolOrFalse($row['no_sponsored_tag']     ?? null);
        $data['social_media_sharing'] = $this->boolOrFalse($row['social_media_sharing'] ?? null);
        $data['post_in_homepage']     = $this->boolOrFalse($row['post_in_homepage']     ?? null);
        $data['extra_notes']          = $row['extra_notes'] ?? null;


        // Categories (Yes/No columns)
        foreach (self::CATEGORY_COLUMNS as $col) {
            // In the parsed rows we already canonicalized headers → DB-like keys
            if (isset($row[$col]) && $this->bool($row[$col])) {
                $cats[] = $this->categoryDisplayName($col);
            }
        }

        return [$data, $cats, $errors];
    }

    // Converts things like "Never Opened", "E-commerce / B2B", "Local" -> "never_opened", "e_commerce_b2b", "local"
    private function enumSnake(?string $v): ?string
    {
        if ($v === null) return null;

        $v = trim($v);
        if ($v === '' || in_array(strtolower($v), ['?', 'n/a', 'na', 'none', '-'], true)) {
            return null;
        }

        // unify some separators before snake()
        $v = str_replace(['&', '+'], ' and ', $v);
        $v = preg_replace('/[\/\-]+/u', ' ', $v);      // slashes/dashes -> space
        $v = preg_replace('/[^\pL\pN ]+/u', '', $v);   // keep letters/numbers/spaces
        return \Illuminate\Support\Str::snake($v);     // lowercase_with_underscores
    }


    /** Normalize header: lowercase, strip weird bytes/symbols, collapse spaces. */
    private function normalizeHeader(?string $h): string
    {
        $h = $h ?? '';
        $h = preg_replace('/\x{FEFF}|\x{200B}|\x{00A0}|\x{0080}/u', '', $h); // BOM/nbsp/invisible
        $h = strtolower(trim($h));
        $h = preg_replace('/\s+/',' ', $h);
        return $h;
    }

    /** Map normalized header to our canonical field key. */
    private function canonicalField(string $normHeader): ?string
    {
        // direct alias map
        if (isset(self::HEADER_MAP[$normHeader])) {
            return self::HEADER_MAP[$normHeader];
        }

        // category columns pass-through (already normalized)
        if (in_array($normHeader, self::CATEGORY_COLUMNS, true)) {
            return $normHeader;
        }

        // common quirky exporter variants
        $alt = [
            '€ publisher'        => 'publisher',
            'publisher €'        => 'publisher',
            'kialvo '            => 'kialvo',
            'ai '                => 'ai',
            'date_added'         => 'date_added',
        ];
        if (isset($alt[$normHeader]) && isset(self::HEADER_MAP[$alt[$normHeader]])) {
            return self::HEADER_MAP[$alt[$normHeader]];
        }

        return null; // unknown → ignore
    }

    private function cleanDomain(?string $v): ?string
    {
        if (!$v) return null;
        $v = trim($v);
        $v = preg_replace('#^https?://#i','',$v);
        $v = preg_replace('#^www\.#i','',$v);
        return strtolower($v);
    }

    private function num($v): ?float
    {
        if ($v === null || $v === '') return null;
        $v = str_replace([' ', "\u{00A0}", ','], ['', '', ''], (string)$v);
        $v = str_replace(['€','$'], '', $v);
        // handle negatives like " -18" or "−18"
        $v = preg_replace('/[^\d\.\-]/','', $v);
        if ($v === '' || $v === '-') return null;
        return is_numeric($v) ? (float)$v : null;
    }

    private function date($v): ?string
    {
        if (!$v) return null;
        $v = trim((string)$v);

        $formats = [
            'Y-m-d','d/m/Y','m/d/Y','d-m-Y','m-d-Y','d.m.Y','Y/m/d',
        ];
        foreach ($formats as $fmt) {
            try {
                return Carbon::createFromFormat($fmt, $v)->format('Y-m-d');
            } catch (\Throwable $e) {}
        }
        // last attempt: Carbon::parse
        try {
            return Carbon::parse($v)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function bool($v): ?bool
    {
        if ($v === null || $v === '') return null;
        $s = strtolower(trim((string)$v));
        return in_array($s, ['1','y','yes','true','t','✓','check','x'], true) ? true
            : (in_array($s, ['0','n','no','false','f'], true) ? false : null);
    }


    private function categoryDisplayName(string $key): string
    {
        // Pretty → DB category name mapping (adjust if your DB uses different labels)
        return match ($key) {
            'hobby and d.i.y' => 'Hobby and D.I.Y',
            'moda/fashion'    => 'Moda/Fashion',
            default           => Str::title($key),
        };
    }

    private function resolveContact(string $ref): ?Contact
    {
        $ref = trim($ref);

        if (str_contains($ref, '@')) {
            return Contact::where('email', $ref)->first();
        }

        return Contact::whereRaw('LOWER(name) = ?', [strtolower($ref)])->first();
    }

}
