<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use MongoDB\Laravel\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Cache;

use DateTimeInterface;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Create a Sanctum token stored in MongoDB (bypasses SQL-only NewAccessToken type).
     *
     * @param  array<int, string>  $abilities
     */
    public function createToken(string $name, array $abilities = ['*'], ?DateTimeInterface $expiresAt = null): object
    {
        $plainTextToken = $this->generateTokenString();

        $accessToken = $this->tokens()->create([
            'name' => $name,
            'token' => hash('sha256', $plainTextToken),
            'abilities' => $abilities,
            'expires_at' => $expiresAt,
        ]);

        return new class($accessToken, $accessToken->getKey().'|'.$plainTextToken)
        {
            public function __construct(
                public PersonalAccessToken $accessToken,
                public string $plainTextToken,
            ) {}
        };
    }

    protected static function booted(): void
    {
        static::saved(static function () {
            Cache::forget('api.reports.dashboard');
            Cache::forget('api.dashboard.summary');
        });
        static::deleted(static function () {
            Cache::forget('api.reports.dashboard');
            Cache::forget('api.dashboard.summary');
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
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

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}
