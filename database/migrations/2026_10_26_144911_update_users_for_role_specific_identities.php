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
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('nidn')->nullable()->unique();
            $table->string('nim')->nullable()->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('users')->whereNull('email')->exists()) {
            throw new RuntimeException(
                'Cannot roll back the users identity migration while users have a null email.'
            );
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['nidn']);
            $table->dropUnique(['nim']);
            $table->dropColumn(['nidn', 'nim']);
            $table->string('email')->nullable(false)->change();
        });
    }
};
