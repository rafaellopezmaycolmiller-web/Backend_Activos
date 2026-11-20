<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('assets', 'specifications')) {
            Schema::table('assets', function (Blueprint $table): void {
                $table->json('specifications')->nullable()->after('description');
            });
        }
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table): void {
            $table->dropColumn('specifications');
        });
    }
};
