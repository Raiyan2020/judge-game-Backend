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
            ->withPivot('role', 'status', 'title', 'invited_by')
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

    /**
     * Memoized so `globalRank()` and `localRank()` (called separately by
     * `UserResource`) agree on the SAME chosen role.
     *
     * @var array{global:int,local:int}|null
     */
    private ?array $rankCache = null;

    /**
     * The user's global + local rank, computed the SAME way the ranking screen
     * ranks (`UserRepository::usersByRoleRank` — positional, per role, local by
     * `country_id`), so the profile card matches the board instead of the old
     * "count strictly greater by total_points + 1", which collapsed to 1 for
     * everyone while no points are awarded.
     *
     * A user can hold several roles across groups; we report their BEST-standing
     * role (lowest global position) and that same role's local position. With no
     * group role we fall back to the everyone board (total_points).
     *
     * @return array{global:int,local:int}
     */
    private function computeRanks(): array
    {
        if ($this->rankCache !== null) {
            return $this->rankCache;
        }

        $repo = app(\App\Repositories\UserRepository::class);
        $roles = $repo->acceptedRolesOf($this);

        if (empty($roles)) {
            return $this->rankCache = [
                'global' => $repo->rankInRoleBoard($this, null, false),
                'local'  => $repo->rankInRoleBoard($this, null, true),
            ];
        }

        $best = null;
        foreach ($roles as $role) {
            $global = $repo->rankInRoleBoard($this, $role, false);
            if ($best === null || $global < $best['global']) {
                $best = [
                    'global' => $global,
                    'local'  => $repo->rankInRoleBoard($this, $role, true),
                ];
            }
        }

        return $this->rankCache = $best;
    }

    public function globalRank(): int
    {
        return $this->computeRanks()['global'];
    }

    public function localRank(): int
    {
        return $this->computeRanks()['local'];
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
