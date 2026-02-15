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

/* -------------------------------------------------------------------------
     * 新方針：精密計算メソッド（心臓部）
     * ------------------------------------------------------------------------- */

    /**
     * 【計算ロジック】実働合計時間を秒単位で算出する
     */
    public function getWorkSeconds(): int
    {
        if (!$this->clock_in || !$this->clock_out) {
            return 0;
        }

        $start = \Carbon\Carbon::parse($this->clock_in);
        $end = \Carbon\Carbon::parse($this->clock_out);
        $diffSeconds = $end->diffInSeconds($start);

        $restSeconds = $this->getRestSeconds();

        return (int)max(0, $diffSeconds - $restSeconds);
    }

    /**
     * 【計算ロジック】休憩合計時間を秒単位で算出する
     */
    public function getRestSeconds(): int
    {
        $totalSeconds = 0;
        foreach ($this->rests as $rest) {
            if ($rest->rest_in && $rest->rest_out) {
                $in = \Carbon\Carbon::parse($rest->rest_in);
                $out = \Carbon\Carbon::parse($rest->rest_out);
                $totalSeconds += $out->diffInSeconds($in);
            }
        }
        return (int)$totalSeconds;
    }

    /**
     * 【重要】DB保存用のフォーマット (HH:MM:SS)
     * 内部での更新時にこれを使用します
     */
    public function formatSecondsForDb(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds / 60) % 60);
        return sprintf('%02d:%02d:00', $hours, $minutes); // 秒を 00 で固定
    }

    /**
     * 【整形ロジック】秒を 1:00 形式の文字列に変換する
     */
    private function formatSecondsToShimei(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds / 60) % 60);
        return sprintf('%d:%02d', $hours, $minutes);
    }

    /* -------------------------------------------------------------------------
     * アクセサ（表示用：DBのカラム値を無視して常に再計算する）
     * ------------------------------------------------------------------------- */

    /**
     * 休憩合計時間を算出するアクセサ (total_rest_time)
     */
    public function getTotalRestTimeAttribute()
    {
        // DBの中身を見ず、常にリレーションから計算
        $seconds = $this->getRestSeconds();
        if ($seconds === 0) return null;

        return $this->formatSecondsToShimei($seconds);
    }

    /**
     * 実働合計時間（退勤 - 出勤 - 休憩合計）を算出するアクセサ (total_time)
     */
    public function getTotalTimeAttribute()
    {
        // DBの中身を見ず、常にリレーションから計算
        if (!$this->clock_in || !$this->clock_out) return null;

        $seconds = $this->getWorkSeconds();
        return $this->formatSecondsToShimei($seconds);
    }
}
