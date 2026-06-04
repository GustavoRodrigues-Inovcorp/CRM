<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_form_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_form_id')->constrained()->cascadeOnDelete();
            $table->foreignId('entity_id')->nullable()->constrained()->nullOnDelete();
            $table->json('data');
            $table->string('ip')->nullable();
            $table->string('origin')->nullable(); /* URL de origem */
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_form_submissions');
    }
};