<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reconciliation_runs', function (Blueprint $table): void {
            $table->id(); $table->string('code', 50)->unique(); $table->string('name', 180);
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->foreignId('inventory_campaign_id')->nullable()->constrained('inventory_campaigns')->nullOnDelete();
            $table->string('source_file_name', 255); $table->string('source_path', 500)->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('status', 20)->default('completed'); $table->timestamp('executed_at');
            $table->string('notes', 1000)->nullable(); $table->timestamps();
            $table->index(['status', 'executed_at']);
        });
        Schema::create('reconciliation_differences', function (Blueprint $table): void {
            $table->id(); $table->foreignId('reconciliation_run_id')->constrained('reconciliation_runs')->cascadeOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->string('asset_code', 100); $table->string('difference_type', 40);
            $table->json('system_snapshot')->nullable(); $table->json('reference_snapshot')->nullable();
            $table->string('status', 30)->default('pending'); $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable(); $table->string('resolution_notes', 1000)->nullable(); $table->timestamps();
            $table->index(['reconciliation_run_id', 'status']);
        });
    }
    public function down(): void { Schema::dropIfExists('reconciliation_differences'); Schema::dropIfExists('reconciliation_runs'); }
};
