<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rest extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_record_id',
        'rest_in',
        'rest_out',
    ];

    protected $casts = [
        'rest_in' => 'datetime:H:i',
        'rest_out' => 'datetime:H:i',
    ];

    public function attendanceRecord()
    {
        return $this->belongsTo(AttendanceRecord::class);
    }
}
