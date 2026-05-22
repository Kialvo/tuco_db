<?php

namespace App\Support\Ai;

class InternalAiOpenApiDocument
{
    public static function toArray(): array
    {
        return [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'Linkinablink Internal AI API',
                'version' => '1.0.0',
                'description' => 'Read-only internal API endpoints approved for the AI orchestration platform.',
            ],
            'security' => [
                ['AiOrchestrationKey' => []],
            ],
            'components' => [
                'securitySchemes' => [
                    'AiOrchestrationKey' => [
                        'type' => 'apiKey',
                        'in' => 'header',
                        'name' => 'X-AI-Orchestration-Key',
                    ],
                ],
                'schemas' => [
                    'PaginatedResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'data' => ['type' => 'array', 'items' => ['type' => 'object']],
                            'meta' => ['type' => 'object'],
                        ],
                    ],
                ],
            ],
            'paths' => [
                '/api/ai/internal/health' => [
                    'get' => [
                        'operationId' => 'internalAiHealth',
                        'summary' => 'Check internal AI API health',
                        'parameters' => [],
                        'responses' => self::jsonResponses(),
                    ],
                ],
                '/api/ai/internal/overview' => [
                    'get' => [
                        'operationId' => 'internalAiOverview',
                        'summary' => 'Read internal business overview counts',
                        'parameters' => [],
                        'responses' => self::jsonResponses(),
                    ],
                ],
                '/api/ai/internal/domains/search' => [
                    'get' => [
                        'operationId' => 'internalAiDomainsSearch',
                        'summary' => 'Search domain inventory',
                        'parameters' => [
                            ...self::paginationParameters(),
                            ...self::deletedParameters(),
                            ...self::domainSearchParameters(),
                            self::queryParameter('sensitive_price_min', 'number', 'Minimum sensitive topic price.'),
                            self::queryParameter('sensitive_price_max', 'number', 'Maximum sensitive topic price.'),
                        ],
                        'responses' => self::paginatedResponses(),
                    ],
                ],
                '/api/ai/internal/new-entries/search' => [
                    'get' => [
                        'operationId' => 'internalAiNewEntriesSearch',
                        'summary' => 'Search internal prospecting entries',
                        'parameters' => [
                            ...self::paginationParameters(),
                            ...self::deletedParameters(),
                            ...self::domainSearchParameters(),
                            self::queryParameter('first_contact_date_from', 'string', 'Filter by first contact date on or after this date.'),
                            self::queryParameter('first_contact_date_to', 'string', 'Filter by first contact date on or before this date.'),
                            self::queryParameter('copied_to_overview', 'boolean', 'Filter by whether the entry was copied to overview.'),
                        ],
                        'responses' => self::paginatedResponses(),
                    ],
                ],
                '/api/ai/internal/storages/search' => [
                    'get' => [
                        'operationId' => 'internalAiStoragesSearch',
                        'summary' => 'Search internal storage/campaign rows',
                        'parameters' => [
                            ...self::paginationParameters(),
                            ...self::deletedParameters(),
                            self::queryParameter('q', 'string', 'Search campaign, domain, publisher, URLs, or related text.'),
                            self::queryParameter('domain', 'string', 'Filter by domain name.'),
                            self::queryParameter('campaign', 'string', 'Filter by campaign name or code.'),
                            self::queryParameter('status', 'string', 'Filter by storage status.'),
                            self::queryParameter('client', 'string', 'Filter by client name.'),
                            self::queryParameter('company', 'string', 'Filter by company name.'),
                            self::queryParameter('contact', 'string', 'Filter by publisher/contact name.'),
                            self::queryParameter('country', 'string', 'Filter by country name.'),
                            self::queryParameter('country_id', 'integer', 'Filter by country id.'),
                            self::queryParameter('language', 'string', 'Filter by language name or code.'),
                            self::queryParameter('language_id', 'integer', 'Filter by language id.'),
                            self::queryParameter('category_id', 'integer', 'Filter by one category id or comma-separated category ids.'),
                            self::queryParameter('publication_date_from', 'string', 'Filter by publication date on or after this date.'),
                            self::queryParameter('publication_date_to', 'string', 'Filter by publication date on or before this date.'),
                            self::queryParameter('invoice_status', 'string', 'Filter invoice status: issued or missing.'),
                            self::queryParameter('billing_status', 'string', 'Filter billing status: billed or unbilled.'),
                            self::queryParameter('payment_status', 'string', 'Filter payment status.'),
                        ],
                        'responses' => self::paginatedResponses(),
                    ],
                ],
                '/api/ai/internal/orders/search' => [
                    'get' => [
                        'operationId' => 'internalAiOrdersSearch',
                        'summary' => 'Search submitted customer orders',
                        'parameters' => [
                            ...self::paginationParameters(),
                            self::queryParameter('status', 'string', 'Filter by order status.'),
                            self::queryParameter('submitted_from', 'string', 'Filter by submitted date on or after this date.'),
                            self::queryParameter('submitted_to', 'string', 'Filter by submitted date on or before this date.'),
                            self::queryParameter('user', 'string', 'Filter by user name or email.'),
                            self::queryParameter('client', 'string', 'Alias for user/client search.'),
                            self::queryParameter('article_type', 'string', 'Filter order items by article type.'),
                            self::queryParameter('domain', 'string', 'Filter by ordered domain.'),
                            self::queryParameter('q', 'string', 'General order search.'),
                        ],
                        'responses' => self::paginatedResponses(),
                    ],
                ],
                '/api/ai/internal/users/search' => [
                    'get' => [
                        'operationId' => 'internalAiUsersSearch',
                        'summary' => 'Search users',
                        'parameters' => [
                            ...self::paginationParameters(),
                            self::queryParameter('q', 'string', 'Search by user name or email.'),
                            self::queryParameter('role', 'string', 'Filter by user role.'),
                            self::queryParameter('verified', 'boolean', 'Filter by email verification state.'),
                        ],
                        'responses' => self::paginatedResponses(),
                    ],
                ],
                '/api/ai/internal/lookups' => [
                    'get' => [
                        'operationId' => 'internalAiLookups',
                        'summary' => 'Read countries, languages, categories, currencies, and statuses',
                        'parameters' => [],
                        'responses' => self::jsonResponses(),
                    ],
                ],
                '/api/ai/internal/analytics/summary' => [
                    'get' => [
                        'operationId' => 'internalAiAnalyticsSummary',
                        'summary' => 'Read grouped analytics summaries',
                        'parameters' => [
                            self::queryParameter('date_from', 'string', 'Filter analytics rows on or after this date.'),
                            self::queryParameter('date_to', 'string', 'Filter analytics rows on or before this date.'),
                            self::queryParameter('group_by', 'string', 'Group by day, week, month, country, language, category, client, or status.'),
                            self::queryParameter('metric', 'string', 'Metric: domains, new_entries, storages, orders, revenue, cost, or profit.'),
                        ],
                        'responses' => self::jsonResponses(),
                    ],
                ],
                '/api/ai/internal/analytics/domain-metrics' => [
                    'get' => [
                        'operationId' => 'internalAiDomainMetrics',
                        'summary' => 'Read domain metric aggregates',
                        'parameters' => [],
                        'responses' => self::jsonResponses(),
                    ],
                ],
            ],
        ];
    }

    private static function domainSearchParameters(): array
    {
        return [
            self::queryParameter('q', 'string', 'Search by domain text.'),
            self::queryParameter('domain', 'string', 'Search by domain text.'),
            self::queryParameter('status', 'string', 'Filter by status.'),
            self::queryParameter('country', 'string', 'Filter by country name, such as Italy.'),
            self::queryParameter('country_id', 'integer', 'Filter by country id.'),
            self::queryParameter('language', 'string', 'Filter by language name or code.'),
            self::queryParameter('language_id', 'integer', 'Filter by language id.'),
            self::queryParameter('category_id', 'integer', 'Filter by one category id or comma-separated category ids.'),
            self::queryParameter('price_min', 'number', 'Minimum public price.'),
            self::queryParameter('price_max', 'number', 'Maximum public price.'),
            self::queryParameter('da_min', 'number', 'Minimum domain authority.'),
            self::queryParameter('dr_min', 'number', 'Minimum domain rating.'),
            self::queryParameter('as_min', 'number', 'Minimum authority score.'),
            self::queryParameter('ms_min', 'number', 'Minimum marketplace score.'),
            self::queryParameter('traffic_min', 'number', 'Minimum available traffic metric.'),
            self::queryParameter('betting', 'boolean', 'Filter betting availability.'),
            self::queryParameter('trading', 'boolean', 'Filter trading availability.'),
        ];
    }

    private static function paginationParameters(): array
    {
        return [
            self::queryParameter('page', 'integer', 'Page number.'),
            self::queryParameter('per_page', 'integer', 'Items per page, capped by the API.'),
            self::queryParameter('sort', 'string', 'Sort column allowlisted by each endpoint.'),
            self::queryParameter('direction', 'string', 'Sort direction: asc or desc.'),
        ];
    }

    private static function deletedParameters(): array
    {
        return [
            self::queryParameter('include_deleted', 'boolean', 'Include soft-deleted rows when supported.'),
            self::queryParameter('only_deleted', 'boolean', 'Return only soft-deleted rows when supported.'),
        ];
    }

    private static function queryParameter(string $name, string $type, string $description): array
    {
        return [
            'name' => $name,
            'in' => 'query',
            'required' => false,
            'description' => $description,
            'schema' => [
                'type' => $type,
            ],
        ];
    }

    private static function jsonResponses(): array
    {
        return [
            '200' => [
                'description' => 'Successful JSON response.',
                'content' => [
                    'application/json' => [
                        'schema' => ['type' => 'object'],
                    ],
                ],
            ],
            '401' => ['description' => 'Unauthorized.'],
            '503' => ['description' => 'AI orchestration API is not configured.'],
        ];
    }

    private static function paginatedResponses(): array
    {
        return [
            '200' => [
                'description' => 'Successful paginated JSON response.',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            '$ref' => '#/components/schemas/PaginatedResponse',
                        ],
                    ],
                ],
            ],
            '401' => ['description' => 'Unauthorized.'],
            '503' => ['description' => 'AI orchestration API is not configured.'],
        ];
    }
}
