@extends('layouts.user')

@section('title', '申請一覧')

@section('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/user/requests.css') }}">
@endsection

@section('content')
    <div class="request-container">
        <div class="request-header">
            <div class="title-line"></div>
            <h1 class="request-title">申請一覧</h1>
        </div>
        <div class="status-tab-wrapper">
            <nav class="status-tabs">
                <a href="{{ route('attendance.request.list', ['status' => 'pending']) }}"
                    class="tab-item {{ $status === 'pending' ? 'is-active' : '' }}">承認待ち</a>
                <a href="{{ route('attendance.request.list', ['status' => 'approved']) }}"
                    class="tab-item {{ $status === 'approved' ? 'is-active' : '' }}">承認済み</a>
            </nav>
            <div class="status-line"></div>
        </div>
        <div class="table-wrapper">
            <table class="request-table">
                <thead>
                    <tr>
                        <th class="col-status">状態</th>
                        <th class="col-name">名前</th>
                        <th class="col-target-date">対象日時</th>
                        <th class="col-reason">申請理由</th>
                        <th class="col-app-date">申請日時</th>
                        <th class="col-detail">詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($requests as $correctionRequest)
                        <tr>
                            <td class="col-status">{{ $correctionRequest->approval_status }}</td>
                            <td class="col-name">{{ $correctionRequest->user->name }}</td>
                            <td class="col-target-date">{{ $correctionRequest->new_date->format('Y/m/d') }}</td>
                            <td class="col-reason">{{ Str::limit($correctionRequest->comment, 20) }}</td>
                            <td class="col-app-date">{{ $correctionRequest->application_date->format('Y/m/d') }}</td>
                            <td class="col-detail">
                                <a href="{{ route('attendance.detail', ['id' => $correctionRequest->attendance_record_id]) }}"
                                    class="detail-link">詳細</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
