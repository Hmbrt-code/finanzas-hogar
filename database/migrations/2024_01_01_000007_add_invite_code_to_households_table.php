<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Add as nullable first so existing rows don't fail
        Schema::table('households', function (Blueprint $table) {
            $table->string('invite_code', 8)->nullable()->unique()->after('name');
        });

        // Back-fill all existing households with a unique code
        DB::table('households')->whereNull('invite_code')->orderBy('id')->each(function ($household) {
            do {
                $code = strtoupper(Str::random(8));
            } while (DB::table('households')->where('invite_code', $code)->exists());

            DB::table('households')->where('id', $household->id)->update(['invite_code' => $code]);
        });

        // Enforce NOT NULL via raw SQL (no requiere doctrine/dbal)
        DB::statement('ALTER TABLE households ALTER COLUMN invite_code SET NOT NULL');
    }

    public function down(): void
    {
        Schema::table('households', function (Blueprint $table) {
            $table->dropUnique(['invite_code']);
            $table->dropColumn('invite_code');
        });
    }
};
