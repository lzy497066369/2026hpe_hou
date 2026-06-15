<?php

namespace App\Services\Voting;

use App\Enums\WorkPublishStatus;
use App\Models\User;
use App\Models\Work;
use App\Models\WorkVote;
use App\Services\Support\AtomicLock;
use Illuminate\Support\Facades\DB;

class VotingService
{
    public function __construct(private readonly AtomicLock $lock)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function vote(User $user, string $workId): array
    {
        return $this->lock->run("vote:user:{$user->id}:work:{$workId}", function () use ($user, $workId): array {
            return DB::transaction(function () use ($user, $workId): array {
            $work = Work::query()->lockForUpdate()->findOrFail($workId);

            abort_if($work->publish_status !== WorkPublishStatus::Published->value, 422, '作品不可投票');
            abort_if($work->user_id === $user->id, 422, '不能给自己的作品投票');

            $today = now()->toDateString();
            $dailyVotes = WorkVote::query()
                ->where('user_id', $user->id)
                ->whereDate('vote_date', $today)
                ->count();
            $sameWorkVotes = WorkVote::query()
                ->where('user_id', $user->id)
                ->where('work_id', $work->id)
                ->whereDate('vote_date', $today)
                ->count();

            abort_if($dailyVotes >= 5, 422, '今日剩余票数不足');
            abort_if($sameWorkVotes >= 3, 422, '今日给该作品投票已达上限');

            WorkVote::query()->create([
                'work_id' => $work->id,
                'user_id' => $user->id,
                'vote_date' => $today,
                'source' => 'h5',
            ]);

            $work->increment('vote_count');
            $work->refresh();

            return [
                'workId' => (string) $work->id,
                'voteCount' => $work->vote_count,
                'remainingVotes' => 4 - $dailyVotes,
            ];
            });
        });
    }
}
