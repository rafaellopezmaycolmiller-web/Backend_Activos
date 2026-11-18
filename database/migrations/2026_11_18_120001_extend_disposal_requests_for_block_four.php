<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disposal_requests', function (Blueprint $table): void {
            $table->string('cause', 120)->nullable()->after('reason');
            $table->foreignId('technical_evaluation_id')->nullable()->after('cause')->constrained('technical_evaluations')->nullOnDelete();
            $table->text('documentation_notes')->nullable()->after('technical_evaluation_id');
            $table->string('authority_status', 40)->default('pending_validation')->after('status');
            $table->text('decision_notes')->nullable()->after('rejection_reason');
            $table->foreignId('reviewed_by')->nullable()->after('decided_by')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('decided_at');
        });
    }

    public function down(): void
    {
        Schema::table('disposal_requests', function (Blueprint $table): void {
            $table->dropForeign(['technical_evaluation_id']);
            $table->dropForeign(['reviewed_by']);
            $table->dropColumn(['cause', 'technical_evaluation_id', 'documentation_notes', 'authority_status', 'decision_notes', 'reviewed_by', 'reviewed_at']);
        });
    }
};
