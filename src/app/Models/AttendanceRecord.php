<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'clock_in',
        'clock_out',
        'total_time',
        'total_rest_time',
        'comment',
    ];

    protected $casts = [
        'date' => 'date',
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function rests()
    {
        return $this->hasMany(Rest::class);
    }

    public function getWorkSeconds(): int
    {
        if (!$this->clock_in || !$this->clock_out) {
            return 0;
        }
        $start = $this->clock_in->copy()->second(0);
        $end = $this->clock_out->copy()->second(0);
        $diffSeconds = $end->diffInSeconds($start);
        $restSeconds = $this->getRestSeconds();
        return (int)max(0, $diffSeconds - $restSeconds);
    }

    public function getRestSeconds(): int
    {
        $totalSeconds = 0;
        foreach ($this->rests as $rest) {
            if ($rest->rest_in && $rest->rest_out) {
                $in = \Carbon\Carbon::parse($rest->rest_in)->copy()->second(0);
                $out = \Carbon\Carbon::parse($rest->rest_out)->copy()->second(0);
                $totalSeconds += $out->diffInSeconds($in);
            }
        }
        return (int)$totalSeconds;
    }

    public function formatSecondsForDb(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds / 60) % 60);
        return sprintf('%02d:%02d:00', $hours, $minutes);
    }

    private function formatSecondsToShimei(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds / 60) % 60);
        return sprintf('%d:%02d', $hours, $minutes);
    }

    public function getTotalRestTimeAttribute()
    {
        $seconds = $this->getRestSeconds();
        if ($seconds === 0) return null;
        return $this->formatSecondsToShimei($seconds);
    }

    public function getTotalTimeAttribute()
    {
        if (!$this->clock_in || !$this->clock_out) return null;
        $seconds = $this->getWorkSeconds();
        return $this->formatSecondsToShimei($seconds);
    }
}
