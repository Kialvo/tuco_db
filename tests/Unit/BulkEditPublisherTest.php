<?php

namespace Tests\Unit;

use App\Http\Controllers\NewEntryController;
use App\Http\Controllers\WebsiteController;
use PHPUnit\Framework\TestCase;

/**
 * Publisher must be bulk-editable on Domains and New Entries.
 *
 * Three separate lists have to agree for a bulk-edit field to work: the label
 * list in the modal (what the dropdown offers), the widget list (how the value
 * is picked) and the controller's allow-list (what the save accepts). Before
 * this change `contact_id` was in none of the first two and not in the
 * allow-list either, so the field simply did not exist for Domains.
 *
 * The allow-list is the half that can be asserted without rendering Blade, and
 * it is the half that fails silently: a dropdown offering a field the backend
 * rejects looks fine until someone presses Save.
 */
class BulkEditPublisherTest extends TestCase
{
    public function test_publisher_is_bulk_editable_on_domains(): void
    {
        $this->assertContains('contact_id', WebsiteController::BULK_EDITABLE);
    }

    public function test_publisher_is_bulk_editable_on_new_entries(): void
    {
        $this->assertContains('contact_id', NewEntryController::BULK_EDITABLE);
    }

    /**
     * The other relation fields stayed bulk-editable.
     *
     * Adding an entry to these hand-maintained arrays is easy to get wrong —
     * a stray comma drops a field and nobody notices until a bulk edit is
     * refused.
     */
    public function test_the_existing_relation_fields_are_untouched(): void
    {
        foreach (['status', 'language_id', 'country_id', 'linkbuilder', 'type_of_website'] as $field) {
            $this->assertContains($field, WebsiteController::BULK_EDITABLE, "Domains lost [{$field}]");
            $this->assertContains($field, NewEntryController::BULK_EDITABLE, "New Entries lost [{$field}]");
        }
    }
}
