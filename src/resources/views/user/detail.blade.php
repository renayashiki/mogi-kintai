@extends('layouts.user')

@section('title', '勤怠詳細')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/user/detail.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800&display=swap" rel="stylesheet">
@endsection

@section('content')
    <div class="detail-container">
        <div class="detail-header">
            <div class="title-line"></div>
            <h1 class="detail-title">勤怠詳細</h1>
        </div>

        <form action="{{ route('attendance.update', ['id' => $attendance->id]) }}" method="POST">
            @csrf
            <div class="detail-table-wrapper">
                <table class="detail-table">
                    <tbody>
                        <tr>
                            <th class="col-label">名前</th>
                            <td class="col-value">
                                <span class="name-display">{{ str_replace(' ', '　', $attendance->user->name) }}</span>
                            </td>
                        </tr>
                        <tr>
                            <th class="col-label">日付</th>
                            <td class="col-value">
                                <div class="date-display">
                                    <span
                                        class="year-val">{{ \Carbon\Carbon::parse($attendance->date)->format('Y年') }}</span>
                                    <span
                                        class="date-val">{{ \Carbon\Carbon::parse($attendance->date)->format('n月j日') }}</span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th class="col-label">出勤・退勤</th>
                            <td class="col-value">
                                <div class="time-group">
                                    <input type="text" name="clock_in"
                                        value="{{ old('clock_in', \Carbon\Carbon::parse($attendance->clock_in)->format('H:i')) }}"
                                        class="input-field">
                                    <span class="range-tilde">〜</span>
                                    <input type="text" name="clock_out"
                                        value="{{ old('clock_out', \Carbon\Carbon::parse($attendance->clock_out)->format('H:i')) }}"
                                        class="input-field">
                                </div>
                            </td>
                        </tr>

                        {{-- 休憩回数分の表示 --}}
                        @foreach ($attendance->rests as $index => $rest)
                            <tr>
                                <th class="col-label">休憩{{ $index + 1 }}</th>
                                <td class="col-value">
                                    <div class="time-group">
                                        <input type="text" name="rests[{{ $index }}][in]"
                                            value="{{ old("rests.$index.in", \Carbon\Carbon::parse($rest->rest_in)->format('H:i')) }}"
                                            class="input-field">
                                        <span class="range-tilde">〜</span>
                                        <input type="text" name="rests[{{ $index }}][out]"
                                            value="{{ old("rests.$index.out", \Carbon\Carbon::parse($rest->rest_out)->format('H:i')) }}"
                                            class="input-field">
                                    </div>
                                </td>
                            </tr>
                        @endforeach

                        {{-- 仕様要件：追加で１つ分の入力フィールド --}}
                        @php $nextIndex = $attendance->rests->count(); @endphp
                        <tr>
                            <th class="col-label">休憩{{ $nextIndex + 1 }}</th>
                            <td class="col-value">
                                <div class="time-group">
                                    <input type="text" name="rests[{{ $nextIndex }}][in]"
                                        value="{{ old("rests.$nextIndex.in") }}" class="input-field">
                                    <span class="range-tilde">〜</span>
                                    <input type="text" name="rests[{{ $nextIndex }}][out]"
                                        value="{{ old("rests.$nextIndex.out") }}" class="input-field">
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <th class="col-label">備考</th>
                            <td class="col-value">
                                {{-- 幅を休憩の開始〜終了に合わせるためのラッパー --}}
                                <div class="textarea-container">
                                    <textarea name="comment" class="textarea-field">{{ old('comment', $attendance->comment) }}</textarea>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- ボタン・メッセージは枠(wrapper)の外へ --}}
            <div class="detail-actions">
                @if ($hasPendingRequest)
                    <p class="pending-message">＊承認待ちのため修正はできません。</p>
                @else
                    <button type="submit" class="submit-button">修正</button>
                @endif
            </div>
        </form>
    </div>
@endsection
