<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'avatar',
        'last_login_at',
        'last_login_ip',
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
            'last_login_at' => 'datetime',
            'is_platform_owner' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'admin' && $this->is_platform_owner;
    }

    public function businesses()
    {
        return $this->belongsToMany(Business::class)->withPivot('role')->withTimestamps();
    }

    public function ownedBusinesses()
    {
        return $this->hasMany(Business::class, 'owner_id');
    }

    public function workspaceRole(?Business $business): ?string
    {
        if (! $business) {
            return null;
        }

        if ((int) $business->owner_id === (int) $this->id) {
            return 'owner';
        }

        $role = $business->users()
            ->whereKey($this->id)
            ->value('business_user.role');

        return $role ? strtolower((string) $role) : null;
    }

    public function hasWorkspaceRole(?Business $business, string ...$roles): bool
    {
        return in_array($this->workspaceRole($business), array_map('strtolower', $roles), true);
    }
}
