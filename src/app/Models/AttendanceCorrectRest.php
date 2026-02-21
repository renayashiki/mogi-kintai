<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceCorrectRest extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_correct_id',
        'new_rest_in',
        'new_rest_out',
    ];


    protected $casts = [
        'new_rest_in' => 'datetime',
        'new_rest_out' => 'datetime',
    ];

    public function attendanceCorrect()
    {
        return $this->belongsTo(AttendanceCorrect::class);
    }
}
