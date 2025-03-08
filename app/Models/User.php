<?php

namespace App\Models;

use App\Traits\HasNotifications;
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
use App\Models\Notification;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable implements HasMedia
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, Filterable, InteractsWithMedia, HasNotifications;

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

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile()
            ->registerMediaConversions(function (Media $media) {
                $this->addMediaConversion('thumb')
                    ->width(100)
                    ->height(100)
                    ->sharpen(10)
                    ->nonQueued();

                $this->addMediaConversion('small')
                    ->width(150)
                    ->height(150)
                    ->sharpen(10)
                    ->nonQueued();
            });
    }


    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getAvatarAttribute(): ?string
    {
        if ($media = $this->getFirstMedia('avatar')) {
            return $media->getUrl();
        }

        return null;
    }

    public function getAvatarOrInitialsAttribute(): string
    {
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

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function unreadNotifications(): HasMany
    {
        return $this->notifications()->where('is_seen', false);
    }

    public function readNotifications(): HasMany
    {
        return $this->notifications()->where('is_seen', true);
    }

    public function markAllNotificationsAsRead(): void
    {
        $this->notifications()->update(['is_seen' => true]);
    }

    public function markNotificationAsRead(Notification $notification): void
    {
        if ($notification->user_id === $this->id) {
            $notification->update(['is_seen' => true]);
        }
    }

    public function markNotificationAsUnread(Notification $notification): void
    {
        if ($notification->user_id === $this->id) {
            $notification->update(['is_seen' => false]);
        }
    }

    public function notificationPreferences(): HasOne
    {
        return $this->hasOne(NotificationPreference::class);
    }

    public function paymentDetails(): HasOne
    {
        return $this->hasOne(PaymentDetail::class);
    }
}
