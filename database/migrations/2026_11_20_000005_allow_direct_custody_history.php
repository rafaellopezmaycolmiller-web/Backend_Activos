<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custodianships', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->change();
            $table->string('responsible_name', 180)->nullable()->after('user_id');
            $table->string('responsible_document', 30)->nullable()->after('responsible_name');
            $table->foreignId('area_id')->nullable()->after('responsible_document')->constrained('areas')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->after('area_id')->constrained('asset_locations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('custodianships', function (Blueprint $table): void {
            $table->dropForeign(['area_id']);
            $table->dropForeign(['location_id']);
            $table->dropColumn(['responsible_name', 'responsible_document', 'area_id', 'location_id']);
        });
    }
};
