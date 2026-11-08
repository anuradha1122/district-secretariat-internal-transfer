<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_password_reset_codes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('eligible_employee_id')
                ->unique()
                ->constrained('eligible_employees')
                ->cascadeOnDelete();

            $table->string('code_hash', 64);

            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();

            $table->foreignId('issued_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_password_reset_codes');
    }
};
