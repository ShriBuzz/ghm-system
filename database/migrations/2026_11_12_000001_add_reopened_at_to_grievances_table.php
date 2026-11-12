<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * When a stopped case was last reopened: its working days count from then (A2, U3).
     */
    public function up(): void
    {
        Schema::table('grievances', function (Blueprint $table) {
            $table->timestamp('reopened_at')->nullable()->after('closed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('grievances', function (Blueprint $table) {
            $table->dropColumn('reopened_at');
        });
    }
};
