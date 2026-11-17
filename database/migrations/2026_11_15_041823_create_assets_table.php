<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table): void {
            $table->id();
            $table->string('asset_code', 40)->unique();
            $table->string('inventory_code', 40)->nullable()->unique();
            $table->foreignId('category_id')->constrained('asset_categories')->restrictOnDelete();
            $table->foreignId('status_id')->constrained('asset_statuses')->restrictOnDelete();
            $table->foreignId('area_id')->constrained('areas')->restrictOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('asset_locations')->nullOnDelete();
            $table->string('name', 180);
            $table->text('description')->nullable();
            $table->string('brand', 100)->nullable();
            $table->string('model', 120)->nullable();
            $table->string('serial_number', 120)->nullable()->index();
            $table->date('acquisition_date')->nullable();
            $table->decimal('acquisition_value', 14, 2)->nullable();
            $table->unsignedSmallInteger('useful_life_years')->nullable();
            $table->unsignedInteger('current_version')->default(1);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['area_id', 'is_active']);
            $table->index(['category_id', 'status_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
