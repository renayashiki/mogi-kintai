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
        // --- 準備: 現在日時を固定（UI画像に合わせて設定） ---
        // 2026年2月16日(月) 10時05分30秒 と仮定
        $mockNow = Carbon::create(2026, 2, 16, 10, 5, 30);
        Carbon::setTestNow($mockNow);

        // 日本語の曜日を取得するためにロケールを日本に設定
        Carbon::setLocale('ja');

        // --- 準備: ログインユーザーの作成 ---
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        // 1. 勤怠打刻画面を開く（手順：準備後にログインを遵守）
        $response = $this->actingAs($user)->get(route('attendance.index'));
        $response->assertStatus(200);

        // 2. 画面に表示されている日時情報を確認する
        // UI画像に基づき「YYYY年M月D日(曜)」および「HH:mm」の形式を確認します

        // 日付の確認: 「2026年2月16日(月)」の形式
        $dateString = $mockNow->isoFormat('YYYY年M月D日(ddd)');
        $response->assertSee($dateString);

        // 時刻の確認: 「10:05」が出ているか
        // 指針「表示はシンプルに（秒を切り捨てる）」に基づき検証
        $response->assertSee('10:05');

        // 秒（:30）が表示されていないことを確認
        $response->assertDontSee(':30');

        // テスト終了後に時刻固定を解除
        Carbon::setTestNow();
    }
}
