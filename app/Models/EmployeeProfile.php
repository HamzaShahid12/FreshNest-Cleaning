<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_title', 'bio', 'skills', 'working_days', 'work_start', 'work_end',
        'emergency_contact', 'is_available', 'hired_on',
    ];

    protected function casts(): array
    {
        return [
            'skills' => 'array',
            'working_days' => 'array',
            'is_available' => 'boolean',
            'hired_on' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function canDoService(int $serviceId): bool
    {
        $skills = $this->skills ?? [];

        return empty($skills) || in_array($serviceId, array_map('intval', $skills), true);
    }
}
