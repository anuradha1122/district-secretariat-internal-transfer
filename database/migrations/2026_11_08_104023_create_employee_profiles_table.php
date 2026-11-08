<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_profiles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->unique()
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('office_id')
                ->constrained('offices')
                ->restrictOnDelete();

            $table->foreignId('service_id')
                ->constrained('services')
                ->restrictOnDelete();

            $table->string('name_with_initials');
            $table->string('designation');
            $table->string('employee_no', 50)->nullable();
            $table->string('mobile_no', 20)->nullable();

            $table->timestamps();

            $table->index(['office_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_profiles');
    }
};
