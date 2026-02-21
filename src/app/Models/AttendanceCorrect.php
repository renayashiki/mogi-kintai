<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceCorrect extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'attendance_record_id',
        'approval_status',
        'application_date',
        'new_date',
        'new_clock_in',
        'new_clock_out',
        'new_rest1_in',
        'new_rest1_out',
        'new_rest2_in',
        'new_rest2_out',
        'comment',
    ];

    protected $casts = [
        'application_date' => 'date',
        'new_date' => 'date',
        'new_clock_in' => 'datetime',
        'new_clock_out' => 'datetime',
        'new_rest1_in' => 'datetime',
        'new_rest1_out' => 'datetime',
        'new_rest2_in' => 'datetime',
        'new_rest2_out' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendanceRecord()
    {
        return $this->belongsTo(AttendanceRecord::class);
    }

    public function attendanceCorrectRests()
    {
        return $this->hasMany(AttendanceCorrectRest::class);
    }
}
