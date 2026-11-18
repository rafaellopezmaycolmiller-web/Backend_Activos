<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::create('disposal_request_documents', function (Blueprint $table): void {
        $table->id(); $table->foreignId('disposal_request_id')->constrained()->cascadeOnDelete();
        $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
        $table->string('file_name'); $table->string('path'); $table->string('mime_type', 120); $table->unsignedBigInteger('size'); $table->string('sha256', 64); $table->timestamps();
    }); }
    public function down(): void { Schema::dropIfExists('disposal_request_documents'); }
};
