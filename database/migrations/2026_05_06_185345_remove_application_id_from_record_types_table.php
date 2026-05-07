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
        Schema::table('record_types', function (Blueprint $table) {
            $table->dropUnique('record_types_application_id_slug_unique');
            $table->dropForeign(['application_id']);
            $table->dropColumn('application_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('record_types', function (Blueprint $table) {
            $table->foreignId('application_id')->constrained();
            $table->unique(['application_id', 'slug'], 'record_types_application_id_slug_unique');
        });
    }
};
