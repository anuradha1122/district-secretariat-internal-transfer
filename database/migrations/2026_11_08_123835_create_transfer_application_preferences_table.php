<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_application_preferences', function (Blueprint $table) {
            $table->id();

            $table->foreignId('transfer_application_id');

            $table->foreign('transfer_application_id', 'tap_application_fk')
                ->references('id')
                ->on('transfer_applications')
                ->cascadeOnDelete();

            $table->foreignId('office_id');

            $table->foreign('office_id', 'tap_office_fk')
                ->references('id')
                ->on('offices')
                ->restrictOnDelete();

            $table->unsignedTinyInteger('priority');

            $table->text('reason')->nullable();

            $table->timestamps();

            $table->unique(
                ['transfer_application_id', 'priority'],
                'transfer_preferences_application_priority_unique'
            );

            $table->unique(
                ['transfer_application_id', 'office_id'],
                'transfer_preferences_application_office_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_application_preferences');
    }
};
