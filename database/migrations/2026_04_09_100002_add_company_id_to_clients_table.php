<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Step 1: add the FK column (nullable so existing rows don't break)
        Schema::table('clients', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->after('email');
            $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
        });

        // ── Step 2: seed companies table from distinct company names in clients
        //    For each case-insensitive group, prefer the row whose name is NOT all-lowercase
        //    (i.e. the capitalised/mixed-case version). If all are lowercase, keep the first one.
        $groups = DB::table('clients')
            ->whereNotNull('company')
            ->where('company', '!=', '')
            ->select('company')
            ->get()
            ->groupBy(fn($r) => strtolower(trim($r->company)));

        foreach ($groups as $lowerName => $rows) {
            // prefer non-all-lowercase if available
            $preferred = $rows->first(fn($r) => $r->company !== strtolower($r->company))
                ?? $rows->first();

            $name = trim($preferred->company);

            // insert company (ignore if a race somehow created a duplicate)
            DB::table('companies')->insertOrIgnore(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);

            $companyId = DB::table('companies')->where('name', $name)->value('id');

            // update all clients whose company matches (case-insensitively) to point to this id
            foreach ($rows as $row) {
                DB::table('clients')
                    ->whereRaw('LOWER(TRIM(company)) = ?', [strtolower(trim($row->company))])
                    ->update(['company_id' => $companyId]);
            }
        }

        // ── Step 3: drop the old text column
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('company');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('company')->nullable()->after('email');
        });

        // restore text values from companies relation
        DB::statement('
            UPDATE clients c
            JOIN companies co ON co.id = c.company_id
            SET c.company = co.name
        ');

        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};
