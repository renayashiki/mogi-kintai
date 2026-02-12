@extends('layouts.admin')

@section('title', '勤怠一覧')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/daily.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
@endsection

@section('content')
    <div class="daily-container">
        <div class="daily-header">
            <div class="title-line"></div>
            <h1 class="daily-title">{{ $date->format('Y年n月j日') }}の勤怠</h1>
        </div>

        <div class="date-selector-bar">
            {{-- 前日 --}}
            <a href="{{ route('admin.attendance.list', ['date' => $date->copy()->subDay()->format('Y-m-d')]) }}"
                class="date-nav prev">
                <span class="nav-icon">@include('components.arrow-left-svg')</span>
                <span class="nav-text">前日</span>
            </a>

            {{-- カレンダー選択 --}}
            <div class="calendar-picker">
                <form action="{{ route('admin.attendance.list') }}" method="GET" id="date-form">
                    <label for="date-input" class="calendar-label">
                        @include('components.calendar-svg')
                        <span>{{ $date->format('Y/m/d') }}</span>
                    </label>
                    <input type="date" name="date" id="date-input" value="{{ $date->format('Y-m-d') }}"
                        onchange="this.form.submit()">
                </form>
            </div>

            {{-- 翌日 --}}
            <a href="{{ route('admin.attendance.list', ['date' => $date->copy()->addDay()->format('Y-m-d')]) }}"
                class="date-nav next">
                <span class="nav-text">翌日</span>
                <span class="nav-icon">@include('components.arrow-right-svg')</span>
            </a>
        </div>

        <div class="attendance-table-wrapper">
            <table class="attendance-table">
                <thead>
                    <tr>
                        <th class="col-name">名前</th>
                        <th class="col-start">出勤</th>
                        <th class="col-end">退勤</th>
                        <th class="col-rest">休憩</th>
                        <th class="col-total">合計</th>
                        <th class="col-detail">詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($attendances as $attendance)
                        <tr>
                            <td class="col-name">{{ $attendance->user->name }}</td>

                            {{-- 出勤 --}}
                            <td class="col-start">
                                {{ $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '' }}
                            </td>

                            {{-- 退勤 --}}
                            <td class="col-end">
                                {{ $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '' }}
                            </td>

                            {{-- 休憩時間 (01:00 -> 1:00) --}}
                            <td class="col-rest">
                                {{ $attendance->total_rest_time ?? '' }}
                            </td>

                            {{-- 合計勤務時間 (01:00 -> 1:00) --}}
                            <td class="col-total">
                                {{ $attendance->total_time ?? '' }}
                            </td>

                            <td class="col-detail">
                                <a href="{{ route('admin.attendance.detail', ['id' => $attendance->id]) }}"
                                    class="detail-link">詳細</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dateInput = document.getElementById('date-input');
            const calendarLabel = document.querySelector('.calendar-label');

            // labelをクリックしたときに確実にカレンダーを開く
            calendarLabel.addEventListener('click', function(e) {
                // デフォルトの動作を保証
                if (typeof dateInput.showPicker === 'function') {
                    dateInput.showPicker();
                } else {
                    dateInput.focus();
                    dateInput.click();
                }
            });
        });
    </script>
@endsection
