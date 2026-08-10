<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\CaseRole;
use App\Http\Traits\AvatarOperations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'nickname', 'username', 'phone', 'country_code', 'code', 'image', 'gender', 'language', 'fcm_token', 'notified', 'birthdate', 'status', 'country_id', 'pending_phone', 'pending_country_code', 'pending_phone_code', 'pending_phone_expires_at'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, AvatarOperations;

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
            // Cast so expiry is compared as a date, not a string.
            'pending_phone_expires_at' => 'datetime',
        ];
    }

    # Relations
    public function groups()
    {
        return $this->belongsToMany(Group::class)
            ->withPivot('role', 'status', 'title')
            ->withTimestamps();
    }

    public function subscriptions()
    {
        return $this->hasMany(PackageSubscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(PackageSubscription::class)
            ->paid()
            ->where(function ($query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            })
            ->latestOfMany();
    }

    public function groupPermissions()
    {
        return $this->hasMany(GroupUserPermission::class);
    }

    public function points()
    {
        return $this->hasOne(Point::class);
    }

    public function globalRank(): int
    {
        $myPoints = $this->points?->total_points ?? 0;

        return Point::where('total_points', '>', $myPoints)->count() + 1;
    }

    public function localRank(?int $countryCode = null): int
    {
        $countryCode ??= $this->country_code;

        $myPoints = $this->points?->total_points ?? 0;

        return Point::whereHas('user', function ($q) use ($countryCode) {
            $q->where('country_code', $countryCode);
        })
            ->where('total_points', '>', $myPoints)
            ->count() + 1;
    }

    public function plaintiffCases()
    {
        return $this->belongsToMany(
            LegalCase::class,
            'legal_case_parties',
            'user_id',
            'legal_case_id'
        )->wherePivot('role', CaseRole::PLAINTIFF->value);
    }

    public function defendantCases()
    {
        return $this->belongsToMany(
            LegalCase::class,
            'legal_case_parties',
            'user_id',
            'legal_case_id'
        )->wherePivot('role', CaseRole::DEFENDANT->value);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}
