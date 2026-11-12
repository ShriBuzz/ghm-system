<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * When the Administrators were told an open case had passed its target, so they are told
     * once per crossing; a reopening clears it (U6).
     */
    public function up(): void
    {
        Schema::table('grievances', function (Blueprint $table) {
            $table->timestamp('escalated_at')->nullable()->after('reopened_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('grievances', function (Blueprint $table) {
            $table->dropColumn('escalated_at');
        });
    }
};
