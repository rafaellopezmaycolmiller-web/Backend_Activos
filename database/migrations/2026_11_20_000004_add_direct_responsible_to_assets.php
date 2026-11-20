<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('assets', 'responsible_name')) {
            Schema::table('assets', function (Blueprint $table): void {
                $table->string('responsible_name', 180)->nullable()->after('location_id');
                $table->string('responsible_document', 30)->nullable()->after('responsible_name');
            });
        }
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table): void {
            $table->dropColumn(['responsible_name', 'responsible_document']);
        });
    }
};
