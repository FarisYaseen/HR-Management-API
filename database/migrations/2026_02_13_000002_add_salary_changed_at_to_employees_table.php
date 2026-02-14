<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->timestamp('salary_changed_at')->nullable()->after('salary');
        });

        // Backfill old data so existing employees can be filtered immediately.
        DB::table('employees')
            ->whereNull('salary_changed_at')
            ->update(['salary_changed_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('salary_changed_at');
        });
    }
};
