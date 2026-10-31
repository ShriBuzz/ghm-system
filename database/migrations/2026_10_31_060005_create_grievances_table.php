<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // No IP address or user-agent column, for any case (S5).
        Schema::create('grievances', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('pin_hash');
            $table->string('channel');
            $table->boolean('is_anonymous');
            $table->text('name')->nullable();
            $table->text('phone')->nullable();
            $table->text('email')->nullable();
            $table->string('preferred_contact')->nullable();
            $table->foreignId('category_id')->constrained();
            $table->foreignId('location_id')->constrained();
            $table->date('incident_date');
            $table->string('recurrence');
            $table->text('description');
            $table->text('complainant_type')->nullable();
            $table->text('sex')->nullable();
            $table->text('age_group')->nullable();
            $table->text('ethnicity')->nullable();
            $table->text('disability')->nullable();
            $table->string('status')->index();
            $table->string('priority')->nullable()->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('manually_restricted')->default(false);
            $table->boolean('is_restricted')->default(false)->index();
            $table->string('locale', 2);
            $table->timestamp('received_at')->index();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grievances');
    }
};
