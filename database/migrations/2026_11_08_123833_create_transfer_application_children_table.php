<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_application_children', function (Blueprint $table) {
            $table->id();

            $table->foreignId('transfer_application_id')
                ->constrained('transfer_applications')
                ->cascadeOnDelete();

            $table->string('name');

            $table->unsignedTinyInteger('age')->nullable();

            $table->string('school')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_application_children');
    }
};
