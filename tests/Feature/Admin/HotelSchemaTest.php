<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class HotelSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_hotels_table_and_approvable_columns_exist(): void
    {
        $this->assertTrue(Schema::hasTable('hotels'));
        foreach (['hotels', 'venues', 'packages'] as $t) {
            $this->assertTrue(Schema::hasColumns($t, [
                'approval_status', 'submitted_at', 'submitted_by',
                'reviewed_at', 'reviewed_by', 'review_notes', 'changes_pending_review',
            ]));
        }
        $this->assertTrue(Schema::hasColumn('venues', 'hotel_id'));
        $this->assertTrue(Schema::hasColumn('packages', 'hotel_id'));
    }
}
