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
        // 日本語の曜日を取得するためにロケールを日本に設定
        Carbon::setLocale('ja');

        // --- 準備: 現在時刻を「その瞬間の現実の時刻」として取得 ---
        // 秒まで含めてシステム時刻を取得します
        $now = Carbon::now();
        Carbon::setTestNow($now);

    // ログインユーザーの作成（特定のサンプルに依存しないようFactoryを使用） [cite: 2026-02-03]
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        // 1. 勤怠打刻画面を開く（手順：準備後にログインを遵守）
        $response = $this->actingAs($user)->get(route('attendance.index'));
        $response->assertStatus(200);

        // 2. 画面に表示されている日時情報を確認する
        // 【期待挙動】画面上に表示されている日時が現在の日時と一致する

        // 日付の期待値： YYYY年M月D日(曜) 形式
        $expectedDate = $now->isoFormat('YYYY年M月D日(ddd)');

        // 時刻の期待値： HH:mm 形式
        // 方針「計算は精密に、表示はシンプルに（秒は切り捨て）」を適用 [cite: 2026-02-04]
        $expectedTime = $now->format('H:i');

        // 証明：画面に「現在の日付」が含まれているか
        $response->assertSee($expectedDate);

        // 証明：画面に「現在の時刻（秒なし）」が含まれているか
        $response->assertSee($expectedTime);

        // 証明：秒が表示されていないことの担保（表示がシンプルであることの証明） [cite: 2026-02-04]
        // 秒（例 :00〜:59）がHTML内に存在しないことを確認
        $response->assertDontSee($now->format('H:i:s'));

        // テスト終了後に時刻固定を解除
        Carbon::setTestNow();
    }
}
