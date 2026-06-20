<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\RegistrationAuditStatus;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'employee_no',
        'nickname',
        'phone',
        'address',
        'work_address_code',
        'avatar',
        'status',
        'role',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function apiTokens()
    {
        return $this->hasMany(ApiToken::class);
    }

    public function registrationProfile()
    {
        return $this->hasOne(RegistrationProfile::class);
    }

    public function quotaApplications()
    {
        return $this->hasMany(QuotaApplication::class);
    }

    public function uploadedFiles()
    {
        return $this->hasMany(UploadedFile::class);
    }

    public function works()
    {
        return $this->hasMany(Work::class);
    }

    public function gameRecords()
    {
        return $this->hasMany(GameRecord::class);
    }

    public function availableWorkQuota(): int
    {
        $approvedExtraQuota = $this->approved_quota_applications_count
            ?? $this->quotaApplications()
                ->where('audit_status', RegistrationAuditStatus::Approved->value)
                ->count();

        return 1 + $approvedExtraQuota;
    }

    public function usedWorkQuota(): int
    {
        return $this->works_count ?? $this->works()->count();
    }

    public function remainingWorkQuota(): int
    {
        return max($this->availableWorkQuota() - $this->usedWorkQuota(), 0);
    }

    public function workQuotaDisplay(): string
    {
        return $this->usedWorkQuota().'/'.$this->availableWorkQuota();
    }

    public function latestQuotaApplication()
    {
        return $this->hasOne(QuotaApplication::class)->latestOfMany();
    }

    public function quotaApplicationSummary(): string
    {
        $submitted = $this->submitted_quota_applications_count
            ?? $this->quotaApplications()
                ->where('audit_status', RegistrationAuditStatus::Submitted->value)
                ->count();
        $approved = $this->approved_quota_applications_count
            ?? $this->quotaApplications()
                ->where('audit_status', RegistrationAuditStatus::Approved->value)
                ->count();
        $rejected = $this->rejected_quota_applications_count
            ?? $this->quotaApplications()
                ->where('audit_status', RegistrationAuditStatus::Rejected->value)
                ->count();

        return "待审核 {$submitted} / 已通过 {$approved} / 已驳回 {$rejected}";
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->role, ['admin', 'super_admin', 'operator', 'auditor'], true)
            && $this->status === 'active';
    }
}
