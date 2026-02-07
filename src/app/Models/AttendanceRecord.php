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

    /**
     * 休憩合計時間を算出するアクセサ (total_rest_time)
     */
    public function getTotalRestTimeAttribute()
    {
        $totalSeconds = 0;

        foreach ($this->rests as $rest) {
            if ($rest->rest_in && $rest->rest_out) {
                $in = \Carbon\Carbon::parse($rest->rest_in);
                $out = \Carbon\Carbon::parse($rest->rest_out);
                $totalSeconds += $out->diffInSeconds($in);
            }
        }

        if ($totalSeconds === 0) return null;

        // 「計算は精密に、表示はシンプルに」に基づき H:i 形式で返す
        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds / 60) % 60);
        return sprintf('%02d:%02d', $hours, $minutes);
    }

    /**
     * 実働合計時間（退勤 - 出勤 - 休憩合計）を算出するアクセサ (total_time)
     */
    public function getTotalTimeAttribute()
    {
        if (!$this->clock_in || !$this->clock_out) return null;

        $start = \Carbon\Carbon::parse($this->clock_in);
        $end = \Carbon\Carbon::parse($this->clock_out);

        // 総拘束秒数
        $diffSeconds = $end->diffInSeconds($start);

        // 休憩合計秒数を差し引く
        $restSeconds = 0;
        foreach ($this->rests as $rest) {
            if ($rest->rest_in && $rest->rest_out) {
                $restSeconds += \Carbon\Carbon::parse($rest->rest_out)->diffInSeconds(\Carbon\Carbon::parse($rest->rest_in));
            }
        }

        $workSeconds = $diffSeconds - $restSeconds;
        if ($workSeconds < 0) $workSeconds = 0;

        $hours = floor($workSeconds / 3600);
        $minutes = floor(($workSeconds / 60) % 60);
        return sprintf('%02d:%02d', $hours, $minutes);
    }

    public function attendanceCorrects()
    {
        return $this->hasOne(AttendanceCorrect::class);
    }
}
