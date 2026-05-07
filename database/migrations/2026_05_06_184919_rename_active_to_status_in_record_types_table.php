<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('record_types', function (Blueprint $table) {
            $table->string('status')->default('active');
        });

        DB::table('record_types')->where('active', true)->update(['status' => 'active']);
        DB::table('record_types')->where('active', false)->update(['status' => 'inactive']);

        Schema::table('record_types', function (Blueprint $table) {
            $table->dropColumn('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('record_types', function (Blueprint $table) {
            $table->boolean('active')->default(true);
        });

        DB::table('record_types')->where('status', 'active')->update(['active' => true]);
        DB::table('record_types')->where('status', 'inactive')->update(['active' => false]);

        Schema::table('record_types', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
