<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mutual_match_runs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('transfer_cycle_id')
                ->constrained('transfer_cycles')
                ->restrictOnDelete();

            $table->foreignId('service_id')
                ->constrained('services')
                ->restrictOnDelete();

            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('algorithm_version', 30);

            $table->unsignedInteger('input_count')->default(0);
            $table->unsignedInteger('candidate_count')->default(0);

            /*
             * These snapshots include personal information and should be
             * protected like the applications themselves.
             */
            $table->json('input_snapshot');
            $table->json('candidate_snapshot');

            $table->timestamp('generated_at');

            $table->timestamps();

            $table->index(
                ['transfer_cycle_id', 'service_id', 'generated_at'],
                'mmr_cycle_service_date_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mutual_match_runs');
    }
};
