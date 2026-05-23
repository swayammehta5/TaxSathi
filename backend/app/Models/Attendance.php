<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Attendance extends Model
{
    protected $fillable = ['user_id', 'date', 'status', 'overtime_hours'];

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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Employees are stored as users in this application. */
    public function employee()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Filter by calendar month when `date` is stored as Y-m-d (MongoDB string dates).
     */
    public function scopeForMonth($query, int $month, int $year)
    {
        $start = sprintf('%04d-%02d-01', $year, $month);
        $end = sprintf('%04d-%02d-%02d', $year, $month, (int) date('t', strtotime($start)));

        return $query->where('date', '>=', $start)->where('date', '<=', $end);
    }

    public function scopeForYear($query, int $year)
    {
        return $query->where('date', '>=', "{$year}-01-01")->where('date', '<=', "{$year}-12-31");
    }

    public function scopeForDate($query, string $date)
    {
        return $query->where('date', $date);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'overtime_hours' => 'float',
        ];
    }
}
