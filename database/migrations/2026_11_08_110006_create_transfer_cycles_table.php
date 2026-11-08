<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_cycles', function (Blueprint $table) {
            $table->id();

            $table->string('name');

            $table->date('opens_on');
            $table->date('closes_on');

            $table->string('status', 20)->default('draft');

            $table->text('description')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['status', 'opens_on', 'closes_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_cycles');
    }
};
