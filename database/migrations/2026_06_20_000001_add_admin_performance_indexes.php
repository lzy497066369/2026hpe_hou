<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('works', function (Blueprint $table): void {
            if (! Schema::hasIndex('works', 'works_submitted_at_index')) {
                $table->index('submitted_at', 'works_submitted_at_index');
            }

            if (! Schema::hasIndex('works', 'works_audit_submitted_index')) {
                $table->index(['audit_status', 'submitted_at'], 'works_audit_submitted_index');
            }

            if (! Schema::hasIndex('works', 'works_group_type_submitted_index')) {
                $table->index(['group', 'type', 'submitted_at'], 'works_group_type_submitted_index');
            }

            if (! Schema::hasIndex('works', 'works_vote_count_index')) {
                $table->index('vote_count', 'works_vote_count_index');
            }
        });

        Schema::table('registration_profiles', function (Blueprint $table): void {
            if (! Schema::hasIndex('registration_profiles', 'registration_profiles_submitted_at_index')) {
                $table->index('submitted_at', 'registration_profiles_submitted_at_index');
            }

            if (! Schema::hasIndex('registration_profiles', 'registration_profiles_audit_submitted_index')) {
                $table->index(['audit_status', 'submitted_at'], 'registration_profiles_audit_submitted_index');
            }
        });

        Schema::table('quota_applications', function (Blueprint $table): void {
            if (! Schema::hasIndex('quota_applications', 'quota_applications_submitted_at_index')) {
                $table->index('submitted_at', 'quota_applications_submitted_at_index');
            }

            if (! Schema::hasIndex('quota_applications', 'quota_applications_audit_submitted_index')) {
                $table->index(['audit_status', 'submitted_at'], 'quota_applications_audit_submitted_index');
            }
        });

        Schema::table('lottery_records', function (Blueprint $table): void {
            if (! Schema::hasIndex('lottery_records', 'lottery_records_drawn_at_index')) {
                $table->index('drawn_at', 'lottery_records_drawn_at_index');
            }

            if (! Schema::hasIndex('lottery_records', 'lottery_records_result_drawn_index')) {
                $table->index(['result_status', 'drawn_at'], 'lottery_records_result_drawn_index');
            }
        });

        Schema::table('game_records', function (Blueprint $table): void {
            if (! Schema::hasIndex('game_records', 'game_records_played_at_index')) {
                $table->index('played_at', 'game_records_played_at_index');
            }

            if (! Schema::hasIndex('game_records', 'game_records_distance_index')) {
                $table->index('distance', 'game_records_distance_index');
            }

            if (! Schema::hasIndex('game_records', 'game_records_duration_index')) {
                $table->index('duration', 'game_records_duration_index');
            }
        });

        Schema::table('operation_logs', function (Blueprint $table): void {
            if (! Schema::hasIndex('operation_logs', 'operation_logs_created_at_index')) {
                $table->index('created_at', 'operation_logs_created_at_index');
            }

            if (! Schema::hasIndex('operation_logs', 'operation_logs_module_created_index')) {
                $table->index(['module', 'created_at'], 'operation_logs_module_created_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('operation_logs', function (Blueprint $table): void {
            $table->dropIndex('operation_logs_module_created_index');
            $table->dropIndex('operation_logs_created_at_index');
        });

        Schema::table('game_records', function (Blueprint $table): void {
            $table->dropIndex('game_records_duration_index');
            $table->dropIndex('game_records_distance_index');
            $table->dropIndex('game_records_played_at_index');
        });

        Schema::table('lottery_records', function (Blueprint $table): void {
            $table->dropIndex('lottery_records_result_drawn_index');
            $table->dropIndex('lottery_records_drawn_at_index');
        });

        Schema::table('quota_applications', function (Blueprint $table): void {
            $table->dropIndex('quota_applications_audit_submitted_index');
            $table->dropIndex('quota_applications_submitted_at_index');
        });

        Schema::table('registration_profiles', function (Blueprint $table): void {
            $table->dropIndex('registration_profiles_audit_submitted_index');
            $table->dropIndex('registration_profiles_submitted_at_index');
        });

        Schema::table('works', function (Blueprint $table): void {
            $table->dropIndex('works_vote_count_index');
            $table->dropIndex('works_group_type_submitted_index');
            $table->dropIndex('works_audit_submitted_index');
            $table->dropIndex('works_submitted_at_index');
        });
    }
};
