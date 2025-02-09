<?php

namespace App\Models;

use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements HasMedia
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, Filterable, InteractsWithMedia;

    protected $guarded = ['id'];

    protected $hidden = [
        'remember_token',
    ];

    protected $appends = [
        'full_name',
        'avatar_or_initials',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'invited_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getAvatarOrInitialsAttribute(): string
    {
        if ($this->avatar) {
            return $this->avatar;
        }

        return strtoupper(mb_substr($this->first_name, 0, 1) . mb_substr($this->last_name, 0, 1));
    }

    public function ownedContainers(): HasMany
    {
        return $this->hasMany(Container::class, 'owner_id');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'created_by');
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('Super-Admin');
    }

    public function paychecks(): HasMany
    {
        return $this->hasMany(Paycheck::class);
    }
}
