<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_votes', function (Blueprint $table): void {
            if (! Schema::hasIndex('work_votes', 'work_votes_user_vote_date_index')) {
                $table->index(['user_id', 'vote_date'], 'work_votes_user_vote_date_index');
            }

            if (! Schema::hasIndex('work_votes', 'work_votes_user_work_vote_date_index')) {
                $table->index(['user_id', 'work_id', 'vote_date'], 'work_votes_user_work_vote_date_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('work_votes', function (Blueprint $table): void {
            if (Schema::hasIndex('work_votes', 'work_votes_user_work_vote_date_index')) {
                $table->dropIndex('work_votes_user_work_vote_date_index');
            }

            if (Schema::hasIndex('work_votes', 'work_votes_user_vote_date_index')) {
                $table->dropIndex('work_votes_user_vote_date_index');
            }
        });
    }
};
