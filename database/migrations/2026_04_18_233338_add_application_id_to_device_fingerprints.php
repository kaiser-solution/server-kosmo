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
        Schema::table('device_fingerprints', function (Blueprint $table) {
            $table->dropUnique(['fingerprint']);
            $table->foreignId('application_id')->nullable()->after('user_id')->constrained()->cascadeOnDelete();
            $table->unique(['fingerprint', 'application_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('device_fingerprints', function (Blueprint $table) {
            $table->dropUnique(['fingerprint', 'application_id']);
            $table->dropForeign(['application_id']);
            $table->dropColumn('application_id');
            $table->unique('fingerprint');
        });
    }
};
