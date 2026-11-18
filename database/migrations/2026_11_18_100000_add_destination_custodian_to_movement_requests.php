<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('movement_requests', function (Blueprint $table): void {
            $table->foreignId('to_user_id')->nullable()->after('to_location_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('movement_requests', function (Blueprint $table): void {
            $table->dropForeign(['to_user_id']);
            $table->dropColumn('to_user_id');
        });
    }
};
