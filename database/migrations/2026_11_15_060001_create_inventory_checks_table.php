<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_checks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('campaign_id')->constrained('inventory_campaigns')->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('assets')->restrictOnDelete();
            $table->foreignId('checked_by')->constrained('users')->restrictOnDelete();
            $table->string('result', 30);
            $table->foreignId('observed_area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->foreignId('observed_location_id')->nullable()->constrained('asset_locations')->nullOnDelete();
            $table->timestamp('checked_at');
            $table->string('notes', 1000)->nullable();
            $table->timestamps();
            $table->unique(['campaign_id', 'asset_id']);
            $table->index(['campaign_id', 'result']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_checks');
    }
};
