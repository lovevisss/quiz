<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'student_id',
        'is_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
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
            'two_factor_confirmed_at' => 'datetime',
            'is_admin' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Post, User>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * @return BelongsToMany<Role, User>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function hasRole(string $role): bool
    {
        return $this->roles()
            ->where(function ($query) use ($role): void {
                $query->where('name', $role)
                    ->orWhere('slug', $role);
            })
            ->exists();
    }

    public function assignRole(string $role): void
    {
        $roleModel = Role::query()
            ->where('name', $role)
            ->orWhere('slug', $role)
            ->firstOrFail();

        $this->roles()->syncWithoutDetaching([$roleModel->id]);
    }

    public function removeRole(string $role): void
    {
        $roleModel = Role::query()
            ->where('name', $role)
            ->orWhere('slug', $role)
            ->first();

        if (! $roleModel) {
            throw (new ModelNotFoundException())->setModel(Role::class, [$role]);
        }

        $this->roles()->detach($roleModel->id);
    }

    public function friends()
    {
        return $this->belongsToMany(User::class, 'friends', 'friend_id', 'user_id');
    }

    /**
     * @return BelongsToMany<Post, User>
     */
    public function likedPosts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'likes', 'user_id', 'post_id');
    }
}
