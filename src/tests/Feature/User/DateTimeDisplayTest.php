<?php

namespace Tests\Feature\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Carbon\Carbon;

class DateTimeDisplayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ID 4: 日時取得機能
     * 現在の日時情報がUIと同じ形式で出力されている
     */
    public function test_current_date_and_time_are_displayed_correctly()
    {
        Carbon::setLocale('ja');
        $now = Carbon::now();
        Carbon::setTestNow($now);
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('attendance.index'));
        $response->assertStatus(200);
        $expectedDate = $now->isoFormat('YYYY年M月D日(ddd)');
        $expectedTime = $now->format('H:i');
        $response->assertSee($expectedDate);
        $response->assertSee($expectedTime);
        $response->assertDontSee($now->format('H:i:s'));
        Carbon::setTestNow();
    }
}
