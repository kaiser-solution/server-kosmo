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
        // Add application_id to permissions
        Schema::table('permissions', function (Blueprint $table) {
            if (! Schema::hasColumn('permissions', 'application_id')) {
                $table->foreignId('application_id')->nullable()->constrained()->onDelete('cascade');
            }
        });

        // Drop pivot permission_application
        Schema::dropIfExists('permission_application');

        // Fix typo aplication_plan
        Schema::dropIfExists('aplication_plan');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('application_plan')) {
            Schema::table('application_plan', function (Blueprint $table) {
                $table->renameColumn('application_id', 'aplication_id');
            });
            Schema::rename('application_plan', 'aplication_plan');
        }

        Schema::create('permission_application', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permission_id')->constrained()->onDelete('cascade');
            $table->foreignId('application_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->dropForeign(['application_id']);
            $table->dropColumn('application_id');
        });
    }
};
