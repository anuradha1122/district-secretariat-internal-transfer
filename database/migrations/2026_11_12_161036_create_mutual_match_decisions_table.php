<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mutual_match_decisions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('mutual_match_run_id')
                ->constrained('mutual_match_runs')
                ->restrictOnDelete();

            $table->foreignId('transfer_cycle_id')
                ->constrained('transfer_cycles')
                ->restrictOnDelete();

            $table->foreignId('application_a_id')
                ->constrained('transfer_applications')
                ->restrictOnDelete();

            $table->foreignId('application_b_id')
                ->constrained('transfer_applications')
                ->restrictOnDelete();

            $table->foreignId('reviewer_id')
                ->constrained('users')
                ->restrictOnDelete();

            /*
             * confirmed or rejected
             */
            $table->string('outcome', 20);

            $table->text('remarks')->nullable();

            $table->timestamp('decided_at');

            $table->timestamps();

            $table->unique(
                [
                    'mutual_match_run_id',
                    'application_a_id',
                    'application_b_id',
                ],
                'mmd_run_pair_unique'
            );
        });

        Schema::create('mutual_match_participants', function (Blueprint $table) {
            $table->id();

            $table->foreignId('mutual_match_decision_id')
                ->constrained('mutual_match_decisions')
                ->restrictOnDelete();

            $table->foreignId('transfer_cycle_id')
                ->constrained('transfer_cycles')
                ->restrictOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('transfer_application_id')
                ->constrained('transfer_applications')
                ->restrictOnDelete();

            $table->timestamps();

            /*
             * A user may be in only one confirmed pair per cycle.
             * Rejected pairs do not create participant rows.
             */
            $table->unique(
                ['transfer_cycle_id', 'user_id'],
                'mmp_cycle_user_unique'
            );

            $table->unique(
                ['mutual_match_decision_id', 'user_id'],
                'mmp_decision_user_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mutual_match_participants');
        Schema::dropIfExists('mutual_match_decisions');
    }
};
