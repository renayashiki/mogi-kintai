@extends('layouts.user')

@section('title', '勤怠詳細')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/user/detail.css') }}">
@endsection

@section('content')
    <div class="detail-container">
        <div class="detail-header">
            <div class="title-line"></div>
            <h1 class="detail-title">勤怠詳細</h1>
        </div>

        <form action="{{ route('attendance.update', ['id' => $attendance->id]) }}" method="POST" class="detail-form">
            @csrf
            <table class="detail-table">
                <tr>
                    <th>名前</th>
                    <td>
                        <span class="display-text">{{ $attendance->user->name }}</span>
                    </td>
                </tr>
                <tr>
                    <th>日付</th>
                    <td>
                        <span class="display-text">
                            {{ \Carbon\Carbon::parse($attendance->date)->format('Y年') }}
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            {{ \Carbon\Carbon::parse($attendance->date)->format('n月j日') }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <th>出勤・退勤</th>
                    <td>
                        <div class="time-input-group">
                            <input type="text" name="new_clock_in"
                                value="{{ old('new_clock_in', \Carbon\Carbon::parse($attendance->clock_in)->format('H:i')) }}"
                                class="time-input">
                            <span class="tilde">〜</span>
                            <input type="text" name="new_clock_out"
                                value="{{ old('new_clock_out', \Carbon\Carbon::parse($attendance->clock_out)->format('H:i')) }}"
                                class="time-input">
                        </div>
                    </td>
                </tr>
                {{-- 休憩のループ --}}
                @foreach ($attendance->rests as $index => $rest)
                    <tr>
                        <th>休憩{{ $index > 0 ? $index + 1 : '' }}</th>
                        <td>
                            <div class="time-input-group">
                                <input type="text" name="rests[{{ $index }}][in]"
                                    value="{{ old("rests.$index.in", \Carbon\Carbon::parse($rest->rest_in)->format('H:i')) }}"
                                    class="time-input">
                                <span class="tilde">〜</span>
                                <input type="text" name="rests[{{ $index }}][out]"
                                    value="{{ old("rests.$index.out", \Carbon\Carbon::parse($rest->rest_out)->format('H:i')) }}"
                                    class="time-input">
                            </div>
                        </td>
                @endforeach
                </tr>
                <tr>
                    <th>備考</th>
                    <td>
                        <textarea name="comment" class="comment-textarea">{{ old('comment', $attendance->comment) }}</textarea>
                    </td>
                </tr>
            </table>

            {{-- 承認待ちメッセージ --}}
            @if ($attendance->status === 'pending')
                {{-- 状態判定は実際のカラム名に合わせてください --}}
                <p class="pending-message">＊承認待ちのため修正はできません。</p>
            @else
                <div class="form-actions">
                    <button type="submit" class="submit-btn">修正</button>
                </div>
            @endif
        </form>
    </div>
@endsection
