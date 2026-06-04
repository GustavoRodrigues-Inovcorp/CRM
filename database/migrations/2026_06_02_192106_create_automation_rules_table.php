<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('trigger')->default('no_activity'); /* Gatilho */
            $table->integer('trigger_days');                   /* Dias sem atividade */
            $table->string('action')->default('create_activity'); /* Ação */
            $table->string('activity_type')->default('task');
            $table->string('activity_title');
            $table->text('activity_description')->nullable();
            $table->boolean('notify')->default(true);          /* Notificar utilizador */
            $table->boolean('active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_rules');
    }
};