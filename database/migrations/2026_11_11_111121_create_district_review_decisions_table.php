<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('district_review_decisions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('transfer_application_id')
                ->constrained('transfer_applications')
                ->restrictOnDelete();

            $table->foreignId('office_review_decision_id')
                ->constrained('office_review_decisions')
                ->restrictOnDelete();

            $table->foreignId('reviewer_id')
                ->constrained('users')
                ->restrictOnDelete();

            /*
             * approved or rejected
             */
            $table->string('outcome', 20);

            $table->text('remarks')->nullable();

            $table->timestamp('decided_at');

            $table->timestamps();

            /*
             * One district decision per Office Head forwarding.
             */
            $table->unique(
                'office_review_decision_id',
                'drd_office_decision_unique'
            );

            $table->index(
                ['transfer_application_id', 'decided_at'],
                'drd_application_date_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('district_review_decisions');
    }
};
