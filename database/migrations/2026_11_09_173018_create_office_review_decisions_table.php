<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('office_review_decisions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('transfer_application_id')
                ->constrained('transfer_applications')
                ->restrictOnDelete();

            $table->foreignId('reviewer_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('office_id')
                ->constrained('offices')
                ->restrictOnDelete();

            /*
             * forward or returned
             */
            $table->string('outcome', 20);

            /*
             * recommended or not_recommended.
             * Null for a return.
             */
            $table->string('recommendation', 30)->nullable();

            $table->boolean('insufficient_service')->default(false);
            $table->boolean('replacement_required')->default(false);
            $table->boolean('pending_matters')->default(false);
            $table->boolean('other_circumstances')->default(false);

            $table->boolean('signed_paper_verified')->default(false);

            /*
             * Optional for forwarding; required when returned.
             */
            $table->text('remarks')->nullable();

            /*
             * Records the submitted data as it appeared at the
             * time of the decision, including related records.
             */
            $table->json('application_snapshot');

            $table->timestamp('decided_at');

            $table->timestamps();

            $table->index(
                ['transfer_application_id', 'decided_at'],
                'ord_application_date_idx'
            );

            $table->index(
                ['office_id', 'decided_at'],
                'ord_office_date_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('office_review_decisions');
    }
};
