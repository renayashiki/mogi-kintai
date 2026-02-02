<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendanceCorrectRestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendance_correct_rests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_correct_id')->constrained('attendance_corrects');
            $table->time('new_rest_in')->nullable();
            $table->time('new_rest_out')->nullable();
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
        Schema::dropIfExists('attendance_correct_rests');
    }
}
