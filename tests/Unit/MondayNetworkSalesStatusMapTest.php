<?php

namespace Tests\Unit;

use App\Console\Commands\ImportMondayNetworkSales as Importer;
use PHPUnit\Framework\TestCase;

/**
 * The Monday → LIAB status mapping for the Network Sales migration.
 *
 * The config file is required directly rather than through config(): these
 * assertions need no application, and this repo's .env points at LIVE
 * PRODUCTION.
 *
 * The mapping is the whole point of the migration — if a status lands in the
 * wrong bucket the Approvals chart reports the wrong numbers, silently and
 * permanently, because the imported rows are historical and nobody will
 * re-check them.
 */
class MondayNetworkSalesStatusMapTest extends TestCase
{
    /** @var array<string, array{label: string, group: int, tone: string, decision: string}> */
    private array $statuses;

    protected function setUp(): void
    {
        parent::setUp();

        $config = require __DIR__.'/../../config/linkbuilding.php';
        $this->statuses = $config['publication_statuses'];
    }

    /** Every status on the board is accounted for — an unmapped one is skipped on import. */
    public function test_every_board_status_is_mapped(): void
    {
        $boardStatuses = [
            'Sold', 'Too expensive', 'Low Metrics', 'Waiting answer',
            'Client Refused', 'Not interested at the moment', 'Disappeared',
        ];

        foreach ($boardStatuses as $status) {
            $this->assertArrayHasKey($status, Importer::STATUS_MAP, "Board status \"{$status}\" has no LIAB mapping.");
        }

        $this->assertCount(count($boardStatuses), Importer::STATUS_MAP, 'The map has entries for statuses the board does not use.');
    }

    /** Every target must actually exist, or the import writes an unusable status. */
    public function test_every_mapped_status_exists_in_liab(): void
    {
        foreach (Importer::STATUS_MAP as $monday => $slug) {
            $this->assertArrayHasKey($slug, $this->statuses, "\"{$monday}\" maps to \"{$slug}\", which is not a publication status.");
        }
    }

    /**
     * "Disappeared" must NOT become publisher_disappeared.
     *
     * That status means the PUBLISHER vanished after the client had approved,
     * and LIAB classifies it as approved. On the board it means the client went
     * quiet — a lost sale. Mapping them together would count every lost sale as
     * an approval and inflate the approval rate.
     */
    public function test_client_disappeared_is_not_publisher_disappeared(): void
    {
        $this->assertNotSame('publisher_disappeared', Importer::STATUS_MAP['Disappeared']);
        $this->assertSame('rejected', $this->statuses[Importer::STATUS_MAP['Disappeared']]['decision']);
        $this->assertSame('approved', $this->statuses['publisher_disappeared']['decision']);
    }

    /**
     * Every lost sale must be 'rejected', not 'pending'.
     *
     * The rejection-reasons chart breaks down rejected statuses individually;
     * pending ones are lumped together and excluded from the approval rate, so
     * a pending mapping would make the board's view impossible to reproduce.
     */
    public function test_every_lost_sale_is_rejected_so_it_appears_in_the_chart(): void
    {
        $lost = ['Too expensive', 'Low Metrics', 'Client Refused', 'Not interested at the moment', 'Disappeared'];

        foreach ($lost as $monday) {
            $slug = Importer::STATUS_MAP[$monday];
            $this->assertSame(
                'rejected',
                $this->statuses[$slug]['decision'],
                "\"{$monday}\" maps to \"{$slug}\", which is not rejected — it would not appear in the rejection breakdown."
            );
        }
    }

    /** A sale is a published article. */
    public function test_sold_maps_to_article_published(): void
    {
        $this->assertSame('article_published', Importer::STATUS_MAP['Sold']);
        $this->assertSame('approved', $this->statuses['article_published']['decision']);
    }

    /** The three statuses added for this migration, with the shape the chart needs. */
    public function test_the_new_statuses_exist_and_are_shaped_correctly(): void
    {
        foreach (['refused_other', 'not_interested', 'customer_disappeared'] as $slug) {
            $this->assertArrayHasKey($slug, $this->statuses);
            $this->assertSame('rejected', $this->statuses[$slug]['decision']);
            $this->assertSame(1, $this->statuses[$slug]['group'], 'Site Evaluation — the client refused before production.');
            $this->assertStringStartsWith('Refused by Client', $this->statuses[$slug]['label']);
        }
    }

    /** Statuses that never go live must stay out of the campaign in-flight set. */
    public function test_the_new_statuses_are_not_counted_as_in_flight(): void
    {
        $config = require __DIR__.'/../../config/linkbuilding.php';

        foreach (['refused_other', 'not_interested', 'customer_disappeared'] as $slug) {
            $this->assertNotContains($slug, $config['publication_inflight_statuses']);
        }
    }
}
