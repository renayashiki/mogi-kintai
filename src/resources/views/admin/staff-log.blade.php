@extends('layouts.admin')

@section('title', 'スタッフ別勤怠一覧')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/staff-log.css') }}">
@endsection

@section('content')
    <div class="monthly-container">
        <div class="monthly-header">
            <div class="title-line"></div>
            <h1 class="monthly-title">{{ $user->name }}さんの勤怠</h1>
        </div>
        <div class="month-selector-bar">
            <a href="{{ route('staff.log', ['id' => $user->id, 'month' => $currentMonth->copy()->subMonth()->format('Y-m')]) }}"
                class="month-nav prev">
                <span class="nav-icon">@include('components.arrow-left-svg')</span>
                <span class="nav-text">前月</span>
            </a>
            <div class="calendar-picker">
                <form action="{{ route('staff.log', ['id' => $user->id]) }}" method="GET" id="month-form">
                    <label for="month-input" class="calendar-label">
                        @include('components.calendar-svg')
                        <span>{{ $currentMonth->format('Y/m') }}</span>
                    </label>
                    <input type="month" name="month" id="month-input" value="{{ $currentMonth->format('Y-m') }}"
                        onchange="this.form.submit()">
                </form>
            </div>
            <a href="{{ route('staff.log', ['id' => $user->id, 'month' => $currentMonth->copy()->addMonth()->format('Y-m')]) }}"
                class="month-nav next">
                <span class="nav-text">翌月</span>
                <span class="nav-icon">@include('components.arrow-right-svg')</span>
            </a>
        </div>
        <div class="attendance-table-wrapper">
            <table class="attendance-table">
                <thead>
                    <tr>
                        <th class="col-date">日付</th>
                        <th class="col-start">出勤</th>
                        <th class="col-end">退勤</th>
                        <th class="col-rest">休憩</th>
                        <th class="col-total">合計</th>
                        <th class="col-detail">詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @php $daysInMonth = $currentMonth->daysInMonth; @endphp
                    @for ($i = 1; $i <= $daysInMonth; $i++)
                        @php
                            $date = $currentMonth->copy()->day($i);
                            $dateStr = $date->format('Y-m-d');
                            $dayName = ['日', '月', '火', '水', '木', '金', '土'][$date->dayOfWeek];
                            $attendance = $attendances->get($dateStr);
                        @endphp
                        <tr>
                            <td class="col-date">{{ $date->format('m/d') }}({{ $dayName }})</td>
                            <td class="col-start">
                                {{ $attendance ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '' }}
                            </td>
                            <td class="col-end">
                                {{ $attendance && $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '' }}
                            <td class="col-rest">
                                {{ $attendance->total_rest_time ?? '' }}
                            </td>
                            <td class="col-total">
                                {{ $attendance->total_time ?? '' }}
                            </td>
                            <td class="col-detail">
                                @if ($attendance)
                                    <a href="{{ route('admin.attendance.detail', ['id' => $attendance->id]) }}"
                                        class="detail-link">詳細</a>
                                @else
                                    <span class="detail-link">詳細</span>
                                @endif
                            </td>
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>
        <div class="csv-export-container">
            <a href="{{ route('staff.log.csv', ['id' => $user->id, 'month' => $currentMonth->format('Y-m')]) }}"
                class="csv-button">
                <span>CSV出力</span>
            </a>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const monthInput = document.getElementById('month-input');
            const calendarLabel = document.querySelector('.calendar-label');
            calendarLabel.addEventListener('click', function(e) {
                e.preventDefault();
                if (typeof monthInput.showPicker === 'function') {
                    monthInput.showPicker();
                } else {
                    monthInput.focus();
                    monthInput.click();
                }
            });
        });
    </script>
@endsection
