<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_application_work_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('transfer_application_id');

            $table->foreign('transfer_application_id', 'tawh_application_fk')
                ->references('id')
                ->on('transfer_applications')
                ->cascadeOnDelete();

            $table->string('workplace_name');

            $table->date('from_date')->nullable();

            $table->date('to_date')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_application_work_histories');
    }
};
