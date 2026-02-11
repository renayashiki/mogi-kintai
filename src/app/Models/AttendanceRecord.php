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
        // --- ここから追加 ---
        // DBに直接 '01:00:00' 等が入っているなら、計算せずにそれを優先する
        if (!empty($this->attributes['total_rest_time'])) {
            $parts = explode(':', $this->attributes['total_rest_time']);
            $hours = (int)$parts[0];   // '00' -> 0
            $minutes = (int)$parts[1]; // '35' -> 35
            return sprintf('%d:%02d', $hours, $minutes); // 0:35 / 1:00 / 10:00 形式を確定
        }
        // --- ここまで追加 ---

        $totalSeconds = 0;
        foreach ($this->rests as $rest) {
            if ($rest->rest_in && $rest->rest_out) {
                $in = \Carbon\Carbon::parse($rest->rest_in);
                $out = \Carbon\Carbon::parse($rest->rest_out);
                $totalSeconds += $out->diffInSeconds($in);
            }
        }

        if ($totalSeconds === 0) return null;

        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds / 60) % 60);
        // 見本に合わせて %02d:%02d から %d:%02d (1:00形式) に微調整
        return sprintf('%d:%02d', $hours, $minutes);
    }

    /**
     * 実働合計時間（退勤 - 出勤 - 休憩合計）を算出するアクセサ (total_time)
     */
    public function getTotalTimeAttribute()
    {
        // --- ここから追加 ---
        // DBに値があるなら、それを優先（西さんの複製データの救済）
        if (!empty($this->attributes['total_time'])) {
            $parts = explode(':', $this->attributes['total_time']);
            $hours = (int)$parts[0];
            $minutes = (int)$parts[1];
            return sprintf('%d:%02d', $hours, $minutes);
        }
        // --- ここまで追加 ---

        if (!$this->clock_in || !$this->clock_out) return null;

        $start = \Carbon\Carbon::parse($this->clock_in);
        $end = \Carbon\Carbon::parse($this->clock_out);
        $diffSeconds = $end->diffInSeconds($start);

        $restSeconds = 0;
        foreach ($this->rests as $rest) {
            if ($rest->rest_in && $rest->rest_out) {
                $restSeconds += \Carbon\Carbon::parse($rest->rest_out)->diffInSeconds(\Carbon\Carbon::parse($rest->rest_in));
            }
        }

        $workSeconds = max(0, $diffSeconds - $restSeconds);
        $hours = floor($workSeconds / 3600);
        $minutes = floor(($workSeconds / 60) % 60);
        return sprintf('%d:%02d', $hours, $minutes);
    }
}
