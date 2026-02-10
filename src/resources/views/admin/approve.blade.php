@extends('layouts.admin')

@section('title', '申請詳細')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/approve.css') }}">
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

        <form action="{{ route('admin.attendance.approve', ['attendance_correct_request_id' => $correctionRequest->id]) }}"
            method="POST">
            @csrf
            <div class="detail-table-wrapper">
                <table class="detail-table">
                    <tbody>
                        <tr>
                            <th class="col-label">名前</th>
                            <td class="col-value">
                                <span
                                    class="name-display">{{ str_replace(' ', '　', $correctionRequest->user->name) }}</span>
                            </td>
                        </tr>
                        <tr>
                            <th class="col-label">日付</th>
                            <td class="col-value">
                                <div class="date-display">
                                    <span class="year-val">{{ $correctionRequest->new_date->format('Y年') }}</span>
                                    <span class="date-val">{{ $correctionRequest->new_date->format('n月j日') }}</span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th class="col-label">出勤・退勤</th>
                            <td class="col-value">
                                <div class="time-group">
                                    {{-- [cite: 2026-02-04] 表示はシンプルに(秒切り捨て) --}}
                                    <span class="text-display">{{ $correctionRequest->new_clock_in->format('H:i') }}</span>
                                    <span class="range-tilde">〜</span>
                                    <span class="text-display">{{ $correctionRequest->new_clock_out->format('H:i') }}</span>
                                </div>
                            </td>
                        </tr>

                        {{-- 休憩リクエストのループ表示 --}}
                        @php
                            // 表示用に全ての休憩リクエストを配列にまとめます
                            $requestedRests = [];
                            if ($correctionRequest->new_rest1_in) {
                                $requestedRests[] = [
                                    'in' => $correctionRequest->new_rest1_in,
                                    'out' => $correctionRequest->new_rest1_out,
                                ];
                            }
                            if ($correctionRequest->new_rest2_in) {
                                $requestedRests[] = [
                                    'in' => $correctionRequest->new_rest2_in,
                                    'out' => $correctionRequest->new_rest2_out,
                                ];
                            }
                            foreach ($correctionRequest->attendanceCorrectRests as $extra) {
                                $requestedRests[] = ['in' => $extra->new_rest_in, 'out' => $extra->new_rest_out];
                            }
                        @endphp

                        @foreach ($requestedRests as $index => $rest)
                            <tr>
                                <th class="col-label">{{ $index === 0 ? '休憩' : '休憩' . ($index + 1) }}</th>
                                <td class="col-value">
                                    <div class="time-group">
                                        {{-- [原則] 表示はシンプルに（秒切り捨て） --}}
                                        <span
                                            class="text-display">{{ \Carbon\Carbon::parse($rest['in'])->format('H:i') }}</span>
                                        <span class="range-tilde">〜</span>
                                        <span
                                            class="text-display">{{ $rest['out'] ? \Carbon\Carbon::parse($rest['out'])->format('H:i') : '' }}</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach

                        <tr>
                            <th class="col-label">備考</th>
                            <td class="col-value">
                                <div class="textarea-container">
                                    <p class="text-display-multiline">{{ $correctionRequest->comment }}</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="detail-actions">
                @if ($correctionRequest->approval_status === '承認待ち')
                    <button type="submit" class="submit-button">承認</button>
                @else
                    {{-- ステータスが「承認済み」の場合、またはそれ以外はこちらが表示される --}}
                    <button type="button" class="approved-button" disabled>承認済み</button>
                @endif
            </div>
        </form>
    </div>
@endsection
