@extends('layouts.admin')

@section('title', '申請承認')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/approve.css') }}">
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
                            <td class="col-value col-name">
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
                                    <span class="text-display">{{ $correctionRequest->new_clock_in->format('H:i') }}</span>
                                    <span class="range-tilde">〜</span>
                                    <span class="text-display">{{ $correctionRequest->new_clock_out->format('H:i') }}</span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th class="col-label">休憩</th>
                            <td class="col-value">
                                <div class="time-group">
                                    @if ($correctionRequest->new_rest1_in)
                                        <span
                                            class="text-display">{{ \Carbon\Carbon::parse($correctionRequest->new_rest1_in)->format('H:i') }}</span>
                                        <span class="range-tilde">〜</span>
                                        <span
                                            class="text-display">{{ $correctionRequest->new_rest1_out ? \Carbon\Carbon::parse($correctionRequest->new_rest1_out)->format('H:i') : '' }}</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th class="col-label">休憩2</th>
                            <td class="col-value">
                                <div class="time-group">
                                    @if ($correctionRequest->new_rest2_in)
                                        <span
                                            class="text-display">{{ \Carbon\Carbon::parse($correctionRequest->new_rest2_in)->format('H:i') }}</span>
                                        <span class="range-tilde">〜</span>
                                        <span
                                            class="text-display">{{ $correctionRequest->new_rest2_out ? \Carbon\Carbon::parse($correctionRequest->new_rest2_out)->format('H:i') : '' }}</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @if ($correctionRequest->attendanceCorrectRests->isNotEmpty())
                            @foreach ($correctionRequest->attendanceCorrectRests as $index => $extra)
                                <tr>
                                    <th class="col-label">休憩{{ $index + 3 }}</th>
                                    <td class="col-value">
                                        <div class="time-group">
                                            <span
                                                class="text-display">{{ \Carbon\Carbon::parse($extra->new_rest_in)->format('H:i') }}</span>
                                            <span class="range-tilde">〜</span>
                                            <span
                                                class="text-display">{{ $extra->new_rest_out ? \Carbon\Carbon::parse($extra->new_rest_out)->format('H:i') : '' }}</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                        <tr>
                            <th class="col-label">備考</th>
                            <td class="col-value col-comment">
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
                    <button type="button" class="approved-button" disabled>承認済み</button>
                @endif
            </div>
        </form>
    </div>
@endsection
