<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('record_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('name');          // ex: "Transação", "Questionário"
            $table->string('slug', 100);     // ex: "transaction", "survey"
            $table->text('description')->nullable();
            $table->json('schema')->nullable(); // campos esperados no payload
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['application_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('record_types');
    }
};
