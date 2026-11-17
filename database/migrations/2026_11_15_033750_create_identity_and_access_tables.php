<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('areas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->string('code', 30)->unique();
            $table->string('name', 160);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('area_id')->nullable()->after('id')->constrained('areas')->nullOnDelete();
            $table->string('document_number', 20)->nullable()->unique()->after('name');
            $table->string('job_title', 120)->nullable()->after('document_number');
            $table->string('phone', 30)->nullable()->after('job_title');
            $table->boolean('is_active')->default(true)->index()->after('password');
            $table->boolean('mfa_required')->default(false)->after('is_active');
            $table->unsignedSmallInteger('failed_login_attempts')->default(0)->after('mfa_required');
            $table->timestamp('locked_until')->nullable()->index()->after('failed_login_attempts');
            $table->timestamp('last_login_at')->nullable()->after('locked_until');
        });

        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(true);
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 120)->unique();
            $table->string('name', 160);
            $table->string('module', 80)->index();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('permission_role', function (Blueprint $table): void {
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->primary(['permission_id', 'role_id']);
        });

        Schema::create('role_user', function (Blueprint $table): void {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('scope', 30)->default('own_area');
            $table->timestamps();
            $table->primary(['role_id', 'user_id']);
        });

        Schema::create('login_challenges', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('code_hash');
            $table->unsignedSmallInteger('attempts_left')->default(5);
            $table->timestamp('expires_at')->index();
            $table->timestamp('consumed_at')->nullable();
            $table->boolean('remember')->default(false);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'consumed_at']);
        });

        Schema::create('audit_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->string('event', 120)->index();
            $table->string('subject_type')->nullable();
            $table->string('subject_id')->nullable();
            $table->uuid('request_id')->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
        Schema::dropIfExists('login_challenges');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('area_id');
            $table->dropColumn([
                'document_number',
                'job_title',
                'phone',
                'is_active',
                'mfa_required',
                'failed_login_attempts',
                'locked_until',
                'last_login_at',
            ]);
        });

        Schema::dropIfExists('areas');
    }
};
