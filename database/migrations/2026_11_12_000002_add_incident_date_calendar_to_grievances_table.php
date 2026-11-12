<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The calendar the complainant gave the incident date in, "bs" or "ad" (P5, U9). The date
     * itself is always stored in AD; this only lets a month-only BS date show as that BS month.
     */
    public function up(): void
    {
        Schema::table('grievances', function (Blueprint $table) {
            $table->string('incident_date_calendar', 2)->default('ad')->after('incident_date_exact');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('grievances', function (Blueprint $table) {
            $table->dropColumn('incident_date_calendar');
        });
    }
};
