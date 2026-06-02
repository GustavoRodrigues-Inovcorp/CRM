<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            /* Associação polimórfica — pode ligar a Entity, Person ou Deal */
            $table->nullableMorphs('eventable');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['meeting', 'call', 'task', 'email'])->default('task');
            $table->datetime('start_at');
            $table->datetime('end_at')->nullable();
            $table->string('location')->nullable();
            $table->boolean('completed')->default(false);
            $table->string('color')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};