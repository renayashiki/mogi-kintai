<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendanceCorrectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendance_corrects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('attendance_record_id')->constrained('attendance_records');
            $table->string('approval_status');
            $table->date('application_date');
            $table->date('new_date');
            $table->time('new_clock_in');
            $table->time('new_clock_out');
            $table->time('new_rest1_in')->nullable();
            $table->time('new_rest1_out')->nullable();
            $table->time('new_rest2_in')->nullable();
            $table->time('new_rest2_out')->nullable();
            $table->string('comment');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendance_corrects');
    }
}
