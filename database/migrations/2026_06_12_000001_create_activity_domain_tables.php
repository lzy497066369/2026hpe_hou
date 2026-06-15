<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('uploaded_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('url');
            $table->string('mime_type')->default('application/octet-stream');
            $table->unsignedBigInteger('size')->default(0);
            $table->string('checksum')->nullable();
            $table->string('usage_type')->index();
            $table->boolean('is_committed')->default(false);
            $table->timestamps();
        });

        Schema::create('registration_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('employee_no')->index();
            $table->string('name');
            $table->string('department');
            $table->string('contact');
            $table->foreignId('material_file_id')->nullable()->constrained('uploaded_files')->nullOnDelete();
            $table->string('audit_status')->default('draft')->index();
            $table->string('audit_remark')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('works', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type')->index();
            $table->string('group')->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('cover_file_id')->nullable()->constrained('uploaded_files')->nullOnDelete();
            $table->foreignId('content_file_id')->nullable()->constrained('uploaded_files')->nullOnDelete();
            $table->string('tool_name')->nullable();
            $table->text('prompt_text')->nullable();
            $table->string('audit_status')->default('submitted')->index();
            $table->string('publish_status')->default('hidden')->index();
            $table->unsignedInteger('vote_count')->default(0);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('work_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_id')->constrained('works')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('vote_date')->index();
            $table->string('source')->default('h5');
            $table->timestamps();
        });

        Schema::create('prizes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('level')->index();
            $table->unsignedInteger('stock')->default(0);
            $table->string('status')->default('active')->index();
            $table->string('image_url')->nullable();
            $table->timestamps();
        });

        Schema::create('lottery_qualifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('source_type')->index();
            $table->boolean('qualified')->default(false);
            $table->unsignedInteger('chance_count')->default(0);
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'source_type']);
        });

        Schema::create('lottery_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('prize_id')->nullable()->constrained('prizes')->nullOnDelete();
            $table->string('result_status')->default('pending')->index();
            $table->timestamp('drawn_at')->nullable();
            $table->timestamps();
        });

        Schema::create('prize_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lottery_record_id')->unique()->constrained('lottery_records')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('claim_type');
            $table->string('receiver_name');
            $table->string('receiver_phone');
            $table->string('receiver_address')->nullable();
            $table->string('pickup_name')->nullable();
            $table->string('pickup_phone')->nullable();
            $table->string('pickup_employee_no')->nullable();
            $table->string('pickup_remark')->nullable();
            $table->string('claim_status')->default('submitted')->index();
            $table->timestamps();
        });

        Schema::create('game_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('distance')->default(0);
            $table->unsignedInteger('score')->default(0);
            $table->unsignedInteger('duration')->default(0);
            $table->timestamp('played_at')->nullable();
            $table->timestamps();
            $table->index(['score', 'distance']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_records');
        Schema::dropIfExists('prize_claims');
        Schema::dropIfExists('lottery_records');
        Schema::dropIfExists('lottery_qualifications');
        Schema::dropIfExists('prizes');
        Schema::dropIfExists('work_votes');
        Schema::dropIfExists('works');
        Schema::dropIfExists('registration_profiles');
        Schema::dropIfExists('uploaded_files');
        Schema::dropIfExists('api_tokens');
    }
};
