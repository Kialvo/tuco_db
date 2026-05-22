<?php

namespace App\Http\Controllers\Api\Ai;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Language;
use App\Models\NewEntry;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Storage;
use App\Models\User;
use App\Models\Website;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class InternalAiController extends Controller
{
    private const DEFAULT_PER_PAGE = 20;

    private const MAX_PER_PAGE = 50;

    private array $columnCache = [];

    private array $tableCache = [];

    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'service' => 'linkinablink-internal-ai-api',
            'read_only' => true,
            'database' => $this->databaseStatus(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function overview(): JsonResponse
    {
        return response()->json([
            'data' => [
                'active_domains' => $this->activeDomainsCount(),
                'new_entries' => $this->safeCount('new_entries', excludeDeleted: true),
                'storages' => $this->safeCount('storage', excludeDeleted: true),
                'submitted_orders' => $this->safeCount('orders', callback: function (QueryBuilder $query) {
                    $query->where('status', Order::STATUS_SUBMITTED);
                }),
                'users_by_role' => $this->usersByRole(),
                'active_clients' => $this->safeCount('clients', excludeDeleted: true),
                'companies' => $this->safeCount('companies'),
                'domains_by_status' => $this->statusCounts('websites'),
                'orders_by_status' => $this->statusCounts('orders'),
                'latest_updates' => [
                    'websites' => $this->latestTimestamp('websites'),
                    'new_entries' => $this->latestTimestamp('new_entries'),
                    'storage' => $this->latestTimestamp('storage'),
                    'orders' => $this->latestTimestamp('orders'),
                    'users' => $this->latestTimestamp('users'),
                    'clients' => $this->latestTimestamp('clients'),
                    'companies' => $this->latestTimestamp('companies'),
                ],
            ],
            'meta' => [
                'read_only' => true,
            ],
        ]);
    }

    public function domainsSearch(Request $request): JsonResponse
    {
        if (! $this->tableExists('websites')) {
            return $this->emptyPaginatedResponse($request, ['table_available' => false]);
        }

        $query = Website::query()
            ->select($this->existingColumns('websites', $this->websiteColumns()))
            ->with($this->websiteRelations());

        $this->applyDeletedScope($query, 'websites', $request);
        $this->applyDomainFilters($query, $request, 'websites');

        $this->applySort($query, 'websites', $request, [
            'domain_name',
            'status',
            'price',
            'sensitive_topic_price',
            'publisher_price',
            'profit',
            'DA',
            'DR',
            'as_metric',
            'semrush_traffic',
            'ms',
            'created_at',
            'updated_at',
        ], 'domain_name');

        $paginator = $query->paginate($this->perPage($request), ['*'], 'page', $this->page($request));

        return $this->paginatedResponse(
            $paginator,
            fn (Website $website) => $this->transformWebsite($website, $this->deletedRequested($request)),
            ['source' => 'websites']
        );
    }

    public function newEntriesSearch(Request $request): JsonResponse
    {
        if (! $this->tableExists('new_entries')) {
            return $this->emptyPaginatedResponse($request, ['table_available' => false]);
        }

        $query = NewEntry::query()
            ->select($this->existingColumns('new_entries', $this->newEntryColumns()))
            ->with($this->newEntryRelations());

        $this->applyDeletedScope($query, 'new_entries', $request);
        $this->applyDomainFilters($query, $request, 'new_entries');
        $this->applyDateRange($query, 'first_contact_date', $request, 'first_contact_date_from', 'first_contact_date_to');

        if ($request->has('copied_to_overview') && $this->hasColumn('new_entries', 'copied_to_overview')) {
            $query->where('copied_to_overview', $this->boolValue($request->query('copied_to_overview')) ? 1 : 0);
        }

        $this->applySort($query, 'new_entries', $request, [
            'domain_name',
            'status',
            'price',
            'publisher_price',
            'DA',
            'DR',
            'as_metric',
            'semrush_traffic',
            'ms',
            'first_contact_date',
            'created_at',
            'updated_at',
        ], 'domain_name');

        $paginator = $query->paginate($this->perPage($request), ['*'], 'page', $this->page($request));

        return $this->paginatedResponse(
            $paginator,
            fn (NewEntry $entry) => $this->transformNewEntry($entry, $this->deletedRequested($request)),
            ['source' => 'new_entries']
        );
    }

    public function storagesSearch(Request $request): JsonResponse
    {
        if (! $this->tableExists('storage')) {
            return $this->emptyPaginatedResponse($request, ['table_available' => false]);
        }

        $query = Storage::query()
            ->select($this->existingColumns('storage', $this->storageColumns()))
            ->with($this->storageRelations());

        $this->applyDeletedScope($query, 'storage', $request);
        $this->applyStorageFilters($query, $request);

        $this->applySort($query, 'storage', $request, [
            'status',
            'campaign',
            'campaign_code',
            'total_cost',
            'total_revenues',
            'profit',
            'publication_date',
            'created_at',
            'updated_at',
        ], 'publication_date');

        $paginator = $query->paginate($this->perPage($request), ['*'], 'page', $this->page($request));

        return $this->paginatedResponse(
            $paginator,
            fn (Storage $storage) => $this->transformStorage($storage, $this->deletedRequested($request)),
            ['source' => 'storage']
        );
    }

    public function ordersSearch(Request $request): JsonResponse
    {
        if (! $this->tableExists('orders')) {
            return $this->emptyPaginatedResponse($request, ['table_available' => false]);
        }

        $query = Order::query()
            ->select($this->existingColumns('orders', [
                'id',
                'user_id',
                'status',
                'notes',
                'submitted_at',
                'status_changed_at',
                'created_at',
                'updated_at',
            ]))
            ->with($this->orderRelations())
            ->where('status', '!=', Order::STATUS_DRAFT);

        $this->applyOrderFilters($query, $request);

        $this->applySort($query, 'orders', $request, [
            'id',
            'status',
            'submitted_at',
            'status_changed_at',
            'created_at',
            'updated_at',
        ], 'submitted_at');

        $paginator = $query->paginate($this->perPage($request), ['*'], 'page', $this->page($request));

        return $this->paginatedResponse(
            $paginator,
            fn (Order $order) => $this->transformOrder($order),
            ['source' => 'orders']
        );
    }

    public function usersSearch(Request $request): JsonResponse
    {
        if (! $this->tableExists('users')) {
            return $this->emptyPaginatedResponse($request, ['table_available' => false]);
        }

        $query = User::query()
            ->select($this->existingColumns('users', [
                'id',
                'name',
                'email',
                'role',
                'email_verified_at',
                'created_at',
                'updated_at',
            ]));

        if ($q = $this->filledString($request, 'q')) {
            $query->where(function (Builder $inner) use ($q) {
                $inner->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        if ($role = $this->filledString($request, 'role')) {
            $query->where('role', $role);
        }

        if ($request->has('verified')) {
            $this->boolValue($request->query('verified'))
                ? $query->whereNotNull('email_verified_at')
                : $query->whereNull('email_verified_at');
        }

        $this->applySort($query, 'users', $request, [
            'id',
            'name',
            'email',
            'role',
            'created_at',
            'updated_at',
        ], 'created_at');

        $paginator = $query->paginate($this->perPage($request), ['*'], 'page', $this->page($request));

        return $this->paginatedResponse(
            $paginator,
            fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'verified' => $user->email_verified_at !== null,
                'email_verified_at' => $this->formatDate($user->email_verified_at),
                'created_at' => $this->formatDate($user->created_at),
                'updated_at' => $this->formatDate($user->updated_at),
            ],
            ['source' => 'users']
        );
    }

    public function lookups(): JsonResponse
    {
        return response()->json([
            'data' => [
                'countries' => $this->countriesLookup(),
                'languages' => $this->languagesLookup(),
                'categories' => $this->categoriesLookup(),
                'currencies' => $this->currenciesLookup(),
                'statuses' => [
                    'websites' => $this->distinctValues('websites', 'status'),
                    'new_entries' => $this->distinctValues('new_entries', 'status'),
                    'storage' => $this->distinctValues('storage', 'status'),
                    'orders' => $this->orderStatuses(),
                ],
                'article_types' => [
                    OrderItem::TYPE_STANDARD,
                    OrderItem::TYPE_SENSITIVE,
                ],
            ],
            'meta' => [
                'read_only' => true,
            ],
        ]);
    }

    public function analyticsSummary(Request $request): JsonResponse
    {
        $metric = (string) $request->query('metric', 'storages');
        $groupBy = (string) $request->query('group_by', 'month');
        $allowedMetrics = ['domains', 'new_entries', 'storages', 'orders', 'revenue', 'cost', 'profit'];
        $allowedGroups = ['day', 'week', 'month', 'country', 'language', 'category', 'client', 'status'];

        if (! in_array($metric, $allowedMetrics, true) || ! in_array($groupBy, $allowedGroups, true)) {
            return response()->json([
                'message' => 'Unsupported analytics metric or group_by value.',
                'allowed' => [
                    'metric' => $allowedMetrics,
                    'group_by' => $allowedGroups,
                ],
            ], 422);
        }

        $source = $this->analyticsSource($metric);

        if (! $this->tableExists($source['table'])) {
            return response()->json([
                'data' => [],
                'meta' => [
                    'metric' => $metric,
                    'group_by' => $groupBy,
                    'table_available' => false,
                ],
            ]);
        }

        $query = DB::table($source['table']);
        $this->applyBuilderDeletedScope($query, $source['table']);
        $this->applyAnalyticsDateRange($query, $source['table'], $request);

        if ($source['table'] === 'orders' && $this->hasColumn('orders', 'status')) {
            $query->where('orders.status', '!=', Order::STATUS_DRAFT);
        }

        $groupResult = $this->applyAnalyticsGrouping($query, $source['table'], $groupBy);

        if ($groupResult === null) {
            return response()->json([
                'message' => 'The requested group_by value is not supported for this metric.',
            ], 422);
        }

        $this->selectAnalyticsMetric($query, $source);

        $rows = $query->get()->map(function (object $row) use ($metric, $groupBy) {
            $count = isset($row->count) ? (int) $row->count : null;
            $value = property_exists($row, 'value') ? $this->number($row->value) : null;

            return [
                'group_by' => $groupBy,
                'group' => [
                    'key' => $row->group_key ?? null,
                    'label' => $this->analyticsGroupLabel($row),
                    'id' => $row->group_id ?? null,
                ],
                'metric' => $metric,
                'count' => $count,
                'total' => $value,
                'average' => $value !== null && $count > 0 ? round($value / $count, 2) : null,
            ];
        })->values();

        return response()->json([
            'data' => $rows,
            'meta' => [
                'metric' => $metric,
                'group_by' => $groupBy,
                'date_from' => $request->query('date_from'),
                'date_to' => $request->query('date_to'),
            ],
        ]);
    }

    public function domainMetrics(): JsonResponse
    {
        if (! $this->tableExists('websites')) {
            return response()->json([
                'data' => [],
                'meta' => [
                    'table_available' => false,
                ],
            ]);
        }

        $base = DB::table('websites');
        $this->applyBuilderDeletedScope($base, 'websites');

        $trafficColumn = $this->firstExistingColumn('websites', ['semrush_traffic', 'organic_traffic', 'ahrefs_traffic']);

        return response()->json([
            'data' => [
                'total_domains' => (clone $base)->count(),
                'by_country' => $this->domainGroupMetrics('country', $base),
                'by_language' => $this->domainGroupMetrics('language', $base),
                'by_category' => $this->domainGroupMetrics('category', $base),
                'pricing' => [
                    'average_price' => $this->aggregateNumber($base, 'websites', 'price', 'avg'),
                    'min_price' => $this->aggregateNumber($base, 'websites', 'price', 'min'),
                    'max_price' => $this->aggregateNumber($base, 'websites', 'price', 'max'),
                    'average_publisher_price' => $this->aggregateNumber($base, 'websites', 'publisher_price', 'avg'),
                ],
                'seo' => [
                    'average_da' => $this->aggregateNumber($base, 'websites', 'DA', 'avg'),
                    'average_dr' => $this->aggregateNumber($base, 'websites', 'DR', 'avg'),
                    'average_traffic' => $trafficColumn ? $this->aggregateNumber($base, 'websites', $trafficColumn, 'avg') : null,
                ],
                'sensitive_topics' => [
                    'available_count' => $this->countPositive($base, 'websites', 'sensitive_topic_price'),
                    'average_price' => $this->aggregateNumber($base, 'websites', 'sensitive_topic_price', 'avg'),
                ],
                'flags' => [
                    'betting_count' => $this->countTruthy($base, 'websites', 'betting'),
                    'trading_count' => $this->countTruthy($base, 'websites', 'trading'),
                ],
            ],
            'meta' => [
                'read_only' => true,
            ],
        ]);
    }

    private function applyDomainFilters(Builder $query, Request $request, string $table): void
    {
        $q = $this->filledString($request, 'q') ?: $this->filledString($request, 'domain');

        if ($q && $this->hasColumn($table, 'domain_name')) {
            $query->where('domain_name', 'like', "%{$q}%");
        }

        if (($status = $this->filledString($request, 'status')) && $this->hasColumn($table, 'status')) {
            $query->where('status', $status);
        }

        if ($countryId = $this->positiveInt($request->query('country_id'))) {
            $query->where('country_id', $countryId);
        }

        if (($country = $this->filledString($request, 'country')) && $this->canUseBelongsTo('countries', $table, 'country_id')) {
            $query->whereHas('country', function (Builder $inner) use ($country) {
                $inner->where('country_name', 'like', "%{$country}%");
            });
        }

        if ($languageId = $this->positiveInt($request->query('language_id'))) {
            $query->where('language_id', $languageId);
        }

        if (($language = $this->filledString($request, 'language')) && $this->canUseBelongsTo('languages', $table, 'language_id')) {
            $query->whereHas('language', function (Builder $inner) use ($language) {
                $inner->where('name', 'like', "%{$language}%")
                    ->orWhere('code', 'like', "%{$language}%");
            });
        }

        $categoryIds = $this->ids($request, 'category_id');
        $pivot = $table === 'new_entries' ? 'category_new_entry' : 'category_website';

        if ($categoryIds !== [] && $this->tableExists('categories') && $this->tableExists($pivot)) {
            $query->whereHas('categories', function (Builder $inner) use ($categoryIds) {
                $inner->whereIn('categories.id', $categoryIds);
            });
        }

        $this->applyNumericRange($query, $table, 'price', $request, 'price_min', 'price_max');
        $this->applyNumericRange($query, $table, 'DA', $request, 'da_min', null);
        $this->applyNumericRange($query, $table, 'DR', $request, 'dr_min', null);
        $this->applyNumericRange($query, $table, 'as_metric', $request, 'as_min', null);
        $this->applyNumericRange($query, $table, 'ms', $request, 'ms_min', null);

        if ($trafficMin = $this->numeric($request->query('traffic_min'))) {
            $trafficColumns = $this->existingColumns($table, ['semrush_traffic', 'organic_traffic', 'ahrefs_traffic']);

            if ($trafficColumns !== []) {
                $query->where(function (Builder $inner) use ($trafficColumns, $trafficMin) {
                    foreach ($trafficColumns as $index => $column) {
                        $method = $index === 0 ? 'where' : 'orWhere';
                        $inner->{$method}($column, '>=', $trafficMin);
                    }
                });
            }
        }

        foreach (['betting', 'trading'] as $flag) {
            if ($request->has($flag) && $this->hasColumn($table, $flag)) {
                $query->where($flag, $this->boolValue($request->query($flag)) ? 1 : 0);
            }
        }
    }

    private function applyStorageFilters(Builder $query, Request $request): void
    {
        $q = $this->filledString($request, 'q') ?: $this->filledString($request, 'domain');

        if ($q) {
            $query->where(function (Builder $inner) use ($q) {
                foreach (['campaign', 'campaign_code', 'publisher', 'target_url', 'article_url'] as $column) {
                    if ($this->hasColumn('storage', $column)) {
                        $inner->orWhere($column, 'like', "%{$q}%");
                    }
                }

                if ($this->tableExists('websites')) {
                    $inner->orWhereHas('site', function (Builder $site) use ($q) {
                        $site->where('domain_name', 'like', "%{$q}%");
                    });
                }

                if ($this->tableExists('clients')) {
                    $inner->orWhereHas('client', function (Builder $client) use ($q) {
                        $client->where('first_name', 'like', "%{$q}%")
                            ->orWhere('last_name', 'like', "%{$q}%")
                            ->orWhere('email', 'like', "%{$q}%");

                        if ($this->tableExists('companies')) {
                            $client->orWhereHas('company', function (Builder $company) use ($q) {
                                $company->where('name', 'like', "%{$q}%");
                            });
                        }
                    });
                }
            });
        }

        foreach (['campaign', 'status'] as $column) {
            if (($value = $this->filledString($request, $column)) && $this->hasColumn('storage', $column)) {
                $query->where($column, 'like', $column === 'status' ? $value : "%{$value}%");
            }
        }

        if (($client = $this->filledString($request, 'client')) && $this->tableExists('clients')) {
            $query->whereHas('client', function (Builder $inner) use ($client) {
                $inner->where('first_name', 'like', "%{$client}%")
                    ->orWhere('last_name', 'like', "%{$client}%")
                    ->orWhere('email', 'like', "%{$client}%");
            });
        }

        if (($company = $this->filledString($request, 'company')) && $this->tableExists('clients') && $this->tableExists('companies')) {
            $query->whereHas('client.company', function (Builder $inner) use ($company) {
                $inner->where('name', 'like', "%{$company}%");
            });
        }

        if (($contact = $this->filledString($request, 'contact')) && $this->tableExists('contacts') && $this->tableExists('contact_storage')) {
            $query->whereHas('contacts', function (Builder $inner) use ($contact) {
                $inner->where('name', 'like', "%{$contact}%")
                    ->orWhere('first_name', 'like', "%{$contact}%")
                    ->orWhere('last_name', 'like', "%{$contact}%")
                    ->orWhere('email', 'like', "%{$contact}%");
            });
        }

        if ($countryId = $this->positiveInt($request->query('country_id'))) {
            $query->where('country_id', $countryId);
        }

        if ($languageId = $this->positiveInt($request->query('language_id'))) {
            $query->where('language_id', $languageId);
        }

        if (($country = $this->filledString($request, 'country')) && $this->canUseBelongsTo('countries', 'storage', 'country_id')) {
            $query->whereHas('country', function (Builder $inner) use ($country) {
                $inner->where('country_name', 'like', "%{$country}%");
            });
        }

        if (($language = $this->filledString($request, 'language')) && $this->canUseBelongsTo('languages', 'storage', 'language_id')) {
            $query->whereHas('language', function (Builder $inner) use ($language) {
                $inner->where('name', 'like', "%{$language}%")
                    ->orWhere('code', 'like', "%{$language}%");
            });
        }

        $categoryIds = $this->ids($request, 'category_id');

        if ($categoryIds !== [] && $this->tableExists('categories') && $this->tableExists('category_storage')) {
            $query->whereHas('categories', function (Builder $inner) use ($categoryIds) {
                $inner->whereIn('categories.id', $categoryIds);
            });
        }

        $this->applyDateRange($query, 'publication_date', $request, 'publication_date_from', 'publication_date_to');
        $this->applyInvoiceFilters($query, $request);
    }

    private function applyOrderFilters(Builder $query, Request $request): void
    {
        if (($status = $this->filledString($request, 'status')) && $this->hasColumn('orders', 'status')) {
            $query->where('status', $status);
        }

        $this->applyDateRange($query, 'submitted_at', $request, 'submitted_from', 'submitted_to');

        if ($user = $this->filledString($request, 'user') ?: $this->filledString($request, 'client')) {
            if ($this->tableExists('users')) {
                $query->whereHas('user', function (Builder $inner) use ($user) {
                    $inner->where('name', 'like', "%{$user}%")
                        ->orWhere('email', 'like', "%{$user}%");
                });
            }
        }

        if ($articleType = $this->filledString($request, 'article_type')) {
            if ($this->tableExists('order_items')) {
                $query->whereHas('items', function (Builder $inner) use ($articleType) {
                    $inner->where('article_type', $articleType);
                });
            }
        }

        if ($domain = $this->filledString($request, 'domain') ?: $this->filledString($request, 'q')) {
            if ($this->tableExists('order_items') && $this->tableExists('websites')) {
                $query->whereHas('items.website', function (Builder $inner) use ($domain) {
                    $inner->where('domain_name', 'like', "%{$domain}%");
                });
            }
        }
    }

    private function applyInvoiceFilters(Builder $query, Request $request): void
    {
        if ($invoiceStatus = $this->filledString($request, 'invoice_status')) {
            if ($invoiceStatus === 'issued') {
                $query->where(function (Builder $inner) {
                    $this->orWhereNotBlank($inner, ['invoice_menford', 'invoice_company']);
                });
            } elseif ($invoiceStatus === 'missing') {
                $query->where(function (Builder $inner) {
                    $this->whereBlank($inner, ['invoice_menford', 'invoice_company']);
                });
            }
        }

        if ($billingStatus = $this->filledString($request, 'billing_status')) {
            if ($billingStatus === 'billed' && $this->hasColumn('storage', 'bill_publisher_date')) {
                $query->whereNotNull('bill_publisher_date');
            } elseif ($billingStatus === 'unbilled' && $this->hasColumn('storage', 'bill_publisher_date')) {
                $query->whereNull('bill_publisher_date');
            }
        }

        if ($paymentStatus = $this->filledString($request, 'payment_status')) {
            match ($paymentStatus) {
                'paid_to_us' => $this->hasColumn('storage', 'payment_to_us_date') ? $query->whereNotNull('payment_to_us_date') : null,
                'unpaid_to_us' => $this->hasColumn('storage', 'payment_to_us_date') ? $query->whereNull('payment_to_us_date') : null,
                'publisher_paid' => $this->hasColumn('storage', 'payment_to_publisher_date') ? $query->whereNotNull('payment_to_publisher_date') : null,
                'publisher_unpaid' => $this->hasColumn('storage', 'payment_to_publisher_date') ? $query->whereNull('payment_to_publisher_date') : null,
                default => null,
            };
        }
    }

    private function transformWebsite(Website $website, bool $includeDeleted): array
    {
        $data = [
            'id' => $website->id,
            'domain' => $website->domain_name,
            'status' => $website->status,
            'country' => $this->countryPayload($website->country ?? null),
            'language' => $this->languagePayload($website->language ?? null),
            'categories' => $this->categoriesPayload($website->categories ?? collect()),
            'type' => $website->type_of_website,
            'currency' => $website->currency_code ?? $website->website_currency ?? $website->currency?->code,
            'prices' => [
                'price' => $this->number($website->price),
                'sensitive_topic_price' => $this->number($website->sensitive_topic_price),
                'publisher_price' => $this->number($website->publisher_price),
                'link_insertion_price' => $this->number($website->link_insertion_price),
                'no_follow_price' => $this->number($website->no_follow_price),
                'special_topic_price' => $this->number($website->special_topic_price),
                'banner_price' => $this->number($website->banner_price),
                'sitewide_link_price' => $this->number($website->sitewide_link_price),
                'original_publisher_price' => $this->number($website->original_publisher_price),
                'original_link_insertion_price' => $this->number($website->original_link_insertion_price),
                'original_no_follow_price' => $this->number($website->original_no_follow_price),
                'original_special_topic_price' => $this->number($website->original_special_topic_price),
                'original_banner_price' => $this->number($website->original_banner_price),
                'original_sitewide_link_price' => $this->number($website->original_sitewide_link_price),
                'profit' => $this->number($website->profit),
            ],
            'seo_metrics' => $this->seoPayload($website),
            'flags' => [
                'betting' => $this->boolOrNull($website->betting),
                'trading' => $this->boolOrNull($website->trading),
                'permanent_link' => $this->boolOrNull($website->permanent_link),
                'more_than_one_link' => $this->boolOrNull($website->more_than_one_link),
                'copywriting' => $this->boolOrNull($website->copywriting),
                'no_sponsored_tag' => $this->boolOrNull($website->no_sponsored_tag),
                'social_media_sharing' => $this->boolOrNull($website->social_media_sharing),
                'post_in_homepage' => $this->boolOrNull($website->post_in_homepage),
            ],
            'evaluation' => [
                'automatic_evaluation' => $this->number($website->automatic_evaluation),
                'kialvo_evaluation' => $this->number($website->kialvo_evaluation),
                'date_kialvo_evaluation' => $this->formatDate($website->date_kialvo_evaluation),
            ],
            'dates' => [
                'date_added' => $this->formatDate($website->date_added),
                'date_publisher_price' => $this->formatDate($website->date_publisher_price),
                'created_at' => $this->formatDate($website->created_at),
                'updated_at' => $this->formatDate($website->updated_at),
            ],
            'notes' => [
                'notes' => $this->safeText($website->notes),
                'extra_notes' => $this->safeText($website->extra_notes),
            ],
        ];

        if ($includeDeleted) {
            $data['dates']['deleted_at'] = $this->formatDate($website->deleted_at);
        }

        return $data;
    }

    private function transformNewEntry(NewEntry $entry, bool $includeDeleted): array
    {
        $data = [
            'id' => $entry->id,
            'domain' => $entry->domain_name,
            'status' => $entry->status,
            'country' => $this->countryPayload($entry->country ?? null),
            'language' => $this->languagePayload($entry->language ?? null),
            'categories' => $this->categoriesPayload($entry->categories ?? collect()),
            'type' => $entry->type_of_website,
            'currency' => $entry->currency_code ?? $entry->website_currency ?? $entry->currency?->code,
            'prices' => [
                'price' => $this->number($entry->price),
                'sensitive_topic_price' => $this->number($entry->sensitive_topic_price),
                'publisher_price' => $this->number($entry->publisher_price),
                'link_insertion_price' => $this->number($entry->link_insertion_price),
                'no_follow_price' => $this->number($entry->no_follow_price),
                'special_topic_price' => $this->number($entry->special_topic_price),
                'profit' => $this->number($entry->profit),
            ],
            'seo_metrics' => $this->seoPayload($entry),
            'flags' => [
                'betting' => $this->boolOrNull($entry->betting),
                'trading' => $this->boolOrNull($entry->trading),
                'copied_to_overview' => $this->boolOrNull($entry->copied_to_overview),
            ],
            'evaluation' => [
                'automatic_evaluation' => $this->number($entry->automatic_evaluation),
                'kialvo_evaluation' => $this->number($entry->kialvo_evaluation),
                'date_kialvo_evaluation' => $this->formatDate($entry->date_kialvo_evaluation),
            ],
            'dates' => [
                'first_contact_date' => $this->formatDate($entry->first_contact_date),
                'date_publisher_price' => $this->formatDate($entry->date_publisher_price),
                'created_at' => $this->formatDate($entry->created_at),
                'updated_at' => $this->formatDate($entry->updated_at),
            ],
            'notes' => [
                'notes' => $this->safeText($entry->notes),
                'extra_notes' => $this->safeText($entry->extra_notes),
            ],
        ];

        if ($includeDeleted) {
            $data['dates']['deleted_at'] = $this->formatDate($entry->deleted_at);
        }

        return $data;
    }

    private function transformStorage(Storage $storage, bool $includeDeleted): array
    {
        $data = [
            'id' => $storage->id,
            'status' => $storage->status,
            'campaign' => [
                'name' => $storage->campaign,
                'code' => $storage->campaign_code,
                'linkbuilder' => $storage->LB,
            ],
            'client' => $this->clientPayload($storage->client ?? null),
            'company' => $this->companyPayload($storage->client?->company ?? null),
            'domain' => [
                'id' => $storage->site?->id,
                'domain' => $storage->site?->domain_name,
            ],
            'country' => $this->countryPayload($storage->country ?? null),
            'language' => $this->languagePayload($storage->language ?? null),
            'categories' => $this->categoriesPayload($storage->categories ?? collect()),
            'contacts' => $this->contactsPayload($storage->contacts ?? collect()),
            'copywriter' => [
                'id' => $storage->copy?->id,
                'label' => $storage->copy?->copy_val,
                'copy_nr' => $storage->copy_nr,
            ],
            'publisher' => [
                'label' => $this->safeText($storage->publisher),
                'amount' => $this->number($storage->publisher_amount),
                'currency' => $storage->publisher_currency,
                'period' => $storage->publisher_period,
            ],
            'financials' => [
                'total_cost' => $this->number($storage->total_cost),
                'menford' => $this->number($storage->menford),
                'client_copy' => $this->number($storage->client_copy),
                'total_revenues' => $this->number($storage->total_revenues),
                'profit' => $this->number($storage->profit),
            ],
            'urls' => [
                'target_url' => $storage->target_url,
                'article_url' => $storage->article_url,
                'anchor_text' => $storage->anchor_text,
            ],
            'dates' => [
                'article_sent_to_publisher' => $this->formatDate($storage->article_sent_to_publisher),
                'publication_date' => $this->formatDate($storage->publication_date),
                'expiration_date' => $this->formatDate($storage->expiration_date),
                'copywriter_commision_date' => $this->formatDate($storage->copywriter_commision_date),
                'copywriter_submission_date' => $this->formatDate($storage->copywriter_submission_date),
                'created_at' => $this->formatDate($storage->created_at),
                'updated_at' => $this->formatDate($storage->updated_at),
            ],
            'billing' => [
                'invoice_to_client_issued' => $this->anyNotBlank($storage, ['invoice_menford', 'invoice_company']),
                'bill_from_publisher_received' => $storage->bill_publisher_date !== null,
                'bill_from_publisher_date' => $this->formatDate($storage->bill_publisher_date),
                'payment_to_us_received' => $storage->payment_to_us_date !== null,
                'payment_to_us_date' => $this->formatDate($storage->payment_to_us_date),
                'payment_to_us_method' => $storage->method_payment_to_us,
                'payment_to_publisher_sent' => $storage->payment_to_publisher_date !== null,
                'payment_to_publisher_date' => $this->formatDate($storage->payment_to_publisher_date),
                'payment_to_publisher_method' => $storage->method_payment_to_publisher,
            ],
        ];

        if ($includeDeleted) {
            $data['dates']['deleted_at'] = $this->formatDate($storage->deleted_at);
        }

        return $data;
    }

    private function transformOrder(Order $order): array
    {
        $items = $order->items ?? collect();

        return [
            'id' => $order->id,
            'status' => $order->status,
            'submitted_at' => $this->formatDate($order->submitted_at),
            'status_changed_at' => $this->formatDate($order->status_changed_at),
            'user' => [
                'id' => $order->user?->id,
                'name' => $order->user?->name,
                'email' => $order->user?->email,
            ],
            'items_count' => $items->count(),
            'article_types' => $items->pluck('article_type')->filter()->unique()->values(),
            'total_amount' => $this->number($items->sum(fn (OrderItem $item) => (float) $item->unit_price)),
            'selected_domains_count' => $items->count(),
            'selected_domains' => $items->take(10)->map(function (OrderItem $item) {
                return [
                    'website_id' => $item->website?->id,
                    'domain' => $item->website?->domain_name,
                    'article_type' => $item->article_type,
                    'unit_price' => $this->number($item->unit_price),
                    'seo_metrics' => [
                        'DA' => $this->intOrNull($item->website?->DA),
                        'DR' => $this->intOrNull($item->website?->DR),
                        'semrush_traffic' => $this->intOrNull($item->website?->semrush_traffic),
                    ],
                ];
            })->values(),
            'notes' => $this->safeText($order->notes),
            'created_at' => $this->formatDate($order->created_at),
            'updated_at' => $this->formatDate($order->updated_at),
        ];
    }

    private function websiteColumns(): array
    {
        return [
            'id',
            'domain_name',
            'status',
            'country_id',
            'language_id',
            'currency_id',
            'currency_code',
            'website_currency',
            'publisher_price',
            'link_insertion_price',
            'no_follow_price',
            'special_topic_price',
            'profit',
            'price',
            'sensitive_topic_price',
            'original_publisher_price',
            'original_link_insertion_price',
            'original_no_follow_price',
            'original_special_topic_price',
            'banner_price',
            'sitewide_link_price',
            'original_banner_price',
            'original_sitewide_link_price',
            'automatic_evaluation',
            'kialvo_evaluation',
            'date_kialvo_evaluation',
            'type_of_website',
            'DA',
            'PA',
            'TF',
            'CF',
            'DR',
            'UR',
            'ZA',
            'as_metric',
            'seozoom',
            'TF_vs_CF',
            'semrush_traffic',
            'ahrefs_keyword',
            'ahrefs_traffic',
            'ms',
            'organic_keywords',
            'organic_traffic',
            'kw_traffic_ratio',
            'seo_metrics_date',
            'betting',
            'trading',
            'permanent_link',
            'more_than_one_link',
            'copywriting',
            'no_sponsored_tag',
            'social_media_sharing',
            'post_in_homepage',
            'date_added',
            'date_publisher_price',
            'notes',
            'extra_notes',
            'created_at',
            'updated_at',
            'deleted_at',
        ];
    }

    private function newEntryColumns(): array
    {
        return array_values(array_unique(array_merge($this->websiteColumns(), [
            'first_contact_date',
            'copied_to_overview',
        ])));
    }

    private function storageColumns(): array
    {
        return [
            'id',
            'website_id',
            'status',
            'LB',
            'client_id',
            'copy_id',
            'copy_nr',
            'copywriter_commision_date',
            'copywriter_submission_date',
            'copywriter_period',
            'language_id',
            'country_id',
            'publisher_amount',
            'publisher_currency',
            'publisher',
            'total_cost',
            'menford',
            'client_copy',
            'total_revenues',
            'profit',
            'campaign',
            'anchor_text',
            'target_url',
            'campaign_code',
            'article_sent_to_publisher',
            'publication_date',
            'expiration_date',
            'publisher_period',
            'article_url',
            'method_payment_to_us',
            'invoice_menford',
            'invoice_company',
            'payment_to_us_date',
            'bill_publisher_date',
            'payment_to_publisher_date',
            'method_payment_to_publisher',
            'created_at',
            'updated_at',
            'deleted_at',
        ];
    }

    private function websiteRelations(): array
    {
        $relations = [];

        if ($this->tableExists('countries')) {
            $relations['country'] = fn ($query) => $query->select($this->existingColumns('countries', ['id', 'country_name']));
        }

        if ($this->tableExists('languages')) {
            $relations['language'] = fn ($query) => $query->select($this->existingColumns('languages', ['id', 'name', 'code']));
        }

        if ($this->tableExists('currencies')) {
            $relations['currency'] = fn ($query) => $query->select($this->existingColumns('currencies', ['id', 'code', 'symbol', 'name']));
        }

        if ($this->tableExists('categories') && $this->tableExists('category_website')) {
            $relations['categories'] = fn ($query) => $query->select($this->qualifiedExistingColumns('categories', ['id', 'name']));
        }

        return $relations;
    }

    private function newEntryRelations(): array
    {
        $relations = [];

        if ($this->tableExists('countries')) {
            $relations['country'] = fn ($query) => $query->select($this->existingColumns('countries', ['id', 'country_name']));
        }

        if ($this->tableExists('languages')) {
            $relations['language'] = fn ($query) => $query->select($this->existingColumns('languages', ['id', 'name', 'code']));
        }

        if ($this->tableExists('currencies')) {
            $relations['currency'] = fn ($query) => $query->select($this->existingColumns('currencies', ['id', 'code', 'symbol', 'name']));
        }

        if ($this->tableExists('categories') && $this->tableExists('category_new_entry')) {
            $relations['categories'] = fn ($query) => $query->select($this->qualifiedExistingColumns('categories', ['id', 'name']));
        }

        return $relations;
    }

    private function storageRelations(): array
    {
        $relations = [];

        if ($this->tableExists('websites')) {
            $relations['site'] = fn ($query) => $query->select($this->existingColumns('websites', ['id', 'domain_name', 'country_id', 'language_id']));
        }

        if ($this->tableExists('clients')) {
            $relations['client'] = fn ($query) => $query->select($this->existingColumns('clients', ['id', 'first_name', 'last_name', 'email', 'company_id']));

            if ($this->tableExists('companies')) {
                $relations['client.company'] = fn ($query) => $query->select($this->existingColumns('companies', ['id', 'name']));
            }
        }

        if ($this->tableExists('copy_tbl')) {
            $relations['copy'] = fn ($query) => $query->select($this->existingColumns('copy_tbl', ['id', 'copy_val']));
        }

        if ($this->tableExists('countries')) {
            $relations['country'] = fn ($query) => $query->select($this->existingColumns('countries', ['id', 'country_name']));
        }

        if ($this->tableExists('languages')) {
            $relations['language'] = fn ($query) => $query->select($this->existingColumns('languages', ['id', 'name', 'code']));
        }

        if ($this->tableExists('categories') && $this->tableExists('category_storage')) {
            $relations['categories'] = fn ($query) => $query->select($this->qualifiedExistingColumns('categories', ['id', 'name']));
        }

        if ($this->tableExists('contacts') && $this->tableExists('contact_storage')) {
            $relations['contacts'] = fn ($query) => $query->select($this->qualifiedExistingColumns('contacts', ['id', 'name', 'first_name', 'last_name']));
        }

        return $relations;
    }

    private function orderRelations(): array
    {
        $relations = [];

        if ($this->tableExists('users')) {
            $relations['user'] = fn ($query) => $query->select($this->existingColumns('users', ['id', 'name', 'email']));
        }

        if ($this->tableExists('order_items')) {
            $relations['items'] = fn ($query) => $query->select($this->existingColumns('order_items', ['id', 'order_id', 'website_id', 'article_type', 'unit_price']));

            if ($this->tableExists('websites')) {
                $relations['items.website'] = fn ($query) => $query->select($this->existingColumns('websites', [
                    'id',
                    'domain_name',
                    'DA',
                    'DR',
                    'semrush_traffic',
                ]));
            }
        }

        return $relations;
    }

    private function paginatedResponse(LengthAwarePaginator $paginator, callable $transformer, array $extraMeta = []): JsonResponse
    {
        return response()->json([
            'data' => $paginator->getCollection()->map($transformer)->values(),
            'meta' => array_merge([
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'read_only' => true,
            ], $extraMeta),
        ]);
    }

    private function emptyPaginatedResponse(Request $request, array $extraMeta = []): JsonResponse
    {
        return response()->json([
            'data' => [],
            'meta' => array_merge([
                'current_page' => $this->page($request),
                'per_page' => $this->perPage($request),
                'total' => 0,
                'last_page' => 1,
                'read_only' => true,
            ], $extraMeta),
        ]);
    }

    private function seoPayload(object $model): array
    {
        return [
            'DA' => $this->intOrNull($model->DA ?? null),
            'PA' => $this->intOrNull($model->PA ?? null),
            'TF' => $this->intOrNull($model->TF ?? null),
            'CF' => $this->intOrNull($model->CF ?? null),
            'DR' => $this->intOrNull($model->DR ?? null),
            'UR' => $this->intOrNull($model->UR ?? null),
            'ZA' => $this->intOrNull($model->ZA ?? null),
            'as_metric' => $this->intOrNull($model->as_metric ?? null),
            'seozoom' => $this->number($model->seozoom ?? null),
            'TF_vs_CF' => $this->number($model->TF_vs_CF ?? null),
            'semrush_traffic' => $this->intOrNull($model->semrush_traffic ?? null),
            'ahrefs_keyword' => $this->intOrNull($model->ahrefs_keyword ?? null),
            'ahrefs_traffic' => $this->intOrNull($model->ahrefs_traffic ?? null),
            'ms' => $this->intOrNull($model->ms ?? null),
            'organic_keywords' => $this->intOrNull($model->organic_keywords ?? null),
            'organic_traffic' => $this->intOrNull($model->organic_traffic ?? null),
            'kw_traffic_ratio' => $this->number($model->kw_traffic_ratio ?? null),
            'seo_metrics_date' => $this->formatDate($model->seo_metrics_date ?? null),
        ];
    }

    private function countryPayload(?object $country): ?array
    {
        if (! $country) {
            return null;
        }

        return [
            'id' => $country->id,
            'name' => $country->country_name,
        ];
    }

    private function languagePayload(?object $language): ?array
    {
        if (! $language) {
            return null;
        }

        return [
            'id' => $language->id,
            'name' => $language->name,
            'code' => $language->code,
        ];
    }

    private function categoriesPayload(iterable $categories): array
    {
        return collect($categories)->map(fn ($category) => [
            'id' => $category->id,
            'name' => $category->name,
        ])->values()->all();
    }

    private function clientPayload(?object $client): ?array
    {
        if (! $client) {
            return null;
        }

        return [
            'id' => $client->id,
            'name' => $this->personName($client, 'Client #'.$client->id),
        ];
    }

    private function companyPayload(?object $company): ?array
    {
        if (! $company) {
            return null;
        }

        return [
            'id' => $company->id,
            'name' => $company->name,
        ];
    }

    private function contactsPayload(iterable $contacts): array
    {
        return collect($contacts)->map(fn ($contact) => [
            'id' => $contact->id,
            'name' => $this->personName($contact, 'Contact #'.$contact->id),
            'role' => $contact->pivot->role ?? null,
            'is_primary' => isset($contact->pivot->is_primary) ? (bool) $contact->pivot->is_primary : null,
        ])->values()->all();
    }

    private function countriesLookup(): array
    {
        if (! $this->tableExists('countries')) {
            return [];
        }

        return Country::query()
            ->select($this->existingColumns('countries', ['id', 'country_name']))
            ->orderBy('country_name')
            ->get()
            ->map(fn (Country $country) => [
                'id' => $country->id,
                'name' => $country->country_name,
            ])
            ->values()
            ->all();
    }

    private function languagesLookup(): array
    {
        if (! $this->tableExists('languages')) {
            return [];
        }

        return Language::query()
            ->select($this->existingColumns('languages', ['id', 'name', 'code']))
            ->orderBy('name')
            ->get()
            ->map(fn (Language $language) => [
                'id' => $language->id,
                'name' => $language->name,
                'code' => $language->code,
            ])
            ->values()
            ->all();
    }

    private function categoriesLookup(): array
    {
        if (! $this->tableExists('categories')) {
            return [];
        }

        return Category::query()
            ->select($this->existingColumns('categories', ['id', 'name']))
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
            ])
            ->values()
            ->all();
    }

    private function currenciesLookup(): array
    {
        if (! $this->tableExists('currencies')) {
            return [];
        }

        return Currency::query()
            ->select($this->existingColumns('currencies', ['id', 'code', 'symbol', 'name']))
            ->orderBy('code')
            ->get()
            ->map(fn (Currency $currency) => [
                'id' => $currency->id,
                'code' => $currency->code,
                'symbol' => $currency->symbol,
                'name' => $currency->name,
            ])
            ->values()
            ->all();
    }

    private function activeDomainsCount(): ?int
    {
        return $this->safeCount('websites', true, function (QueryBuilder $query) {
            if ($this->hasColumn('websites', 'status')) {
                $query->where('status', '!=', 'past');
            }
        });
    }

    private function usersByRole(): array
    {
        if (! $this->tableExists('users') || ! $this->hasColumn('users', 'role')) {
            return [];
        }

        return DB::table('users')
            ->select('role', DB::raw('COUNT(*) as total'))
            ->groupBy('role')
            ->orderBy('role')
            ->get()
            ->mapWithKeys(fn ($row) => [(string) ($row->role ?: 'unknown') => (int) $row->total])
            ->all();
    }

    private function statusCounts(string $table): array
    {
        if (! $this->tableExists($table) || ! $this->hasColumn($table, 'status')) {
            return [];
        }

        $query = DB::table($table)->select('status', DB::raw('COUNT(*) as total'))->groupBy('status')->orderBy('status');
        $this->applyBuilderDeletedScope($query, $table);

        return $query->get()
            ->map(fn ($row) => [
                'status' => $row->status,
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();
    }

    private function distinctValues(string $table, string $column): array
    {
        if (! $this->tableExists($table) || ! $this->hasColumn($table, $column)) {
            return [];
        }

        return DB::table($table)
            ->whereNotNull($column)
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->filter(fn ($value) => $value !== '')
            ->values()
            ->all();
    }

    private function orderStatuses(): array
    {
        return array_values(array_unique(array_merge(
            Order::STATUSES,
            ['confirmed', 'approved'],
            $this->distinctValues('orders', 'status')
        )));
    }

    private function analyticsSource(string $metric): array
    {
        return match ($metric) {
            'domains' => ['table' => 'websites', 'aggregate' => 'count'],
            'new_entries' => ['table' => 'new_entries', 'aggregate' => 'count'],
            'orders' => ['table' => 'orders', 'aggregate' => 'count'],
            'revenue' => ['table' => 'storage', 'aggregate' => 'sum', 'column' => 'total_revenues'],
            'cost' => ['table' => 'storage', 'aggregate' => 'sum', 'column' => 'total_cost'],
            'profit' => ['table' => 'storage', 'aggregate' => 'sum', 'column' => 'profit'],
            default => ['table' => 'storage', 'aggregate' => 'count'],
        };
    }

    private function applyAnalyticsGrouping(QueryBuilder $query, string $table, string $groupBy): ?array
    {
        if (in_array($groupBy, ['day', 'week', 'month'], true)) {
            $dateColumn = $this->analyticsDateColumn($table);

            if (! $dateColumn) {
                return null;
            }

            $expression = $this->dateGroupExpression($table.'.'.$dateColumn, $groupBy);
            $query->selectRaw($expression.' as group_key')->groupByRaw($expression)->orderBy('group_key');

            return ['type' => 'time'];
        }

        if ($groupBy === 'status' && $this->hasColumn($table, 'status')) {
            $query->selectRaw($table.'.status as group_key')->groupBy($table.'.status')->orderBy('group_key');

            return ['type' => 'status'];
        }

        if ($groupBy === 'country' && in_array($table, ['websites', 'new_entries', 'storage'], true) && $this->hasColumn($table, 'country_id') && $this->tableExists('countries')) {
            $query->leftJoin('countries', $table.'.country_id', '=', 'countries.id')
                ->selectRaw('countries.id as group_id')
                ->selectRaw('countries.country_name as group_label')
                ->groupBy('countries.id', 'countries.country_name')
                ->orderBy('group_label');

            return ['type' => 'country'];
        }

        if ($groupBy === 'language' && in_array($table, ['websites', 'new_entries', 'storage'], true) && $this->hasColumn($table, 'language_id') && $this->tableExists('languages')) {
            $query->leftJoin('languages', $table.'.language_id', '=', 'languages.id')
                ->selectRaw('languages.id as group_id')
                ->selectRaw('languages.name as group_label')
                ->selectRaw('languages.code as group_key')
                ->groupBy('languages.id', 'languages.name', 'languages.code')
                ->orderBy('group_label');

            return ['type' => 'language'];
        }

        if ($groupBy === 'category') {
            $pivot = [
                'websites' => ['table' => 'category_website', 'foreign' => 'website_id'],
                'new_entries' => ['table' => 'category_new_entry', 'foreign' => 'new_entry_id'],
                'storage' => ['table' => 'category_storage', 'foreign' => 'storage_id'],
            ][$table] ?? null;

            if (! $pivot || ! $this->tableExists($pivot['table']) || ! $this->tableExists('categories')) {
                return null;
            }

            $query->leftJoin($pivot['table'], $pivot['table'].'.'.$pivot['foreign'], '=', $table.'.id')
                ->leftJoin('categories', $pivot['table'].'.category_id', '=', 'categories.id')
                ->selectRaw('categories.id as group_id')
                ->selectRaw('categories.name as group_label')
                ->groupBy('categories.id', 'categories.name')
                ->orderBy('group_label');

            return ['type' => 'category'];
        }

        if ($groupBy === 'client' && $table === 'storage' && $this->tableExists('clients') && $this->hasColumn('storage', 'client_id')) {
            $query->leftJoin('clients', 'storage.client_id', '=', 'clients.id')
                ->selectRaw('clients.id as group_id')
                ->selectRaw('clients.first_name as first_name')
                ->selectRaw('clients.last_name as last_name')
                ->groupBy('clients.id', 'clients.first_name', 'clients.last_name')
                ->orderBy('first_name')
                ->orderBy('last_name');

            return ['type' => 'client'];
        }

        if ($groupBy === 'client' && $table === 'orders' && $this->tableExists('users') && $this->hasColumn('orders', 'user_id')) {
            $query->leftJoin('users', 'orders.user_id', '=', 'users.id')
                ->selectRaw('users.id as group_id')
                ->selectRaw('users.name as group_label')
                ->groupBy('users.id', 'users.name')
                ->orderBy('group_label');

            return ['type' => 'client'];
        }

        return null;
    }

    private function selectAnalyticsMetric(QueryBuilder $query, array $source): void
    {
        $table = $source['table'];

        if ($source['aggregate'] === 'sum' && $this->hasColumn($table, $source['column'])) {
            $query->selectRaw('COUNT(DISTINCT '.$table.'.id) as count')
                ->selectRaw('SUM(COALESCE('.$table.'.'.$source['column'].', 0)) as value');

            return;
        }

        $query->selectRaw('COUNT(DISTINCT '.$table.'.id) as count');
    }

    private function analyticsGroupLabel(object $row): string
    {
        if (isset($row->group_label) && $row->group_label !== null && $row->group_label !== '') {
            return (string) $row->group_label;
        }

        $name = trim((string) (($row->first_name ?? '').' '.($row->last_name ?? '')));

        if ($name !== '') {
            return $name;
        }

        if (isset($row->group_key) && $row->group_key !== null && $row->group_key !== '') {
            return (string) $row->group_key;
        }

        return 'Unknown';
    }

    private function domainGroupMetrics(string $group, QueryBuilder $base): array
    {
        $query = clone $base;
        $averagePriceExpression = $this->hasColumn('websites', 'price') ? 'AVG(websites.price)' : 'NULL';
        $averageDaExpression = $this->hasColumn('websites', 'DA') ? 'AVG(websites.DA)' : 'NULL';

        if ($group === 'country' && $this->tableExists('countries') && $this->hasColumn('websites', 'country_id')) {
            return $query->leftJoin('countries', 'websites.country_id', '=', 'countries.id')
                ->selectRaw('countries.id as id')
                ->selectRaw('countries.country_name as label')
                ->selectRaw('COUNT(websites.id) as total')
                ->selectRaw($averagePriceExpression.' as average_price')
                ->selectRaw($averageDaExpression.' as average_da')
                ->groupBy('countries.id', 'countries.country_name')
                ->orderByDesc('total')
                ->get()
                ->map(fn ($row) => $this->domainMetricRow($row))
                ->values()
                ->all();
        }

        if ($group === 'language' && $this->tableExists('languages') && $this->hasColumn('websites', 'language_id')) {
            return $query->leftJoin('languages', 'websites.language_id', '=', 'languages.id')
                ->selectRaw('languages.id as id')
                ->selectRaw('languages.name as label')
                ->selectRaw('COUNT(websites.id) as total')
                ->selectRaw($averagePriceExpression.' as average_price')
                ->selectRaw($averageDaExpression.' as average_da')
                ->groupBy('languages.id', 'languages.name')
                ->orderByDesc('total')
                ->get()
                ->map(fn ($row) => $this->domainMetricRow($row))
                ->values()
                ->all();
        }

        if ($group === 'category' && $this->tableExists('categories') && $this->tableExists('category_website')) {
            return $query->leftJoin('category_website', 'category_website.website_id', '=', 'websites.id')
                ->leftJoin('categories', 'category_website.category_id', '=', 'categories.id')
                ->selectRaw('categories.id as id')
                ->selectRaw('categories.name as label')
                ->selectRaw('COUNT(DISTINCT websites.id) as total')
                ->selectRaw($averagePriceExpression.' as average_price')
                ->selectRaw($averageDaExpression.' as average_da')
                ->groupBy('categories.id', 'categories.name')
                ->orderByDesc('total')
                ->get()
                ->map(fn ($row) => $this->domainMetricRow($row))
                ->values()
                ->all();
        }

        return [];
    }

    private function domainMetricRow(object $row): array
    {
        return [
            'id' => $row->id,
            'label' => $row->label ?: 'Unknown',
            'total' => (int) $row->total,
            'average_price' => $this->number($row->average_price),
            'average_da' => $this->number($row->average_da),
        ];
    }

    private function applyNumericRange(Builder $query, string $table, string $column, Request $request, ?string $minKey, ?string $maxKey): void
    {
        if (! $this->hasColumn($table, $column)) {
            return;
        }

        if ($minKey && ($min = $this->numeric($request->query($minKey))) !== null) {
            $query->where($column, '>=', $min);
        }

        if ($maxKey && ($max = $this->numeric($request->query($maxKey))) !== null) {
            $query->where($column, '<=', $max);
        }
    }

    private function applyDateRange(Builder $query, string $column, Request $request, string $fromKey, string $toKey): void
    {
        $table = $query->getModel()->getTable();

        if (! $this->hasColumn($table, $column)) {
            return;
        }

        if ($from = $this->filledString($request, $fromKey)) {
            $query->whereDate($column, '>=', $from);
        }

        if ($to = $this->filledString($request, $toKey)) {
            $query->whereDate($column, '<=', $to);
        }
    }

    private function applySort(Builder $query, string $table, Request $request, array $allowedColumns, string $defaultColumn): void
    {
        $sort = (string) $request->query('sort', $defaultColumn);
        $direction = strtolower((string) $request->query('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        if (! in_array($sort, $allowedColumns, true) || ! $this->hasColumn($table, $sort)) {
            $sort = $this->hasColumn($table, $defaultColumn) ? $defaultColumn : 'id';
        }

        $query->orderBy($sort, $direction);
    }

    private function applyDeletedScope(Builder $query, string $table, Request $request): void
    {
        if (! $this->hasColumn($table, 'deleted_at')) {
            return;
        }

        if ($this->boolValue($request->query('include_deleted')) || $this->boolValue($request->query('only_deleted'))) {
            $query->withTrashed();
        }

        if ($this->boolValue($request->query('only_deleted'))) {
            $query->whereNotNull($table.'.deleted_at');
        }
    }

    private function applyBuilderDeletedScope(QueryBuilder $query, string $table): void
    {
        if ($this->hasColumn($table, 'deleted_at')) {
            $query->whereNull($table.'.deleted_at');
        }
    }

    private function applyAnalyticsDateRange(QueryBuilder $query, string $table, Request $request): void
    {
        $dateColumn = $this->analyticsDateColumn($table);

        if (! $dateColumn) {
            return;
        }

        if ($from = $this->filledString($request, 'date_from')) {
            $query->whereDate($table.'.'.$dateColumn, '>=', $from);
        }

        if ($to = $this->filledString($request, 'date_to')) {
            $query->whereDate($table.'.'.$dateColumn, '<=', $to);
        }
    }

    private function analyticsDateColumn(string $table): ?string
    {
        return match ($table) {
            'orders' => $this->firstExistingColumn($table, ['submitted_at', 'created_at']),
            'storage' => $this->firstExistingColumn($table, ['publication_date', 'created_at']),
            'new_entries' => $this->firstExistingColumn($table, ['first_contact_date', 'created_at']),
            default => $this->firstExistingColumn($table, ['created_at', 'date_added']),
        };
    }

    private function dateGroupExpression(string $qualifiedColumn, string $groupBy): string
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return match ($groupBy) {
                'day' => "strftime('%Y-%m-%d', {$qualifiedColumn})",
                'week' => "strftime('%Y-W%W', {$qualifiedColumn})",
                default => "strftime('%Y-%m', {$qualifiedColumn})",
            };
        }

        return match ($groupBy) {
            'day' => "DATE_FORMAT({$qualifiedColumn}, '%Y-%m-%d')",
            'week' => "DATE_FORMAT({$qualifiedColumn}, '%x-W%v')",
            default => "DATE_FORMAT({$qualifiedColumn}, '%Y-%m')",
        };
    }

    private function safeCount(string $table, bool $excludeDeleted = false, ?callable $callback = null): ?int
    {
        if (! $this->tableExists($table)) {
            return null;
        }

        $query = DB::table($table);

        if ($excludeDeleted) {
            $this->applyBuilderDeletedScope($query, $table);
        }

        if ($callback) {
            $callback($query);
        }

        return $query->count();
    }

    private function latestTimestamp(string $table): ?string
    {
        if (! $this->tableExists($table)) {
            return null;
        }

        $column = $this->firstExistingColumn($table, ['updated_at', 'created_at']);

        if (! $column) {
            return null;
        }

        return $this->formatDate(DB::table($table)->max($column));
    }

    private function aggregateNumber(QueryBuilder $base, string $table, string $column, string $method): ?float
    {
        if (! $this->hasColumn($table, $column)) {
            return null;
        }

        $value = (clone $base)->{$method}($table.'.'.$column);

        return $this->number($value);
    }

    private function countTruthy(QueryBuilder $base, string $table, string $column): ?int
    {
        if (! $this->hasColumn($table, $column)) {
            return null;
        }

        return (clone $base)->where($table.'.'.$column, 1)->count();
    }

    private function countPositive(QueryBuilder $base, string $table, string $column): ?int
    {
        if (! $this->hasColumn($table, $column)) {
            return null;
        }

        return (clone $base)->where($table.'.'.$column, '>', 0)->count();
    }

    private function tableExists(string $table): bool
    {
        if (! array_key_exists($table, $this->tableCache)) {
            try {
                $this->tableCache[$table] = Schema::hasTable($table);
            } catch (Throwable) {
                $this->tableCache[$table] = false;
            }
        }

        return $this->tableCache[$table];
    }

    private function hasColumn(string $table, string $column): bool
    {
        return in_array($column, $this->columnListing($table), true);
    }

    private function firstExistingColumn(string $table, array $columns): ?string
    {
        foreach ($columns as $column) {
            if ($this->hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    private function existingColumns(string $table, array $columns): array
    {
        $existing = $this->columnListing($table);

        return array_values(array_filter($columns, fn (string $column) => in_array($column, $existing, true)));
    }

    private function qualifiedExistingColumns(string $table, array $columns): array
    {
        return array_map(
            fn (string $column) => $table.'.'.$column,
            $this->existingColumns($table, $columns)
        );
    }

    private function columnListing(string $table): array
    {
        if (! array_key_exists($table, $this->columnCache)) {
            if (! $this->tableExists($table)) {
                $this->columnCache[$table] = [];
            } else {
                try {
                    $this->columnCache[$table] = Schema::getColumnListing($table);
                } catch (Throwable) {
                    $this->columnCache[$table] = [];
                }
            }
        }

        return $this->columnCache[$table];
    }

    private function canUseBelongsTo(string $relatedTable, string $table, string $foreignKey): bool
    {
        return $this->tableExists($relatedTable) && $this->hasColumn($table, $foreignKey);
    }

    private function perPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', self::DEFAULT_PER_PAGE);

        return max(1, min(self::MAX_PER_PAGE, $perPage));
    }

    private function page(Request $request): int
    {
        return max(1, (int) $request->query('page', 1));
    }

    private function ids(Request $request, string $key): array
    {
        $value = $request->query($key, []);
        $values = is_array($value) ? $value : explode(',', (string) $value);

        return collect($values)
            ->map(fn ($item) => $this->positiveInt($item))
            ->filter()
            ->values()
            ->all();
    }

    private function filledString(Request $request, string $key): ?string
    {
        $value = $request->query($key);

        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function positiveInt(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        $value = (int) $value;

        return $value > 0 ? $value : null;
    }

    private function numeric(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private function boolValue(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private function boolOrNull(mixed $value): ?bool
    {
        return $value === null ? null : (bool) $value;
    }

    private function number(mixed $value): ?float
    {
        return $value === null || $value === '' ? null : round((float) $value, 2);
    }

    private function intOrNull(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }

    private function formatDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toIso8601String();
        } catch (Throwable) {
            return (string) $value;
        }
    }

    private function safeText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);

        if ($text === '') {
            return null;
        }

        $text = preg_replace('/\b(api[_-]?key|access[_-]?key|token|secret|password|passwd|pwd|bearer|authorization)\b\s*[:=]\s*[^,\s;]+/i', '$1=[redacted]', $text);
        $text = preg_replace('/\bsk-[A-Za-z0-9_-]{12,}\b/', '[redacted-secret]', $text);
        $text = preg_replace('/\bAIza[A-Za-z0-9_-]{20,}\b/', '[redacted-secret]', $text);

        return Str::limit($text, 1000);
    }

    private function personName(object $model, string $fallback): string
    {
        $name = trim((string) ($model->name ?? ''));

        if ($name !== '') {
            return $name;
        }

        $name = trim((string) (($model->first_name ?? '').' '.($model->last_name ?? '')));

        return $name !== '' ? $name : $fallback;
    }

    private function anyNotBlank(object $model, array $columns): bool
    {
        foreach ($columns as $column) {
            if (isset($model->{$column}) && trim((string) $model->{$column}) !== '') {
                return true;
            }
        }

        return false;
    }

    private function orWhereNotBlank(Builder $query, array $columns): void
    {
        foreach ($columns as $index => $column) {
            if (! $this->hasColumn('storage', $column)) {
                continue;
            }

            $method = $index === 0 ? 'where' : 'orWhere';
            $query->{$method}(function (Builder $inner) use ($column) {
                $inner->whereNotNull($column)->where($column, '!=', '');
            });
        }
    }

    private function whereBlank(Builder $query, array $columns): void
    {
        foreach ($columns as $column) {
            if (! $this->hasColumn('storage', $column)) {
                continue;
            }

            $query->where(function (Builder $inner) use ($column) {
                $inner->whereNull($column)->orWhere($column, '');
            });
        }
    }

    private function deletedRequested(Request $request): bool
    {
        return $this->boolValue($request->query('include_deleted')) || $this->boolValue($request->query('only_deleted'));
    }

    private function databaseStatus(): array
    {
        try {
            DB::connection()->getPdo();

            return ['reachable' => true];
        } catch (Throwable) {
            return ['reachable' => false];
        }
    }
}
