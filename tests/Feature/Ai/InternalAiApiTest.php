<?php

namespace Tests\Feature\Ai;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InternalAiApiTest extends TestCase
{
    private const API_KEY = 'test-internal-api-key';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.ai_orchestration.key' => self::API_KEY,
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => false,
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->createSchema();
        $this->seedData();
    }

    public function test_missing_and_wrong_api_key_are_rejected(): void
    {
        $this->getJson('/api/ai/internal/health')->assertUnauthorized();

        $this->getJson('/api/ai/internal/health', [
            'X-AI-Orchestration-Key' => 'wrong-key',
        ])->assertUnauthorized();
    }

    public function test_valid_api_key_can_reach_health(): void
    {
        $this->getJson('/api/ai/internal/health', $this->headers())
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('read_only', true);
    }

    public function test_openapi_schema_describes_domain_country_and_language_filters(): void
    {
        $response = $this->getJson('/api/ai/internal/openapi.json', $this->headers())
            ->assertOk()
            ->assertJsonPath('openapi', '3.1.0')
            ->assertJsonPath('components.securitySchemes.AiOrchestrationKey.name', 'X-AI-Orchestration-Key')
            ->assertJsonPath('paths./api/ai/internal/domains/search.get.operationId', 'internalAiDomainsSearch');

        $parameters = collect($response->json('paths./api/ai/internal/domains/search.get.parameters'));

        $this->assertTrue($parameters->contains(fn (array $parameter) => $parameter['name'] === 'country' && $parameter['in'] === 'query'));
        $this->assertTrue($parameters->contains(fn (array $parameter) => $parameter['name'] === 'country_id' && $parameter['schema']['type'] === 'integer'));
        $this->assertTrue($parameters->contains(fn (array $parameter) => $parameter['name'] === 'language' && $parameter['in'] === 'query'));
        $this->assertTrue($parameters->contains(fn (array $parameter) => $parameter['name'] === 'language_id' && $parameter['schema']['type'] === 'integer'));
        $this->assertTrue($parameters->contains(fn (array $parameter) => $parameter['name'] === 'per_page' && $parameter['schema']['type'] === 'integer'));
    }

    public function test_list_endpoints_paginate_and_cap_per_page(): void
    {
        foreach ([
            '/api/ai/internal/domains/search?per_page=99',
            '/api/ai/internal/new-entries/search?per_page=99',
            '/api/ai/internal/storages/search?per_page=99',
            '/api/ai/internal/orders/search?per_page=99',
            '/api/ai/internal/users/search?per_page=99',
        ] as $path) {
            $this->getJson($path, $this->headers())
                ->assertOk()
                ->assertJsonPath('meta.per_page', 50)
                ->assertJsonStructure(['data', 'meta' => ['current_page', 'per_page', 'total', 'last_page']]);
        }
    }

    public function test_domains_search_filters_and_excludes_sensitive_fields(): void
    {
        $response = $this->getJson('/api/ai/internal/domains/search?q=example&da_min=50&betting=1', $this->headers())
            ->assertOk()
            ->assertJsonPath('data.0.domain', 'example.com')
            ->assertJsonPath('data.0.prices.publisher_price', 60)
            ->assertJsonPath('data.0.seo_metrics.DA', 55);

        $payload = $response->json();

        $this->assertJsonHasNoSensitiveKeys($payload);
        $this->assertStringNotContainsString('super-secret-domain-token', json_encode($payload));
        $this->assertStringContainsString('[redacted]', json_encode($payload));
    }

    public function test_storage_and_order_search_return_internal_summaries_without_files_or_auth_data(): void
    {
        $storage = $this->getJson('/api/ai/internal/storages/search?q=Campaign&invoice_status=issued', $this->headers())
            ->assertOk()
            ->assertJsonPath('data.0.campaign.name', 'Campaign A')
            ->assertJsonPath('data.0.financials.profit', 50)
            ->assertJsonPath('data.0.billing.invoice_to_client_issued', true)
            ->json();

        $order = $this->getJson('/api/ai/internal/orders/search?status=submitted&article_type=standard', $this->headers())
            ->assertOk()
            ->assertJsonPath('data.0.status', 'submitted')
            ->assertJsonPath('data.0.user.email', 'admin@example.com')
            ->assertJsonPath('data.0.total_amount', 100)
            ->json();

        $this->assertJsonHasNoSensitiveKeys($storage);
        $this->assertJsonHasNoSensitiveKeys($order);
        $this->assertStringNotContainsString('attachment.zip', json_encode($storage));
    }

    public function test_users_search_excludes_auth_internals(): void
    {
        $payload = $this->getJson('/api/ai/internal/users/search?q=Admin&role=admin', $this->headers())
            ->assertOk()
            ->assertJsonPath('data.0.email', 'admin@example.com')
            ->assertJsonPath('data.0.role', 'admin')
            ->json();

        $this->assertJsonHasNoSensitiveKeys($payload);
    }

    public function test_analytics_endpoints_return_calculated_summaries(): void
    {
        $this->getJson('/api/ai/internal/analytics/summary?metric=profit&group_by=client', $this->headers())
            ->assertOk()
            ->assertJsonPath('data.0.metric', 'profit')
            ->assertJsonPath('data.0.total', 50);

        $this->getJson('/api/ai/internal/analytics/domain-metrics', $this->headers())
            ->assertOk()
            ->assertJsonPath('data.total_domains', 2)
            ->assertJsonPath('data.pricing.average_price', 150)
            ->assertJsonPath('data.flags.betting_count', 1);
    }

    public function test_lookups_return_safe_reference_data(): void
    {
        $payload = $this->getJson('/api/ai/internal/lookups', $this->headers())
            ->assertOk()
            ->assertJsonPath('data.countries.0.name', 'Italy')
            ->assertJsonPath('data.languages.0.code', 'en')
            ->assertJsonPath('data.categories.0.name', 'Tech')
            ->json();

        $this->assertJsonHasNoSensitiveKeys($payload);
    }

    private function headers(): array
    {
        return ['X-AI-Orchestration-Key' => self::API_KEY];
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('remember_token')->nullable();
            $table->string('role')->nullable();
            $table->string('google_id')->nullable();
            $table->timestamps();
        });

        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('country_name');
        });

        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
        });

        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('symbol')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('websites', function (Blueprint $table) {
            $table->id();
            $table->string('domain_name');
            $table->string('status')->nullable();
            $table->unsignedBigInteger('country_id')->nullable();
            $table->unsignedBigInteger('language_id')->nullable();
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->string('currency_code')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('sensitive_topic_price', 10, 2)->nullable();
            $table->decimal('publisher_price', 10, 2)->nullable();
            $table->decimal('profit', 10, 2)->nullable();
            $table->unsignedInteger('DA')->nullable();
            $table->unsignedInteger('PA')->nullable();
            $table->unsignedInteger('DR')->nullable();
            $table->unsignedInteger('as_metric')->nullable();
            $table->unsignedInteger('semrush_traffic')->nullable();
            $table->unsignedInteger('ms')->nullable();
            $table->boolean('betting')->default(false);
            $table->boolean('trading')->default(false);
            $table->text('notes')->nullable();
            $table->text('extra_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('category_website', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('website_id');
            $table->timestamps();
        });

        Schema::create('new_entries', function (Blueprint $table) {
            $table->id();
            $table->string('domain_name');
            $table->string('status')->nullable();
            $table->unsignedBigInteger('country_id')->nullable();
            $table->unsignedBigInteger('language_id')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('publisher_price', 10, 2)->nullable();
            $table->unsignedInteger('DA')->nullable();
            $table->unsignedInteger('DR')->nullable();
            $table->date('first_contact_date')->nullable();
            $table->boolean('copied_to_overview')->default(false);
            $table->text('notes')->nullable();
            $table->text('extra_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('category_new_entry', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('new_entry_id');
            $table->timestamps();
        });

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('copy_tbl', function (Blueprint $table) {
            $table->id();
            $table->string('copy_val');
        });

        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('storage', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('website_id')->nullable();
            $table->string('status')->nullable();
            $table->string('LB')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('copy_id')->nullable();
            $table->string('copy_nr')->nullable();
            $table->unsignedBigInteger('language_id')->nullable();
            $table->unsignedBigInteger('country_id')->nullable();
            $table->decimal('publisher_amount', 10, 2)->nullable();
            $table->string('publisher_currency')->nullable();
            $table->string('publisher')->nullable();
            $table->decimal('total_cost', 10, 2)->nullable();
            $table->decimal('menford', 10, 2)->nullable();
            $table->decimal('client_copy', 10, 2)->nullable();
            $table->decimal('total_revenues', 10, 2)->nullable();
            $table->decimal('profit', 10, 2)->nullable();
            $table->string('campaign')->nullable();
            $table->string('anchor_text')->nullable();
            $table->string('target_url')->nullable();
            $table->string('campaign_code')->nullable();
            $table->date('article_sent_to_publisher')->nullable();
            $table->date('publication_date')->nullable();
            $table->date('expiration_date')->nullable();
            $table->string('publisher_period')->nullable();
            $table->string('article_url')->nullable();
            $table->string('method_payment_to_us')->nullable();
            $table->string('invoice_menford')->nullable();
            $table->string('invoice_company')->nullable();
            $table->date('payment_to_us_date')->nullable();
            $table->date('bill_publisher_date')->nullable();
            $table->date('payment_to_publisher_date')->nullable();
            $table->string('method_payment_to_publisher')->nullable();
            $table->string('files')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('category_storage', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('storage_id');
            $table->timestamps();
        });

        Schema::create('contact_storage', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contact_id');
            $table->unsignedBigInteger('storage_id');
            $table->boolean('is_primary')->default(false);
            $table->string('role')->nullable();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('status')->index();
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('status_changed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('website_id');
            $table->string('article_type');
            $table->decimal('unit_price', 10, 2);
            $table->timestamps();
        });
    }

    private function seedData(): void
    {
        $now = now();

        DB::table('users')->insert([
            'id' => 1,
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'email_verified_at' => $now,
            'password' => 'hashed-password',
            'remember_token' => 'remember-secret',
            'role' => 'admin',
            'google_id' => 'google-secret',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('countries')->insert(['id' => 1, 'country_name' => 'Italy']);
        DB::table('languages')->insert(['id' => 1, 'name' => 'English', 'code' => 'en']);
        DB::table('currencies')->insert(['id' => 1, 'code' => 'EUR', 'symbol' => 'EUR', 'name' => 'Euro', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('categories')->insert(['id' => 1, 'name' => 'Tech', 'created_at' => $now, 'updated_at' => $now]);

        DB::table('websites')->insert([
            [
                'id' => 1,
                'domain_name' => 'example.com',
                'status' => 'active',
                'country_id' => 1,
                'language_id' => 1,
                'currency_id' => 1,
                'currency_code' => 'EUR',
                'price' => 100,
                'sensitive_topic_price' => 150,
                'publisher_price' => 60,
                'profit' => 40,
                'DA' => 55,
                'PA' => 40,
                'DR' => 45,
                'as_metric' => 30,
                'semrush_traffic' => 10000,
                'ms' => 200,
                'betting' => true,
                'trading' => false,
                'notes' => 'api_key=super-secret-domain-token',
                'extra_notes' => 'safe operator note',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'domain_name' => 'second.test',
                'status' => 'active',
                'country_id' => 1,
                'language_id' => 1,
                'currency_id' => 1,
                'currency_code' => 'EUR',
                'price' => 200,
                'sensitive_topic_price' => null,
                'publisher_price' => 120,
                'profit' => 80,
                'DA' => 20,
                'PA' => 10,
                'DR' => 15,
                'as_metric' => 10,
                'semrush_traffic' => 250,
                'ms' => 20,
                'betting' => false,
                'trading' => true,
                'notes' => null,
                'extra_notes' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        DB::table('category_website')->insert(['category_id' => 1, 'website_id' => 1, 'created_at' => $now, 'updated_at' => $now]);

        DB::table('new_entries')->insert([
            'id' => 1,
            'domain_name' => 'prospect.example',
            'status' => 'waiting_for_first_answer',
            'country_id' => 1,
            'language_id' => 1,
            'price' => 80,
            'publisher_price' => 50,
            'DA' => 35,
            'DR' => 30,
            'first_contact_date' => '2026-05-01',
            'copied_to_overview' => false,
            'notes' => 'prospect note',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('category_new_entry')->insert(['category_id' => 1, 'new_entry_id' => 1, 'created_at' => $now, 'updated_at' => $now]);

        DB::table('companies')->insert(['id' => 1, 'name' => 'Client Co', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('clients')->insert(['id' => 1, 'first_name' => 'Client', 'last_name' => 'Person', 'email' => 'client@example.com', 'company_id' => 1, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('copy_tbl')->insert(['id' => 1, 'copy_val' => 'Writer A']);
        DB::table('contacts')->insert(['id' => 1, 'name' => 'Publisher Contact', 'email' => 'publisher@example.com', 'phone' => '123', 'created_at' => $now, 'updated_at' => $now]);

        DB::table('storage')->insert([
            'id' => 1,
            'website_id' => 1,
            'status' => 'published',
            'LB' => 'LB1',
            'client_id' => 1,
            'copy_id' => 1,
            'copy_nr' => 'C-1',
            'language_id' => 1,
            'country_id' => 1,
            'publisher_amount' => 60,
            'publisher_currency' => 'EUR',
            'publisher' => 'Publisher Label',
            'total_cost' => 100,
            'menford' => 20,
            'client_copy' => 10,
            'total_revenues' => 150,
            'profit' => 50,
            'campaign' => 'Campaign A',
            'anchor_text' => 'Anchor',
            'target_url' => 'https://client.example/target',
            'campaign_code' => 'CMP-A',
            'article_sent_to_publisher' => '2026-05-02',
            'publication_date' => '2026-05-03',
            'expiration_date' => '2027-05-03',
            'publisher_period' => '12 months',
            'article_url' => 'https://example.com/article',
            'method_payment_to_us' => 'bank',
            'invoice_menford' => 'INV-1',
            'invoice_company' => null,
            'payment_to_us_date' => '2026-05-04',
            'bill_publisher_date' => '2026-05-05',
            'payment_to_publisher_date' => null,
            'method_payment_to_publisher' => 'bank',
            'files' => 'attachment.zip',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('category_storage')->insert(['category_id' => 1, 'storage_id' => 1, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('contact_storage')->insert(['contact_id' => 1, 'storage_id' => 1, 'is_primary' => true, 'role' => 'publisher', 'created_at' => $now, 'updated_at' => $now]);

        DB::table('orders')->insert([
            'id' => 1,
            'user_id' => 1,
            'status' => 'submitted',
            'notes' => 'order note',
            'submitted_at' => $now,
            'status_changed_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('order_items')->insert([
            'id' => 1,
            'order_id' => 1,
            'website_id' => 1,
            'article_type' => 'standard',
            'unit_price' => 100,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function assertJsonHasNoSensitiveKeys(array $payload): void
    {
        $forbidden = [
            'password',
            'remember_token',
            'token',
            'secret',
            'google_id',
            'files',
            'snapshot',
            'api_key',
            'access_key',
        ];

        $walk = function (array $value) use (&$walk, $forbidden): void {
            foreach ($value as $key => $item) {
                $this->assertNotContains(strtolower((string) $key), $forbidden);

                if (is_array($item)) {
                    $walk($item);
                }
            }
        };

        $walk($payload);
    }
}
