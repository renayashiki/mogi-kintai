@extends('layouts.admin')

@section('title', '勤怠一覧')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/daily.css') }}">
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
                    @foreach ($users as $user)
                        @php
                            $attendance = $attendances->get($user->id);
                        @endphp
                        <tr>
                            <td class="col-name">{{ $user->name }}</td>
                            <td class="col-start">{{ $attendance ? $attendance->clock_in->format('H:i') : '' }}</td>
                            <td class="col-end">
                                {{ $attendance && $attendance->clock_out ? $attendance->clock_out->format('H:i') : '' }}
                            </td>
                            <td class="col-rest">{{ $attendance ? $attendance->total_rest_time : '' }}</td>
                            <td class="col-total">{{ $attendance ? $attendance->total_work_time : '' }}</td>
                            <td class="col-detail">
                                <a href="{{ route('admin.attendance.detail', ['id' => $user->id]) }}"
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
