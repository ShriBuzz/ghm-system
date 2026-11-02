<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "I don't remember the exact day" (P5): the date then holds the first of the month.
     */
    public function up(): void
    {
        Schema::table('grievances', function (Blueprint $table) {
            $table->boolean('incident_date_exact')->default(true)->after('incident_date');
        });
    }

    public function down(): void
    {
        Schema::table('grievances', function (Blueprint $table) {
            $table->dropColumn('incident_date_exact');
        });
    }
};
