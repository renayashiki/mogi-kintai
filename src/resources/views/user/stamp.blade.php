@extends('layouts.user')

@section('title', '打刻')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/user/stamp.css') }}">
@endsection

@section('content')
    <div class="stamp-container">
        <h1 class="visually-hidden">打刻画面</h1>
        <div class="status-badge">
            @if ($attendanceStatus === 'outside')
                <span class="status-text">勤務外</span>
            @elseif ($attendanceStatus === 'working')
                <span class="status-text">出勤中</span>
            @elseif ($attendanceStatus === 'resting')
                <span class="status-text">休憩中</span>
            @elseif ($attendanceStatus === 'finished')
                <span class="status-text">退勤済</span>
            @endif
        </div>
        <p class="stamp-date" id="current-date">
            {{ now()->format('Y年n月j日') }}({{ ['日', '月', '火', '水', '木', '金', '土'][now()->dayOfWeek] }})</p>
        <div class="stamp-time" id="current-time">{{ now()->format('H:i') }}</div>
        <div class="stamp-actions">
            @if ($attendanceStatus === 'outside' && !$hasClockIn)
                <form action="{{ route('attendance.store') }}" method="post">
                    @csrf
                    <input type="hidden" name="type" value="clock_in">
                    <button type="submit" class="stamp-button btn-black">出勤</button>
                </form>
            @elseif ($attendanceStatus === 'working')
                <div class="button-group">
                    <form action="{{ route('attendance.store') }}" method="post">
                        @csrf
                        <input type="hidden" name="type" value="clock_out">
                        <button type="submit" class="stamp-button btn-black">退勤</button>
                    </form>
                    <form action="{{ route('attendance.store') }}" method="post">
                        @csrf
                        <input type="hidden" name="type" value="rest_in">
                        <button type="submit" class="stamp-button btn-white">休憩入</button>
                    </form>
                </div>
            @elseif ($attendanceStatus === 'resting')
                <form action="{{ route('attendance.store') }}" method="post">
                    @csrf
                    <input type="hidden" name="type" value="rest_out">
                    <button type="submit" class="stamp-button btn-white">休憩戻</button>
                </form>
            @elseif ($attendanceStatus === 'finished')
                <p class="finish-message">お疲れ様でした。</p>
            @endif
        </div>
    </div>
    <script>
        function updateClock() {
            const now = new Date();
            const days = ['日', '月', '火', '水', '木', '金', '土'];
            const dateEl = document.getElementById('current-date');
            const timeEl = document.getElementById('current-time');
            if (dateEl) {
                dateEl.textContent = `${now.getFullYear()}年${now.getMonth() + 1}月${now.getDate()}日(${days[now.getDay()]})`;
            }
            if (timeEl) {
                timeEl.textContent = now.getHours().toString().padStart(2, '0') + ':' +
                    now.getMinutes().toString().padStart(2, '0');
            }
        }
        setInterval(updateClock, 1000);
    </script>
@endsection
