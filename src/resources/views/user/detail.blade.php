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

        <form action="{{ route('attendance.update', ['id' => $attendance->id]) }}" method="POST" novalidate>
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
                                @if ($hasPendingRequest || $isApproved)
                                    {{-- 承認待ちはテキストのみ --}}
                                    <div class="time-group">
                                        <span class="text-display">{{ $attendance->clock_in->format('H:i') }}</span>
                                        <span class="range-tilde">〜</span>
                                        <span
                                            class="text-display">{{ $attendance->clock_out ? $attendance->clock_out->format('H:i') : '' }}</span>
                                    </div>
                                @else
                                    <div class="time-group">
                                        {{-- 通常時は入力枠 --}}
                                        <input type="text" name="clock_in"
                                            value="{{ old('clock_in', $attendance->clock_in->format('H:i')) }}"
                                            class="input-field">
                                        <span class="range-tilde">〜</span>
                                        <input type="text" name="clock_out"
                                            value="{{ old('clock_out', $attendance->clock_out ? $attendance->clock_out->format('H:i') : '') }}"
                                            class="input-field">
                                    </div>
                                @endif
                            </td>
                        </tr>

                        {{-- 休憩回数分の表示 --}}
                        @foreach ($attendance->rests as $index => $rest)
                            <tr>
                                <th class="col-label">{{ $index === 0 ? '休憩' : '休憩' . ($index + 1) }}</th>
                                <td class="col-value">
                                    <div class="time-group">
                                        @if ($hasPendingRequest || $isApproved)
                                            <span
                                                class="text-display">{{ $rest->rest_in ? \Carbon\Carbon::parse($rest->rest_in)->format('H:i') : '' }}</span>
                                            <span class="range-tilde">〜</span>
                                            <span
                                                class="text-display">{{ $rest->rest_out ? \Carbon\Carbon::parse($rest->rest_out)->format('H:i') : '' }}</span>
                                        @else
                                            <input type="text" name="rests[{{ $index }}][in]"
                                                value="{{ old("rests.$index.in", $rest->rest_in ? \Carbon\Carbon::parse($rest->rest_in)->format('H:i') : '') }}"
                                                class="input-field">
                                            <span class="range-tilde">〜</span>
                                            <input type="text" name="rests[{{ $index }}][out]"
                                                value="{{ old("rests.$index.out", $rest->rest_out ? \Carbon\Carbon::parse($rest->rest_out)->format('H:i') : '') }}"
                                                class="input-field">
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach

                        {{-- 仕様要件：追加で１つ分の入力フィールド --}}
                        @if (!$hasPendingRequest && !$isApproved)
                            @php $nextIndex = $attendance->rests->count(); @endphp
                            <tr>
                                <th class="col-label">{{ $nextIndex === 0 ? '休憩' : '休憩' . ($nextIndex + 1) }}</th>
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
                        @endif
                        <tr>
                            <th class="col-label">備考</th>
                            <td class="col-value">
                                {{-- 幅を休憩の開始〜終了に合わせるためのラッパー --}}
                                <div class="textarea-container">
                                    @if ($hasPendingRequest || $isApproved)
                                        {{-- 承認待ちはテキストとして表示（枠なし） --}}
                                        <p class="text-display-multiline">{{ $attendance->comment }}</p>
                                    @else
                                        <textarea name="comment" class="textarea-field">{{ old('comment', $attendance->comment) }}</textarea>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="error-messages">
                @if ($errors->any())
                    <div class="error-container">
                        <ul class="error-list">
                            @foreach (collect($errors->all())->unique() as $error)
                                <li class="error-item">{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
            {{-- ボタン・メッセージは枠(wrapper)の外へ --}}
            <div class="detail-actions">
                @if ($isApproved)
                @elseif ($hasPendingRequest)
                    <p class="pending-message">*承認待ちのため修正はできません。</p>
                @else
                    <button type="submit" class="submit-button">修正</button>
                @endif
            </div>
        </form>
    </div>
@endsection
