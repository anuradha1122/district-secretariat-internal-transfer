<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('office_reviewer_assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('office_id')
                ->constrained('offices')
                ->restrictOnDelete();

            $table->boolean('is_active')->default(true);

            $table->foreignId('assigned_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('assigned_at')->nullable();

            $table->foreignId('revoked_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('revoked_at')->nullable();

            $table->timestamps();

            $table->unique(
                ['user_id', 'office_id'],
                'ora_user_office_unique'
            );

            $table->index(
                ['office_id', 'is_active'],
                'ora_office_active_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('office_reviewer_assignments');
    }
};
