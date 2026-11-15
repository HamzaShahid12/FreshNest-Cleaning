<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * Role, active flag, notes and password-change flag are intentionally NOT mass assignable.
     * Privileged code sets them explicitly with forceFill().
     */
    protected $fillable = ['name', 'email', 'password', 'phone'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function assignedBookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'employee_id');
    }

    public function employeeProfile(): HasOne
    {
        return $this->hasOne(EmployeeProfile::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /** Send the verification link without letting a mail failure break registration or booking. */
    public function sendVerificationSafely(): void
    {
        try {
            $this->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** Relative URL of the profile photo (works on any host), or null. */
    public function avatarUrl(): ?string
    {
        return $this->avatar ? '/storage/'.ltrim($this->avatar, '/') : null;
    }

    public function isAdminRole(): bool
    {
        return in_array($this->role, config('freshnest.admin_roles'), true);
    }

    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    public function roleLabel(): string
    {
        return config('freshnest.roles')[$this->role] ?? $this->role;
    }
}
