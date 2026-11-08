<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_applications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('transfer_cycle_id')
                ->constrained('transfer_cycles')
                ->restrictOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('reference_no', 40)->unique();

            /*
             * Draft data is nullable so the employee can save
             * and continue the application later. Required fields
             * will be validated before submission.
             */
            $table->string('full_name')->nullable();

            $table->string('name_with_initials_sinhala')->nullable();

            $table->string('name_with_initials_english')->nullable();

            $table->date('date_of_birth')->nullable();

            $table->string('gender', 30)->nullable();

            $table->text('permanent_address')->nullable();

            $table->text('temporary_address')->nullable();

            $table->string('office_phone', 20)->nullable();

            $table->string('personal_phone', 20)->nullable();

            $table->string('marital_status', 30)->nullable();

            $table->string('spouse_name')->nullable();

            $table->string('spouse_occupation')->nullable();

            $table->string('spouse_workplace')->nullable();

            $table->string('post')->nullable();

            $table->string('grade')->nullable();

            $table->string('appointment_letter_no')->nullable();

            $table->boolean('is_confirmed')->nullable();

            $table->foreignId('current_office_id')
                ->nullable()
                ->constrained('offices')
                ->restrictOnDelete();

            $table->foreignId('service_id')
                ->nullable()
                ->constrained('services')
                ->restrictOnDelete();

            $table->date('reported_to_current_office_on')->nullable();

            $table->boolean('accept_nearby_office')->nullable();

            $table->text('transfer_reason')->nullable();

            $table->boolean('declaration_accepted')->default(false);

            $table->string('status', 30)->default('draft');

            $table->timestamp('submitted_at')->nullable();

            $table->timestamps();

            $table->unique(
                ['transfer_cycle_id', 'user_id'],
                'transfer_applications_cycle_user_unique'
            );

            $table->index(['status', 'current_office_id']);

            $table->index(['transfer_cycle_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_applications');
    }
};
