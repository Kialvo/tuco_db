<?php

namespace Tests\Feature\Tokens;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

/**
 * Base case for the hold / release / capture tests.
 *
 * Adds the three tables the token schema does not own — websites, orders and
 * order_items — then runs the REAL hold migration on top, so the columns under
 * test are the ones that will exist in production rather than a hand-written
 * approximation.
 *
 * The base tables are minimal on purpose. Building them from their own
 * migrations would drag in the broken from-scratch chain (see TokenTestCase),
 * and none of these tests care about the other ~90 columns on websites.
 */
abstract class TokenHoldTestCase extends TokenTestCase
{
    private const HOLD_MIGRATION = 'database/migrations/2026_09_10_000001_add_token_holds_to_order_items_table.php';

    protected function setUp(): void
    {
        parent::setUp();

        $this->createMarketplaceTables();

        $this->artisan('migrate', ['--path' => self::HOLD_MIGRATION, '--realpath' => false]);

        // These tests are about the spending machinery, so it is switched on.
        // Production defaults it OFF — see config/tokens.php.
        config(['tokens.spending_enabled' => true]);
    }

    private function createMarketplaceTables(): void
    {
        if (! Schema::hasTable('websites')) {
            Schema::create('websites', function ($table) {
                $table->id();
                $table->string('domain_name');
                $table->decimal('price', 10, 2)->nullable();
                $table->decimal('sensitive_topic_price', 10, 2)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('orders')) {
            Schema::create('orders', function ($table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('status')->default('draft');
                $table->text('notes')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('status_changed_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('storage')) {
            Schema::create('storage', function ($table) {
                $table->id();
                $table->string('status')->nullable();
                $table->string('publisher_domain')->nullable();
                $table->unsignedBigInteger('lb_campaign_id')->nullable();
                $table->timestamps();
                $table->softDeletes();   // the Storage model uses SoftDeletes
            });
        }

        if (! Schema::hasTable('order_items')) {
            Schema::create('order_items', function ($table) {
                $table->id();
                $table->foreignId('order_id')->constrained()->cascadeOnDelete();
                $table->foreignId('website_id')->constrained()->restrictOnDelete();
                $table->unsignedBigInteger('storage_id')->nullable();
                $table->string('article_type')->default('standard');
                $table->decimal('unit_price', 10, 2)->default(0);
                $table->timestamps();
                $table->unique(['order_id', 'website_id']);
            });
        }
    }

    /**
     * A publication linked to an ordered site.
     *
     * Inserted with the query builder and re-read, deliberately: saving the
     * model would fire Storage's boot hooks — status events, campaign progress,
     * notifications — which need half the CRM schema and none of which these
     * tests are about. Settlement is called directly instead.
     */
    protected function makePublication(OrderItem $item, string $status): \App\Models\Storage
    {
        $id = \Illuminate\Support\Facades\DB::table('storage')->insertGetId([
            'status' => $status,
            'publisher_domain' => 'pub'.$item->id.'.test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $item->forceFill(['storage_id' => $id])->save();

        return \App\Models\Storage::find($id);
    }

    /** A website at a given price, so an order item has something to cost. */
    protected function makeWebsite(float $price = 527): \App\Models\Website
    {
        static $n = 0;
        $n++;

        return \App\Models\Website::create([
            'domain_name' => 'site'.$n.'.test',
            'price' => $price,
        ]);
    }

    /** A submitted order for a user, with one item per price given. */
    protected function makeOrder(User $user, array $prices = [527], string $status = 'submitted'): Order
    {
        $order = Order::create([
            'user_id' => $user->id,
            'status' => $status,
            'submitted_at' => now(),
        ]);

        foreach ($prices as $price) {
            $website = $this->makeWebsite($price);

            OrderItem::create([
                'order_id' => $order->id,
                'website_id' => $website->id,
                'article_type' => OrderItem::TYPE_STANDARD,
                'unit_price' => $price,
            ]);
        }

        return $order->fresh('items');
    }

    /** Give an account tokens to spend, without going through a purchase. */
    protected function fund(User $user, int $tokens): \App\Models\TokenAccount
    {
        $ledger = app(\App\Services\Tokens\TokenLedger::class);
        $account = $ledger->accountFor($user);

        $ledger->credit(
            $account,
            $tokens,
            \App\Models\TokenTransaction::TYPE_ADJUSTMENT,
            'test:fund:'.$user->id.':'.$tokens.':'.uniqid(),
        );

        return $account->fresh();
    }
}
