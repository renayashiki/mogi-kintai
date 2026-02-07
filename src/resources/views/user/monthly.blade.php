@extends('layouts.user')

@section('title', '勤怠一覧')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/user/monthly.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
@endsection

@section('content')
    <div class="monthly-container">
        <div class="monthly-header">
            <div class="title-line"></div>
            <h1 class="monthly-title">勤怠一覧</h1>
        </div>

        <div class="month-selector-bar">
            {{-- 前月 --}}
            <a href="{{ route('attendance.list', ['month' => $currentMonth->copy()->subMonth()->format('Y-m')]) }}"
                class="month-nav prev">
                <span class="nav-icon">@include('components.arrow-left-svg')</span>
                <span class="nav-text">前月</span>
            </a>

            {{-- ★修正箇所：ここをカレンダー選択フォームに差し替え --}}
            <div class="calendar-picker">
                <form action="{{ route('attendance.list') }}" method="GET" id="month-form">
                    <label for="month-input" class="calendar-label">
                        @include('components.calendar-svg')
                        <span>{{ $currentMonth->format('Y/m') }}</span>
                    </label>
                    <input type="month" name="month" id="month-input" value="{{ $currentMonth->format('Y-m') }}"
                        onchange="this.form.submit()">
                </form>
            </div>

            {{-- 翌月 --}}
            <a href="{{ route('attendance.list', ['month' => $currentMonth->copy()->addMonth()->format('Y-m')]) }}"
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
                    @php
                        $daysInMonth = $currentMonth->daysInMonth;
                    @endphp
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
                                {{ $attendance ? mb_convert_kana(\Carbon\Carbon::parse($attendance->clock_in)->format('H:i'), 'a') : '' }}
                            </td>
                            <td class="col-end">
                                {{ $attendance && $attendance->clock_out ? mb_convert_kana(\Carbon\Carbon::parse($attendance->clock_out)->format('H:i'), 'a') : '' }}
                            </td>
                            <td class="col-rest">
                                {{ $attendance && $attendance->total_rest_time ? ltrim(mb_convert_kana(\Carbon\Carbon::parse($attendance->total_rest_time)->format('H:i'), 'a'), '0') : '' }}
                            </td>
                            <td class="col-total">
                                {{ $attendance && $attendance->total_time ? ltrim(mb_convert_kana(\Carbon\Carbon::parse($attendance->total_time)->format('H:i'), 'a'), '0') : '' }}
                            </td>
                            <td class="col-detail">
                                <a href="{{ route('attendance.detail', ['id' => $dateStr]) }}" class="detail-link">詳細</a>
                            </td>
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const monthInput = document.getElementById('month-input');
            const calendarLabel = document.querySelector('.calendar-label');

            // アイコンや文字が含まれる「label」全体をクリックした時の処理
            calendarLabel.addEventListener('click', function(e) {
                e.preventDefault(); // label本来の挙動を一旦止める

                // 裏にあるinputのカレンダー機能を強制的に呼び出す
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
