<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    const STATUS_OUTSIDE = 'outside';
    const STATUS_WORKING = 'working';
    const STATUS_RESTING = 'resting';
    const STATUS_FINISHED = 'finished';

    protected $fillable = [
        'name',
        'email',
        'password',
        'admin_status',
        'attendance_status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'admin_status' => 'integer',
    ];

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function attendanceCorrects()
    {
        return $this->hasMany(AttendanceCorrect::class);
    }
    /**
     * メール認証済みかどうかを判定する（根本解決のための上書き）
     * DBの email_verified_at に値が入っていれば、セッションの状態に関わらず「認証済み」とみなす。
     */
    public function hasVerifiedEmail()
    {
        return ! is_null($this->email_verified_at);
    }
}
