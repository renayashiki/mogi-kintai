@extends('layouts.user')

@section('title', '打刻')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/user/stamp.css') }}">
@endsection

@section('content')
    <div class="stamp-container">
        {{-- ステータス表示 --}}
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

        {{-- 日付 (ページ主見出し) --}}
        <h1 class="stamp-date">2026年2月4日(水)</h1>

        {{-- 現在時刻 (大きなデジタル時計) --}}
        <div class="stamp-time">10:30</div>

        {{-- 打刻アクションエリア --}}
        <div class="stamp-actions">
            @if ($attendanceStatus === 'outside')
                <form action="#" method="post">
                    @csrf
                    <button type="submit" class="stamp-button btn-black">出勤</button>
                </form>
            @elseif ($attendanceStatus === 'working')
                <div class="button-group">
                    <form action="#" method="post">
                        @csrf
                        <button type="submit" class="stamp-button btn-black">退勤</button>
                    </form>
                    <form action="#" method="post">
                        @csrf
                        <button type="submit" class="stamp-button btn-white">休憩入</button>
                    </form>
                </div>
            @elseif ($attendanceStatus === 'resting')
                <form action="#" method="post">
                    @csrf
                    <button type="submit" class="stamp-button btn-white">休憩戻</button>
                </form>
            @elseif ($attendanceStatus === 'finished')
                <p class="finish-message">お疲れ様でした。</p>
            @endif
        </div>
    </div>
@endsection
