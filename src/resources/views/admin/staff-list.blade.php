@extends('layouts.admin') {{-- 管理者用レイアウト --}}

@section('title', 'スタッフ一覧')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/admin/staff.css') }}">
@endsection

@section('content')
    <div class="staff-container">
        <div class="staff-header">
            <div class="title-line"></div>
            <h1 class="staff-title">スタッフ一覧</h1>
        </div>
        <div class="table-wrapper">
            <table class="staff-table">
                <thead>
                    <tr>
                        <th class="col-name">名前</th>
                        <th class="col-email">メールアドレス</th>
                        <th class="col-detail">月次勤怠</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td class="col-name">{{ $user->name }}</td>
                            <td class="col-email">{{ $user->email }}</td>
                            <td class="col-detail">
                                <a href="{{ route('staff.log', ['id' => $user->id]) }}" class="detail-link">詳細</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
